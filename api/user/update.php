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

    $uid = htmlspecialchars(strip_tags($data["uid"]));
    $pass = htmlspecialchars(strip_tags($data["pass"]));
    $fields = $data["fields"];

    // Ensure all the fields are set 
    if (empty($uid) || empty($pass) || empty($fields)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    $availableFields = array(
        "fname" => false,
        "lname" => false,
        "email" => false,
        "pass"  => false,
        "repass" => false
    );

    // Loop through each of the passed fields
    // Check its validity
    // If the field exists, clean it and map the value to the available field
    foreach ($fields as $field => $value) {
        if (!isset($availableFields[$field])) {
            output("error", ENUMS::FIELD_NOT_EXIST);
        }

        $fields->$field = htmlspecialchars(strip_tags($value));
        $availableFields[$field] = $fields[$field];
    }

    // Special cases to ensure the passwords are set and equal
    if (!empty($fields["pass"]) && empty($fields["repass"])) {
        output("error", ENUMS::UPDATE_PASS_NOT_EQUAL);
    }

    if (!empty($fields["repass"]) && empty($fields["pass"])) {
        output("error", ENUMS::UPDATE_PASS_NOT_EQUAL);
    }

    if ($fields["pass"] != $fields["repass"]) {
        output("error", ENUMS::UPDATE_PASS_NOT_EQUAL);
    }

    // Get the database link and select the current information for the user
    $link = $db->getLink();

    $stmt = $link->prepare("SELECT * FROM `users` WHERE id = :id");
    $stmt->bindParam(":id", $uid);
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stmt->rowCount() == 0) {
        output("error", ENUMS::USER_NOT_EXIST);
    }

    // Ensure the password is equal (user must provide password to change data)
    $hashedPass = $res["hashed_pass"];
    if (!password_verify($pass, $hashedPass)) {
        output("error", ENUMS::PASS_NOT_EQUAL);
    }

    // We assume each field was passed (to be changed)
    $setFields = array(
        "firstname" => $availableFields["fname"],
        "lastname" => $availableFields["lname"],
        "email" => $availableFields["email"],
        "hashed_pass" => $availableFields["pass"]
    );

    // If the password is to be changed generate a new one
    if ($setFields["hashed_pass"]) {
        $setFields["hashed_pass"] = password_hash($setFields["hashed_pass"], PASSWORD_BCRYPT);
    }

    $stmt = $link->prepare("UPDATE `users` SET firstname = :firstname, lastname =:lastname, email=:email, hashed_pass=:hashed_pass WHERE id = :uid; SELECT * FROM  `users` WHERE id = :uid;");

    // Prepare the query. If we didn't get passed a value for a field, use the current one
    foreach ($setFields as $key => $val) {
        if (!$val) {
            $setFields[$key] = $res[$key];
        }

        $stmt->bindParam(":" . $key, $setFields[$key]);
    }

    // Bind the UID and send it!
    $stmt->bindParam("uid", $uid);
    $stmt->execute();

    // Generate the response token
    $data = array(
        "fname" => $setFields["firstname"], 
        "lname" => $setFields["lastname"], 
        "email" => $setFields["email"], 
        "uid" => $uid
    );

    $token = $jwt->generateToken($data);

    output("success", $data);

?>