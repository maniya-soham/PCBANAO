<?php
require_once __DIR__ . '/../helpers.php';
$pdo = db(); // starts session via config.php

// Always return JSON for POST, simple redirect for GET fallback
function respond_json($arr) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept JSON body {product_id, qty}
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    $id = isset($body['product_id']) ? (int)$body['product_id'] : 0;
    $qty = isset($body['qty']) ? (int)$body['qty'] : 1;
    if ($qty < 1) $qty = 1;

    if ($id <= 0) {
        respond_json(['ok' => false, 'message' => 'Invalid product id']);
    }

    // Optional: check product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        respond_json(['ok' => false, 'message' => 'Product not found']);
    }

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;

    respond_json(['ok' => true, 'message' => 'Added to cart. View Orders to checkout.']);
    // no further code after respond_json()
} else {
    // GET fallback: /pcbanaop2/pages/cart_add.php?product_id=1&qty=1
    $id  = isset($_GET['product_id']) ? (int)$_GET['product_id'] : (int)($_GET['id'] ?? 0);
    $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
    if ($qty < 1) $qty = 1;

    if ($id > 0) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
        flash('msg','Added to cart.');
        // IMPORTANT: include the subfolder in redirect on XAMPP
        header('Location: /pcbanaop2/orders.php');
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo "Invalid request";
}
