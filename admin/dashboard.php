<?php
require_once __DIR__ . '/../helpers.php';
require_login();
require_role('admin');
$pdo = db();

// ---------------- Helpers ----------------
function whitelisted_tables(): array {
    return ['users','products','categories','orders','order_items','product_images','builds','build_items','addresses'];
}
function table_allowed(string $t): bool {
    return in_array($t, whitelisted_tables(), true);
}
function write_csv_and_exit($rows,$filename){
    header('Content-Type:text/csv; charset=utf-8');
    header('Content-Disposition:attachment; filename="'.$filename.'"');
    $out=fopen('php://output','w');
    if($rows && count($rows)){
        fputcsv($out,array_keys($rows[0]));
        foreach($rows as $r) fputcsv($out,array_values($r));
    }
    fclose($out); exit;
}

$messages=[];$errors=[];

// ---------------- POST Actions ----------------
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();

    // Seller actions
    if(isset($_POST['seller_action'],$_POST['user_id'])){
        $uid=(int)$_POST['user_id'];
        $status=$_POST['seller_action']==='approve'?'approved':'rejected';
        $pdo->prepare("UPDATE users SET seller_status=? WHERE id=?")->execute([$status,$uid]);
        $messages[]="Seller #$uid set to $status";
    }

    // User role change
    if(isset($_POST['role_action'],$_POST['user_id'])){
        $uid=(int)$_POST['user_id']; $role=$_POST['role_action'];
        if(in_array($role,['user','seller','admin'])){
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
            $messages[]="User #$uid role changed to $role";
        }
    }

    // Ban / Unban
    if(isset($_POST['ban_user'])){
        $uid=(int)$_POST['ban_user'];
        $pdo->prepare("UPDATE users SET banned=1 WHERE id=?")->execute([$uid]);
        $messages[]="User #$uid banned.";
    }
    if(isset($_POST['unban_user'])){
        $uid=(int)$_POST['unban_user'];
        $pdo->prepare("UPDATE users SET banned=0 WHERE id=?")->execute([$uid]);
        $messages[]="User #$uid unbanned.";
    }

    // Order status update
    if(isset($_POST['order_id'],$_POST['status'])){
        $oid=(int)$_POST['order_id'];
        $allowed=['placed','packed','shipped','delivered','canceled'];
        $status=$_POST['status'];
        if(in_array($status,$allowed,true)){
            $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,$oid]);
            $messages[]="Order #$oid → $status";
        }
    }

    // Bulk order update
    if(isset($_POST['bulk_order_ids'],$_POST['bulk_status'])){
        $allowed=['placed','packed','shipped','delivered','canceled'];
        $status=$_POST['bulk_status'];
        if(in_array($status,$allowed,true)){
            foreach($_POST['bulk_order_ids'] as $oid){
                $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status,(int)$oid]);
            }
            $messages[]="Bulk updated orders to $status";
        }
    }

    // Delete row
    if(isset($_POST['delete_table'],$_POST['delete_id'])){
        $t=preg_replace('/[^a-z_]/','',$_POST['delete_table']);
        $id=(int)$_POST['delete_id'];
        if(table_allowed($t)){
            $pdo->prepare("DELETE FROM $t WHERE id=?")->execute([$id]);
            $messages[]="Deleted id=$id from $t";
        }
    }

    // Export CSV
    if(isset($_POST['export_table'])){
        $t=preg_replace('/[^a-z_]/','',$_POST['export_table']);
        if(table_allowed($t)){
            $rows=$pdo->query("SELECT * FROM $t ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            write_csv_and_exit($rows,"{$t}_".date('Y-m-d').".csv");
        }
    }
}

// ---------------- Stats ----------------
$total_users=$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products=$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders=$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue=$pdo->query("SELECT IFNULL(SUM(total),0) FROM orders")->fetchColumn();

// ---------------- Table viewer ----------------
$view_table=$_GET['table']??null;
$page=max(1,(int)($_GET['page']??1));
$perPage=50; $offset=($page-1)*$perPage;
$search=trim($_GET['q']??'');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <base href="/pcbanaop2/">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Panel</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<h1>Admin Panel</h1>

<?php foreach($messages as $m):?><div class="small" style="color:green"><?=htmlspecialchars($m)?></div><?php endforeach;?>
<?php foreach($errors as $e):?><div class="small" style="color:red"><?=htmlspecialchars($e)?></div><?php endforeach;?>

<div class="kpis">
  <div class="kpi"><div>Users</div><h2><?=$total_users?></h2></div>
  <div class="kpi"><div>Products</div><h2><?=$total_products?></h2></div>
  <div class="kpi"><div>Orders</div><h2><?=$total_orders?></h2></div>
  <div class="kpi"><div>Revenue</div><h2><?=money($total_revenue)?></h2></div></div>
