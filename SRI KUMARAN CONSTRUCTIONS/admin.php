<?php
// admin.php - minimal admin panel
// place in same folder

// ---------- CONFIG ----------
$admin_password = "admin123"; // <-- change this
$saveFile = __DIR__ . "/submissions.json";
// ---------- END CONFIG ------

session_start();

// simple login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged'] = true;
    } else {
        $error = "Incorrect password.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    // show login form
    ?>
    <!doctype html><html><head><meta charset="utf-8"><title>Admin Login</title></head><body style="font-family:Arial,sans-serif;padding:30px;">
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post">
      <input type="password" name="password" placeholder="Password" style="padding:8px;width:200px;">
      <button type="submit" style="padding:8px;">Login</button>
    </form>
    </body></html>
    <?php
    exit;
}

// load submissions
$subs = [];
if (file_exists($saveFile)) {
    $subs = json_decode(file_get_contents($saveFile), true);
    if (!is_array($subs)) $subs = [];
}

// handle approve/reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action']; // approve or reject
    foreach ($subs as &$s) {
        if ($s['id'] === $id) {
            if ($action === 'approve') $s['status'] = 'approved';
            if ($action === 'reject') $s['status'] = 'rejected';
            $s['reviewed_at'] = date('c');
            break;
        }
    }
    file_put_contents($saveFile, json_encode($subs, JSON_PRETTY_PRINT));
    header("Location: admin.php");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin — Submissions</title>
  <style>
    body{font-family:Arial,sans-serif;padding:20px;background:#f7f7f7}
    .card{background:white;padding:15px;border-radius:8px;margin-bottom:12px;box-shadow:0 2px 6px rgba(0,0,0,0.05)}
    .meta{font-size:0.9rem;color:#666}
    .actions a{margin-right:10px;}
    .status-pending{color:#ff9900}
    .status-approved{color:#16a34a}
    .status-rejected{color:#ef4444}
    .logout{float:right}
  </style>
</head>
<body>
  <h2>Submissions</h2>
  <a href="admin.php?logout=1" class="logout">Logout</a>
  <p>Pending / Approved / Rejected</p>

  <?php if (empty($subs)): ?>
    <p>No submissions yet.</p>
  <?php else: ?>
    <?php foreach ($subs as $s): ?>
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <strong><?php echo htmlspecialchars($s['name']); ?></strong>
            <div class="meta"><?php echo htmlspecialchars($s['email']); ?> • <?php echo htmlspecialchars($s['created_at']); ?></div>
          </div>
          <div>
            <span class="meta <?php echo 'status-'.$s['status']; ?>"><?php echo ucfirst($s['status']); ?></span>
          </div>
        </div>
        <p style="margin-top:12px"><?php echo nl2br(htmlspecialchars($s['message'])); ?></p>
        <div class="actions">
          <?php if ($s['status'] === 'pending'): ?>
            <a href="admin.php?action=approve&id=<?php echo urlencode($s['id']); ?>">Approve</a>
            <a href="admin.php?action=reject&id=<?php echo urlencode($s['id']); ?>">Reject</a>
          <?php else: ?>
            <small>Reviewed at: <?php echo isset($s['reviewed_at']) ? htmlspecialchars($s['reviewed_at']) : '-'; ?></small>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
