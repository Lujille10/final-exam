<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /index.php"); exit(); }
require_once __DIR__ . '/../../public/database.config.php';

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
$message = $errors = '';
$activeTab = $_GET['tab'] ?? 'general';

// ── SAVE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($activeTab === 'profile') {
        $newUsername  = trim($_POST['username']  ?? '');
        $newFullName  = trim($_POST['full_name'] ?? '');
        $newEmail     = trim($_POST['email']     ?? '');
        $newPass      = trim($_POST['new_pass']  ?? '');
        $confirmPass  = trim($_POST['confirm_pass'] ?? '');

        if (empty($newUsername)) {
            $errors = "Username is required.";
        } elseif (!empty($newPass) && $newPass !== $confirmPass) {
            $errors = "New passwords do not match.";
        } elseif (!empty($newPass) && strlen($newPass) < 6) {
            $errors = "Password must be at least 6 characters.";
        } else {
            if (!empty($newPass)) {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE accounts SET username=?, password=?, full_name=?, email=? WHERE id=?");
                $stmt->bind_param("ssssi", $newUsername, $hashed, $newFullName, $newEmail, $_SESSION['user_id']);
            } else {
                $stmt = $conn->prepare("UPDATE accounts SET username=?, full_name=?, email=? WHERE id=?");
                $stmt->bind_param("sssi", $newUsername, $newFullName, $newEmail, $_SESSION['user_id']);
            }
            if ($stmt->execute()) {
                $_SESSION['username'] = $newUsername;
                $message = "Profile updated successfully!";
            } else {
                $errors = "Failed to update profile.";
            }
            $stmt->close();
        }

    } elseif ($activeTab === 'security') {
        $currentPass = trim($_POST['current_pass'] ?? '');
        $newPass     = trim($_POST['new_pass']     ?? '');
        $confirmPass = trim($_POST['confirm_pass'] ?? '');

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $errors = "All password fields are required.";
        } elseif ($newPass !== $confirmPass) {
            $errors = "New passwords do not match.";
        } elseif (strlen($newPass) < 6) {
            $errors = "New password must be at least 6 characters.";
        } else {
            $stmt = $conn->prepare("SELECT password FROM accounts WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row && password_verify($currentPass, $row['password'])) {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt2  = $conn->prepare("UPDATE accounts SET password=? WHERE id=?");
                $stmt2->bind_param("si", $hashed, $_SESSION['user_id']);
                $message = $stmt2->execute() ? "Password changed successfully!" : "Failed to change password.";
                $stmt2->close();
            } else {
                $errors = "Current password is incorrect.";
            }
        }

    } elseif ($activeTab === 'general') {
        $_SESSION['settings_sys_name']    = trim($_POST['sys_name']    ?? '');
        $_SESSION['settings_sys_email']   = trim($_POST['sys_email']   ?? '');
        $_SESSION['settings_low_stock']   = (int)($_POST['low_stock']  ?? 5);
        $message = "General settings saved successfully!";

    } elseif ($activeTab === 'system') {
        $_SESSION['settings_per_page']    = (int)($_POST['per_page']   ?? 10);
        $_SESSION['settings_logging']     = isset($_POST['logging'])     ? 1 : 0;
        $_SESSION['settings_maintenance'] = isset($_POST['maintenance']) ? 1 : 0;
        $message = "System settings saved successfully!";
    }
}

