<?php
set_time_limit(0);
include 'DbWrapper.php';
include_once("../APi/api.php");

$startAttId = 1024;
$endAttId = 1400;
$PROX_USE = 0;


function get_tiny_url($url)  {  
	$ch = curl_init();  
	$timeout = 5;  
	curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);  
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);  
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);  
	$data = curl_exec($ch);  
	curl_close($ch);  
	return $data;  
}


function insert_attributes($id) {
	// connect to DB 
	$dbWrapper = new DbWrapper();
	//$getUrlQuery = 'SELECT `FacebookPhotoId`, `PhotoLink` FROM `Photos` WHERE `Id` = '. $id;
	$getUrlQuery = "SELECT `PhotoLink` FROM `Photos` AS a
					WHERE NOT EXISTS(SELECT *
					FROM NoProfilePic AS b WHERE a.FacebookPhotoId = b.FakePhotoId)
					AND `Id` = $id";
	// run Query
	echo "$getUrlQuery <br>";
	$result = $dbWrapper->execute($getUrlQuery);

	if ($result->num_rows == 0) {
		//no profile pic
		return;
	}
	GLOBAL $PROX_USE;
	$row = ($result->fetch_assoc());
	$picUrl = $row['PhotoLink']; // extracted link

	$picUrl = get_tiny_url($picUrl);
	echo "pic url: ".$picUrl. "<br>";
	chdir('../APi/');

	// run in betaface
	$api = new betaFaceApi($id);
	$face = $api->get_Image_attributes($picUrl,$PROX_USE);
	echo $api->image_Attributes;
	$setIsValidPhoto = 0;

	if($face != -1) {
		// face found
		echo "face found!!!! <br>";
		$setIsValidPhoto = 1;
		$dbWrapper->insert($api->image_Attributes);
	}
	if($face != 0){
		$updateQuery = "UPDATE `Photos` SET `IsValidPhoto` = $setIsValidPhoto WHERE `Id` = $id";
		echo "$updateQuery <br><br>";
		$result = $dbWrapper->execute($updateQuery);
	}
	return;
} 

function insert_att_all_photo() {

	GLOBAL $startAttId;
	GLOBAL $endAttId;
	for ($i = $startAttId; $i <= $endAttId; $i++) {
		ob_start();

		echo "id : $i <br>";
		insert_attributes($i);
		// flush all output
		ob_end_flush();
		flush();
			 
			// close current session
		if (session_id()) session_write_close();
	}

} 
//$num = $argv[1];
//$num = 638;
//echo $num."\n";
insert_att_all_photo();
//insert_att_all_photo($num);
?> 
