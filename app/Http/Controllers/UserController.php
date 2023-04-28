<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

class UserController extends Controller
{
    public function load_data()
    {
        if (file_exists('data.json')) {
            //app()->call('App\Http\Controllers\AdminController@update_json');
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
        $result = DB::select("SELECT `id`, `disciplineName`, `groupsHistory`, `semester`, `loadType`, `formingDivisionuuid`, `readingDivisionuuid`,
       `compensationType`, `plannedHours`, `realHours`, `isHour` FROM `Loads` WHERE `tkey` = '$tkey'");

        foreach ($result as $row)
        {
            $planned = $row->plannedHours;
            $real = $row->realHours;
            $diff = (float)$planned - (float)$real;

            $plannedHours = $row->plannedHours == '' ? 0 : $row->plannedHours;
            $realHours = $row->realHours == '' ? 0 : $row->realHours;

            $forming = DB::select("SELECT `name` FROM `Divisions` WHERE `uuid`='$row->formingDivisionuuid'")[0];
            $reading = DB::select("SELECT `name` FROM `Divisions` WHERE `uuid`='$row->readingDivisionuuid'")[0];

            $line = [
                "id" => "$row->id",
                "disciplineName" => "$row->disciplineName",
                "groupsHistory" => "$row->groupsHistory",
                "semester" => "$row->semester",
                "loadType" => "$row->loadType",
                "formingDivisionuuid" => "$forming->name",
                "readingDivisionuuid" => "$reading->name",
                "compensationType" => "$row->compensationType",
                "plannedHours" => $plannedHours,
                "realHours" => $realHours,
                "diff" => $diff,
                "isHour" => $row->isHour
                ];
            array_push($data, $line);
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function change_hours() {
        $value = request()->get('value');
        $guidPerson1C = request()->get('guidPerson1C');
        DB::update("UPDATE `PhysFace1C` SET `hours` = '$value' WHERE `PhysFace1C`.`guidPerson1C` = '$guidPerson1C'");

        $json = file_get_contents('data.json');
        $data = json_decode($json);
        foreach ($data as $row)
        {
            if ($row->guidPerson1C == $guidPerson1C)
            {
                $row->hoursOnStake = $value;
                $row->hours = $row->bHoursPlaned - $row->hoursOnStake;
            }
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents('data.json', $json);
    }

    public function change_stake() {
        $value = request()->get('value');
        $tkey = request()->get('tkey');
        DB::update("UPDATE `Teachers` SET `stake` = '$value' WHERE `Teachers`.`tkey` = '$tkey'");

        $json = file_get_contents('data.json');
        $data = json_decode($json);
        foreach ($data as $row)
        {
            if ($row->tkey == $tkey)
            {
                $row->stake = $value;
            }
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents('data.json', $json);
    }

    public function change_ishour() {
        $value = request()->get('value');
        $id = (int)request()->get('id');
        DB::update("UPDATE `Loads` SET `isHour` = '$value' WHERE `Loads`.`id` = $id");
    }
}
