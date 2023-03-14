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

function prepareData($data)
{
    global $connection;
    return mysqli_real_escape_string($connection, stripslashes(htmlspecialchars($data)));
}

$get_data = file_get_contents("http://runp.dit.urfu.ru:8990/api/teachers");
$get_data = json_decode($get_data);
var_dump($get_data);


?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keycloak Auth</title>
    <style>
        .content {
            width: 300px;
            margin: auto;
            margin-top: 100px;
            text-align: center;
        }
    </style>
</head>

<body>
    
</body>

</html>