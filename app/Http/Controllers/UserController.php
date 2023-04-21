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
            $id = $result[0]->roleID;
            $sql = DB::select("SELECT `roleName` FROM `Roles` WHERE `roleID` = $id");
            return $sql[0]->roleName;
        }
        else {
            return 'norole';
        }
    }

    public function load_data_by_tkey(string $tkey) {
        if (!isset($_GET['tkey']))
        {
            return 'No Data';
        }
        $tkey = $_GET['tkey'];
        $data = array();
        $result = DB::select("SELECT `disciplineName`, `groupsHistory`, `semester`, `loadType`, `formingDivisionuuid`, `readingDivisionuuid`,
       `compensationType`, `plannedHours`, `realHours`, `isHour` FROM `Loads` WHERE `tkey` = '$tkey'");

        return json_encode($result, JSON_UNESCAPED_UNICODE);
//        foreach ($result as $row)
//        {
//            $disciplineName = $row->disciplineName;
//            $groupsHistory = $row->groupsHistory;
//            $semester = $row->semester;
//            $loadType = $row->loadType;
//            $formingDivisionuuid = $row->formingDivisionuuid;
//            $readingDivisionuuid = $row->readingDivisionuuid;
//            $compensationType = $row->compensationType;
//            $tkey = $row->tkey;
//            $tkey = $row->tkey;
//            $tkey = $row->tkey;
//            $tkey = $row->tkey;
//            $name = $row->lastName;
//            $infoWorkPlaces = $row->infoWorkPlaces;
//            $stake = $row->stake;
//
//            $line = [
//                "disciplineName" => "$tkey",
//                "groupsHistory" => "$name",
//                "semester" => "$infoWorkPlaces",
//                "loadType" => "$stake",
//                "formingDivisionuuid" => "0",
//                "readingDivisionuuid" => "0",
//                "compensationType" => $b[0],
//                "plannedHours" => $b[1],
//                "realHours" => $b[2],
//                "diff" => $c[0],
//                "isHour" => $c[1]
//                ];
//            array_push($data, $line);
//        }

    }

//{ headerName: "Дисциплина", field: "disciplineName", width: 280, filter: true, floatingFilter: true,},
//{ headerName: "Академическая группа", field: "groupsHistory", width: 280, filter: true, floatingFilter: true,},
//{ headerName: "Семестр", field: "semester",  width: 115, filter: true, floatingFilter: true,},
//{ headerName: "Вид нагрузки", field: "loadType", width: 140, filter: true, floatingFilter: true,},
//{ headerName: "Формирующая кафедра", field: "formingDivisionuuid", width: 140, filter: true, floatingFilter: true,},
//{ headerName: "Читающая кафедра", field: "readingDivisionuuid", width: 140, filter: true, floatingFilter: true,},
//{ headerName: "Тип нагрузки", field: "compensationType", width: 140, filter: true, floatingFilter: true,},
//{ headerName: "Планируемое кол-во часов", field: "plannedHours", width: 140},
//{ headerName: "Фактическое кол-во часов", field: "realHours", editable: true, width: 140},
//{ headerName: "Разница", field: "diff", width: 140},
//{ headerName: "Почасовая оплата", field: "isHour", width: 140},
}
