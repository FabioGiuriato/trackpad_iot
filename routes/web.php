<?php

use App\Http\Controllers\StudioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SoundController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\ButtonMappingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('studio'));

Route::get('/studio', [StudioController::class, 'index'])->middleware('auth')->name('studio');
Route::get('/songs', [SongController::class, 'index'])->middleware('auth')->name('songs.index');
Route::post('/songs', [SongController::class, 'store'])->middleware('auth')->name('songs.store');
Route::put('/songs/{song}', [SongController::class, 'update'])->middleware('auth')->name('songs.update');
Route::put('/songs/{song}/pattern', [SongController::class, 'updatePattern'])->middleware('auth')->name('songs.pattern.update');
Route::delete('/songs/{song}', [SongController::class, 'destroy'])->middleware('auth')->name('songs.destroy');
Route::get('/songs/{song}/play', [SongController::class, 'play'])->middleware('auth')->name('songs.play');

Route::get('/suoni', [SoundController::class, 'index'])->middleware('auth')->name('sounds.index');
Route::get('/suoni/upload', [SoundController::class, 'upload'])->middleware('auth')->name('sounds.upload');
Route::post('/suoni', [SoundController::class, 'store'])->middleware('auth')->name('sounds.store');
Route::put('/suoni/{button}', [SoundController::class, 'update'])->middleware('auth')->name('sounds.update');
Route::delete('/suoni/{button}', [SoundController::class, 'destroy'])->middleware('auth')->name('sounds.destroy');

Route::get('/pulsanti', [ButtonMappingController::class, 'edit'])->middleware('auth')->name('buttons.mapping');
Route::put('/pulsanti', [ButtonMappingController::class, 'update'])->middleware('auth')->name('buttons.mapping.update');
Route::post('/pulsanti/reset', [ButtonMappingController::class, 'reset'])->middleware('auth')->name('buttons.mapping.reset');

Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth')->name('profile.show');
Route::put('/profile', [ProfileController::class, 'update'])->middleware('auth')->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('auth')->name('profile.password');

Route::get('/iot/live', [DeviceController::class, 'live'])->middleware('auth')->name('iot.live');
Route::get('/iot/events/latest', [DeviceController::class, 'latestEvents'])->middleware('auth')->name('iot.events.latest');
Route::post('/iot/events', [DeviceController::class, 'storeEvent'])->name('iot.events.store');

Route::get('/auth', [AuthController::class, 'index'])->middleware('guest')->name('auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
