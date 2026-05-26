<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /index.php"); exit(); }
require_once __DIR__ . '/../../controllers/product.php';
require_once __DIR__ . '/../../public/database.config.php';

$conn = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

// ── Ensure dedicated categories table exists ──────────────
$conn->query("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

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

// ── ADD CATEGORY ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add_cat') {
    $catName = trim($_POST['cat_name'] ?? '');
    $catDesc = trim($_POST['cat_desc'] ?? '');
    if (empty($catName)) {
        $errors = "Category name is required.";
    } else {
        $chk = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $chk->bind_param("s", $catName); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors = "Category \"$catName\" already exists.";
        } else {
            $chk->close();
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $catName, $catDesc);
            if ($stmt->execute()) {
                $message = "Category \"$catName\" added successfully!";
                logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', 'Added', 'add', "Added category \"$catName\"");
            } else { $errors = "Failed to add category."; }
            $stmt->close();
        }

    }
}

// ── DELETE CATEGORY ───────────────────────────────────────
if (isset($_GET['delete_cat'])) {
    $catToDel = urldecode($_GET['delete_cat']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE name = ?");
    $stmt->bind_param("s", $catToDel);
    if ($stmt->execute()) {
        $message = "Category \"$catToDel\" deleted. Existing equipment in this category is unaffected.";
        logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', 'Deleted', 'delete', "Deleted category \"$catToDel\"");
    } else { $errors = "Failed to delete category."; }
    $stmt->close();
}

// ── EDIT CATEGORY ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit_cat') {
    $oldName = trim($_POST['old_cat_name'] ?? '');
    $newName = trim($_POST['cat_name']     ?? '');
    $newDesc = trim($_POST['cat_desc']     ?? '');
    if (empty($newName)) {
        $errors = "Category name is required.";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE name=?");
        $stmt->bind_param("sss", $newName, $newDesc, $oldName);
        $stmt->execute(); $stmt->close();
        $stmt2 = $conn->prepare("UPDATE products SET category=? WHERE category=?");
        $stmt2->bind_param("ss", $newName, $oldName);
        $stmt2->execute();
        $affected = $stmt2->affected_rows;
        $stmt2->close();
        $message = "Category renamed to \"$newName\" ($affected equipment updated).";
        logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin', 'Edited', 'edit', "Renamed category \"$oldName\" → \"$newName\"");
    }
}

// ── FETCH CATEGORIES ──────────────────────────────────────
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $conn->prepare("SELECT name, description FROM categories WHERE name LIKE ? ORDER BY name ASC");
    $like = "%$search%"; $stmt->bind_param("s", $like); $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT name, description FROM categories ORDER BY name ASC");
}
$categories = [];
while ($row = $result->fetch_assoc()) { $categories[] = $row; }

$countRes = $conn->query("SELECT category, COUNT(*) as cnt FROM products WHERE name != '__cat_placeholder__' GROUP BY category");
$catCounts = [];
while ($r = $countRes->fetch_assoc()) { $catCounts[$r['category']] = $r['cnt']; }

