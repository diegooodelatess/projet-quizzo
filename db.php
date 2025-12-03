<?php
$servername = "localhost";
$username = "root"; // ton utilisateur MySQL
$password = "Doris10101010!";     // ton mot de passe MySQL
$database = "quizzo"; // nom de la base

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}
?>
