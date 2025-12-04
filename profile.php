<?php
// profile.php
session_start();
require_once 'db.php';
if($_SESSION['role']!=='user') header("Location: login.php");

$user_id = $_SESSION['user_id'];
$error = '';
$stmt = $conn->prepare("SELECT email FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$res=$stmt->get_result();
$user=$res->fetch_assoc();
$stmt->close();

if($_SERVER['REQUEST_METHOD']==='POST'){
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if($email!=''){
        // check unique email except current
        $check = $conn->prepare("SELECT id FROM users WHERE email=? AND id<>?");
        $check->bind_param("si",$email,$user_id);
        $check->execute();
        $check->store_result();
        if($check->num_rows>0){
            $error = "Email déjà utilisé.";
        } else {
            $hash = $password?password_hash($password,PASSWORD_DEFAULT):null;
            if($hash){
                $stmt=$conn->prepare("UPDATE users SET email=?,password=? WHERE id=?");
                $stmt->bind_param("ssi",$email,$hash,$user_id);
            } else {
                $stmt=$conn->prepare("UPDATE users SET email=? WHERE id=?");
                $stmt->bind_param("si",$email,$user_id);
            }
            $stmt->execute();
            $stmt->close();
            header("Location: dashboard_user.php");
            exit();
        }
        $check->close();
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
<img src="logo.png" class="logo" alt="Logo">
<h1>Modifier profil</h1>
<?php if($error) echo "<p class='error'>".htmlspecialchars($error)."</p>"; ?>
<form method="POST">
<label>Email :</label>
<input type="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>
<label>Nouveau mot de passe :</label>
<input type="password" name="password" placeholder="Laisser vide pour conserver">
<button type="submit" class="btn">Modifier</button>
</form>
<p><a href="dashboard_user.php">Retour</a></p>
</div>
</body>
</html>
