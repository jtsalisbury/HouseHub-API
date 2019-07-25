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

    // Verify we are receiving an unmodified token from a genuine source
    if (!$jwt->verifyToken($token)) {
        output("error", ENUMS::TOKEN_INVALID);
    }

    // Decode the payload and grab our parameters
    $data = json_decode($jwt->decodePayload($token), true);

    $email = $data["email"];
    $pass  = $data["pass"];


    if (empty($email) || empty($pass)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    $link = $db->getLink();

    if (!$link) {
        output("error", ENUMS::DB_NOT_CONNECTED);
    }

    // Sanitize our strings
    $email = htmlspecialchars(strip_tags($email));
    $pass  = htmlspecialchars(strip_tags($pass));

    $stmt = $link->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    // Test to ensure we have a user with this email
    if ($stmt->rowCount() == 1) {
        $hashedPass = $res["hashed_pass"];

        // Compare the passwords
        if (password_verify($pass, $hashedPass)) {
            $data = array(
                "fname" => $res["firstname"], 
                "lname" => $res["lastname"], 
                "email" => $res["email"],
                "admin" => $res["admin"],
                "created" => $res["created"], 
                "uid" => $res["id"]
            );

            // Return a token with the specified information of the user
            $token = $jwt->generateToken($data);

            output("success", $token);
        }

        // If the passwords don't match, this is where we will find out
        output("error", ENUMS::PASS_NOT_EQUAL);
    } 

    // User didn't exist!
    output("error", ENUMS::USER_NOT_EXIST);
?>