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
	$html = @file_get_html($url);
	if($html){

		$anchor_tags = array(); 
		// find all link
		foreach($html->find('a') as $e){ 
	
			$anchor_tags[] = [
						'href' => $e->href,
						'rel' => $e->rel,
						'innertext' => $e->innertext,
			];
		}
	}else{
		$anchor_tags = array(); 
	}

	return unique_key($anchor_tags,'href');
}

function check_404($url) {
   $headers = get_headers($url);
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

	$anchor_tags_final_array = loopthroughallpages($source_url);
	if(isset($anchor_tags_final_array) && count($anchor_tags_final_array) > 0){

		$anchor_tags_final_array = validate_urls($anchor_tags_final_array);
		check_link_exists($anchor_tags_final_array,$connect,$source_url);
	}
	
    
}
echo "DONE";

// validate if url is correct or not
function validate_urls($anchor_tags_final_array){
	$valide_urls = array();
	foreach ($anchor_tags_final_array as $key => $value) {
		if (filter_var($value['href'], FILTER_VALIDATE_URL)) {
			$valide_urls[] = $value;
		}
	}	
	return $valide_urls;
}

/*
	GABRIEL -
	1. Check to see if page url found in the page or not
	2. Check to see if a record matching all four items above (href, rel, innertext, source) exists already. 
	3. If not, add this record to the database.
*/

function check_link_exists($anchor_tags_final_array,$connect,$page_url){
	foreach ($anchor_tags_final_array as $anchor) {
	
		// Log the links on this page that contain Fit Small Business
		
		if ( strpos($anchor['href'], '://fitsmallbusiness.com') !==false ) {
		
			$source = $page_url;
			$rel = $anchor['rel'];
			$innertext = $anchor['innertext'];
			$href = $anchor['href'];
	
			$query1 = mysqli_query($connect,"SELECT * FROM crawls WHERE source='$source' and rel='$rel' and innertext='$innertext' and href='$href'");
			if(mysqli_num_rows($query1)>0){
				echo "Already Exists <br>";
	
			}else{
				mysqli_query($connect,"INSERT INTO crawls(`source`,`rel`,`href`,`innertext`) values('$source','$rel','$href','$innertext')");
				
			}
			
			echo "Found in : ".$page_url.'<br>';
			
		}
		
	}
	
}

/*
	GABRIEL -
	1. Update the record in links_inbound_all with the current timestamp showing that you have checked this link
	custom_crawl_date = date("Y-m-d H:i:s")
	
	2. Figure out how to handle errors
*/

?>
