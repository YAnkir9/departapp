<?php 
$serverName = "";
$userName = "root";
$password = "";
$dbname = "project";

$conn = new mysqli($serverName,$userName,$password,$dbname);
if($conn->connect_error){
    die("Network connection error :".$conn->connect_error);
}
?>
