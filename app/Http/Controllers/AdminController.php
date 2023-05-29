<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

class AdminController extends Controller
{
    public function update_teachers()
    {
        $get_user = request()->get('user');
        DB::insert("INSERT INTO `Loging` (`login`, `message`) VALUES ('INFO', 'Начато обновление учителей (user=$get_user)')");
        $result = $this->load_from_api('http://runp.dit.urfu.ru:8990/api/teachers');

        foreach ($result as $value) {

            //PhysFace1C
            if ($value->guidPerson1C != null && $value->guidPhysPerson1C != null) {
                $sql = DB::select("SELECT `id` FROM `PhysFace1C` WHERE `guidPhysFace1C` = '$value->guidPhysPerson1C' AND `guidPerson1C` = '$value->guidPerson1C'");
                if (empty($sql))
                {
                    DB::insert("INSERT INTO `PhysFace1C` (`guidPhysFace1C`, `guidPerson1C`, `postName`, `workPlace`, `year`, `stake`, `hours`)
            VALUES ('$value->guidPhysPerson1C', '$value->guidPerson1C', '$value->post', '$value->workPlace', '', '$value->stake', '')");
                }
                else
                {
                    DB::update("UPDATE `PhysFace1C` SET `postName` = '$value->post', `workPlace` = '$value->workPlace',
                        `stake` = '$value->stake' WHERE `guidPhysFace1C` = '$value->guidPhysPerson1C' AND `guidPerson1C` = '$value->guidPerson1C'");
                }
            }
            //PhysFace1C

            //Teachers
            $InfoWorkPlaces = "$value->post: $value->stake ($value->workPlace)";

            $sql = DB::select("SELECT `tkey` FROM `Teachers` WHERE `tkey` = '$value->tkey'");
            if (empty($sql))
            {
                DB::insert("INSERT INTO `Teachers` (`tkey`, `guidPerson1C`, `lastName`, `firstName`, `patronymic`, `samAccountName`, `stake`, `infoWorkPlaces`)
        VALUES ('$value->tkey', '$value->guidPerson1C', '$value->lastName', '$value->firstName', '$value->patronymic',
        '$value->samAccountName', '$value->stake', '$InfoWorkPlaces')");
            }
            else
            {
                $sql = DB::select("SELECT `infoWorkPlaces`, `stake` FROM `Teachers` WHERE `tkey` = '$value->tkey'");
                if (!str_contains($sql[0]->infoWorkPlaces, $InfoWorkPlaces)) {
                    $InfoWorkPlaces = $sql[0]->infoWorkPlaces . ", $InfoWorkPlaces";
                    $stake = (float)$sql[0]->stake + (float)$value->stake;
                    DB::update("UPDATE `Teachers` SET `infoWorkPlaces` = '$InfoWorkPlaces', `stake` = '$stake' WHERE `Teachers`.`tkey` = '$value->tkey'");
                }
            }
            //Teachers
        }
        return $this->update_stakes("Завершено обновление учителей (user=$get_user)");
    }

    public function update_stakes($massage)
    {
        $years = DB::select("SELECT `year` FROM `Years`");
        $teachers = DB::select("SELECT `tkey`, `stake` FROM `Teachers`");

        foreach ($years as $year_row)
        {
            foreach ($teachers as $teacher_row) {
                $sql = DB::select("SELECT `id` FROM `Stakes` WHERE `year` = '$year_row->year' AND `tkey` = '$teacher_row->tkey'");
                if (empty($sql)) {
                    DB::insert("INSERT INTO `Stakes` (`stake`, `year`, `tkey`) VALUES ('$teacher_row->stake', '$year_row->year', '$teacher_row->tkey')");
                }
            }
        }
        return $this->update_json($massage);
    }

    public function update_loads()
    {
        if (!isset($_GET['year'])) {
            return 'Data Error: Year is required';
        }

        $get_year = request()->get('year');
        if ($get_year == null || $get_year == '') {
            $get_year = $this->setYear();
        }
        $get_tkey = request()->get('tkey');
        $get_user = request()->get('user');
        DB::insert("INSERT INTO `Loging` (`login`, `message`) VALUES ('INFO', 'Начато обновление нагрузок (year=$get_year, tkey=$get_tkey, user=$get_user)')");

        $result = '';
        if ($get_tkey == null || $get_tkey == '') {
            DB::update("UPDATE `Loads` SET `deleted`='1' WHERE `year` = '$get_year'");
            $result = $this->load_from_api("http://runp.dit.urfu.ru:8990/api/loads?year=$get_year");
        } else {
            DB::update("UPDATE `Loads` SET `deleted`='1' WHERE `year` = '$get_year' AND `tkey` = '$get_tkey'");
            $result = $this->load_from_api("http://runp.dit.urfu.ru:8990/api/loads?year=$get_year&tkey=$get_tkey");
        }

        foreach ($result as $value) {
            $year = $value->year;
            DB::insert("INSERT IGNORE INTO `Years` (`year`) VALUES ('$year')");
            $semester = $value->semester;
            foreach ($value->teachers as $teacher) {
                $guidPerson1C = $teacher->guidPerson1C;
                $tkey = $teacher->tkey;
                foreach ($teacher->loads as $load) {
                    $formingDivisionuuid = $load->formingDivision->uuid;
                    $readingDivisionuuid = $load->readingDivision->uuid;
                    $formingDivisionname = $load->formingDivision->name;
                    $readingDivisionname = $load->readingDivision->name;
                    $groupsHistory = implode(", ", $load->groupHistory);
                    $disciplineName = $load->disciplineName;
                    $compensationType = $load->compensationType;
                    $loadType = $load->loadType;
                    $plannedHours = $load->plannedHours;
                    $realHours = $load->realHours;
                    //tkey, GroupsHistory, DisciplineName, LoadType, Semester, Year

                    $sql = DB::select("SELECT id FROM `Loads` WHERE `disciplineName` = '$disciplineName' AND `loadType` = '$loadType'
                    AND `semester` = '$semester' AND `year` = '$year' AND `tkey` = '$tkey' AND `groupsHistory` = '$groupsHistory'");

                    if (empty($sql)) {
                        DB::insert("INSERT INTO `Loads` (`guidPerson1C`, `formingDivisionuuid`, `readingDivisionuuid`, `groupsHistory`,
                `disciplineName`, `compensationType`, `loadType`, `plannedHours`, `realHours`, `semester`, `year`, `tkey`, `deleted`)
                VALUES ('$guidPerson1C', '$formingDivisionuuid', '$readingDivisionuuid', '$groupsHistory', '$disciplineName',
                '$compensationType', '$loadType', '$plannedHours', '$realHours', '$semester', '$year', '$tkey', '0')");
                    } else {
                        $id = $sql[0]->id;

                        if ($compensationType == 'контракт')
                        {
                            DB::update("UPDATE `Loads` SET `plannedHours` = '$plannedHours', `realHours` = '$realHours',
                   `guidPerson1C` = '$guidPerson1C', `formingDivisionuuid` = '$formingDivisionuuid', `readingDivisionuuid` = '$readingDivisionuuid', `deleted` = '0' WHERE `id` = '$id'");
                        }
                        else
                        {
                            DB::update("UPDATE `Loads` SET `plannedHours` = '$plannedHours',
                   `guidPerson1C` = '$guidPerson1C', `formingDivisionuuid` = '$formingDivisionuuid', `readingDivisionuuid` = '$readingDivisionuuid', `deleted` = '0' WHERE `id` = '$id'");
                        }
                    }
                    DB::insert("INSERT IGNORE INTO `Divisions` (`uuid`, `name`) VALUES ('$formingDivisionuuid', '$formingDivisionname')");
                    DB::insert("INSERT IGNORE INTO `Divisions` (`uuid`, `name`) VALUES ('$readingDivisionuuid', '$readingDivisionname')");

                }
            }
        }
        $massage = "Завершено обновление нагрузок (year=$get_year, tkey=$get_tkey, user=$get_user)";
        if ($get_tkey == null || $get_tkey == '') {
            return $this->update_year_json($get_year, $massage);
        }
        return $this->update_teacher_in_json($get_year, $get_tkey, $massage);
    }

    public function update_teacher_in_json($year, $tkey, $massage)
    {
        if (!file_exists("$year.json")) return $this->update_year_json($year);;

        $json = file_get_contents("$year.json");
        $data = json_decode($json);
        $allowedDivisions = $this->get_allowed_divisions();

        $b = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year' AND deleted='0'", $allowedDivisions);
        $c = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year' AND deleted='0'", $allowedDivisions);
        $a = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND year='$year' AND deleted='0'", $allowedDivisions);

        $data->$tkey->bHoursPlaned = $b[0];
        $data->$tkey->bHoursReal = $b[1];
        $data->$tkey->bHoursDiff = $b[2];
        $data->$tkey->cHoursPlaned = $c[0];
        $data->$tkey->cHoursReal = $c[1];
        $data->$tkey->cHoursDiff = $c[2];
        $data->$tkey->hoursPlaned = $a[0];
        $data->$tkey->hoursReal = $a[1];
        $data->$tkey->hoursDiff = $a[2];

        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        unlink("$year.json");
        file_put_contents("$year.json", $json);
        return $this->update_stakes($massage);
    }

    public function update_year_json($year, $massage)
    {
        $data = array();
        $result = DB::select("SELECT tkey FROM `Teachers`");
        $allowedDivisions = $this->get_allowed_divisions();

        foreach ($result as $row) {
            $tkey = $row->tkey;

            $b = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year' AND deleted='0'", $allowedDivisions);
            $c = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year' AND deleted='0'", $allowedDivisions);
            $a = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND year='$year' AND deleted='0'", $allowedDivisions);

            $line = [ $tkey => [
                "bHoursPlaned" => $b[0], "bHoursReal" => $b[1], "bHoursDiff" => $b[2],
                "cHoursPlaned" => $c[0], "cHoursReal" => $c[1], "cHoursDiff" => $c[2],
                "hoursPlaned" => $a[0], "hoursReal" => $a[1], "hoursDiff" => $a[2]
            ]
            ];
            $data += $line;
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (file_exists("$year.json")) {
            unlink("$year.json");
        }
        file_put_contents("$year.json", $json);
        return $this->update_stakes($massage);
    }

    public function update_json($massage = 'test') {
        $years = DB::select("SELECT `year` FROM `Years`");
        $data = array();
        $teachers = DB::select("SELECT * FROM `Teachers`");

        foreach ($years as $year_row) {
            $year = $year_row->year;
            if (file_exists("$year.json")) {
                $json = file_get_contents("$year.json");
                $current_year_loads = json_decode($json);
                foreach ($teachers as $teacher) {
                    $tkey = $teacher->tkey;
                    $name = $teacher->lastName . ' ' . $teacher->firstName . ' ' . $teacher->patronymic;
                    $infoWorkPlaces = $teacher->infoWorkPlaces;

                    $stake = '-';
                    $sql = DB::select("SELECT `stake` FROM `Stakes` WHERE `tkey` = '$tkey' AND `year` = '$year'");
                    if (!empty($sql))
                    {
                        $stake = $sql[0]->stake;
                    }

                    $h = DB::select("SELECT hours FROM PhysFace1C WHERE guidPerson1C='$teacher->guidPerson1C'");
                    $hoursOnStake = 0;

                    if (!empty($h))
                        $hoursOnStake = (float)$h[0]->hours;
                    var_dump($current_year_loads);
                    $hours = $current_year_loads->$tkey->bHoursPlaned - $hoursOnStake;

                    $line = ["tkey" => "$tkey", "name" => "$name", "infoWorkPlaces" => "$infoWorkPlaces", "stake" => $stake,
                        "hoursOnStake" => $hoursOnStake, "hours" => round($hours,2),
                        "bHoursPlaned" => $current_year_loads->$tkey->bHoursPlaned,
                        "bHoursReal" => $current_year_loads->$tkey->bHoursReal,
                        "bHoursDiff" => $current_year_loads->$tkey->bHoursDiff,
                        "cHoursPlaned" => $current_year_loads->$tkey->cHoursPlaned,
                        "cHoursReal" => $current_year_loads->$tkey->cHoursReal,
                        "cHoursDiff" => $current_year_loads->$tkey->cHoursDiff,
                        "hoursPlaned" => $current_year_loads->$tkey->hoursPlaned,
                        "hoursReal" => $current_year_loads->$tkey->hoursReal,
                        "hoursDiff" => $current_year_loads->$tkey->hoursDiff,
                        "year" => $year,
                        "guidPerson1C" => $teacher->guidPerson1C];
                    array_push($data, $line);
                }
            }
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (file_exists("data.json")) {
            unlink("data.json");
        }
        file_put_contents('data.json', $json);
        DB::insert("INSERT INTO `Loging` (`login`, `message`) VALUES ('INFO', '$massage')");
        return 'Success';
    }

    public function load_from_api($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, "iritrtf:SHi&7zTrpEf&A");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $request = curl_exec($ch);
        curl_close($ch);
        if (!$request) die('Error: API connection failed!');

        return json_decode($request);
    }

    public function setYear() : int
    {
        $year = '';
        if ((int)date('n') >= 9) {
            $year = (int)date('o');
        } else {
            $year = (int)date('o') - 1;
        }
        return $year;
    }

    public function SummHours($sql, $allowedDivisions)
    {
        $real = 0;
        $planed = 0;
        $result = DB::select($sql);
        foreach ($result as $row)
        {
            if (in_array($row->readingDivisionuuid, $allowedDivisions)) {
                $planed += (float)$row->plannedHours;
                $real += (float)$row->realHours;
            }
        }
        $diff = $planed-$real;
        $real = round($real, 2);
        $planed = round($planed, 2);
        $diff = round($diff, 2);
        return [$planed, $real, $diff];
    }

    public function get_allowed_divisions() {
        $result = DB::select("SELECT `uuid` FROM `Divisions` WHERE `rtfParent` = '1'");
        $data = array();

        foreach ($result as $row)
        {
            array_push($data, $row->uuid);
        }
        return $data;
    }
}
