<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'mon-ser-rtf-123');
define('DB_NAME', 'eduload');

$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($connection, 'utf8');
if ($connection->connect_error) {
    die('Error: Database connection failed: ' . $connection->connect_error);
}


if (file_exists('loads.json'))
{
    $get_data = file_get_contents('loads.json');
    $result = json_decode($get_data);

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
                $groupsHistory = implode(", ", $load->groupHistory);
                $disciplineName = $load->disciplineName;
                $compensationType = $load->compensationType;
                $loadType = $load->loadType;
                $plannedHours = $load->plannedHours;
                $realHours = $load->realHours;
                
                $sql = "INSERT INTO `Loads` (`guidPhysFace1C`, `formingDivisionuuid`, `readingDivisionuuid`, `groupsHistory`, 
                `disciplineName`, `compensationType`, `loadType`, `plannedHours`, `realHours`, `semester`, `year`, `tkey`) 
                VALUES ('$guidPhysFace1C', '$formingDivisionuuid', '$readingDivisionuuid', '$groupsHistory', '$disciplineName', 
                '$compensationType', '$loadType', '$plannedHours', '$realHours', '$semester', '$year', '$tkey')";
                $result = mysqli_query($connection, $sql);
                if ($result == false) {
                    die("SQL Error");
                }

                try {
                    addDivision($connection, $load->formingDivision->uuid, $load->formingDivision->name);
                    addDivision($connection, $load->readingDivision->uuid, $load->readingDivision->name);
                } catch (Exception $e) {}
            }
        }

    }
    
}

function addDivision($connection, $uuid, $name) {
    $sql = "INSERT INTO `Divisions` (`uuid`, `name`) VALUES ('$uuid', '$name')";
    mysqli_query($connection, $sql);
}

?>