<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    public function load_data()
    {
        if (file_exists("data.json")) {
            $get_accountName = request()->get('accountName');

            if ($get_accountName == '' || $get_accountName == null) {
                return file_get_contents('data.json');
            }
            else {
                return $this->get_one_teacher($get_accountName);
            }

        }
        return 'No Data';
    }

    public function get_one_teacher($accountName) {
        $tkey = DB::select("SELECT tkey FROM Teachers WHERE samAccountName='$accountName'");
        if (empty($tkey)) return 'No data';
        else $tkey = $tkey[0]->tkey;
        $json = file_get_contents('data.json');
        $data = json_decode($json);
        $result = array();
        foreach ($data as $row)
        {
            if ($row->tkey == $tkey)
            {
                array_push($result, $row);
            }
        }
        $json = json_encode($result, JSON_UNESCAPED_UNICODE);
        return $json;
    }

    public function check_user()
    {
        $login = request()->get('login');
        $id = request()->get('samAccountName');
        $isKC = request()->get('isKeycloak');
        DB::insert("INSERT IGNORE INTO `Users` (`login`, `roleID`, `samAccountName`, `password`, `isKeycloak`) VALUES ('$login', '4', '$id', NULL, '$isKC')");
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
        if (!isset($_GET['tkey']) && !isset($_GET['year']))
        {
            return 'No Data';
        }
        $tkey = $_GET['tkey'];
        $year = $_GET['year'];
        return $this->get_data_by_tkey($tkey, $year);
    }

    public function get_data_by_tkey($tkey, $year) {
        $data = array();
        $result = DB::select("SELECT `id`, `disciplineName`, `groupsHistory`, `semester`, `loadType`, `formingDivisionuuid`, `readingDivisionuuid`,
       `compensationType`, `plannedHours`, `realHours`, `isHour` FROM `Loads` WHERE `tkey` = '$tkey' AND `year` = '$year' AND deleted='0'");

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
                "plannedHours" => round($plannedHours, 2),
                "realHours" => round($realHours, 2),
                "diff" => round($diff, 2),
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
        $year = request()->get('year');
        DB::update("UPDATE `Stakes` SET `stake` = '$value' WHERE `tkey` = '$tkey' AND `year` = '$year'");

        $json = file_get_contents('data.json');
        $data = json_decode($json);
        foreach ($data as $row)
        {
            if ($row->tkey == $tkey && $row->year == $year)
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
        if ($value == 'true') $value = 1;
        if ($value == 'false') $value = 0;
        DB::update("UPDATE `Loads` SET `isHour` = '$value' WHERE `Loads`.`id` = $id");
    }

    public function change_realhours() {
        $value = request()->get('value');
        $id = (int)request()->get('id');
        DB::update("UPDATE `Loads` SET `realHours` = '$value' WHERE `Loads`.`id` = $id");
    }

    public function load_excel() {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
        $headers = [
            'ФИО',
            'Должность/Место работы',
            'Ставка',
            'Часы на ставку',
            'Кол-во часов на почасовую оплату',
            'Планируемое кол-во часов (Бюджет)',
            'Фактическое кол-во часов (Бюджет)',
            'Разница (Бюджет)',
            'Планируемое кол-во часов (Контракт)',
            'Фактическое кол-во часов (Контракт)',
            'Разница (Контракт)',
            'Планируемое кол-во часов (Общее)',
            'Фактическое кол-во часов (Общее)',
            'Разница (Общее)',
            'Год'
        ];
        for ($i = 0; $i < count($letters); $i++) {
            $worksheet->setCellValue($letters[$i] . 1, $headers[$i]);
        }
        $json = $this->load_data();
        $array = json_decode($json);
        $filename = 'Отчет 1.xlsx';

        for ($i = 0; $i < count($array); $i++) {
            $worksheet->setCellValue($letters[0] . ($i + 2), $array[$i]->name);
            $worksheet->setCellValue($letters[1] . ($i + 2), $array[$i]->infoWorkPlaces);
            $worksheet->setCellValue($letters[2] . ($i + 2), $array[$i]->stake);
            $worksheet->setCellValue($letters[3] . ($i + 2), $array[$i]->hoursOnStake);
            $worksheet->setCellValue($letters[4] . ($i + 2), $array[$i]->hours);
            $worksheet->setCellValue($letters[5] . ($i + 2), $array[$i]->bHoursPlaned);
            $worksheet->setCellValue($letters[6] . ($i + 2), $array[$i]->bHoursReal);
            $worksheet->setCellValue($letters[7] . ($i + 2), $array[$i]->bHoursDiff);
            $worksheet->setCellValue($letters[8] . ($i + 2), $array[$i]->cHoursPlaned);
            $worksheet->setCellValue($letters[9] . ($i + 2), $array[$i]->cHoursReal);
            $worksheet->setCellValue($letters[10] . ($i + 2), $array[$i]->cHoursDiff);
            $worksheet->setCellValue($letters[11] . ($i + 2), $array[$i]->hoursPlaned);
            $worksheet->setCellValue($letters[12] . ($i + 2), $array[$i]->hoursReal);
            $worksheet->setCellValue($letters[13] . ($i + 2), $array[$i]->hoursDiff);
            $worksheet->setCellValue($letters[14] . ($i + 2), $array[$i]->year);
        }
        $worksheet->setAutoFilter('A1:O' . count($array) + 1);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        return view('download', ['filename' => $filename]);
    }

    public function load_excel_by_tkey() {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $headers = [
            'Дисциплина',
            'Академическая группа',
            'Семестр',
            'Вид нагрузки',
            'Формирующая кафедра',
            'Читающая кафедра',
            'Тип нагрузки',
            'Планируемое кол-во часов',
            'Фактическое кол-во часов',
            'Разница',
            'Почасовая оплата',
        ];
        for ($i = 0; $i < count($letters); $i++) {
            $worksheet->setCellValue($letters[$i] . 1, $headers[$i]);
        }
        $tkey = $_GET['tkey'];
        $year = $_GET['year'];
        $json = $this->get_data_by_tkey($tkey, $year);
        $array = json_decode($json);
        $name = DB::select("SELECT `lastName`, `firstName`, `patronymic` FROM `Teachers` WHERE `tkey` = '$tkey'");
        $filename = 'Нагрузка преподавателя ' . $name[0]->lastName . ' ' . $name[0]->firstName
            . ' ' . $name[0]->patronymic . ' ' . $year . '.xlsx';

        for ($i = 0; $i < count($array); $i++) {
            $worksheet->setCellValue($letters[0] . ($i + 2), $array[$i]->disciplineName);
            $worksheet->setCellValue($letters[1] . ($i + 2), $array[$i]->groupsHistory);
            $worksheet->setCellValue($letters[2] . ($i + 2), $array[$i]->semester);
            $worksheet->setCellValue($letters[3] . ($i + 2), $array[$i]->loadType);
            $worksheet->setCellValue($letters[4] . ($i + 2), $array[$i]->formingDivisionuuid);
            $worksheet->setCellValue($letters[5] . ($i + 2), $array[$i]->readingDivisionuuid);
            $worksheet->setCellValue($letters[6] . ($i + 2), $array[$i]->compensationType);
            $worksheet->setCellValue($letters[7] . ($i + 2), $array[$i]->plannedHours);
            $worksheet->setCellValue($letters[8] . ($i + 2), $array[$i]->realHours);
            $worksheet->setCellValue($letters[9] . ($i + 2), $array[$i]->diff);
            $worksheet->setCellValue($letters[10] . ($i + 2), $array[$i]->isHour);
        }
        $worksheet->setAutoFilter('A1:K' . count($array) + 1);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        return view('download', ['filename' => $filename]);
    }
}


