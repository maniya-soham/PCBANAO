<?php require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');
$pdo = db();
$id = intval($_GET['id'] ?? 0);
$pdo->prepare("DELETE FROM products WHERE id=? AND seller_id=?")->execute([$id, current_user()['id']]);
flash('msg', 'Deleted');
header('Location: /pcbanaop2/seller/dashboard.php');
