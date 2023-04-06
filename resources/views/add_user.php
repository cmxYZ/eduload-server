<?php 
header('Access-Control-Allow-Origin: *');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'mon-ser-rtf-123');
define('DB_NAME', 'eduload');

$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($connection, 'utf8');
if ($connection->connect_error) {
    die('Error: Database connection failed: ' . $connection->connect_error);
}

if (isset($_POST["login"]) && isset($_POST["samAccauntName"]) && isset($_POST["isKeycloak"]))
{
    $login = $_POST["login"];
    $id = $_POST["samAccauntName"];
    $iskc = $_POST["isKeycloak"];

    $sql = "INSERT INTO `Users` (`login`, `roleID`, `samAccountName`, `password`, `isKeycloak`) VALUES ('$login', NULL, '$id', NULL, '$iskc')";
    try {
    mysqli_query($connection, $sql);
} catch (Exception $e) {
}
}
else echo 'it Works';