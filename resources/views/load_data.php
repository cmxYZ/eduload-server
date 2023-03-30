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

if (!file_exists('data.json')) {
$year = "2022";
$data = array();
$sql = "SELECT * FROM `Teachers`";
$result = mysqli_query($connection, $sql);
$result = $result->fetch_all();

foreach ($result as $row)
{
    $tkey = $row[0];
    $name = $row[2] . ' ' .  $row[3] . ' ' . $row[4];
    $infoWorkPlaces = $row[7];
    $stake = $row[6];

    $b = SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='бюджет' AND year='$year'", $connection);
    $c = SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND compensationType='контракт' AND year='$year'", $connection);
    $a = SummHours("SELECT plannedHours, realHours FROM `Loads` WHERE tkey='$tkey' AND year='$year'", $connection);

    $line = ["name" => "$name", "infoWorkPlaces" => "$infoWorkPlaces", "stake" => "$stake", 
    "hoursOnStake" => "default", "hours" => "default", 
    "bHoursPlaned" => $b[0], "bHoursReal" => $b[1], "bHoursDiff" => $b[2], 
    "cHoursPlaned" => $c[0], "cHoursReal" => $c[1], "cHoursDiff" => $c[2], 
    "hoursPlaned" => $a[0], "hoursReal" => $a[1], "hoursDiff" => $a[2], 
    "year" => $year];
    array_push($data, $line);
}
$json = json_encode($data, JSON_UNESCAPED_UNICODE);
file_put_contents('data.json', $json);
}

$get_data = file_get_contents('data.json');
echo $get_data;






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