<?php

use Illuminate\Support\Facades\DB;

    $login = $_GET["login"];
    $id = $_GET["samAccountName"];

    DB::insert("INSERT IGNORE INTO `Users` (`login`, `roleID`, `samAccountName`, `password`, `isKeycloak`) VALUES ('$login', NULL, '$id', NULL, '1')");
//    try {
//} catch (Exception $e) {
//}
