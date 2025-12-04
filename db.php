<?php
// db.php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$servername = "localhost";
$username = "root";
$password = "Doris10101010!";
$database = "quizzeo";


$conn = new mysqli($servername, $username, $password, $database);
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}
?>
