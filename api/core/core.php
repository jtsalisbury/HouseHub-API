<?php
    error_reporting(E_ERROR | E_PARSE);

    session_start();

    include_once "enums.php";
    include_once "jwt.php";
    include_once "database.php";

    // Parse settings and initialize global Jobjects
    $sec = parse_ini_file("settings.ini");

    // Used for: creation, verification and decoding of tokens
    $jwt = new JWT($sec["signKey"], $sec["signAlgorithm"], $sec["payloadSecret"], $sec["payloadCipher"]);
    
    // Used for: querying of database
    $db = new Database($sec["host"], $sec["db"], $sec["user"], $sec["pass"]);

    // Used for: global ENUMS
    $ENUMS = new ENUMS();

    function output($status, $message) {
        $status = array("status" => $status, "message" => $message);

        die(json_encode($status));
    }
?>