<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

class UserController extends Controller
{
    public function load_data()
    {
        $year = "2022";
        $data = array();
        $result = DB::select("SELECT * FROM `Teachers`");

        foreach ($result as $row)
        {
            $tkey = $row->tkey;
            $name = $row->lastName . ' ' .  $row->firstName . ' ' . $row->patronymic;
            $infoWorkPlaces = $row->infoWorkPlaces;
            $stake = $row->stake == '' ? '-' : $row->stake;

            $b = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year'");
            $c = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year'");
            $a = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND year='$year'");
            $h = DB::select("SELECT hours FROM PhysFace1C WHERE guidPerson1C='$row->guidPerson1C'");
            $hoursOnStake = 0;

            if (!empty($h))
                $hoursOnStake = (float)$h[0]->hours;

            $hours = $b[0] - $hoursOnStake;

            $line = ["tkey" => "$tkey", "name" => "$name", "infoWorkPlaces" => "$infoWorkPlaces", "stake" => "$stake",
                "hoursOnStake" => $hoursOnStake, "hours" => $hours,
                "bHoursPlaned" => $b[0], "bHoursReal" => $b[1], "bHoursDiff" => $b[2],
                "cHoursPlaned" => $c[0], "cHoursReal" => $c[1], "cHoursDiff" => $c[2],
                "hoursPlaned" => $a[0], "hoursReal" => $a[1], "hoursDiff" => $a[2],
                "year" => $year, "guidPerson1C" => $row->guidPerson1C];
            array_push($data, $line);
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public function check_user()
    {
        $login = request()->get('login');
        $id = request()->get('samAccountName');
        DB::insert("INSERT IGNORE INTO `Users` (`login`, `roleID`, `samAccountName`, `password`, `isKeycloak`) VALUES ('$login', NULL, '$id', NULL, '1')");
        $result = DB::select("SELECT `roleID` FROM `Users` WHERE `login` = '$login'");
        if ($result[0]->roleID != null) {
            $id = $result[0]->roleID;
            $sql = DB::select("SELECT `roleName` FROM `Roles` WHERE `roleID` = $id");
            return $sql[0]->roleName;
        }
        else {
            return 'norole';
        }
    }

    public function load_data_by_tkey() {
        if (!isset($_GET['tkey']))
        {
            return 'No Data';
        }
        $tkey = $_GET['tkey'];
        $data = array();
        $result = DB::select("SELECT `disciplineName`, `groupsHistory`, `semester`, `loadType`, `formingDivisionuuid`, `readingDivisionuuid`,
       `compensationType`, `plannedHours`, `realHours`, `isHour` FROM `Loads` WHERE `tkey` = '$tkey'");

        foreach ($result as $row)
        {
            $planned = $row->plannedHours;
            $real = $row->realHours;
            $diff = (float)$planned - (float)$real;

            $forming = DB::select("SELECT `name` FROM `Divisions` WHERE `uuid`='$row->formingDivisionuuid'")[0];
            $reading = DB::select("SELECT `name` FROM `Divisions` WHERE `uuid`='$row->readingDivisionuuid'")[0];
            $info = DB::select("SELECT `` FROM `Divisions` WHERE `uuid`='$row->readingDivisionuuid'")[0];


            $line = [
                "disciplineName" => "$row->disciplineName",
                "groupsHistory" => "$row->groupsHistory",
                "semester" => "$row->semester",
                "loadType" => "$row->loadType",
                "formingDivisionuuid" => "$forming->name",
                "readingDivisionuuid" => "$reading->name",
                "compensationType" => "$row->compensationType",
                "plannedHours" => $row->plannedHours,
                "realHours" => $row->realHours,
                "diff" => $diff,
                "isHour" => $row->isHour
                ];
            array_push($data, $line);
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE);
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

    public function change_hours() {
        $value = request()->get('value');
        $guidPerson1C = request()->get('guidPerson1C');
        DB::update("UPDATE `PhysFace1C` SET `hours` = '$value' WHERE `PhysFace1C`.`guidPerson1C` = '$guidPerson1C'");
    }
}