</div>

<h2>Seller Applications</h2>
<table class="table">
<tr><th>User</th><th>Email</th><th>Status</th><th>Action</th></tr>
<?php foreach($pdo->query("SELECT * FROM users WHERE seller_status IN ('pending','approved') AND role!='admin'") as $u):?>
<tr>
  <td><?=htmlspecialchars($u['name'])?></td>
  <td><?=htmlspecialchars($u['email'])?></td>
  <td><?=htmlspecialchars($u['seller_status'])?></td>
  <td>
    <form method="post" style="display:inline"><?php csrf_field(); ?><input type="hidden" name="user_id" value="<?=$u['id']?>"><button class="btn" name="seller_action" value="approve">Approve</button></form>
    <form method="post" style="display:inline"><?php csrf_field(); ?><input type="hidden" name="user_id" value="<?=$u['id']?>"><button class="btn secondary" name="seller_action" value="reject">Reject</button></form>
  </td>
</tr>
<?php endforeach;?>
</table>

<h2>All Orders (with bulk update)</h2>
<form method="post">
<?php csrf_field(); ?>
<table class="table">
<tr><th></th><th>#</th><th>User</th><th>Date</th><th>Status</th><th>Total</th><th>Set Status</th></tr>
<?php foreach($pdo->query("SELECT o.*,u.email FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 50") as $r):?>
<tr>
  <td><input type="checkbox" name="bulk_order_ids[]" value="<?=$r['id']?>"></td>
  <td>#<?=$r['id']?></td>
  <td><?=htmlspecialchars($r['email'])?></td>
  <td><?=$r['created_at']?></td>
  <td><?=htmlspecialchars($r['status'])?></td>
  <td><?=money($r['total'])?></td>
  <td>
    <form method="post" style="display:inline"><?php csrf_field(); ?><input type="hidden" name="order_id" value="<?=$r['id']?>"><select name="status" class="input"><?php foreach(['placed','packed','shipped','delivered','canceled'] as $s):?><option value="<?=$s?>" <?=$s===$r['status']?'selected':''?>><?=$s?></option><?php endforeach;?></select><button class="btn">Update</button></form>
  </td>
</tr>
<?php endforeach;?>
</table>
<select name="bulk_status" class="input"><?php foreach(['placed','packed','shipped','delivered','canceled'] as $s):?><option value="<?=$s?>"><?=$s?></option><?php endforeach;?></select>
<button class="btn">Bulk Update</button>
</form>

<h2>Direct Table Access</h2>
<form method="get" class="row">
<select name="table" class="input"><option value="">-- Select --</option><?php foreach(whitelisted_tables() as $t):?><option value="<?=$t?>" <?=$view_table===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select>
<input class="input" name="q" placeholder="Search" value="<?=htmlspecialchars($search)?>">
<button class="btn">Open</button>
</form>

<?php if($view_table && table_allowed($view_table)):?>
<?php
$cols=array_map(fn($c)=>$c['name'],$pdo->query("PRAGMA table_info($view_table)")->fetchAll(PDO::FETCH_ASSOC));
$where='';$params=[];
if($search){$searchable=array_intersect($cols,['name','title','email','description','path','status']);if(!$searchable)$searchable=array_slice($cols,0,2);$parts=[];foreach($searchable as $c){$parts[]="$c LIKE ?";$params[]="%$search%";}$where='WHERE '.implode(' OR ',$parts);}
$count=$pdo->prepare("SELECT COUNT(*) FROM $view_table $where");$count->execute($params);$total=$count->fetchColumn();
$stmt=$pdo->prepare("SELECT * FROM $view_table $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");$stmt->execute($params);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<p class="small">Rows: <?=$total?></p>
<form method="post" style="display:inline"><?php csrf_field(); ?><input type="hidden" name="export_table" value="<?=$view_table?>"><button class="btn">Export CSV</button></form>
<table class="table"><tr><?php foreach($cols as $c):?><th><?=$c?></th><?php endforeach;?><th>Actions</th></tr>
<?php foreach($rows as $r):?><tr><?php foreach($cols as $c):?><td><?=htmlspecialchars((string)$r[$c])?></td><?php endforeach;?><td>
<?php if($view_table==='products'):?><a class="btn" href="seller/product_edit.php?id=<?=$r['id']?>">Edit</a><?php endif;?>
<form method="post" style="display:inline" onsubmit="return confirm('Delete row?')"><?php csrf_field(); ?><input type="hidden" name="delete_table" value="<?=$view_table?>"><input type="hidden" name="delete_id" value="<?=$r['id']?>"><button class="btn secondary">Delete</button></form>
</td></tr><?php endforeach;?></table>
<?php endif;?>

<?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
