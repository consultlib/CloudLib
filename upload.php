<?php
session_start();
	require("backend/config.php");
if(!isset($_SESSION['userid'])) {
die("Access denied. You must login first.");
}
if(isset($_FILES['uploadedfile']['name'])) {
$target_path = 'files/'.$_SESSION['userid'].'1';

$target_path = $target_path . basename( $_FILES['uploadedfile']['name']); 

if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
   $link = mysql_connect($db_host, $db_username, $db_password) or die('Could not connect: ' . mysql_error());
   mysql_select_db($db_name) or die('Could not select database');
	$uid = $_SESSION['userid'];
	$name = $_FILES['uploadedfile']['name'];
	$dir = $_POST['uploadedir'];
	$location = $uid.'1'.$name;
	$query = "INSERT INTO `${db_prefix}filesystem` (userid, file, directory, location, sharing) VALUES('${uid}', '${name}', '${dir}', '${location}', 'none');";
	mysql_select_db($db_name) or die('Could not select database');
	$result = mysql_query($query) or die('Query failed: ' . mysql_error());
	echo "The file ".  basename( $_FILES['uploadedfile']['name']). 
    " has been uploaded to your Psych Desktop :)<br> Upload another file? <p>";
} else{
    echo "There was an error uploading the file, please try again! Remember: there are size limitations.";
}
}
?>
<form enctype="multipart/form-data" action="upload.php" method="POST">
<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
Choose a file to upload: <input name="uploadedfile" type="file" /><br />
Psych Desktop Location: <input name="uploadedir" type="text" value="/" /><br />
<input type="submit" value="Upload File to Psych Desktop" />
</form>