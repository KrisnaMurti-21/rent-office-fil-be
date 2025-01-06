<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Menyimpan file di private
// Simpan file di storage/app/private
// if ($request->hasFile('file')) {
//     $file = $request->file('file');
//     $path = $file->store('private', 'local'); // Simpan di storage/app/private

//     // Anda bisa menyimpan $path ke database jika diperlukan
//     return back()->with('success', 'File berhasil diunggah!')->with('path', $path);
// }

Route::get('/private', function () {
    $path = storage_path('app/private/kereta-gantung-ke-gua-hira.jpeg');
    if (file_exists($path)) {
        return response()->file($path);
    }
});
