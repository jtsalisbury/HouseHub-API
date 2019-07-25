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

    if (!$link) {
        output("error", ENUMS::DB_NOT_CONNECTED);
    }
    
    $sql = "SELECT firstname, lastname, email, created, lastmodified, admin, COUNT(listings.id) AS num_listings 
            FROM users 
            LEFT JOIN listings ON listings.creator_uid = users.id
            WHERE users.id = :id";
    
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
        "modified" => $res["lastmodified"],
        "num_listings" => $res["num_listings"]
    );

    $token = $jwt->generateToken($arr);

    output("success", $token);
?>