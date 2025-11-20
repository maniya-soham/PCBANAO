<?php require_once __DIR__ . '/../helpers.php';
require_login();
require_role('admin');
$pdo = db();
$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
if ($id && in_array($action, ['approve', 'reject'])) {
  if ($action === 'approve') {
    $pdo->prepare("UPDATE users SET role='seller', seller_status='approved' WHERE id=?")->execute([$id]);
  } else {
    $pdo->prepare("UPDATE users SET seller_status='rejected' WHERE id=?")->execute([$id]);
  }
}
header('Location: /pcbanaop2/admin/dashboard.php');
