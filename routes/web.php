<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/', [UserController::class, 'index'])->name('index');
Route::get('/loaddata', [UserController::class, 'load_data'])->name('load_data');
Route::get('/checkuser', [UserController::class, 'check_user'])->name('check_user');

Route::get('/updatedb', [AdminController::class, 'update_db'] )->name('update_db');

Route::get('/teachers', function () { return view('teachers'); } );
Route::get('/loads', function () { return view('loads'); } );
Route::get('/updateteachers', function () { return view('update_teachers'); } );
Route::get('/updateloads', function () { return view('update_loads'); } );



//Route::get('/url', [UserController::class, 'index'])->name('user.url');
//php artisan make:controller UserController
