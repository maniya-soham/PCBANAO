<?php require_once __DIR__ . '/../helpers.php';
verify_csrf();
require_login();
$pdo = db();
$name = trim($_POST['name'] ?? '');
if (!$name) {
    flash('warn', 'Name required');
    header('Location: /pcbanaop2/build_pc.php');
    exit;
}
$pdo->prepare("INSERT INTO builds (user_id,name) VALUES (?,?)")->execute([current_user()['id'], $name]);
$bid = $pdo->lastInsertId();
$ids = [];
foreach (['cpu', 'mb', 'ram', 'gpu', 'psu', 'case'] as $k) {
    $v = intval($_POST[$k] ?? 0);
    if ($v) $ids[] = $v;
}
$stmt = $pdo->prepare("INSERT INTO build_items (build_id,product_id) VALUES (?,?)");
foreach ($ids as $pid) {
    $stmt->execute([$bid, $pid]);
}
flash('msg', 'Build saved');
header('Location: /pcbanaop2/build_view.php?id=' . $bid);
