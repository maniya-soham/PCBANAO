<?php
require_once __DIR__ . '/../helpers.php';
require_login();
require_role('seller');

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Allowed statuses (server-side validation)
    $allowed = ['placed', 'packed', 'shipped', 'cancelled'];
    if ($order_id > 0 && in_array($status, $allowed, true)) {
        // Optional: verify that this seller actually owns at least one item in the order.
        // This prevents sellers changing orders that don't include their products.
        $check = $pdo->prepare(
            "SELECT COUNT(*) AS c
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ? AND p.seller_id = ?"
        );
        $check->execute([$order_id, (int) current_user()['id']]);
        $count = (int) $check->fetch()['c'];

        if ($count > 0) {
            $u = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $u->execute([$status, $order_id]);
        }
    }
}

// Redirect back to seller dashboard (adjust path if needed)
header("Location: dashboard.php");
exit;
