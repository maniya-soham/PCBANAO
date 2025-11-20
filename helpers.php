<?php
// helpers.php
require_once __DIR__ . '/config.php';

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_login()
{
    if (!current_user()) {
        header('Location: /pcbanaop2/login.php');
        exit;
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field()
{
    $t = htmlspecialchars(csrf_token());
    echo "<input type='hidden' name='csrf' value='{$t}'>";
}
function verify_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || $_POST['csrf'] !== ($_SESSION['csrf'] ?? '')) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }
}

function is_admin()
{
    return current_user() && current_user()['role'] === 'admin';
}
function is_seller()
{
    return current_user() && current_user()['role'] === 'seller';
}

function flash($key, $msg = null)
{
    if ($msg === null) {
        $m = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $m;
    } else {
        $_SESSION['flash'][$key] = $msg;
    }
}

function handle_upload($input, $destDir = '/uploads')
{
    if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($_FILES[$input]['name'], PATHINFO_EXTENSION);
    $safe = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    $dir = __DIR__ . $destDir;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $path = $dir . '/' . $safe;
    move_uploaded_file($_FILES[$input]['tmp_name'], $path);
    return $destDir . '/' . $safe;
}

function money($p)
{
    return '₹' . number_format($p);
}

function product_image($pdo, $product_id)
{
    $img = $pdo->prepare("SELECT path FROM product_images WHERE product_id=? ORDER BY id LIMIT 1");
    $img->execute([$product_id]);
    $row = $img->fetch();
    if ($row) {
        return "/pcbanaop2" . $row['path'];
    }
    return "/pcbanaop2/assets/placeholder.png";
}


function require_role($role)
{
    if (!current_user() || current_user()['role'] !== $role) {
        http_response_code(403);
        die('Forbidden');
    }
}
