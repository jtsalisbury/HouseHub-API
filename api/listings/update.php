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
    $add_price = is_numeric($data["add_price"]) ? $data["add_price"] : 0;
    $hidden = is_numeric($data["hidden"]) ? $data["hidden"] : 0;
    $pid    = $data["pid"];

    $pics   = $_FILES["file"];

    // Begin checking and ensuring each field is valid
    $num_pics = count($_FILES["file"]["name"]);
    
    if (empty($userID) || empty($title) || empty($desc) || empty($location) || empty($price) || empty($pid)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    if (!is_numeric($price) || !is_numeric($add_price) || !is_numeric($hidden)) {
        output("error", ENUMS::FIELD_TYPE_WRONG);
    }

    if ($price < 0 || $add_price < 0) {
        output("error", ENUMS::FIELD_TYPE_POSITIVE);
    }

    if ($hidden != 0 && $hidden != 1) {
        output("error", ENUMS::FIELD_TYPE_WRONG);
    }

    if ($num_pics > 0 && ($num_pics < 3 || $num_pics > 20)) {
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
    $pid    = htmlspecialchars(strip_tags($pid));

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
    /*$req = array(
        "origins" => $loc,
        "destination" => "",
        "key" => "AIzaSyDrqRMlAyg4AfgxS26_LFJVd_h2ZgXjAdA",
        "units" => "imperial"
    );*/

    // Insert the new listing
    $sql = "UPDATE listings SET title = :title, description = :desc, location = :loc, rent_price = :price, add_price = :add_price, hidden = :hidden";
    if ($num_pics > 0) {
        $sql .= ", num_pictures = :pics";
    }

    $sql .= " WHERE id = :pid";

    $link = $db->getLink();

    if (!$link) {
        output("error", ENUMS::DB_NOT_CONNECTED);
    }

    $stmt = $link->prepare($sql);

    $stmt->bindParam(":title", $title);
    $stmt->bindParam(":desc", $desc);
    $stmt->bindParam(":loc", $loc);
    $stmt->bindParam(":price", $price);
    $stmt->bindParam(":add_price", $add_p);
    $stmt->bindParam(":hidden", $hidden);
    $stmt->bindParam(":pid", $pid);
    if ($num_pics > 0) {
        $stmt->bindParam(":pics", $num_pics);
    }

    try {
        $stmt->execute();
    } catch (PDOException $e) {
        output("error", ENUMS::DUPLICATE_INSERT_TITLE);
    }

    // Grab the post id for the new listing
  
    if ($num_pics > 0) {
        $dir = "../images/" . $pid . "/";

        $objects = scandir($dir);
        foreach ($objects as $object) {
          if ($object != "." && $object != "..") {
            if (filetype($dir."/".$object) == "dir") 
               rrmdir($dir."/".$object); 
            else unlink   ($dir."/".$object);
          }
        }
        reset($objects);
        rmdir($dir);

        // Handle image uploading
        for ($i = 0; $i < $num_pics; $i++) {
            $tmpPath = $_FILES["file"]["tmp_name"][$i];
            $name = $_FILES["file"]["name"][$i];

            if ($tmpPath != "") {

                // Images are saved to /api/images/pid/i.xxx
                mkdir($dir);

                $ext = end((explode(".", $name)));

                $newPath = $dir . ($i) . "." . $ext;

                if (!move_uploaded_file($tmpPath, $newPath)) {
                    output("error", ENUMS::FILE_MOVE_ERROR);
                }
            }
        }       
    }

    $data = array(
        "pid" => $pid
    );

    $token = $jwt->generateToken($data);

    output("success", $token);
?>