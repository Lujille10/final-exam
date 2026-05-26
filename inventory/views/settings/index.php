<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /index.php"); exit(); }
require_once __DIR__ . '/../../public/database.config.php';

$conn = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
foreach (['role'=>"ALTER TABLE accounts ADD COLUMN role VARCHAR(50) DEFAULT 'Staff' AFTER username",
          'email'=>"ALTER TABLE accounts ADD COLUMN email VARCHAR(150) DEFAULT '' AFTER role",
          'full_name'=>"ALTER TABLE accounts ADD COLUMN full_name VARCHAR(150) DEFAULT '' AFTER email"] as $col => $sql) {
    $c = $conn->query("SHOW COLUMNS FROM accounts LIKE '$col'");
    if ($c->num_rows === 0) $conn->query($sql);
}

$message = $errors = '';
$activeTab = $_GET['tab'] ?? 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($activeTab === 'profile') {
        $newUsername = trim($_POST['username']  ?? '');
        $newFullName = trim($_POST['full_name'] ?? '');
        $newEmail    = trim($_POST['email']     ?? '');
        if (empty($newUsername)) {
            $errors = "Username is required.";
        } else {
            $stmt = $conn->prepare("UPDATE accounts SET username=?, full_name=?, email=? WHERE id=?");
            $stmt->bind_param("sssi", $newUsername, $newFullName, $newEmail, $_SESSION['user_id']);
            if ($stmt->execute()) { $_SESSION['username'] = $newUsername; $message = "Profile updated successfully!"; }
            else { $errors = "Failed to update profile."; }
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
            $stmt->bind_param("i", $_SESSION['user_id']); $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($row && password_verify($currentPass, $row['password'])) {
                $hashed = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt2  = $conn->prepare("UPDATE accounts SET password=? WHERE id=?");
                $stmt2->bind_param("si", $hashed, $_SESSION['user_id']);
                $message = $stmt2->execute() ? "Password changed successfully!" : "Failed to change password.";
                $stmt2->close();
            } else { $errors = "Current password is incorrect."; }
        }

    } elseif ($activeTab === 'general') {
        $_SESSION['settings_sys_name']  = trim($_POST['sys_name']   ?? '');
        $_SESSION['settings_sys_email'] = trim($_POST['sys_email']  ?? '');
        $_SESSION['settings_low_stock'] = (int)($_POST['low_stock'] ?? 5);
        $message = "General settings saved!";
    }
}

$stmt = $conn->prepare("SELECT username, full_name, email FROM accounts WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']); $stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc() ?? []; $stmt->close();
$conn->close();

$sysName  = $_SESSION['settings_sys_name']  ?? 'We Are The Oceans Inventory System';
$sysEmail = $_SESSION['settings_sys_email'] ?? 'info@wearetheoceans.com';
$lowStock = $_SESSION['settings_low_stock'] ?? 5;

$tabs = ['general'=>'General','profile'=>'Profile','security'=>'Security'];
if (!isset($tabs[$activeTab])) $activeTab = 'general';
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

  <div class="page-header"><div><h1>Settings</h1></div></div>

  <!-- Tabs -->
  <div class="settings-tabs">
    <?php foreach ($tabs as $key => $label): ?>
    <a href="?tab=<?= $key ?>" class="settings-tab <?= $activeTab === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <form method="POST" action="?tab=<?= htmlspecialchars($activeTab) ?>" style="width:100%;">
    <div class="form-card settings-form-card">
      <div class="form-card-header">
        <h1><?= $tabs[$activeTab] ?> Settings</h1>
        <div class="breadcrumb">Manage your <?= strtolower($tabs[$activeTab]) ?> preferences</div>
      </div>
      <div class="form-card-body">

        <?php if ($activeTab === 'general'): ?>
        <div class="form-row">
          <div class="form-group">
            <label>System Name</label>
            <input type="text" name="sys_name" class="form-control" value="<?= htmlspecialchars($sysName) ?>">
          </div>
          <div class="form-group">
            <label>System Email</label>
            <input type="email" name="sys_email" class="form-control" value="<?= htmlspecialchars($sysEmail) ?>">
          </div>
        </div>
        <div class="form-row">
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
        </div>
        <div class="form-group" style="max-width:320px;">
          <label>Low Stock Threshold</label>
          <input type="number" name="low_stock" class="form-control" value="<?= (int)$lowStock ?>" min="1" max="100">
          <small style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;display:block;">Items at or below this quantity are flagged as low stock.</small>
        </div>

        <?php elseif ($activeTab === 'profile'): ?>
        <div class="form-row">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control"
                   value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>" placeholder="Your full name">
          </div>
          <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" class="form-control"
                   value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" placeholder="your@email.com">
          </div>
          <div class="form-group"></div><!-- spacer -->
        </div>
        <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;">
          To change your password, go to the <a href="?tab=security" style="color:var(--cyan-bright);">Security</a> tab.
        </p>

        <?php elseif ($activeTab === 'security'): ?>
        <div class="form-row">
          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_pass" class="form-control" placeholder="Enter current password" required>
          </div>
          <div class="form-group"></div><!-- spacer -->
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_pass" class="form-control" placeholder="Min. 6 characters" required>
          </div>
          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_pass" class="form-control" placeholder="Repeat new password" required>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.2rem;">
      <a href="?tab=<?= htmlspecialchars($activeTab) ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">💾 Save Changes</button>
    </div>
  </form>
</div>

<style>
/* ── Tabs ── */
.settings-tabs {
  display: flex;
  gap: 0;
  margin-bottom: 1.4rem;
  border-bottom: 1px solid rgba(0,229,255,0.12);
}
.settings-tab {
  padding: .65rem 1.6rem;
  font-size: .84rem;
  font-weight: 600;
  color: var(--text-muted);
  text-decoration: none;
  border-bottom: 2px solid transparent;
  transition: all .2s;
  position: relative;
  top: 1px;
}
.settings-tab:hover { color: var(--text-dim); }
.settings-tab.active { color: var(--cyan-bright); border-bottom-color: var(--cyan-bright); }

/* ── Settings card fills full width ── */
.settings-form-card {
  max-width: 100% !important;
  width: 100% !important;
}
.settings-form-card .form-card-body {
  padding: 1.8rem 2rem;
}

/* ── Two-column grid for form rows ── */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem 1.5rem;
  margin-bottom: .25rem;
}
@media (max-width: 600px) {
  .form-row { grid-template-columns: 1fr; }
}
</style>
<?php require '../partial/footer.php'; ?>