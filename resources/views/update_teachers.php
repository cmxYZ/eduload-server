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


if (file_exists('teachers.json'))
{
    $get_data = file_get_contents('teachers.json');
    $result = json_decode($get_data);



    foreach ($result as $value) {
        $InfoWorkPlaces = '';
        if ($value->guidPerson1C == null || $value->guidPhysPerson1C == null) {
            $InfoWorkPlaces = "$value->post($value->post)";
        }
        else {
            $sql = "INSERT INTO `PhysFace1C` (`guidPhysFace1C`, `guidPerson1C`, `postName`, `workPlace`, `year`, `hours`) 
            VALUES ('$value->guidPhysPerson1C', '$value->guidPerson1C', '$value->post', '$value->workPlace', '', '')";
        
        try {
            mysqli_query($connection, $sql);
            } catch (Exception $e) {}
        }

        $sql = "INSERT INTO `Teachers` (`tkey`, `guidPerson1C`, `lastName`, `firstName`, `patronymic`, `samAccountName`, `stake`, `infoWorkPlaces`) 
        VALUES ('$value->tkey', '$value->guidPerson1C', '$value->lastName', '$value->firstName', '$value->patronymic', 
        '$value->samAccountName', '$value->stake', '$InfoWorkPlaces')";
        
        try {
        mysqli_query($connection, $sql);
        } catch (Exception $e) {
            $sql = "SELECT `infoWorkPlaces` FROM `Teachers` WHERE `tkey` = '$value->tkey'";
            $result = mysqli_query($connection, $sql);
            $result = $result->fetch_row();
            $InfoWorkPlaces = $result[0] . ", $InfoWorkPlaces";
            $sql = "UPDATE `Teachers` SET `infoWorkPlaces` = '$InfoWorkPlaces' WHERE `Teachers`.`tkey` = '$value->tkey'";
            mysqli_query($connection, $sql);
        }
}
}
?>