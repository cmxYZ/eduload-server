<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function PHPUnit\Framework\isEmpty;

class AdminController extends Controller
{
    public function update_teachers()
    {
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
        $this->update_stakes();
        $this->update_json();
    }

    public function update_stakes()
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

    }

    public function update_loads()
    {
        $get_year = request()->get('year');
        $get_tkey = request()->get('tkey');
        $result = '';
        if ($get_year == null || $get_year == '') {
            $year = 2022;
        }
        if ($get_tkey == null || $get_tkey == '') {
            $result = $this->load_from_api("http://runp.dit.urfu.ru:8990/api/loads?year=$get_year");
        }
        else
        {
            $result = $this->load_from_api("http://runp.dit.urfu.ru:8990/api/loads?year=$get_year&tkey=$get_tkey");
        }


        foreach ($result as $value) {
            $year = $value->year;
            DB::insert("INSERT IGNORE INTO `Years` (`year`) VALUES ('$year')");
            $semester = $value->semester;
            foreach ($value->teachers as $teacher) {
                $guidPerson1C = $teacher->guidPerson1C;
                $samAccountName = $teacher->samAccountName;
                $tkey = $teacher->tkey;
                DB::delete("DELETE FROM `Loads` WHERE `year` = '$year' AND `semester` = '$semester'
                      AND `guidPerson1C` = '$guidPerson1C' AND `tkey` = '$tkey'");
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

                    DB::insert("INSERT INTO `Loads` (`guidPerson1C`, `formingDivisionuuid`, `readingDivisionuuid`, `groupsHistory`,
                `disciplineName`, `compensationType`, `loadType`, `plannedHours`, `realHours`, `semester`, `year`, `tkey`)
                VALUES ('$guidPerson1C', '$formingDivisionuuid', '$readingDivisionuuid', '$groupsHistory', '$disciplineName',
                '$compensationType', '$loadType', '$plannedHours', '$realHours', '$semester', '$year', '$tkey')");

                    DB::insert("INSERT IGNORE INTO `Divisions` (`uuid`, `name`) VALUES ('$formingDivisionuuid', '$formingDivisionname')");
                    DB::insert("INSERT IGNORE INTO `Divisions` (`uuid`, `name`) VALUES ('$readingDivisionuuid', '$readingDivisionname')");

                }
            }
        }
        $this->update_json();
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

    public function update_json()
    {
        if (file_exists('data.json')) {
            unlink('data.json');
        }
        $years = DB::select("SELECT `year` FROM `Years`");
        $data = array();
        $result = DB::select("SELECT * FROM `Teachers`");
        $allowedDivisions = $this->get_allowed_divisions();

        foreach ($years as $year_row) {
            $year = $year_row->year;
            foreach ($result as $row) {
                $tkey = $row->tkey;
                $name = $row->lastName . ' ' . $row->firstName . ' ' . $row->patronymic;
                $infoWorkPlaces = $row->infoWorkPlaces;

                $stake = DB::select("SELECT `stake` FROM `Stakes` WHERE `tkey` = '$tkey' AND `year` = '$year'")[0]->stake;
                if ($stake == null || $stake == '') {
                    $stake = '-';
                }

                $b = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year'", $allowedDivisions);
                $c = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year'", $allowedDivisions);
                $a = $this->SummHours("SELECT plannedHours, realHours, readingDivisionuuid FROM `Loads`
                                                    WHERE tkey='$tkey' AND year='$year'", $allowedDivisions);
                $h = DB::select("SELECT hours FROM PhysFace1C WHERE guidPerson1C='$row->guidPerson1C'");
                $hoursOnStake = 0;

                if (!empty($h))
                    $hoursOnStake = (float)$h[0]->hours;

                $hours = $b[0] - $hoursOnStake;

                $line = ["tkey" => "$tkey", "name" => "$name", "infoWorkPlaces" => "$infoWorkPlaces", "stake" => $stake,
                    "hoursOnStake" => $hoursOnStake, "hours" => $hours,
                    "bHoursPlaned" => $b[0], "bHoursReal" => $b[1], "bHoursDiff" => $b[2],
                    "cHoursPlaned" => $c[0], "cHoursReal" => $c[1], "cHoursDiff" => $c[2],
                    "hoursPlaned" => $a[0], "hoursReal" => $a[1], "hoursDiff" => $a[2],
                    "year" => $year, "guidPerson1C" => $row->guidPerson1C];
                array_push($data, $line);
            }
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        file_put_contents('data.json', $json);
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
