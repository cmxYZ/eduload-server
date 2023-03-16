<?php
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', 'mon-ser-rtf-123');
// define('DB_NAME', 'eduload');

// $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// mysqli_set_charset($connection, 'utf8');
// if ($connection->connect_error) {
//     die('Error: Database connection failed: ' . $connection->connect_error);
// }

// function prepareData($data)
// {
//     global $connection;
//     return mysqli_real_escape_string($connection, stripslashes(htmlspecialchars($data)));
// }

if (!file_exists('teachers.json'))
{
    $ch = curl_init('http://runp.dit.urfu.ru:8990/api/teachers');
    curl_setopt($ch, CURLOPT_USERPWD, "iritrtf:SHi&7zTrpEf&A");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $result = curl_exec($ch);
    curl_close($ch);
    if ($result != false)
    {
        file_put_contents('teachers.json', $result);
    } else {
        die('Error: API connection failed: ');
    }
}
    $get_data = file_get_contents('teachers.json');
    $result = json_decode($get_data);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers</title>
</head>

<body>
    
</body>

</html>