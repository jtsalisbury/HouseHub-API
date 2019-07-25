<?php

    header("Access-Control-Allow-Origin: http://u747950311.hostingerapp.com/househub/api/");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once "../core/core.php";

    $data = json_decode(file_get_contents("php://input"), true);

    $token = $data["token"];

    if (!$jwt->verifyToken($token)) {
        output("error", ENUMS::TOKEN_INVALID);
    }

    $data = json_decode($jwt->decodePayload($token), true);

    $fname = $data["fname"];
    $lname = $data["lname"];
    $email = $data["email"];
    $pass  = $data["pass"];
    $repass = $data["repass"];
    $curpass = $data["curpass"];
    $uid = $data["uid"];

    $link = $db->getLink();

    if (!$link) {
        output("error", ENUMS::DB_NOT_CONNECTED);
    }

    // Clean all fields
    $email = htmlspecialchars(strip_tags($email));
    $fname = htmlspecialchars(strip_tags($fname));
    $lname = htmlspecialchars(strip_tags($lname));
    $curpass = htmlspecialchars(strip_tags($curpass));
    $repass  = htmlspecialchars(strip_tags($repass));
    $pass  = htmlspecialchars(strip_tags($pass));
    $uid   = htmlspecialchars(strip_tags($uid));

    /*
        Check to ensure that all fields are set
    */

    if (empty($fname) || empty($lname) || empty($email) || empty($curpass) || empty($uid)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    /*
        CHeck to ensure passwords are equal
    */

    if ($pass != $repass) {
        output("error", ENUMS::UPDATE_PASS_NOT_EQUAL);
    }


    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $link->prepare($sql);
    $stmt->bindParam(":id", $uid);

    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    $realPass = $res["hashed_pass"];

    if (!password_verify($curpass, $realPass)) {
        output("error", ENUMS::PASS_NOT_EQUAL);
    }
    
    // Prepare SQL statement for insertion
    $sql = "UPDATE users SET firstname = :fname, lastname = :lname, email = :email";

    if (!empty($pass)) {
        $sql .= ", hashed_pass = :pass";
    }

    $sql .= " WHERE id = :uid";

    $stmt = $link->prepare($sql);

    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":fname", $fname);
    $stmt->bindParam(":lname", $lname);
    $stmt->bindParam(":uid", $uid);
    
    if (!empty($pass)) {
        $stmt->bindParam(":pass", password_hash($pass, PASSWORD_BCRYPT));
    }

    $stmt->execute();

    $data = array("uid" => $uid);
    $token = $jwt->generateToken($data);

    output("success", $token);
    
?>