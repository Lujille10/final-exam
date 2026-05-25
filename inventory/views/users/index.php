<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /index.php"); exit(); }
require_once __DIR__ . '/../../public/database.config.php';
require_once __DIR__ . '/../../controllers/account.php';

$conn = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

$conn = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

$cols = $conn->query("SHOW COLUMNS FROM accounts LIKE 'role'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE accounts ADD COLUMN role VARCHAR(50) DEFAULT 'Staff' AFTER username");
}
$cols = $conn->query("SHOW COLUMNS FROM accounts LIKE 'email'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE accounts ADD COLUMN email VARCHAR(150) DEFAULT '' AFTER role");
}
$cols = $conn->query("SHOW COLUMNS FROM accounts LIKE 'full_name'");
if ($cols->num_rows === 0) {
    $conn->query("ALTER TABLE accounts ADD COLUMN full_name VARCHAR(150) DEFAULT '' AFTER email");
}
$conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, username VARCHAR(100), action VARCHAR(50),
    action_type VARCHAR(20), details TEXT, ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function logActivity($conn, $userId, $username, $action, $actionType, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, action_type, details, ip) VALUES (?,?,?,?,?,?)");
    if ($stmt) { $stmt->bind_param("isssss", $userId, $username, $action, $actionType, $details, $ip); $stmt->execute(); $stmt->close(); }
}

$message = $errors = '';

// ── ADD USER ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_user') {
    $fullName = trim($_POST['full_name'] ?? '');
    $un       = trim($_POST['username']  ?? '');
    $em       = trim($_POST['email']     ?? '');
    $role     = trim($_POST['role']      ?? 'Staff');
    $pass     = trim($_POST['password']  ?? '');

    if (empty($fullName) || empty($un) || empty($pass)) {
        $errors = "Full name, username, and password are required.";
    } elseif (strlen($pass) < 6) {
        $errors = "Password must be at least 6 characters.";
    } else {
        // Check username uniqueness
        $chk = $conn->prepare("SELECT id FROM accounts WHERE username = ?");
        $chk->bind_param("s", $un);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors = "Username \"$un\" is already taken.";
            $chk->close();
        } else {
            $chk->close();
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO accounts (username, password, role, email, full_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $un, $hashed, $role, $em, $fullName);
            if ($stmt->execute()) {
                $message = "User \"$fullName\" added successfully as $role!";
                logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
                    'Added', 'add', "Added new user \"$un\" with role $role");
            } else {
                $errors = "Failed to add user.";
            }
            $stmt->close();
        }
    }
}

// ── DELETE USER ───────────────────────────────────────────
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $delId = (int)$_GET['delete_user'];
    if ($delId === (int)$_SESSION['user_id']) {
        $errors = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM accounts WHERE id = ?");
        $stmt->bind_param("i", $delId);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $message = "User deleted successfully.";
            logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
                'Deleted', 'delete', "Deleted user ID $delId");
        } else {
            $errors = "Failed to delete user.";
        }
    }
}

// ── EDIT USER ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit_user') {
    $id       = (int)trim($_POST['user_id']   ?? 0);
    $un       = trim($_POST['username']        ?? '');
    $fullName = trim($_POST['full_name']       ?? '');
    $em       = trim($_POST['email']           ?? '');
    $role     = trim($_POST['role']            ?? 'Staff');
    $pass     = trim($_POST['password']        ?? '');

    if (empty($un)) {
        $errors = "Username is required.";
    } else {
        if (!empty($pass)) {
            if (strlen($pass) < 6) {
                $errors = "Password must be at least 6 characters.";
            } else {
                $hashed = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE accounts SET username=?, password=?, role=?, email=?, full_name=? WHERE id=?");
                $stmt->bind_param("sssssi", $un, $hashed, $role, $em, $fullName, $id);
                $result = $stmt->execute();
                $stmt->close();
                if ($result) {
                    if ($id === (int)$_SESSION['user_id']) $_SESSION['username'] = $un;
                    $message = "User updated successfully.";
                    logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
                        'Edited', 'edit', "Updated user \"$un\"");
                } else {
                    $errors = "Failed to update user.";
                }
            }
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET username=?, role=?, email=?, full_name=? WHERE id=?");
            $stmt->bind_param("ssssi", $un, $role, $em, $fullName, $id);
            $result = $stmt->execute();
            $stmt->close();
            if ($result) {
                if ($id === (int)$_SESSION['user_id']) $_SESSION['username'] = $un;
                $message = "User updated successfully.";
                logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
                    'Edited', 'edit', "Updated user \"$un\"");
            } else {
                $errors = "Failed to update user.";
            }
        }
    }
}

