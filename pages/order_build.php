<?php require_once __DIR__ . '/../helpers.php';
verify_csrf();
require_login();
$pdo = db();
$ids = [];
foreach (['cpu', 'mb', 'ram', 'gpu', 'psu', 'case'] as $k) {
    $v = intval($_POST[$k] ?? 0);
    if ($v) $ids[] = $v;
}
if (!$ids) {
    flash('warn', 'No items to order');
    header('Location: /pcbanaop2/build_pc.php');
    exit;
}
$in = implode(',', array_map('intval', $ids));
$items = $pdo->query("SELECT * FROM products WHERE id IN ($in)")->fetchAll();
$total = array_sum(array_map(fn($p) => $p['price'], $items));
$addr = trim($_POST['addr'] ?? '');
$pdo->prepare("INSERT INTO orders (user_id,status,total,shipping_address) VALUES (?,?,?,?)")
    ->execute([current_user()['id'], 'placed', $total, $addr]);
$oid = $pdo->lastInsertId();
$stmt = $pdo->prepare("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,1,?)");
foreach ($items as $p) {
    $stmt->execute([$oid, $p['id'], $p['price']]);
}
flash('msg', 'Order placed!');
header('Location: /pcbanaop2/order.php?id=' . $oid);
