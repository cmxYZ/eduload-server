<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function update_db()
    {
        DB::statement("TRUNCATE `Loads`");
        DB::statement("TRUNCATE `PhysFace1C`");
        DB::statement("TRUNCATE `Teachers`");
        $this->update_loads('2022');
        $this->update_teachers();
        app('App\Http\Controllers\UserController')->updatedatajson();
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
                $InfoWorkPlaces = $result[0] . ", $InfoWorkPlaces";
                $stake = (float)$result[1] + (float)$value->stake;
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
                $guidPhysFace1C = $teacher->guidPhysFace1C;
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

                    DB::insert("INSERT INTO `Loads` (`guidPhysFace1C`, `formingDivisionuuid`, `readingDivisionuuid`, `groupsHistory`,
                `disciplineName`, `compensationType`, `loadType`, `plannedHours`, `realHours`, `semester`, `year`, `tkey`)
                VALUES ('$guidPhysFace1C', '$formingDivisionuuid', '$readingDivisionuuid', '$groupsHistory', '$disciplineName',
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
}
