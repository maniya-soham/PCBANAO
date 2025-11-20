<?php require_once __DIR__ . '/../helpers.php';
verify_csrf();
$pdo = db();
$email = trim($_POST['email'] ?? '');
$stmt = $pdo->prepare("UPDATE users SET seller_status='pending' WHERE email=? AND role!='admin'");
$stmt->execute([$email]);
flash('msg', 'Applied! Admin will review and approve.');
header('Location: /pcbanaop2/login.php');
