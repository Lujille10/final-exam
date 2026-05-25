<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory/index.php"); exit(); }
require_once __DIR__ . '/../../public/database.config.php';

$message = $errors = '';

// ── ACTIVITY LOG TABLE HELPER ─────────────────────────────
// Creates activity_logs table if it doesn't exist, then reads from it.
// Falls back to static demo data if DB unavailable.
$conn = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$useLiveDB = false;
$logs = [];

if (!$conn->connect_error) {
    // Create table if needed
    $conn->query("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        username VARCHAR(100),
        action VARCHAR(50),
        action_type VARCHAR(20),
        details TEXT,
        ip VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $useLiveDB = true;
}

// ── CLEAR LOGS ────────────────────────────────────────────
if (isset($_GET['clear_logs']) && $useLiveDB) {
    $conn->query("DELETE FROM activity_logs");
    $message = "Activity logs cleared.";
}

// ── FETCH LOGS ────────────────────────────────────────────
if ($useLiveDB) {
    $search = trim($_GET['search'] ?? '');
    $actionFilter = $_GET['action_filter'] ?? '';

    $sql = "SELECT * FROM activity_logs WHERE 1=1";
    $params = [];
    $types = "";

    if ($search) {
        $sql .= " AND (username LIKE ? OR details LIKE ? OR action LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like];
        $types = "sss";
    }
    if ($actionFilter) {
        $sql .= " AND action_type = ?";
        $params[] = $actionFilter;
        $types .= "s";
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
}
$conn->close();

// Fallback demo data if DB table is empty
if (empty($logs)) {
    $search = trim($_GET['search'] ?? '');
    $actionFilter = $_GET['action_filter'] ?? '';
    $logs = [
        ['id'=>1,'username'=>'Admin User','action'=>'Added','action_type'=>'add','details'=>'Added new equipment "Diving Mask"','created_at'=>'2024-05-15 10:30:00','ip'=>'192.168.1.1'],
        ['id'=>2,'username'=>'John Dela Cruz','action'=>'Edited','action_type'=>'edit','details'=>'Updated equipment "Oxygen Tank"','created_at'=>'2024-05-15 11:05:00','ip'=>'192.168.1.2'],
        ['id'=>3,'username'=>'Maria Santos','action'=>'Deleted','action_type'=>'delete','details'=>'Deleted equipment "Old Fishing Net"','created_at'=>'2024-05-15 11:45:00','ip'=>'192.168.1.3'],
        ['id'=>4,'username'=>'Kevin Reyes','action'=>'Added','action_type'=>'add','details'=>'Added new equipment "Life Vest"','created_at'=>'2024-05-15 13:20:00','ip'=>'192.168.1.4'],
        ['id'=>5,'username'=>'Admin User','action'=>'Login','action_type'=>'login','details'=>'User logged in','created_at'=>'2024-05-15 14:10:00','ip'=>'192.168.1.1'],
        ['id'=>6,'username'=>'John Dela Cruz','action'=>'Edited','action_type'=>'edit','details'=>'Updated category "Diving Gear"','created_at'=>'2024-05-15 14:45:00','ip'=>'192.168.1.2'],
        ['id'=>7,'username'=>'Maria Santos','action'=>'Added','action_type'=>'add','details'=>'Added new equipment "Rescue Boat"','created_at'=>'2024-05-15 15:15:00','ip'=>'192.168.1.3'],
        ['id'=>8,'username'=>'Admin User','action'=>'Edited','action_type'=>'edit','details'=>'Updated stock for "Water Test Kit"','created_at'=>'2024-05-15 16:00:00','ip'=>'192.168.1.1'],
        ['id'=>9,'username'=>'Kevin Reyes','action'=>'Login','action_type'=>'login','details'=>'User logged in','created_at'=>'2024-05-15 16:30:00','ip'=>'192.168.1.4'],
        ['id'=>10,'username'=>'Maria Santos','action'=>'Deleted','action_type'=>'delete','details'=>'Deleted old category "Misc Equipment"','created_at'=>'2024-05-15 17:00:00','ip'=>'192.168.1.3'],
    ];
    if ($search) {
        $logs = array_filter($logs, fn($l) =>
            stripos($l['username'], $search) !== false ||
            stripos($l['details'], $search) !== false ||
            stripos($l['action'], $search) !== false
        );
    }
    if ($actionFilter) {
        $logs = array_filter($logs, fn($l) => $l['action_type'] === $actionFilter);
    }
}

$actionBadge = ['add'=>'badge-available','edit'=>'badge-inuse','delete'=>'badge-red','login'=>'badge-blue'];

// Pagination
$perPage = (int)($_SESSION['settings_per_page'] ?? 5);
$page    = max(1, (int)($_GET['page'] ?? 1));
$total   = count($logs);
$pages   = max(1, ceil($total / $perPage));
$logsPage = array_slice(array_values($logs), ($page-1)*$perPage, $perPage);
?>
<?php require '../partial/header.php'; ?>
<div class="hero-banner" style="min-height:110px;padding:1.4rem 2.5rem 2.8rem;">
  <div class="hero-text">
    <h1>Activity Logs</h1>
    <p>Track every action taken across the system.</p>
  </div>
  <div class="hero-creature" style="font-size:4rem;">📋</div>
  <div class="hero-wave">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 30" preserveAspectRatio="none" style="height:30px;">
      <path fill="rgba(6,15,30,0.95)" d="M0,15 C200,30 400,0 600,15 C800,30 1000,0 1200,15 L1200,30 L0,30 Z"/>
    </svg>
  </div>
</div>

<div class="page-body">
  <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <div class="page-header">
    <div>
      <h1>Activity Logs</h1>
       </div>
    <div style="display:flex;gap:.6rem;">
      <a href="?" class="btn btn-secondary">🔄 Refresh</a>
      <?php if ($useLiveDB && $total > 0): ?>
      <a href="?clear_logs=1" class="btn btn-danger btn-sm"
         onclick="return confirm('Clear all activity logs?')" style="align-self:center;">🗑 Clear Logs</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$useLiveDB): ?>
  <div class="alert alert-warning">Showing demo data — activity_logs table will be created on next DB connection.</div>
  <?php endif; ?>

  <!-- Search -->
  <div class="card mb-2" style="padding:1rem 1.2rem;">
    <form method="GET" class="filter-bar">
      <div class="search-input-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search activity..." value="<?= htmlspecialchars($search ?? '') ?>">
      </div>
      <select name="action_filter" class="filter-select">
        <option value="">All Actions</option>
        <option value="add" <?= ($_GET['action_filter'] ?? '') === 'add' ? 'selected' : '' ?>>Added</option>
        <option value="edit" <?= ($_GET['action_filter'] ?? '') === 'edit' ? 'selected' : '' ?>>Edited</option>
        <option value="delete" <?= ($_GET['action_filter'] ?? '') === 'delete' ? 'selected' : '' ?>>Deleted</option>
        <option value="login" <?= ($_GET['action_filter'] ?? '') === 'login' ? 'selected' : '' ?>>Login</option>
      </select>
      <button type="submit" class="btn btn-secondary">Search</button>
      <?php if (!empty($search) || !empty($_GET['action_filter'])): ?>
        <a href="?" class="btn btn-secondary">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Table -->
  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Action</th>
            <th>Details</th>
            <th>Date &amp; Time</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = ($page-1)*$perPage+1; foreach ($logsPage as $log): ?>
          <tr style="animation:cardReveal 0.5s <?= ($i % $perPage) * 0.07 ?>s both;">
            <td style="color:var(--text-muted)"><?= $i ?></td>
            <td>
              <span class="equip-icon">👤</span>
              <strong><?= htmlspecialchars($log['username'] ?? $log['user'] ?? 'Unknown') ?></strong>
            </td>
            <td>
              <span class="badge <?= $actionBadge[$log['action_type']] ?? 'badge-gray' ?>">
                <?= htmlspecialchars($log['action']) ?>
              </span>
            </td>
            <td style="color:var(--text-dim);font-size:.8rem;"><?= htmlspecialchars($log['details']) ?></td>
            <td style="color:var(--text-muted);font-size:.75rem;white-space:nowrap;"><?= htmlspecialchars($log['created_at'] ?? $log['datetime'] ?? '') ?></td>
            <td style="font-family:monospace;font-size:.75rem;color:var(--cyan-bright);opacity:.7;"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
          </tr>
          <?php $i++; endforeach; ?>
          <?php if (empty($logsPage)): ?>
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No activity found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <span class="page-info">Showing <?= (($page-1)*$perPage)+1 ?> to <?= min($page*$perPage, $total) ?> of <?= $total ?> entries</span>
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="page-btn">‹</a>
      <?php endif; ?>
      <?php for ($p = 1; $p <= min($pages, 5); $p++): ?>
        <a href="?page=<?= $p ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
           class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($pages > 5): ?>
        <span class="page-btn" style="cursor:default;">…</span>
        <a href="?page=<?= $pages ?>" class="page-btn"><?= $pages ?></a>
      <?php endif; ?>
      <?php if ($page < $pages): ?>
        <a href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>" class="page-btn">›</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require '../partial/footer.php'; ?>