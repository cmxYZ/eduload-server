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
        $sql = "INSERT INTO `Teachers` (`guidPerson1C`, `lastName`, `firstName`, `patronymic`, `postName`, `workPlace`, `samAccountName`, `stake`) 
        VALUES ('$value->guidPerson1C', '$value->lastName', '$value->firstName', '$value->patronymic', '$value->post', 
        '$value->workPlace', '$value->samAccountName', '$value->stake')";
        
        $result = mysqli_query($connection, $sql);

        if ($result == false) {
            die("SQL Error");
        }
    }
    

}

?>