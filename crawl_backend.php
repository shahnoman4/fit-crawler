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

// Crawl Websites 
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




// Update Custom Crawl Date to avoid repeating the crawler
$source_url = $_POST['find_url'];
$id = $_POST['id'];
$custom_crawl_date = date("Y-m-d H:i:s");
mysqli_query($connect,"UPDATE links_inbound_all set custom_crawl_date='$custom_crawl_date' WHERE ID = '$id'");

$anchor_tags_final_array = loopthroughallpages($source_url);
if(isset($anchor_tags_final_array) && count($anchor_tags_final_array) > 0){

    $anchor_tags_final_array = validate_urls($anchor_tags_final_array);
    check_link_exists($anchor_tags_final_array,$connect,$source_url);
}

echo "DONE : " . $id;

?>
