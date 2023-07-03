<?php
	
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "linkdefense";

// Create connection
$connect = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$connect) {
    die("Connection failed: " . $conn->connect_error);
}

?>