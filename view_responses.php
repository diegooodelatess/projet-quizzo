<?php
// view_responses.php
session_start();
require_once 'db.php';
if(!in_array($_SESSION['role'],['school','company'])) header("Location: login.php");
if(!isset($_GET['quiz_id'])) header("Location: dashboard_owner.php");

$quiz_id = intval($_GET['quiz_id']);
$owner_id = $_SESSION['user_id'];

// verify ownership
$stmt = $conn->prepare("SELECT title FROM quizzes WHERE id=? AND owner_id=?");
$stmt->bind_param("ii",$quiz_id,$owner_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows===0){
    header("Location: dashboard_owner.php");
    exit();
}
$quiz = $res->fetch_assoc();
$stmt->close();

// get questions
$qstmt = $conn->prepare("SELECT id, question_text FROM questions WHERE quiz_id=?");
$qstmt->bind_param("i",$quiz_id);
$qstmt->execute();
$qres = $qstmt->get_result();
$questions = [];
while($r = $qres->fetch_assoc()) $questions[$r['id']] = $r['question_text'];
$qstmt->close();

// get responses grouped by user
$res = $conn->prepare("SELECT r.user_id,u.email,r.question_id,r.answer,r.score FROM responses r JOIN users u ON r.user_id=u.id WHERE r.quiz_id=? ORDER BY u.email,r.question_id");
$res->bind_param("i",$quiz_id);
$res->execute();
$rres = $res->get_result();

$users = [];
while($row = $rres->fetch_assoc()){
    $uid = $row['user_id'];
    if(!isset($users[$uid])) $users[$uid] = ['email'=>$row['email'],'scores'=>[],'answers'=>[]];
    $users[$uid]['answers'][$row['question_id']] = $row['answer'];
    $users[$uid]['scores'][$row['question_id']] = $row['score'];
}
$res->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Réponses - <?=htmlspecialchars($quiz['title'])?></title>
<link rel="stylesheet" href="style.css?v=<?=time()?>">
</head>
<body>
<div class="container">
<img src="logo.png" class="logo" alt="Logo">
<h1>Réponses - <?=htmlspecialchars($quiz['title'])?></h1>

<?php if(empty($users)): ?>
<p>Aucune réponse pour ce quiz.</p>
<?php else: ?>
<table>
<tr>
<th>Utilisateur</th>
<?php foreach($questions as $qid=>$qtext): ?><th><?=htmlspecialchars($qtext)?></th><?php endforeach; ?><th>Total</th></tr>
<?php foreach($users as $u): ?>
<tr>
<td><?=htmlspecialchars($u['email'])?></td>
<?php $total=0; foreach($questions as $qid=>$qtext):
    $score = $u['scores'][$qid] ?? 0;
    $answer = $u['answers'][$qid] ?? '-';
    $total += $score;
?>
<td><?=htmlspecialchars($answer)?> (<?=$score?>)</td>
<?php endforeach; ?>
<td><?=$total?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<p><a class="btn-ghost" href="dashboard_owner.php">Retour</a></p>
</div>
</body>
</html>
