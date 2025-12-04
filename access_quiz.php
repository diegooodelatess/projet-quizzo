<?php
// access_quiz.php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");

$quiz_id = intval($_GET['quiz_id'] ?? 0);
$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $key = trim($_POST['access_key'] ?? '');
    if($key==='') $error = "Veuillez saisir la clé d'accès.";
    else {
        $stmt = $conn->prepare("SELECT id, is_active FROM quizzes WHERE access_key=? LIMIT 1");
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows===0) {
            $error = "Clé invalide.";
        } else {
            $q = $res->fetch_assoc();
            if(!$q['is_active']) { $error = "Le quiz n'est pas actif."; }
            else {
                header("Location: take_quiz.php?key=".urlencode($key));
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Accès au quiz</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
<img src="logo.png" class="logo" alt="Logo">
<h1>Accès quiz</h1>
<?php if($error) echo "<p class='error'>".htmlspecialchars($error)."</p>"; ?>
<p>Entrez la clé d'accès fournie par le propriétaire du quiz.</p>
<form method="POST">
<label>Clé d'accès :</label>
<input type="text" name="access_key" maxlength="12" required>
<button type="submit" class="btn">Accéder</button>
</form>
<p><a href="dashboard_user.php">Retour</a></p>
</div>
</body>
</html>
