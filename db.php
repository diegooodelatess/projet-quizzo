<?php
// db.php - centralise la connexion
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "Doris10101010!";
$DB_NAME = "quizzeo";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Erreur connexion BDD: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
