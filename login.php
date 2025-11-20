<?php require_once __DIR__ . '/helpers.php';
verify_csrf();
ensure_schema();

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass = $_POST['password'] ?? '';
  $role_choice = $_POST['role_choice'] ?? 'user'; // user or seller
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if ($u && password_verify($pass, $u['password_hash'])) {
    if ($role_choice === 'seller' && $u['role'] !== 'seller') {
      flash('warn', 'Not a seller yet. Apply below.');
    } else {
      $_SESSION['user'] = $u;
      header('Location: /pcbanaop2/index.php');
      exit;
    }
  } else {
    flash('warn', 'Invalid credentials');
  }
}
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">

  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
  <?php include __DIR__ . '/partials/header.php'; ?>
  <h1>Login</h1>
  <form method="post">
    <?php csrf_field(); ?>
    <label>Email</label>
    <input class="input" name="email" required>
    <label>Password</label>
    <input class="input" type="password" name="password" required>
    <label>Login as</label>
    <select name="role_choice" class="input">
      <option value="user">User</option>
      <option value="seller">Seller</option>
    </select>
    <button class="btn">Login</button>
  </form>

  <h2>Apply to be a Seller</h2>
  <form method="post" action="pages/apply_seller.php">
    <?php csrf_field(); ?>
    <label>Email (must match your user account)</label>
    <input class="input" name="email" required>
    <button class="btn">Apply for Seller</button>
  </form>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>