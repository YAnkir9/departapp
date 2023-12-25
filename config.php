<?php 
$serverName = "localhost";
$userName = "root";
$password = "";
$dbname = "project1";

$conn = new mysqli($serverName,$userName,$password,$dbname);
if($conn->connect_error){
    die("Network connection error :".$conn->connect_error);
}
?>