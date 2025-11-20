<?php
require_once __DIR__ . '/helpers.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE id=? AND user_id=?");
$stmt->execute([$id, (int)current_user()['id']]);
$address = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$address) {
    exit("Address not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $upd = $pdo->prepare("
    UPDATE addresses
    SET label=?, recipient_name=?, phone=?, street_address=?, area=?, city=?, state=?, postal_code=?, country=?, is_default=?
    WHERE id=? AND user_id=?
  ");
    $upd->execute([
        $_POST['label'] ?? '',
        $_POST['recipient_name'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['street_address'] ?? '',
        $_POST['area'] ?? '',
        $_POST['city'] ?? '',
        $_POST['state'] ?? '',
        $_POST['postal_code'] ?? '',
        $_POST['country'] ?? 'India',
        isset($_POST['is_default']) ? 1 : 0,
        $id,
        (int)current_user()['id']
    ]);
    flash('msg', 'Address updated successfully');
    header('Location: profile.php');
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Edit Address</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    <h1>Edit Address</h1>
    <form method="post">
        <?php csrf_field(); ?>
        <label>Label</label>
        <input class="input" name="label" value="<?= htmlspecialchars($address['label']) ?>">
        <label>Recipient Name</label>
        <input class="input" name="recipient_name" value="<?= htmlspecialchars($address['recipient_name']) ?>" required>
        <label>Phone</label>
        <input class="input" name="phone" value="<?= htmlspecialchars($address['phone']) ?>" required>
        <label>Street Address</label>
        <input class="input" name="street_address" value="<?= htmlspecialchars($address['street_address']) ?>" required>
        <label>Area</label>
        <input class="input" name="area" value="<?= htmlspecialchars($address['area']) ?>">
        <label>City</label>
        <input class="input" name="city" value="<?= htmlspecialchars($address['city']) ?>" required>
        <label>State</label>
        <input class="input" name="state" value="<?= htmlspecialchars($address['state']) ?>" required>
        <label>Postal Code</label>
        <input class="input" name="postal_code" value="<?= htmlspecialchars($address['postal_code']) ?>" required>
        <label>Country</label>
        <input class="input" name="country" value="<?= htmlspecialchars($address['country']) ?>">
        <label><input type="checkbox" name="is_default" <?= $address['is_default'] ? 'checked' : '' ?>> Set as default</label>
        <button class="btn" type="submit">Update</button>
    </form>
    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>