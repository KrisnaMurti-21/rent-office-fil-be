<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingTransactionRequest;
use App\Http\Resources\Api\BookingTransactionResource;
use App\Http\Resources\Api\ViewBookingResource;
use App\Models\BookingTransaction;
use App\Models\OfficeSpace;
use DateTime;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class BookingTransactionController extends Controller
{
    public function store(StoreBookingTransactionRequest $request)
    {
        $validatedData = $request->validated();
        $officeSpace = OfficeSpace::find($validatedData['office_space_id']);
        $validatedData['is_paid'] = false;
        $validatedData['booking_trx_id'] = BookingTransaction::generateUniqueTrxId();
        $validatedData['duration'] = $officeSpace->duration;
        $validatedData['ended_at'] = (new DateTime($validatedData['started_at']))->modify("+" . $officeSpace->duration . " days")->format('Y-m-d');
        $bookingTransaction = BookingTransaction::create($validatedData);
        //Mengirim notif ke pelanggan
        $sid = getenv("TWILIO_ACCOUNT_SID");
        $token = getenv("TWILIO_AUTH_TOKEN");
        $messageBody = "Hi {$bookingTransaction->name}, Terimakasih telah booking kantor di FirstOffice.\n\n\ ";
        $messageBody .= "Pesanan kantor {$bookingTransaction->officeSpace->name} Anda sedang kami proses dengan Booking TRX ID: {$bookingTransaction->booking_trx_id}. \n\n";
        $messageBody .= "Kami akan mengiformasikan kembali status pemesanan Anda secepat mungkin. \n\n";
        $no_telephone = $bookingTransaction->phone_number;
        if (strpos($no_telephone, '0') === 0) {
            // Ganti awalan '0' dengan '+62'
            $no_telephone = '+62' . substr($no_telephone, 1);
        }
        $twilio = new Client($sid, $token);
        $twilio->messages->create(
            "whatsapp:" . $no_telephone,
            [
                "body" => $messageBody,
                "from" => "whatsapp:" . getenv("TWILIO_PHONE_NUMBER"),
            ]
        );
        return new BookingTransactionResource($bookingTransaction);
    }

    public function booking_details(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:255',
            'booking_trx_id' => 'required|string|max:255',
        ]);

        $booking = BookingTransaction::where('phone_number', $request->phone_number)->where('booking_trx_id', $request->booking_trx_id)->with(['officeSpace', 'officeSpace.city'])->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'

            ], 404);
        }

        return new ViewBookingResource($booking);
    }
}
