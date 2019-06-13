<?php

    header("Access-Control-Allow-Origin: http://localhost/rest-api-authentication-example/");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once "../core/database.php";
    include_once "../core/core.php";

    $ENUMS = new ENUMS();

    $data = json_decode(file_get_contents("php://input"));

    $fname = $data->fname;
    $lname = $data->lname;
    $email = $data->email;
    $pass  = $data->pass;
    $repass = $data->repass;

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

    if ($pass != $respass) {
        $status["status"] = "error";
        $status["message"] = ENUMS::PASS_NOT_EQUAL;

        die(json_encode($status));
    }

    $link = new Database();

    if (!$link) {
        $status["status"] = "error";
        $status["message"] = ENUMS::DB_NOT_CONNECTED;

        die(json_encode($status));
    }



    // ensure user doesn't exist
    // passowrd hash
    // return status ok

    $email = htmlspecialchars(strip_tags($email));
    $fname = htmlspecialchars(strip_tags($fname));
    $lname = htmlspecialchars(strip_tags($lname));
    $pass  = password_hash(htmlspecialchars(strip_tags($pass)), PASSWORD_BCRYPT);

    $sql = "INSERT INTO users (firstname, lastname, email, hashed_pass) VALUES (:fname, :lname, :email, :pass) OUTPUT Inserted.id";
    $stmt = $link->prepare($sql);

    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":fname", $fname);
    $stmt->bindParam(":lname", $lname);
    $stmt->bindParam(":pass", $pass);

    if ($stmt->execute()) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $status["status"] = "success";

        if ($result->rowCount() == 1) {
            foreach ($result as $row) {
                $status["message"] = array("fname" => $fname, "lname" => $lname, "email" => $email, "uid" => $row["id"]);
            }
        }

        die(json_decode($status));
    }

    $status["status"] = "error";
    $status["message"] = ENUMS::FAILED_NEW_USER;


?>