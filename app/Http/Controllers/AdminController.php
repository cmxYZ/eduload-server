<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function update()
    {
        DB::statement("TRUNCATE `Loads`");
        DB::statement("TRUNCATE `PhysFace1C`");
        DB::statement("TRUNCATE `Teachers`");
        $this->update_loads('2022');
        $this->update_teachers();
        $this->updatedatajson();
        return 'Success';
    }

    public function update_teachers()
    {
        $result = $this->load_from_api('http://runp.dit.urfu.ru:8990/api/teachers');
        foreach ($result as $value) {
            $InfoWorkPlaces = "$value->post: $value->stake ($value->workPlace)";

            if ($value->guidPerson1C != null && $value->guidPhysPerson1C != null) {
                DB::insert("INSERT INTO `PhysFace1C` (`guidPhysFace1C`, `guidPerson1C`, `postName`, `workPlace`, `year`, `stake`, `hours`)
            VALUES ('$value->guidPhysPerson1C', '$value->guidPerson1C', '$value->post', '$value->workPlace', '', '$value->stake', '')");
            }

            $year = $this->setYear();
            DB::insert("INSERT INTO `Stakes` (`stake`, `year`, `tkey`) VALUES ('$value->stake', '$year', '$value->tkey')");

            try {
                DB::insert("INSERT INTO `Teachers` (`tkey`, `guidPerson1C`, `lastName`, `firstName`, `patronymic`, `samAccountName`, `stake`, `infoWorkPlaces`)
        VALUES ('$value->tkey', '$value->guidPerson1C', '$value->lastName', '$value->firstName', '$value->patronymic',
        '$value->samAccountName', '$value->stake', '$InfoWorkPlaces')");
            } catch (\Illuminate\Database\QueryException $ex) {
                $result = DB::select("SELECT `infoWorkPlaces`, `stake` FROM `Teachers` WHERE `tkey` = '$value->tkey'");
                $InfoWorkPlaces = $result[0]->infoWorkPlaces . ", $InfoWorkPlaces";
                $stake = (float)$result[0]->stake + (float)$value->stake;
                DB::update("UPDATE `Teachers` SET `infoWorkPlaces` = '$InfoWorkPlaces' WHERE `Teachers`.`tkey` = '$value->tkey'");
                DB::update("UPDATE `Teachers` SET `stake` = '$stake' WHERE `Teachers`.`tkey` = '$value->tkey'");
            }
        }
    }

    public function update_loads($filterYear)
    {
        $result = $this->load_from_api("http://runp.dit.urfu.ru:8990/api/loads?year=$filterYear");
        foreach ($result as $value) {
            $year = $value->year;
            $semester = $value->semester;
            foreach ($value->teachers as $teacher) {
                $guidPerson1C = $teacher->guidPerson1C;
                $samAccountName = $teacher->samAccountName;
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

                    DB::insert("INSERT INTO `Loads` (`guidPerson1C`, `formingDivisionuuid`, `readingDivisionuuid`, `groupsHistory`,
                `disciplineName`, `compensationType`, `loadType`, `plannedHours`, `realHours`, `semester`, `year`, `tkey`)
                VALUES ('$guidPerson1C', '$formingDivisionuuid', '$readingDivisionuuid', '$groupsHistory', '$disciplineName',
                '$compensationType', '$loadType', '$plannedHours', '$realHours', '$semester', '$year', '$tkey')");
                    DB::insert("INSERT IGNORE INTO `Divisions` (`uuid`, `name`) VALUES ('$formingDivisionuuid', '$formingDivisionname')");
                    DB::insert("INSERT IGNORE INTO `Divisions` (`uuid`, `name`) VALUES ('$readingDivisionuuid', '$readingDivisionname')");
                }
            }
        }
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
            $name = $row->lastName . ' ' .  $row->firstName . ' ' . $row->patronymic;
            $infoWorkPlaces = $row->infoWorkPlaces;
            $stake = $row->stake == '' ? '-' : $row->stake;

            $b = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year'");
            $c = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year'");
            $a = $this->SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND year='$year'");

            $line = ["tkey" => "$tkey", "name" => "$name", "infoWorkPlaces" => "$infoWorkPlaces", "stake" => "$stake",
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
