<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "exam";   // apna database name

$conn = mysqli_connect($host,$user,$pass,$db);

if(!$conn){
    die("Database connection failed");
}
