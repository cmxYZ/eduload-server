<?php

if (!file_exists('loads.json'))
{
    $ch = curl_init('http://runp.dit.urfu.ru:8990/api/loads?year=2022');
    curl_setopt($ch, CURLOPT_USERPWD, "iritrtf:SHi&7zTrpEf&A");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $result = curl_exec($ch);
    curl_close($ch);
    if ($result != false)
    {
        file_put_contents('loads.json', $result);
    } else {
        die('Error: API connection failed: ');
    }
}
    $get_data = file_get_contents('loads.json');
    $result = json_decode($get_data);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loads</title>
</head>

<body>
    
</body>

</html>