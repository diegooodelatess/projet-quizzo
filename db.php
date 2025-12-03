<?php
$servername = "localhost";
$username = "root";
$password = "Doris10101010!";
$database = "quizzo";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}
?>
