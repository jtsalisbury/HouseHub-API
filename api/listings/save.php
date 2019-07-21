<?php
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once "../core/core.php";

    // Grab the token
    $data = json_decode(file_get_contents("php://input"), true);

    $token = $data["token"];

    // Verify the token
    if (!$jwt->verifyToken($token)) {
        output("error", ENUMS::TOKEN_INVALID);
    }

    $data = json_decode($jwt->decodePayload($token), true);

    // Grab possible fields
    $postID = $data["pid"];
    $userID = $data["uid"];

    $postID = htmlspecialchars(strip_tags($postID));
    $userID = htmlspecialchars(strip_tags($userID));

    if (empty($postID) || empty($userID)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    $link = $db->getLink();

    if (!$link) {
        output("error", ENUMS::DB_NOT_CONNECTED);
    }

    $sql = "SELECT * FROM saved_listings WHERE user_id = :uid AND post_id = :pid";
    $stmt = $link->prepare($sql);

    $stmt->bindParam(":uid", $userID);
    $stmt->bindParam(":pid", $postID);

    $stmt->execute();

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the result already exsits, we should delete it
    if ($stmt->rowCount() == 1) {
        $sql = "DELETE FROM saved_listings WHERE user_id = :uid AND post_id = :pid";
        $stmt = $link->prepare($sql);

        $stmt->bindParam(":uid", $userID);
        $stmt->bindParam(":pid", $postID);

        $stmt->execute();

        $data = array("pid" => $postID, "uid" => $userID, "action" => "unsaved");
        $token = $jwt->generateToken($data);

        output("success", $token);
    } 

    // Entry doesn't exist, so save it
    $sql = "INSERT INTO saved_listings (user_id, post_id) VALUES (:uid, :pid)";
    $stmt = $link->prepare($sql);

    $stmt->bindParam(":uid", $userID);
    $stmt->bindParam(":pid", $postID);

    $stmt->execute();

    $data = array("pid" => $postID, "uid" => $userID, "action" => "saved");
    $token = $jwt->generateToken($data);

    output("success", $token);

?>  