<?php
    header("Access-Control-Allow-Origin: http://u747950311.hostingerapp.com/househub/api/");
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

    $viewSaved = $data["saved"];

    //$search_criteria = $data["search_criteria"];
    $price_min = $data["price_min"];
    $price_max = $data["price_max"];

    // Initially escape the fields
    $postID = htmlspecialchars(strip_tags($postID));
    $userID = htmlspecialchars(strip_tags($userID));
    //$search_criteria = "%" . htmlspecialchars(strip_tags($search_criteria)) . "%";
    $price_min = htmlspecialchars(strip_tags($price_min));
    $price_max = htmlspecialchars(strip_tags($price_max));

    // Calculate which listings we are on
    $lcount = 20;
    $lpage  = empty($data["page"]) ? 1 : $data["page"];

    if ($lpage < 0) {
        $lpage = 1;
    }

    $startFrom = ($lpage - 1) * $lcount;

    // Begin selecting all non-hidden listings
    $sql = "SELECT * FROM listings WHERE listings.hidden = 0 ";

    if (!empty($viewSaved) && empty($userID)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    // Listing matching to passed fields
    if (!empty($postID)) {
        $sql .= "WHERE listings.id = :id";

    } elseif (!empty($viewSaved) && !empty($userID)) {
        $sql = "SELECT * FROM saved_listings 
                LEFT JOIN listings ON saved_listings.post_id = listings.id 
                WHERE saved_listings.user_id = :id";

    } elseif (!empty($userID)) {
        $sql .= "WHERE creator_uid = :id";

    }

    // Apply possible filters
    if (!empty($search_criteria)) {
        //$sql .= " AND (listings.title LIKE ':search' OR listings.description LIKE ':search' OR listings.location LIKE ':search')";
    }
    if (!empty($price_min)) {
        $sql .= " AND listings.rent_price >= :p_min";
    }
    if (!empty($price_max)) {
        $sql .= " AND listings.rent_price <= :p_max";
    }

    // Finish constructing the SQL statement
    $sql_page_results = $sql . " LIMIT $startFrom, $lcount";

    $link = $db->getLink();
    $stmt = $link->prepare($sql_page_results);

    // Bind all parameters
    if (!empty($postID)) {
        $stmt->bindParam(":id", $postID);
    }
    if (!empty($userID)) {
        $stmt->bindParam(":id", $userID);
    }
    if (!empty($search_criteria)) {
        //$stmt->bindParam(":search", $search_criteria);
    }
    if (!empty($price_min)) {
        $stmt->bindParam(":p_min", $price_min);
    }

    if (!empty($price_max)) {
        $stmt->bindParam(":p_max", $price_max);
    }

    // Grab all the listings and compile them to an array
    // Also add listing counts, current page, etc
    $stmt->execute();

    $res = $stmt->fetchAll();
    $listing_count = $stmt->rowCount();

    $data = array("page" => $lpage, "total_pages" => 0, "listing_count" => $listing_count, "max_listing_count" => $lcount, "listings" => array());
    foreach ($res as $row) {

        $info = array(
            "pid" => $row["id"],
            "title" => $row["title"],
            "desc" => $row["description"],
            "loc" => $row["location"],
            "base_price" => $row["rent_price"],
            "add_price" => $row["add_price"],
            "creator_uid" => $row["creator_uid"],
            "num_pictures" => $row["num_pictures"]
        );

        array_push($data["listings"], $info);
    }

    $stmt = $link->prepare($sql);

    // Re-create the original statement without the limiting queries
    if (!empty($postID)) {
        $stmt->bindParam(":id", $postID);
    }

    if (!empty($userID)) {
        $stmt->bindParam(":id", $userID);
    }
    if (!empty($search_criteria)) {
        //$stmt->bindParam(":search", $search_criteria);
    }
    if (!empty($price_min)) {
        $stmt->bindParam(":p_min", $price_min);
    }

    if (!empty($price_max)) {
        $stmt->bindParam(":p_max", $price_max);
    }
    $stmt->execute();
    
    // Using the results, calculate the total pages
    $total_records = $stmt->rowCount();
    $total_pages = ceil($total_records / $lcount);

    $data["total_pages"] = $total_pages;

    // Generate the token and output the result
    $token = $jwt->generateToken($data);
    output("success", $token);
?>