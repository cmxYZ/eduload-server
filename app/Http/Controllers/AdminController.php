<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function update_db()
    {
        return view('update_db');
    }
}
