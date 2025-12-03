<?php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");

$user_id = $_SESSION['user_id'];
$error='';

$stmt=$conn->prepare("SELECT email,password FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res=$stmt->get_result();
$user=$res->fetch_assoc();
$stmt->close();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['email']);
    $password=trim($_POST['password']);
    if($email!=''){
        $hash = $password ? password_hash($password,PASSWORD_DEFAULT) : $user['password'];
        $stmt=$conn->prepare("UPDATE users SET email=?,password=? WHERE id=?");
        $stmt->bind_param("ssi",$email,$hash,$user_id);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard_user.php");
        exit();
    } else $error="Email obligatoire.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Profil</title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">

</head>
<body>
<div class="container">
<img src="logo.png" class="logo">
<h1>Modifier profil</h1>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
<label>Email :</label>
<input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
<label>Nouveau mot de passe :</label>
<input type="password" name="password">
<button type="submit">Modifier</button>
</form>
<p><a href="dashboard_user.php">Retour</a></p>
</div>
</body>
</html>
