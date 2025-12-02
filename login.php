<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit();
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') die("Identifiant et mot de passe requis.");

$sql = "SELECT id, username, password, role, active FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) die("Erreur prepare: " . $conn->error);
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) die("Identifiant ou mot de passe incorrect.");
if (!$user['active']) die("Compte désactivé. Contactez l'administrateur.");
if (!password_verify($password, $user['password'])) die("Identifiant ou mot de passe incorrect.");

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

$u = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
if ($u) { $u->bind_param("i", $user['id']); $u->execute(); $u->close(); }

$conn->close();

switch ($user['role']) {
    case 'admin': header("Location: admin.php"); break;
    case 'school': header("Location: school.php"); break;
    case 'company': header("Location: company.php"); break;
    default: header("Location: user.php"); break;
}
exit();
