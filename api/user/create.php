<?php

    header("Access-Control-Allow-Origin: http://u747950311.hostingerapp.com/househub/api/");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once "../core/core.php";

    $data = json_decode(file_get_contents("php://input"));

    $token = $data->token;

    if (!$jwt->verifyToken($token)) {
        $status["status"] = "error";
        $status["message"] = ENUMS::TOKEN_INVALID;

        die(json_encode($status));
    }

    $data = json_decode($token->decodePayload($token));

    $fname = $data["fname"];
    $lname = $data["lname"];
    $email = $data["email"];
    $pass  = $data["pass"];
    $repass = $data["repass"];

    $status = array();

    /*
        Check to ensure that all fields are set
    */

    if (empty($fname) || empty($lname) || empty($email) || empty($pass) || empty($repass)) {
        $status["status"] = "error";
        $status["message"] = ENUMS::FIELD_NOT_SET;

        die(json_encode($status));
    }

    /*
        CHeck to ensure passwords are equal
    */

    if ($pass != $repass) {
        $status["status"] = "error";
        $status["message"] = ENUMS::PASS_NOT_EQUAL;

        die(json_encode($status));
    }

    $link = $db->getLink();

    if (!$link) {
        $status["status"] = "error";
        $status["message"] = ENUMS::DB_NOT_CONNECTED;

        die(json_encode($status));
    }

    // Clean all fields
    $email = htmlspecialchars(strip_tags($email));
    $fname = htmlspecialchars(strip_tags($fname));
    $lname = htmlspecialchars(strip_tags($lname));
    $pass  = password_hash(htmlspecialchars(strip_tags($pass)), PASSWORD_BCRYPT);

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO users (firstname, lastname, email, hashed_pass) VALUES (:fname, :lname, :email, :pass)";
    $stmt = $link->prepare($sql);

    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":fname", $fname);
    $stmt->bindParam(":lname", $lname);
    $stmt->bindParam(":pass", $pass);

    // Try to insert the user. Send a message that the user exists if there's a problem.
    try {

        $stmt->execute();

    } catch (PDOException $e) {

        $status["status"] = "error";
        $status["message"] = ENUMS::INSERT_USER_EXISTS;

        die(json_encode($status));
    }

    // Grab the user ID based on the entry just submitted
    $stmt = $link->prepare("SELECT ID from users WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stmt->rowCount() == 1) {

        // Return the user's information including the ID in an encrypted token  
        $data = array(
            "fname" => $fname, 
            "lname" => $lname, 
            "email" => $email, 
            "uid" => $result["ID"]
        );

        $token = generateToken($data);

        $status["status"] = "success";
        $status["message"] = $token;

        die(json_encode($status));
    }

    // Die for any other reason
    $status["status"] = "error";
    $status["message"] = ENUMS::FAILED_NEW_USER;

    die(json_encode($status));
?>