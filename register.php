<?php require_once __DIR__ . '/helpers.php';
verify_csrf();
ensure_schema();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)")
    ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
  flash('msg', 'Registered! Please login.');
  header('Location: /pcbanaop2/login.php');
  exit;
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sign up</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>
  <h1>Create account</h1>
  <form method="post"><?php csrf_field(); ?>
    <label>Name</label><input class="input" name="name" required>
    <label>Email</label><input class="input" type="email" name="email" required>
    <label>Password</label><input class="input" type="password" name="password" required>
    <button class="btn">Sign up</button>
  </form>
  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>