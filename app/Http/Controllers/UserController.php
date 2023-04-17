<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function load_data()
    {
        $result = $this->get_result();
        return view('json', compact('result'));
    }

    public function check_user()
    {
        return view('check_user');
    }

    public function get_result()
    {
        return [
            'name' => 'John',
            'tkey' => 't3h54918hkhd9h42h3kh8dyfxk34k',
        ];
    }

}
