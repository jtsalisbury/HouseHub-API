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

    $viewSaved = $data["saved"];

    $search_criteria = $data["search_criteria"];
    $price_min = $data["price_min"];
    $price_max = $data["price_max"];

    $showHidden = $data["show_hidden"];
    $requesterid = $data["requesterid"];

    if ($showHidden === 'true') {
        $showHidden = " LIKE '%'";
    } else {
        $showHidden = " = 0";
    }

    // Initially escape the fields
    $postID = htmlspecialchars(strip_tags($postID));
    $userID = htmlspecialchars(strip_tags($userID));
    $search_criteria = htmlspecialchars(strip_tags($search_criteria));
    $price_min = htmlspecialchars(strip_tags($price_min));
    $price_max = htmlspecialchars(strip_tags($price_max));
    $requesterid = htmlspecialchars(strip_tags($requesterid));

    if (empty($requesterid)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    // Calculate which listings we are on
    $lcount = 20;
    $lpage  = empty($data["page"]) ? 1 : $data["page"];

    if ($lpage < 0) {
        $lpage = 1;
    }

    $startFrom = ($lpage - 1) * $lcount;

    // Begin selecting all non-hidden listings
    $sql = "SELECT listings.*, users.firstname, users.lastname, IF(s.user_id IS NULL, 0, 1) AS saved FROM listings 
            LEFT JOIN users ON listings.creator_uid = users.id 
            LEFT JOIN saved_listings s ON (s.post_id = listings.id AND s.user_id = :reqid)
            WHERE listings.hidden" . $showHidden . " ";

    if ($viewSaved === 'true' && empty($userID)) {
        output("error", ENUMS::FIELD_NOT_SET);
    }

    // Listing matching to passed fields
    if (!empty($postID)) {
        $sql .= "AND listings.id = :id";

    } elseif ($viewSaved === 'true' && !empty($userID)) {

        $sql = "SELECT listings.*, users.firstname, users.lastname FROM saved_listings 
                LEFT JOIN listings ON saved_listings.post_id = listings.id
                LEFT JOIN users ON saved_listings.user_id = users.id
                WHERE saved_listings.user_id = :id";

    } elseif (!empty($userID)) {
        $sql .= "AND creator_uid = :id";
    }

    // Apply possible filters
    if (!empty($search_criteria)) {
        $sql .= " AND (listings.title LIKE :search_t OR listings.description LIKE :search_d OR listings.location LIKE :search_l)";
    }
    if (!empty($price_min)) {
        $sql .= " AND listings.rent_price >= :p_min";
    }
    if (!empty($price_max)) {
        $sql .= " AND listings.rent_price <= :p_max";
    }

    $sql .= " ORDER BY listings.id DESC";

    // Finish constructing the SQL statement
    $sql_page_results = $sql . " LIMIT $startFrom, $lcount";

    $link = $db->getLink();

    if (!$link) {
        output("error", ENUMS::DB_NOT_CONNECTED);
    }

    $stmt = $link->prepare($sql_page_results);

    // Bind all parameters
    if (!empty($postID)) {
        $stmt->bindParam(":id", $postID);
    }
    if (!empty($userID)) {
        $stmt->bindParam(":id", $userID);
    }
    if (!empty($search_criteria)) {
        $s = "%" . $search_criteria . "%";

        $stmt->bindParam(":search_t", $s);
        $stmt->bindParam(":search_d", $s);
        $stmt->bindParam(":search_l", $s);
    }
    if (!empty($price_min)) {
        $stmt->bindParam(":p_min", $price_min);
    }
    if (!empty($price_max)) {
        $stmt->bindParam(":p_max", $price_max);
    }
    if ($viewSaved !== 'true') {
        $stmt->bindParam(":reqid", $requesterid);
    }

    // Grab all the listings and compile them to an array
    // Also add listing counts, current page, etc
    $stmt->execute();

    $res = $stmt->fetchAll();
    $listing_count = $stmt->rowCount();

    $data = array("page" => $lpage, "total_listings" => 0, "total_pages" => 0, "listing_count" => $listing_count, "max_listing_count" => $lcount, "listings" => array());
    foreach ($res as $row) {

        $images = array();
        foreach (glob("../images/" . $row["id"] . "/*.*") as $file) {
            array_push($images, end((explode("/", $file))));
        }  

        $info = array(
            "pid" => $row["id"],
            "title" => $row["title"],
            "desc" => $row["description"],
            "loc" => $row["location"],
            "base_price" => $row["rent_price"],
            "add_price" => $row["add_price"],
            "creator_uid" => $row["creator_uid"],
            "num_pictures" => $row["num_pictures"],
            "created" => $row["created_date"],
            "modified" => $row["last_modified"],
            "creator_fname" => $row["firstname"],
            "creator_lname" => $row["lastname"],
            "hidden" => $row["hidden"],
            "saved" => $row["saved"],
            "images" => $images
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
        $s = "%" . $search_criteria . "%";

        $stmt->bindParam(":search_t", $s);
        $stmt->bindParam(":search_d", $s);
        $stmt->bindParam(":search_l", $s);
    }
    if (!empty($price_min)) {
        $stmt->bindParam(":p_min", $price_min);
    }
    if (!empty($price_max)) {
        $stmt->bindParam(":p_max", $price_max);
    }
    if ($viewSaved !== 'true') {
        $stmt->bindParam(":reqid", $requesterid);
    }

    $stmt->execute();
    
    // Using the results, calculate the total pages
    $total_records = $stmt->rowCount();
    $total_pages = ceil($total_records / $lcount);

    $data["total_pages"] = $total_pages;
    $data["total_listings"] = $total_records;

    // Generate the token and output the result
    $token = $jwt->generateToken($data);
    output("success", $token);
?>