// ── FETCH USERS ───────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$users  = [];
$sql    = "SELECT id, username, role, email, full_name, created_at FROM accounts";
if ($search) {
    $sql .= " WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($sql . " ORDER BY created_at ASC");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql . " ORDER BY created_at ASC");
}
while ($row = $res->fetch_assoc()) { $users[] = $row; }

$roleColors = ['Administrator'=>'badge-blue','Staff'=>'badge-green','Viewer'=>'badge-gray'];
$avatars    = ['👤','👥','🧑','👩','🧑‍💼'];

// Find user being edited
$editUser = null;
if (isset($_GET['edit_user']) && is_numeric($_GET['edit_user'])) {
    foreach ($users as $u) {
        if ((int)$u['id'] === (int)$_GET['edit_user']) { $editUser = $u; break; }
    }
}
$conn->close();
?>
<?php require '../partial/header.php'; ?>
<div class="hero-banner" style="min-height:110px;padding:1.4rem 2.5rem 2.8rem;">
  <div class="hero-text"><h1>Users</h1><p>Manage system access and user accounts.</p></div>
  <div class="hero-creature" style="font-size:4rem;">👥</div>
  <div class="hero-wave">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 30" preserveAspectRatio="none" style="height:30px;">
      <path fill="rgba(6,15,30,0.95)" d="M0,15 C200,30 400,0 600,15 C800,30 1000,0 1200,15 L1200,30 L0,30 Z"/>
    </svg>
  </div>
</div>

<div class="page-body">
  <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
  <?php if ($errors):  ?><div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div><?php endif; ?>

  <div class="page-header">
    <div>
      <h1>Users</h1>
      <div class="breadcrumb"><a href="/board/index.php">Dashboard</a> / Users</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('add-user-modal').style.display='flex'">+ Add User</button>
  </div>

  <div class="card mb-2" style="padding:1rem 1.2rem;">
    <form method="GET" class="filter-bar">
      <div class="search-input-wrap" style="max-width:340px;">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Search</button>
      <?php if ($search): ?><a href="?" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>#</th><th>Full Name</th><th>Username</th><th>Email</th>
            <th>Role</th><th>Date Created</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; foreach ($users as $u): ?>
          <tr style="animation:cardReveal 0.5s <?= ($i-1)*0.07 ?>s both;">
            <td style="color:var(--text-muted)"><?= $i ?></td>
            <td>
              <span class="equip-icon"><?= $avatars[($i-1) % count($avatars)] ?></span>
              <strong><?= htmlspecialchars($u['full_name'] ?: $u['username']) ?></strong>
              <?php if ((int)$u['id'] === (int)$_SESSION['user_id']): ?>
                <span class="badge badge-blue" style="margin-left:0.4rem;font-size:0.6rem;">You</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--text-dim)"><?= htmlspecialchars($u['username']) ?></td>
            <td style="color:var(--text-dim)"><?= htmlspecialchars($u['email'] ?: '—') ?></td>
            <td>
              <span class="badge <?= $roleColors[$u['role']] ?? 'badge-gray' ?>">
                <?= htmlspecialchars($u['role'] ?: 'Staff') ?>
              </span>
            </td>
            <td style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars($u['created_at']) ?></td>
            <td>
              <div class="action-btns">
                <button class="btn btn-warning btn-sm"
                  onclick="openEditUser(<?= $u['id'] ?>,'<?= htmlspecialchars(addslashes($u['username'])) ?>','<?= htmlspecialchars(addslashes($u['full_name'] ?? '')) ?>','<?= htmlspecialchars(addslashes($u['email'] ?? '')) ?>','<?= htmlspecialchars(addslashes($u['role'] ?? 'Staff')) ?>')">✏️</button>
                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                <a href="?delete_user=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete user \'<?= htmlspecialchars(addslashes($u['username'])) ?>\'?')">🗑</a>
                <?php else: ?>
                <button class="btn btn-danger btn-sm" disabled style="opacity:0.3;cursor:not-allowed;" title="Cannot delete your own account">🗑</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php $i++; endforeach; ?>
          <?php if (empty($users)): ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <span class="page-info">Showing 1 to <?= count($users) ?> of <?= count($users) ?> entries</span>
      <span class="page-btn active">1</span>
    </div>
  </div>
