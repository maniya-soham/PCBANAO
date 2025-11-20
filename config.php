<?php
// config.php
// Toggle between SQLite (default) and MySQL via DB_DRIVER.
// For MySQL: define env via constants and set DB_DRIVER='mysql'.
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite');
define('DB_SQLITE_PATH', __DIR__ . '/storage/app.db');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'pcbanaop2');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

date_default_timezone_set('Asia/Kolkata');
session_start();

function db()
{
    static $pdo = null;
    if ($pdo) return $pdo;

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } else {
        // Ensure the storage directory exists and is writable
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $dsn = 'sqlite:' . DB_SQLITE_PATH;
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}


function ensure_schema()
{
    $pdo = db();
    // If SQLite and file is empty, load schema
    if (DB_DRIVER === 'sqlite') {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $exists = $stmt->fetch();
        if (!$exists) {
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($sql);
            seed_sample_data($pdo);
        }
    }
}
function seed_sample_data($pdo)
{
    // minimal categories
    $cats = ['CPU', 'Motherboard', 'RAM', 'GPU', 'Storage', 'PSU', 'Case', 'Cooler', 'Monitor', 'Keyboard', 'Mouse'];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
    foreach ($cats as $c) {
        $stmt->execute([$c, strtolower(str_replace(' ', '-', $c))]);
    }
    // admin user
    $pdo->prepare("INSERT INTO users (name,email,password_hash,role,seller_status) VALUES (?,?,?,?,?)")
        ->execute(['Admin', 'admin@pcbanao.local', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'approved']);
    // demo seller
    $pdo->prepare("INSERT INTO users (name,email,password_hash,role,seller_status) VALUES (?,?,?,?,?)")
        ->execute(['Demo Seller', 'seller@pcbanao.local', password_hash('seller123', PASSWORD_DEFAULT), 'seller', 'approved']);
    $seller_id = $pdo->lastInsertId();
    // demo products (CPU, Motherboard, RAM, GPU, PSU, Case)
    $products = [
        ['Intel Core i5-12400F', 'CPU', 14500, 20, json_encode(['socket' => 'LGA1700', 'watt' => 65]), 'A great budget CPU.'],
        ['AMD Ryzen 5 5600', 'CPU', 12999, 15, json_encode(['socket' => 'AM4', 'watt' => 65]), 'Strong value CPU.'],
        ['ASUS Prime B660M-K', 'Motherboard', 9999, 10, json_encode(['socket' => 'LGA1700', 'ram_type' => 'DDR4']), 'mATX LGA1700 board.'],
        ['MSI B550-A Pro', 'Motherboard', 10499, 8, json_encode(['socket' => 'AM4', 'ram_type' => 'DDR4']), 'ATX AM4 board.'],
        ['Corsair Vengeance 16GB (2x8) 3200', 'RAM', 3999, 50, json_encode(['ram_type' => 'DDR4', 'capacity_gb' => 16]), 'Reliable DDR4 kit.'],
        ['Kingston Fury Beast 32GB (2x16) 6000', 'RAM', 9999, 40, json_encode(['ram_type' => 'DDR5', 'capacity_gb' => 32]), 'Fast DDR5 kit.'],
        ['NVIDIA RTX 4060 8GB', 'GPU', 28999, 12, json_encode(['length_mm' => 242, 'tdp' => 115]), 'Efficient 1080p/1440p GPU.'],
        ['AMD RX 6700 XT 12GB', 'GPU', 27999, 9, json_encode(['length_mm' => 267, 'tdp' => 230]), 'Strong 1440p card.'],
        ['Corsair RM650e 650W', 'PSU', 6999, 25, json_encode(['watt' => 650]), '80+ Gold PSU.'],
        ['Ant Esports ICE-200TG', 'Case', 3499, 30, json_encode(['gpu_max_len_mm' => 315]), 'ATX case with glass.']
    ];
    foreach ($products as $p) {
        // map category
        $cid = $pdo->query("SELECT id FROM categories WHERE name='" . $p[1] . "'")->fetch()['id'];
        $pdo->prepare("INSERT INTO products (seller_id,category_id,title,description,price,stock,attributes) VALUES (?,?,?,?,?,?,?)")
            ->execute([$seller_id, $cid, $p[0], $p[5], $p[2], $p[3], $p[4]]);
    }
}
ensure_schema();
