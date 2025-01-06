<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingTransactionResource\Pages;
use App\Filament\Resources\BookingTransactionResource\RelationManagers;
use App\Models\BookingTransaction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingTransactionResource extends Resource
{
    protected static ?string $model = BookingTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('booking_trx_id')->required()->maxLength(255),
                TextInput::make('phone_number')->required()->maxLength(255),
                TextInput::make('total_amount')->required()->numeric()->prefix('IDR'),
                TextInput::make('duration')->required()->numeric()->prefix('Days'),
                DatePicker::make('started_at')->required(),
                DatePicker::make('ended_at')->required(),
                Select::make('is_paid')->options([
                    true => 'Paid',
                    false => 'Not Paid'
                ])->required(),
                Select::make('office_space_id')->relationship('officeSpace', 'name')->searchable()->preload()->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_trx_id')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('officeSpace.name'),
                TextColumn::make('started_at')->date(),
                IconColumn::make('is_paid')->boolean()->trueColor('success')->falseColor('danger')->trueIcon('heroicon-o-check-circle')->falseIcon('heroicon-o-x-circle')->label('Sudah Bayar?'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->action(function (BookingTransaction $record) {
                        $record->is_paid = true;
                        $record->save();

                        Notification::make()
                            ->title('Booking Approved')
                            ->success()
                            ->body('The booking has been successfully approved.')
                            ->send();

                        $sid = getenv("TWILIO_ACCOUNT_SID");
                        $token = getenv("TWILIO_ACCOUNT_TOKEN");
                        $twilio = new \Twilio\Rest\Client($sid, $token);

                        $messageBody = "Hi {$record->name}, pemesanan anda dengan kode {$record->booking_trx_id} sudah terbayar penuh.\n\n";
                        $messageBody .= "Silahkan datang kepada lokasi kantor {$record->officeSpace->name} untuk memulai menggunakan ruangan tersebut.\n\n";
                        $messageBody .= "Jika anda memiliki pertanyaan lebih lanjut, silahkan hubungi kami melalui nomor 0812-3456-7890.\n\n";
                        $no_telephone = $record->phone_number;
                        if (strpos($no_telephone, '0') === 0) {
                            // Ganti awalan '0' dengan '+62'
                            $no_telephone = '+62' . substr($no_telephone, 1);
                        }
                        $twilio->messages->create(
                            "whatsapp:" . $no_telephone,
                            [
                                "body" => $messageBody,
                                "from" => "whatsapp:" . getenv("TWILIO_PHONE_NUMBER"),
                            ]
                        );
                    })
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(BookingTransaction $record) => !$record->is_paid)

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingTransactions::route('/'),
            'create' => Pages\CreateBookingTransaction::route('/create'),
            'edit' => Pages\EditBookingTransaction::route('/{record}/edit'),
        ];
    }
}
