<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function load_data()
    {
        if (file_exists('data.json')) {
            return file_get_contents('data.json');
        }
        return 'No data';
    }

    public function check_user()
    {
        $login = request()->get('login');
        $id = request()->get('samAccountName');
        DB::insert("INSERT IGNORE INTO `Users` (`login`, `roleID`, `samAccountName`, `password`, `isKeycloak`) VALUES ('$login', NULL, '$id', NULL, '1')");
        $result = DB::select("SELECT `roleID` FROM `Users` WHERE `login` = '$login'");
        if ($result[0]->roleID != null) {
            $sql = DB::select("SELECT `roleName` FROM `Roles` WHERE `roleID` = $result[0]->roleID");
            return $sql[0]->roleName;
        }
        else {
            return 'norole';
        }
    }
}
