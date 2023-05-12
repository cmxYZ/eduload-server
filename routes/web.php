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
Route::get('/changerealhours', [UserController::class, 'change_realhours'])->name('change_realhours');

Route::get('/loadexcel', [UserController::class, 'load_excel'])->name('load_excel');
Route::get('/loadexcelbytkey', [UserController::class, 'load_excel_by_tkey'])->name('load_excel_by_tkey');


Route::get('/checkuser', [UserController::class, 'check_user'])->name('check_user');

//AdminController
Route::get('/updateteachers', [AdminController::class, 'update_teachers'] )->name('update_teachers');
Route::get('/updateloads', [AdminController::class, 'update_loads'] )->name('update_loads');

//Route::get('/teachers', function () { return view('teachers'); } );
//Route::get('/loads', function () { return view('loads'); } );
//Route::get('/updateteachers', function () { return view('update_teachers'); } );
//Route::get('/updateloads', function () { return view('update_loads'); } );



//Route::get('/url', [UserController::class, 'index'])->name('user.url');
//php artisan make:controller UserController
