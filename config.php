<?php 
$serverName = "ftpupload.net";
$userName = "if0_35594114";
$password = "RpfIgQE9sj2A";
$dbname = "if0_35594114_spasproject";

$conn = new mysqli($serverName,$userName,$password,$dbname);
if($conn->connect_error){
    die("Network connection error :".$conn->connect_error);
}
?>
