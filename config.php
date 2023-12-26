<?php 
$serverName = "ftpupload.net";
$userName = "if0_35679543";
$password = "N7YI89oDZFm";
$dbname = "if0_35679543_project";

$conn = new mysqli($serverName,$userName,$password,$dbname);
if($conn->connect_error){
    die("Network connection error :".$conn->connect_error);
}
?>
