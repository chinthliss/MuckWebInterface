<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/singleplayer/', function () {
    return view('singleplayer.home');
})->name('singleplayer.home');

Route::get('/multiplayer/', function () {
    return view('multiplayer.home');
})->name('multiplayer.home');
