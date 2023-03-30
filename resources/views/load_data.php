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

$year = "2022";
$data = array();
$sql = "SELECT * FROM `Teachers`";
$result = mysqli_query($connection, $sql);
$result = $result->fetch_all();

foreach ($result as $row)
{
    $b = SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$row->tkey' AND compensationType='бюджет' AND year='$year'", $connection);
    $c = SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$row->tkey' AND compensationType='контракт' AND year='$year'", $connection);
    $a = SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$row->tkey' AND year='$year'", $connection);

    $line = ["name" => "$row->lastName $row->firstName $row->patronymic", "infoWorkPlaces" => "$row->infoWorkPlaces", "stake" => "$row->stake", 
    "hoursOnStake" => "", "hours" => "", 
    "bHoursPlaned" => $b[0], "bHoursReal" => $b[1], "bHoursDiff" => $b[2], 
    "cHoursPlaned" => $c[0], "cHoursReal" => $c[1], "cHoursDiff" => $c[2], 
    "hoursPlaned" => $a[0], "hoursReal" => $a[1], "hoursDiff" => $a[2], 
    "year" => "2022"];
    array_push($data, $line);
}
    
$json = json_encode($data);
echo $json;

function SummHours($sql, $connection)
{
    $real = 0;
    $planed = 0;
    $result = mysqli_query($connection, $sql);
    $result = $result->fetch_all();
    foreach ($result as $row)
    {
        $planed += (float)$row[0];
        $real += (float)$row[0];
    }
    $diff = $planed-$real;
    return ["$planed", "$real", "$diff"];
}