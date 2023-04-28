<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

//UserController
Route::view('/', 'home');
Route::get('/loaddata', [UserController::class, 'load_data'])->name('load_data');
Route::get('/loaddatabytkey', [UserController::class, 'load_data_by_tkey'])->name('load_data_by_tkey');
Route::get('/changehours', [UserController::class, 'change_hours'])->name('change_hours');
Route::get('/changestake', [UserController::class, 'change_stake'])->name('change_stake');
Route::get('/changeishour', [UserController::class, 'change_ishour'])->name('change_ishour');



Route::get('/checkuser', [UserController::class, 'check_user'])->name('check_user');

//AdminController
Route::get('/updatedb', [AdminController::class, 'update'] )->name('update_db');
Route::get('/updatejson', [AdminController::class, 'update_json'])->name('update_json');

//Route::get('/teachers', function () { return view('teachers'); } );
//Route::get('/loads', function () { return view('loads'); } );
//Route::get('/updateteachers', function () { return view('update_teachers'); } );
//Route::get('/updateloads', function () { return view('update_loads'); } );



//Route::get('/url', [UserController::class, 'index'])->name('user.url');
//php artisan make:controller UserController
