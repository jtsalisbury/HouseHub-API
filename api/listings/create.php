<?php
    include_once "../core/core.php";

    $token = $_POST["token"];

    if (!$jwt->verifyToken($token)) {
        output("error", ENUMS::TOKEN_INVALID);
    }

    // Grab our fields
    $data = json_decode($jwt->decodePayload($token), true);

    $userID = $data["uid"];
    $title  = $data["title"];
    $desc   = $data["desc"];
    $location = $data["location"];
    $price  = $data["rent_price"];
    $add_price = $data["add_price"] or 0;
    $hidden = $data["hidden"] or 0;

    $pics   = $_FILES["file"];

    // Begin checking and ensuring each field is valid
    $num_pics = count($_FILES["file"]["name"]);
    
    if (empty($userID) || empty($title) || empty($desc) || empty($location) || empty($price) || $num_pics == 0) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    if (!is_numeric($price) || !is_numeric($add_price) || !is_numeric($hidden) || !is_numeric($num_pics)) {
        output("error", ENUMS::FIELD_TYPE_WRONG);
    }

    if ($price < 0 || $add_price < 0) {
        output("error", ENUMS::FIELD_TYPE_POSITIVE);
    }

    if ($hidden != 0 && $hidden != 1) {
        output("error", ENUMS::FIELD_TYPE_WRONG);
    }

    if ($num_pics < 3 || $num_pics > 20) {
        output("error", ENUMS::INVALID_NUM_IMAGES);
    }

    // Ensure pictures are actually pictures and meeting our size guidelines
    $types = array('image/jpeg', 'image/jpg', 'image/png');
    for ($i = 0; $i < $num_pics; $i++) {
        $path = $_FILES["file"]["tmp_name"][$i];
        $size = $_FILES["file"]["size"][$i];
        $type = $_FILES['file']['type'][$i];

        if (!exif_imagetype($path) or !in_array($type, $types)) {
            output("error", ENUMS::INVALID_FILE_TYPE);
        }

        if ($size > 2000000) {
            output("error", ENUMS::IMAGE_TOO_LARGE);
        }
    }

    // Sanitize the inputs
    $userID = htmlspecialchars(strip_tags($userID));
    $title  = htmlspecialchars(strip_tags($title));
    $desc   = htmlspecialchars(strip_tags($desc));
    $loc    = htmlspecialchars(strip_tags($location));
    $price  = htmlspecialchars(strip_tags($price));
    $add_p  = htmlspecialchars(strip_tags($add_price));
    $hidden = htmlspecialchars(strip_tags($hidden));

    // Insert the new listing
    $sql = "INSERT INTO listings (title, description, location, rent_price, add_price, creator_uid, hidden, num_pictures, first_img_extension) VALUES (:title, :desc, :loc, :price, :add_price, :creator, :hidden, :pics, :ext)";

    $link = $db->getLink();

    $stmt = $link->prepare($sql);


    $name = $_FILES["file"]["name"][0];
    $extension = end((explode(".", $name)));

    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":desc", $desc);
    $stmt->bindParam(":loc", $loc);
    $stmt->bindParam(":price", $price);
    $stmt->bindParam(":add_price", $add_p);
    $stmt->bindParam(":creator", $userID);
    $stmt->bindParam(":hidden", $hidden);
    $stmt->bindParam(":pics", $num_pics);
    $stmt->bindParam(":ext", $extension);

    try {
        $stmt->execute();
    } catch (PDOException $e) {
        output("error", ENUMS::DUPLICATE_INSERT_TITLE);
    }

    // Grab the post id for the new listing
    $sql = "SELECT id FROM listings WHERE title = :title AND description = :desc AND creator_uid = :uid";

    $stmt = $link->prepare($sql);
    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":desc", $desc);
    $stmt->bindParam(":uid", $userID);

    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Assuming we are good, we should return the pid and move all the files to the correct location
    if ($stmt->rowCount() == 1) {

        $pid = $result["id"]

        // Handle image uploading
        for ($i = 0; $i < $num_pics; $i++) {
            $tmpPath = $_FILES["file"]["tmp_name"][$i];
            $name = $_FILES["file"]["name"][$i];

            if ($tmpPath != "") {

                // Images are saved to /api/images/pid/i.xxx
                mkdir("../images/" . $pid . "/");

                $ext = end((explode(".", $name)));

                $newPath = "../images/" . $pid . "/" . ($i) . "." . $ext;

                if (!move_uploaded_file($tmpPath, $newPath)) {
                    output("error", ENUMS::FILE_MOVE_ERROR);
                }
            }
        }

        // Return the user's information including the ID in an encrypted token  
        $data = array(
            "pid" => $pid
        );

        $token = $jwt->generateToken($data);

        output("success", $token);
    }

    // Die for any other reason
    output("error", ENUMS::GENERAL_INSERT_ERROR);
?>