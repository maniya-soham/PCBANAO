<?php
require_once __DIR__ . '/helpers.php';
require_login();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare("
    INSERT INTO addresses (user_id, label, recipient_name, phone, street_address, area, city, state, postal_code, country, is_default)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
  ");
    $stmt->execute([
        (int)current_user()['id'],
        $_POST['label'] ?? '',
        $_POST['recipient_name'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['street_address'] ?? '',
        $_POST['area'] ?? '',
        $_POST['city'] ?? '',
        $_POST['state'] ?? '',
        $_POST['postal_code'] ?? '',
        $_POST['country'] ?? 'India',
        isset($_POST['is_default']) ? 1 : 0
    ]);
    flash('msg', 'Address added successfully');
    header('Location: profile.php');
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Add Address</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <h1>Add New Address</h1>
    <form method="post">
        <?php csrf_field(); ?>
        <label>Label</label>
        <input class="input" name="label" placeholder="Home / Work">
        <label>Recipient Name</label>
        <input class="input" name="recipient_name" required>
        <label>Phone</label>
        <input class="input" name="phone" required>
        <label>Street Address</label>
        <input class="input" name="street_address" required>
        <label>Area</label>
        <input class="input" name="area">
        <label>City</label>
        <input class="input" name="city" required>
        <label>State</label>
        <input class="input" name="state" required>
        <label>Postal Code</label>
        <input class="input" name="postal_code" required>
        <label>Country</label>
        <input class="input" name="country" value="India">
        <label><input type="checkbox" name="is_default"> Set as default</label>
        <button class="btn" type="submit">Save</button>
    </form>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>