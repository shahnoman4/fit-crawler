<?php 
    error_reporting(E_ALL);	

    // Connect to DB
    require('config/database.php');
    $limit = 5;

    $query = "SELECT * FROM `links_inbound_all` WHERE custom_crawl_date is null ORDER BY `links_inbound_all`.`ID` DESC LIMIT 0,".$limit;

    $result = mysqli_query($connect, $query);
    $source_to_check = array();
    while ($row = mysqli_fetch_array($result)) {
        $source_to_check[] = [
            'source_url'=>$row['source_url'],
            'id'=>$row['ID'],
        ];
    }
    $source_to_json = json_encode($source_to_check);
    echo $source_to_json;
    exit;
?>