// Load current user data for profile tab
$currentUser = [];
$stmt = $conn->prepare("SELECT username, full_name, email FROM accounts WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();
$conn->close();

// Load saved session values
$sysName     = $_SESSION['settings_sys_name']    ?? 'We Are The Oceans Inventory System';
$sysEmail    = $_SESSION['settings_sys_email']   ?? 'info@wearetheoceans.com';
$lowStock    = $_SESSION['settings_low_stock']   ?? 5;
$perPage     = $_SESSION['settings_per_page']    ?? 10;
$logging     = $_SESSION['settings_logging']     ?? 1;
$maintenance = $_SESSION['settings_maintenance'] ?? 0;
?>
<?php require '../partial/header.php'; ?>
<div class="hero-banner" style="min-height:110px;padding:1.4rem 2.5rem 2.8rem;">
  <div class="hero-text"><h1>Settings</h1><p>Configure your inventory system preferences.</p></div>
  <div class="hero-creature" style="font-size:4rem;">⚙️</div>
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
      <h1>Settings</h1>
      <div class="breadcrumb"><a href="/views/dashboard/index.php">Dashboard</a> / Settings</div>
    </div>
  </div>

  <div class="settings-tabs">
    <?php foreach (['general'=>'General','profile'=>'Profile','security'=>'Security','system'=>'System'] as $key => $label): ?>
    <a href="?tab=<?= $key ?>" class="settings-tab <?= $activeTab === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <form method="POST" action="?tab=<?= htmlspecialchars($activeTab) ?>" enctype="multipart/form-data">
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.2rem;align-items:start;">

      <!-- Left panel -->
      <div class="form-card">
        <div class="form-card-header">
          <h1><?= ucfirst($activeTab) ?> Settings</h1>
          <div class="breadcrumb">Manage your <?= $activeTab ?> preferences</div>
        </div>
        <div class="form-card-body">

          <?php if ($activeTab === 'general'): ?>
          <div class="form-group">
            <label>System Name</label>
            <input type="text" name="sys_name" class="form-control" value="<?= htmlspecialchars($sysName) ?>">
          </div>
          <div class="form-group">
            <label>System Email</label>
            <input type="email" name="sys_email" class="form-control" value="<?= htmlspecialchars($sysEmail) ?>">
          </div>
          <div class="form-group">
            <label>Default Timezone</label>
            <select name="timezone" class="form-control">
              <option selected>(GMT+08:00) Asia/Manila</option>
              <option>(GMT+00:00) UTC</option>
              <option>(GMT-05:00) America/New_York</option>
              <option>(GMT+09:00) Asia/Tokyo</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date Format</label>
            <select name="date_format" class="form-control">
              <option selected>May 15, 2024 (MM DD, YYYY)</option>
              <option>15/05/2024 (DD/MM/YYYY)</option>
              <option>2024-05-15 (YYYY-MM-DD)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Low Stock Threshold</label>
            <input type="number" name="low_stock" class="form-control" value="<?= (int)$lowStock ?>" min="1" max="100">
            <small style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;display:block;">Items at or below this quantity are flagged as low stock.</small>
          </div>

          <?php elseif ($activeTab === 'profile'): ?>
          <div class="form-row">
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>" placeholder="Your full name">
            </div>
            <div class="form-group">
              <label>Username *</label>
              <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" placeholder="your@email.com">
          </div>
          <div class="form-group">
            <label>New Password <small style="color:var(--text-muted);font-weight:400;">(leave blank to keep current)</small></label>
            <input type="password" name="new_pass" class="form-control" placeholder="Enter new password">
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_pass" class="form-control" placeholder="Repeat new password">
          </div>

          <?php elseif ($activeTab === 'security'): ?>
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_pass" class="form-control" placeholder="Enter current password">
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_pass" class="form-control" placeholder="Min. 6 characters">
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_pass" class="form-control" placeholder="Repeat new password">
          </div>
          <div class="form-group">
            <label>Two-Factor Authentication</label>
            <div style="display:flex;align-items:center;gap:.75rem;margin-top:.3rem;">
              <label class="toggle-switch"><input type="checkbox" name="2fa"><span class="toggle-slider"></span></label>
              <span style="font-size:.8rem;color:var(--text-dim);">Enable 2FA for extra security</span>
            </div>
          </div>

          <?php elseif ($activeTab === 'system'): ?>
          <div class="form-group">
            <label>Items Per Page</label>
            <select name="per_page" class="form-control">
              <?php foreach ([5,10,25,50] as $n): ?>
              <option <?= $perPage == $n ? 'selected' : '' ?>><?= $n ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Enable Activity Logging</label>
            <div style="display:flex;align-items:center;gap:.75rem;margin-top:.3rem;">
              <label class="toggle-switch"><input type="checkbox" name="logging" <?= $logging ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
              <span style="font-size:.8rem;color:var(--text-dim);">Log all user actions to Activity Logs</span>
            </div>
          </div>
          <div class="form-group">
            <label>Maintenance Mode</label>
            <div style="display:flex;align-items:center;gap:.75rem;margin-top:.3rem;">
              <label class="toggle-switch"><input type="checkbox" name="maintenance" <?= $maintenance ? 'checked' : '' ?>><span class="toggle-slider"></span></label>
              <span style="font-size:.8rem;color:var(--text-dim);">Disable access for non-admin users</span>
            </div>
          </div>
          <div class="form-group">
            <label>Database Backup</label>
            <a href="#" class="btn btn-secondary btn-sm" style="display:inline-flex;margin-top:.3rem;">⬇️ Export Backup</a>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <!-- Right panel: logo only (no theme color) -->
      <div class="form-card">
        <div class="form-card-header"><h1>Logo</h1></div>
        <div class="form-card-body" style="text-align:center;">
          <!-- Logo preview -->
          <div id="logoWrap" style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#0d6e8a,#0a3d5c);border:3px solid rgba(0,229,255,0.4);display:flex;align-items:center;justify-content:center;font-size:2.8rem;margin:0 auto 1rem;box-shadow:0 0 24px rgba(0,229,255,0.3);overflow:hidden;">
            <?php if (!empty($_SESSION['logo_data_url'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['logo_data_url']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" id="logoImg">
            <?php else: ?>
            <span id="logoEmoji">🌊</span>
            <?php endif; ?>
          </div>
          <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('logo-input').click()">🖼 Change Logo</button>
          <input type="file" id="logo-input" name="logo_file" accept=".png,.jpg,.jpeg,.gif,.webp" style="display:none" onchange="previewLogo(this)">
          <p style="font-size:.68rem;color:var(--text-muted);margin-top:.6rem;">PNG, JPG up to 2MB</p>
          <?php if (!empty($_SESSION['logo_data_url'])): ?>
          <button type="button" class="btn btn-secondary btn-sm" style="margin-top:.4rem;" onclick="removeLogo()">✕ Remove</button>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.2rem;">
      <a href="?tab=<?= htmlspecialchars($activeTab) ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Changes</button>
    </div>
  </form>
</div>

<?php
// Handle logo upload on POST (any tab)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['logo_file']['tmp_name'])) {
    $file    = $_FILES['logo_file'];
    $allowed = ['image/png','image/jpeg','image/gif','image/webp'];
    if (in_array($file['type'], $allowed) && $file['size'] <= 2 * 1024 * 1024) {
        $data = base64_encode(file_get_contents($file['tmp_name']));
        $_SESSION['logo_data_url'] = 'data:'.$file['type'].';base64,'.$data;
        if (empty($message)) $message = "Logo updated successfully!";
    } else {
        $errors = "Invalid file. Use PNG/JPG/GIF/WEBP under 2MB.";
    }
}
?>

<style>
.settings-tabs{display:flex;gap:0;margin-bottom:1.4rem;border-bottom:1px solid rgba(0,229,255,0.12);}
.settings-tab{padding:.6rem 1.4rem;font-size:.82rem;font-weight:600;color:var(--text-muted);text-decoration:none;border-bottom:2px solid transparent;transition:all .2s;position:relative;top:1px;}
.settings-tab:hover{color:var(--text-dim);}
.settings-tab.active{color:var(--cyan-bright);border-bottom-color:var(--cyan-bright);text-shadow:0 0 10px rgba(0,229,255,.4);}
.toggle-switch{position:relative;display:inline-block;width:42px;height:22px;flex-shrink:0;}
.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;border-radius:22px;background:rgba(255,255,255,0.1);border:1px solid rgba(0,229,255,.2);cursor:pointer;transition:.3s;}
.toggle-slider::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;left:2px;top:2px;background:var(--text-dim);transition:.3s;}
.toggle-switch input:checked + .toggle-slider{background:rgba(0,229,255,.25);border-color:var(--cyan);}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(20px);background:var(--cyan-bright);box-shadow:0 0 8px var(--cyan);}
</style>
<script>
function previewLogo(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const wrap = document.getElementById('logoWrap');
      wrap.innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
function removeLogo() {
  fetch('?tab=<?= $activeTab ?>&remove_logo=1').then(() => location.reload());
}
</script>
<?php
// Handle logo removal
if (isset($_GET['remove_logo'])) {
    unset($_SESSION['logo_data_url']);
    header("Location: ?tab=" . urlencode($activeTab));
    exit();
}
?>
<?php require '../partial/footer.php'; ?>