</div>

<!-- ADD USER MODAL -->
<div id="add-user-modal" style="display:none;position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);align-items:center;justify-content:center;">
  <div class="form-card" style="width:100%;max-width:500px;animation:cardReveal .4s both;">
    <div class="form-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div><h1>Add New User</h1><div class="breadcrumb">System access account</div></div>
      <button onclick="document.getElementById('add-user-modal').style.display='none'" style="background:none;border:none;color:var(--text-dim);font-size:1.3rem;cursor:pointer;">✕</button>
    </div>
    <form method="POST" class="form-card-body">
      <input type="hidden" name="form_action" value="add_user">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="full_name" class="form-control" placeholder="e.g. Juan Dela Cruz" required>
        </div>
        <div class="form-group">
          <label>Username *</label>
          <input type="text" name="username" class="form-control" placeholder="e.g. juandc" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="juan@wearetheoceans.com">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role</label>
          <select name="role" class="form-control">
            <option value="Administrator">Administrator</option>
            <option value="Staff" selected>Staff</option>
            <option value="Viewer">Viewer</option>
          </select>
        </div>
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
      </div>
      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-user-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save User</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT USER MODAL -->
<div id="edit-user-modal" style="display:none;position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);align-items:center;justify-content:center;">
  <div class="form-card" style="width:100%;max-width:500px;animation:cardReveal .4s both;">
    <div class="form-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div><h1>Edit User</h1><div class="breadcrumb">Update account details</div></div>
      <button onclick="document.getElementById('edit-user-modal').style.display='none'" style="background:none;border:none;color:var(--text-dim);font-size:1.3rem;cursor:pointer;">✕</button>
    </div>
    <form method="POST" class="form-card-body">
      <input type="hidden" name="form_action" value="edit_user">
      <input type="hidden" name="user_id" id="edit_user_id">
      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="full_name" id="edit_full_name" class="form-control" placeholder="Full name">
        </div>
        <div class="form-group">
          <label>Username *</label>
          <input type="text" name="username" id="edit_username" class="form-control" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" id="edit_email" class="form-control">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="edit_role" class="form-control">
            <option value="Administrator">Administrator</option>
            <option value="Staff">Staff</option>
            <option value="Viewer">Viewer</option>
          </select>
        </div>
        <div class="form-group">
          <label>New Password <small style="color:var(--text-muted);font-weight:400;">(leave blank to keep)</small></label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 characters">
        </div>
      </div>
      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-user-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-warning">💾 Update User</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditUser(id, username, fullName, email, role) {
  document.getElementById('edit_user_id').value   = id;
  document.getElementById('edit_username').value  = username;
  document.getElementById('edit_full_name').value = fullName;
  document.getElementById('edit_email').value     = email;
  document.getElementById('edit_role').value      = role;
  document.getElementById('edit-user-modal').style.display = 'flex';
}
</script>
<?php require '../partial/footer.php'; ?>