<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function load_data()
    {
        if (!file_exists('data.json')) {
            $this->updatedatajson();
        }
        return file_get_contents('data.json');
    }

    public function check_user()
    {
        $login = request()->get('login');
        $id = request()->get('samAccountName');
        DB::insert("INSERT IGNORE INTO `Users` (`login`, `roleID`, `samAccountName`, `password`, `isKeycloak`) VALUES ('$login', NULL, '$id', NULL, '1')");

        return 'Success';
    }

    public function updatedatajson()
    {
        if (file_exists('data.json')) {
            unlink('data.json');
        }
        $year = "2022";
        $data = array();
        $result = DB::select("SELECT * FROM `Teachers`");

        foreach ($result as $row)
        {
            $tkey = $row->tkey;
            $name = $row->firstName . ' ' .  $row->patronymic . ' ' . $row->lastName;
            $infoWorkPlaces = $row->infoWorkPlaces;
            $stake = $row->stake == '' ? '-' : $row->stake;

            $b = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year'");
            $c = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year'");
            $a = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND year='$year'");

            $line = ["name" => "$name", "infoWorkPlaces" => "$infoWorkPlaces", "stake" => "$stake",
                "hoursOnStake" => "0", "hours" => "0",
                "bHoursPlaned" => $b[0], "bHoursReal" => $b[1], "bHoursDiff" => $b[2],
                "cHoursPlaned" => $c[0], "cHoursReal" => $c[1], "cHoursDiff" => $c[2],
                "hoursPlaned" => $a[0], "hoursReal" => $a[1], "hoursDiff" => $a[2],
                "year" => $year];
            array_push($data, $line);
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents('data.json', $json);
    }

    public function SummHours($sql)
    {
        $real = 0;
        $planed = 0;
        $result = DB::select($sql);
        foreach ($result as $row)
        {
            $planed += (float)$row->plannedHours;
            $real += (float)$row->realHours;
        }
        $diff = $planed-$real;
        $real = round($real, 3);
        $planed = round($planed, 3);
        $diff = round($diff, 3);
        return [$planed, $real, $diff];
    }
}
