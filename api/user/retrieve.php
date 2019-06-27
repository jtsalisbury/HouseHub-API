<?php

    header("Access-Control-Allow-Origin: http://u747950311.hostingerapp.com/househub/api/");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once "../core/core.php";

    // Read the token
    $data = json_decode(file_get_contents("php://input"), true);

    $token = $data["token"];

    // Verify the token
    if (!$jwt->verifyToken($token)) {
        output("error", ENUMS::TOKEN_INVALID);
    }

    $data = json_decode($jwt->decodePayload($token), true);

    $uid = htmlspecialchars(strip_tags($data["uid"])) + 0;

    // Verify required fields
    if (empty($uid)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    // Grab relevant user information from the id
    $link = $db->getLink();
    
    $sql = "SELECT firstname, lastname, email, created, lastmodified, admin FROM users WHERE id = :id";
    
    $stmt = $link->prepare($sql);
    $stmt->bindParam(":id", $uid); 
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ensure the user exists
    if ($stmt->rowCount() == 0) {
        output("error", ENUMS::USER_NOT_EXIST);
    }

    // Output information for the user
    $arr = array(
        "fname" => $res["firstname"],
        "lname" => $res["lastname"],
        "email" => $res["email"],
        "admin" => $res["admin"],
        "created" => $res["created"],
        "modified" => $res["lastmodified"]
    );

    $token = $jwt->generateToken($data);

    output("success", $data);
?>