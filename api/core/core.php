<?php
    error_reporting(E_ERROR | E_PARSE);

    session_start();

    include_once "enums.php";
    include_once "jwt.php";
    include_once "database.php";

    // Parse settings and initialize global Jobjects
    $data = parse_ini_file("settings.ini");

    // Used for: creation, verification and decoding of tokens
    $jwt = new JWT($data["signKey"], $data["signAlgorithm"], $data["payloadSecret"], $data["payloadCipher"]);
    
    // Used for: querying of database
    $db = new Database($data["host"], $data["db"], $data["user"], $data["pass"]);

    // Used for: global ENUMS
    $ENUMS = new ENUMS();

    function output($status, $message) {
        $status = array("status" => $status, "message" => $message);

        die(json_encode($status));
    }
?>