$icons = ['🤿','🎣','🦺','🚤','🧪','⚓','🔭','🌊','🐠','🐋'];
$conn->close();
?>
<?php require '../partial/header.php'; ?>
<div class="hero-banner" style="min-height:110px;padding:1.4rem 2.5rem 2.8rem;">
  <div class="hero-text"><h1>Categories</h1><p>Organize your marine equipment by category.</p></div>
  <div class="hero-creature" style="font-size:4rem;">🐠</div>
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
    <div><h1>Categories</h1>
  </div>
    <button class="btn btn-primary" onclick="document.getElementById('add-cat-modal').style.display='flex'">+ Add Category</button>
  </div>

  <div class="card mb-2" style="padding:1rem 1.2rem;">
    <form method="GET" class="filter-bar">
      <div class="search-input-wrap">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search categories..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <button type="submit" class="btn btn-secondary">Search</button>
      <?php if ($search): ?><a href="?" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr><th>#</th><th>Category Name</th><th>Description</th><th>Total Equipment</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
          <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">No categories yet. Add one!</td></tr>
          <?php else: $i = 1; foreach ($categories as $cat): ?>
          <tr style="animation:cardReveal 0.5s <?= ($i-1)*0.06 ?>s both;">
            <td style="color:var(--text-muted)"><?= $i ?></td>
            <td>
              <span class="equip-icon"><?= $icons[($i-1) % count($icons)] ?></span>
              <strong><?= htmlspecialchars($cat['name']) ?></strong>
            </td>
            <td style="color:var(--text-dim)"><?= htmlspecialchars($cat['description'] ?: 'Ocean inventory category.') ?></td>
            <td><span class="badge badge-blue"><?= $catCounts[$cat['name']] ?? 0 ?> items</span></td>
            <td>
              <div class="action-btns">
                <button class="btn btn-warning btn-sm"
                  onclick="openEditCat('<?= htmlspecialchars(addslashes($cat['name'])) ?>','<?= htmlspecialchars(addslashes($cat['description'])) ?>')">✏️</button>
                <a href="?delete_cat=<?= urlencode($cat['name']) ?>" class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete category \'<?= htmlspecialchars(addslashes($cat['name'])) ?>\'? Equipment in this category will keep their label.')">🗑</a>
              </div>
            </td>
          </tr>
          <?php $i++; endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="pagination">
      <span class="page-info">Showing 1 to <?= count($categories) ?> of <?= count($categories) ?> entries</span>
      <span class="page-btn active">1</span>
    </div>
  </div>
</div>

<!-- ADD MODAL -->
<div id="add-cat-modal" style="display:none;position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
  <div class="form-card" style="width:100%;max-width:460px;animation:cardReveal 0.4s both;">
    <div class="form-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div><h1>Add Category</h1><div class="breadcrumb">New inventory category</div></div>
      <button onclick="document.getElementById('add-cat-modal').style.display='none'" style="background:none;border:none;color:var(--text-dim);font-size:1.3rem;cursor:pointer;">✕</button>
    </div>
    <form method="POST" class="form-card-body">
      <input type="hidden" name="form_action" value="add_cat">
      <div class="form-group">
        <label>Category Name *</label>
        <input type="text" name="cat_name" class="form-control" placeholder="e.g. Diving Gear" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="cat_desc" class="form-control" rows="3" placeholder="Brief description..."></textarea>
      </div>
      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-cat-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Category</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div id="edit-cat-modal" style="display:none;position:fixed;inset:0;z-index:999;background:rgba(0,0,0,0.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
  <div class="form-card" style="width:100%;max-width:460px;animation:cardReveal 0.4s both;">
    <div class="form-card-header" style="display:flex;justify-content:space-between;align-items:center;">
      <div><h1>Edit Category</h1><div class="breadcrumb">Rename category</div></div>
      <button onclick="document.getElementById('edit-cat-modal').style.display='none'" style="background:none;border:none;color:var(--text-dim);font-size:1.3rem;cursor:pointer;">✕</button>
    </div>
    <form method="POST" class="form-card-body">
      <input type="hidden" name="form_action" value="edit_cat">
      <input type="hidden" name="old_cat_name" id="edit_old_cat_name">
      <div class="form-group">
        <label>Category Name *</label>
        <input type="text" name="cat_name" id="edit_cat_name" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="cat_desc" id="edit_cat_desc" class="form-control" rows="3"></textarea>
      </div>
      <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem;">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-cat-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-warning">💾 Update Category</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditCat(name, desc) {
  document.getElementById('edit_old_cat_name').value = name;
  document.getElementById('edit_cat_name').value     = name;
  document.getElementById('edit_cat_desc').value     = desc;
  document.getElementById('edit-cat-modal').style.display = 'flex';
}
</script>
<?php require '../partial/footer.php'; ?>