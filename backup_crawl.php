<?php
	
error_reporting(E_ALL);	

// Connect to DB
require('config/database.php');

// example of how to use basic selector to retrieve HTML contents
require('simple_html_dom.php');

function unique_key($array,$keyname){

 $new_array = array();
 foreach($array as $key=>$value){

   $ignore_list = ['','#','#0','/'];
   if(!isset($new_array[$value[$keyname]]) && !in_array($value[$keyname], $ignore_list)){
     $new_array[$value[$keyname]] = $value;
   }

 }
 $new_array = array_values($new_array);
 return $new_array;
}

$anchor_tags_final_array = array(); 

function loopthroughallpages($url){
	// get DOM from URL or file
	$html = file_get_html($url);

	$anchor_tags = array(); 
	// find all link
	foreach($html->find('a') as $e){ 

		$anchor_tags[] = [
					'href' => $e->href,
					'rel' => $e->rel,
					'innertext' => $e->innertext,
		];
	}

	return unique_key($anchor_tags,'href');
}

function check_404($url) {
   $headers=get_headers($url);
   if ($headers[0]!='HTTP/1.1 200 OK'){
   		return true;
   } else{
   		return false;
   } 
}

/* GABRIEL-
	1. Query the links_inbound_all table to get the source_url of the next record
	select source_url from links_inbound_all where custom_crawl_date is null order by ID desc
	result of that query should be the page_url variable below.
	*/


// I Just Queried 1 Records.
$limit = 5;

$query = "SELECT * FROM `links_inbound_all` WHERE custom_crawl_date is null ORDER BY `links_inbound_all`.`ID` DESC LIMIT 0,".$limit;

$result = mysqli_query($connect, $query);
$source_to_check = array();
while ($row = mysqli_fetch_array($result)) {
    $source_url = $row['source_url'];

    // Update Custom Crawl Date to avoid repeating the crawler
    $custom_crawl_date = date("Y-m-d H:i:s");
	$id = $row['ID'];
	mysqli_query($connect,"UPDATE links_inbound_all set custom_crawl_date='$custom_crawl_date' WHERE ID = '$id'");


    if(!check_404($source_url)){
    	
    	$source_to_check[] = $source_url;
    }else{
    	echo $source_url . " :: Not Found 404 <br>";

    }
    
}
// Page URL should pull the most recent item from the database
if(count($source_to_check) > 0){
	$page_url = $source_to_check[0];
}else{
	echo "ALL the selected Links are down";
	exit;
}

// $page_url = 'https://buenavid.com/';

$anchor_tags_final_array = loopthroughallpages($page_url);

$visited_urls = array();
foreach ($anchor_tags_final_array as $key => $value) {
	if (filter_var($value['href'], FILTER_VALIDATE_URL)) {
		if(!in_array($value['href'], $visited_urls)){
			$visited_urls[] = $value['href'];
			$response = loopthroughallpages($value['href']);
			$anchor_tags_final_array = array_merge($anchor_tags_final_array,$response);
		}
	}	
}

$anchor_tags_final_array = unique_key($anchor_tags_final_array,'href');

// print_r($anchor_tags_final_array);

foreach ($anchor_tags_final_array as $anchor) {
	
	// Log the links on this page that contain Fit Small Business
	
	if ( strpos($anchor['href'], '://fitsmallbusiness.com') !==false ) {
		
		$data = array(
			'href'=>$anchor['href'],
			'rel'=>$anchor['rel'],
			'innertext'=>$anchor['innertext'],
			'source'=>$page_url,
		);
		
		// print_r($data);
		$source = $page_url;
		$rel = $anchor['rel'];
		$innertext = $anchor['innertext'];
		$href = $anchor['href'];

		$query1 = mysqli_query($connect,"SELECT * FROM crawls WHERE source='$source' and rel='$rel' and innertext='$innertext' and href='$href'");
		if(mysqli_num_rows($query1)>0){
			echo "Already Exists";

		}else{
			$query = mysqli_query($connect,"INSERT INTO crawls(`source`,`rel`,`href`,`innertext`) values('$source','$rel','$href','$innertext')");
			
		}
		/*
			GABRIEL -
			1. Check to see if a record matching all four items above (href, rel, innertext, source) exists already. 
			2. If not, add this record to the database.
		*/
		
	}
	
}

/*
	GABRIEL -
	1. Update the record in links_inbound_all with the current timestamp showing that you have checked this link
	custom_crawl_date = date("Y-m-d H:i:s")
	
	2. Figure out how to handle errors
*/

/*
$find_url = 'https://buenavid.com/patient/register';
$key = array_search($find_url, array_column($anchor_tags_final_array, 'href'));

if($key){

	$anchor_tags_final_array[$key]['status'] = http_response_code();
 	print_r($anchor_tags_final_array[$key]);

}else{

	$data['error'] = "Not Found";
	$data['status'] = 404;
 	print_r($data);
 }
*/
?>
