<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
require_once __DIR__ . '/../../models/product.php';
require_once __DIR__ . '/../../controllers/product.php';
require_once __DIR__ . '/../../public/database.config.php';

// ── DB connection for activity logging + location support ──
$conn = new mysqli($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

// Ensure location column exists
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS location VARCHAR(150) DEFAULT '' AFTER category");

// Ensure activity_logs table exists
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

$productController = new ProductController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$message = $errors = "";

$view = 'list';
$editProduct = null;

if (isset($_GET['add'])) {
    $view = 'add';
} elseif (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $view = 'edit';
    // Fetch with location
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $_GET['edit']);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editProduct) { $view = 'list'; }
}

// ── DELETE ────────────────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Get name before delete
    $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $result = $productController->delete((int)$_GET['delete']);
    if ($result) {
        $message = "Equipment deleted successfully.";
        logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
            'Deleted', 'delete', 'Deleted equipment "'.($row['name'] ?? 'Unknown').'"');
    } else {
        $message = "Failed to delete equipment.";
    }
    $view = 'list';
}

// ── ADD submit ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'add') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');

    if (empty($name) || empty($category)) {
        $errors = "Equipment name and category are required.";
        $view = 'add';
    } else {
        // Insert with location column
        $stmt2 = $conn->prepare("INSERT INTO products (name, description, quantity, price, category, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssidss", $name, $description, $quantity, $price, $category, $location);
        $result = $stmt2->execute();
        $stmt2->close();

        if ($result) {
            $message = "Equipment added successfully!";
            $view = 'list';
            logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
                'Added', 'add', "Added new equipment \"$name\" (Category: $category, Qty: $quantity)");
        } else {
            $errors = "Failed to add equipment.";
            $view = 'add';
        }
    }
}

// ── EDIT submit ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'edit') {
    $id          = (int)($_POST['product_id'] ?? 0);
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity    = (int)($_POST['quantity'] ?? 0);
    $price       = (float)($_POST['price'] ?? 0);
    $category    = trim($_POST['category'] ?? '');
    $location    = trim($_POST['location'] ?? '');

    if (empty($name) || empty($category)) {
        $errors = "Equipment name and category are required.";
        $view = 'edit';
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editProduct = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $stmt2 = $conn->prepare("UPDATE products SET name=?, description=?, quantity=?, price=?, category=?, location=? WHERE id=?");
        $stmt2->bind_param("ssidssi", $name, $description, $quantity, $price, $category, $location, $id);
        $result = $stmt2->execute();
        $stmt2->close();

        if ($result) {
            $message = "Equipment updated!";
            $view = 'list';
            logActivity($conn, $_SESSION['user_id'], $_SESSION['username'] ?? 'Admin',
                'Edited', 'edit', "Updated equipment \"$name\"");
        } else {
            $errors = "Failed to update.";
            $view = 'edit';
            $stmt4 = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt4->bind_param("i", $id);
            $stmt4->execute();
            $editProduct = $stmt4->get_result()->fetch_assoc();
            $stmt4->close();
        }
    }
}

// ── List data ─────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$cat_filter = $_GET['category'] ?? '';
$st_filter  = $_GET['status'] ?? '';

// Fetch all products with location
$allResult = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
$products = [];
while ($row = $allResult->fetch_assoc()) { $products[] = $row; }

$categories = $productController->getCategories();

if ($search || $cat_filter) {
    $products = array_filter($products, function($p) use ($search, $cat_filter) {
        $ms = !$search || stripos($p['name'], $search) !== false || stripos($p['description'], $search) !== false;
        $mc = !$cat_filter || $p['category'] === $cat_filter;
        return $ms && $mc;
    });
}

function equip_status($qty) {
    if ($qty == 0) return ['label'=>'Out of Stock', 'badge'=>'badge-red'];
    if ($qty <= 5) return ['label'=>'Low Stock',    'badge'=>'badge-low'];
    return ['label'=>'Available', 'badge'=>'badge-available'];
}

$icons = ['📷','🪣','🔬','🧤','🚤','🔭','⚓','🎣','🦺','🛟'];
?>
<?php require '../partial/header.php'; ?>
<div class="hero-banner" style="min-height:90px;padding:1.2rem 2.5rem;">
  <div class="hero-text">
    <?php if ($view === 'add'): ?>
      <h1>Add New Equipment</h1><p>Fill in the details to register new inventory.</p>
    <?php elseif ($view === 'edit'): ?>
      <h1>Edit Equipment</h1><p>Update the details for: <strong style="color:#90caf9"><?= htmlspecialchars($editProduct['name'] ?? '') ?></strong></p>
    <?php else: ?>
      <h1>All Equipment</h1><p>Manage your marine inventory — add, edit, or remove items.</p>
    <?php endif; ?>
  </div>
</div>

<div class="page-body">
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($errors):  ?><div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div><?php endif; ?>

<!-- ══ ADD EQUIPMENT ══ -->
<?php if ($view === 'add'): ?>
<div class="page-header">
  <div>
    <h1>Add New Equipment</h1>
    <div class="breadcrumb"><a href="/views/dashboard/index.php">Dashboard</a> / <a href="?">Equipment</a> / Add New</div>
  </div>
  <a href="?" class="btn btn-secondary">← Back</a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.2rem;align-items:start;">
  <div class="form-card">
    <div class="form-card-header">
      <h1>Equipment Details</h1>
      <div class="breadcrumb"><a href="/board/index.php">Dashboard</a> / <a href="?">Equipment</a> / Add New</div>
    </div>
    <form method="POST" class="form-card-body">
      <input type="hidden" name="form_action" value="add">
      <div class="form-row">
        <div class="form-group">
          <label>Equipment Name *</label>
          <input type="text" name="name" class="form-control" placeholder="Enter equipment name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Description (Optional)</label>
          <input type="text" name="description" class="form-control" placeholder="Enter description..." value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select name="category" class="form-control" required>
          <option value="">Select category</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= ($_POST['category'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Quantity *</label>
          <input type="number" name="quantity" class="form-control" min="0" placeholder="0" required value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>">
        </div>
        <div class="form-group">
          <label>Price (₱) *</label>
          <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="0.00" required value="<?= htmlspecialchars($_POST['price'] ?? '0.00') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Location</label>
        <select name="location" class="form-control">
          <option value="">— Select Location —</option>
          <?php
          $locations = ['Storage A','Storage B','Storage C','Boat 1','Boat 2','Lab Room','Harbor','Dive Room','Equipment Bay','Field Station'];
          $selLoc = $_POST['location'] ?? '';
          foreach ($locations as $loc): ?>
          <option value="<?= htmlspecialchars($loc) ?>" <?= $selLoc === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:0.5rem;">
        <a href="?" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">💾 Save Equipment</button>
      </div>
    </form>
  </div>

  <!-- Image upload card -->
  <div class="form-card">
    <div class="form-card-header"><h1>Equipment Image</h1><div class="breadcrumb">Optional</div></div>
    <div class="form-card-body">
      <div class="upload-zone" onclick="document.getElementById('eq-img').click()" id="uploadZone">
        <div class="upload-icon" id="uploadIcon">☁️</div>
        <p id="uploadText">Drag &amp; drop image here<br>or <span class="upload-link">click to browse</span></p>
        <input type="file" id="eq-img" accept="image/*" style="display:none" onchange="previewImage(this)">
        <img id="imgPreview" src="" alt="" style="display:none;max-width:100%;max-height:160px;border-radius:8px;margin-top:0.75rem;">
      </div>
    </div>
  </div>
</div>

<!-- ══ EDIT EQUIPMENT ══ -->
<?php elseif ($view === 'edit' && $editProduct): ?>
<div class="page-header">
  <div>
    <h1>Edit Equipment</h1>
    <div class="breadcrumb"><a href="/views/dashboard/index.php">Dashboard</a> / <a href="?">Equipment</a> / Edit</div>
  </div>
  <a href="?" class="btn btn-secondary">← Back</a>
</div>
<div class="form-card" style="max-width:700px;">
  <div class="form-card-header">
    <h1>Update: <?= htmlspecialchars($editProduct['name']) ?></h1>
    <div class="breadcrumb"><a href="/views/dashboard/index.php">Dashboard</a> / <a href="?">Equipment</a> / Edit</div>
  </div>
  <form method="POST" class="form-card-body">
    <input type="hidden" name="form_action" value="edit">
    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
    <div class="form-row">
      <div class="form-group">
        <label>Equipment Name *</label>
        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $editProduct['name']) ?>">
      </div>
      <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($_POST['description'] ?? $editProduct['description']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Category *</label>
        <select name="category" class="form-control" required>
          <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= ($c === ($_POST['category'] ?? $editProduct['category'])) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Quantity *</label>
        <input type="number" name="quantity" class="form-control" min="0" required value="<?= htmlspecialchars($_POST['quantity'] ?? $editProduct['quantity']) ?>">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Price (₱) *</label>
        <input type="number" name="price" class="form-control" min="0" step="0.01" required value="<?= htmlspecialchars($_POST['price'] ?? $editProduct['price']) ?>">
      </div>
      <div class="form-group">
        <label>Location</label>
        <select name="location" class="form-control">
          <option value="">— Select Location —</option>
          <?php
          $locations = ['Storage A','Storage B','Storage C','Boat 1','Boat 2','Lab Room','Harbor','Dive Room','Equipment Bay','Field Station'];
          $selLoc = $_POST['location'] ?? $editProduct['location'] ?? '';
          foreach ($locations as $loc): ?>
          <option value="<?= htmlspecialchars($loc) ?>" <?= $selLoc === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
      <button type="submit" class="btn btn-warning">💾 Update Equipment</button>
      <a href="?" class="btn btn-secondary">Cancel</a>
      <a href="?delete=<?= $editProduct['id'] ?>" class="btn btn-danger" style="margin-left:auto;" onclick="return confirm('Delete this equipment permanently?')">🗑 Delete</a>
    </div>
  </form>
</div>

<!-- ══ EQUIPMENT LIST ══ -->
<?php else: ?>
<div class="page-header">
  <div>
    <h1>All Equipment</h1>
    <div class="breadcrumb"><a href="/views/dashboard/index.php">Dashboard</a> / Equipment</div>
  </div>
  <a href="?add=1" class="btn btn-primary">+ Add Equipment</a>
</div>

<div class="card mb-2" style="padding:1rem 1.2rem;">
  <form method="GET" class="filter-bar">
    <div class="search-input-wrap">
      <span class="search-icon">🔍</span>
      <input type="text" name="search" placeholder="Search equipment..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <select name="category" class="filter-select">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= htmlspecialchars($cat) ?>" <?= $cat_filter === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="filter-select">
      <option value="">All Statuses</option>
      <option value="available" <?= $st_filter === 'available' ? 'selected' : '' ?>>Available</option>
      <option value="low" <?= $st_filter === 'low' ? 'selected' : '' ?>>Low Stock</option>
      <option value="out" <?= $st_filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
    </select>
    <button type="submit" class="btn btn-secondary">🔽 Filter</button>
    <a href="?" class="btn btn-secondary">Clear</a>
  </form>
</div>

<div class="card">
  <?php if (empty($products)): ?>
  <div style="padding:2.5rem;text-align:center;color:var(--text-muted);">
    No equipment found.
    <?php if (!$search && !$cat_filter): ?><a href="?add=1" style="color:var(--blue-bright);">Add your first item!</a><?php endif; ?>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Equipment Name</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>Status</th>
          <th>Location</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; foreach ($products as $p): ?>
        <?php $st = equip_status((int)$p['quantity']); $ico = $icons[($i-1) % count($icons)]; ?>
        <tr>
          <td style="color:var(--text-muted)"><?= $i++ ?></td>
          <td>
            <span class="equip-icon"><?= $ico ?></span>
            <strong><?= htmlspecialchars($p['name']) ?></strong>
          </td>
          <td><span class="badge badge-blue"><?= htmlspecialchars($p['category']) ?></span></td>
          <td><?= $p['quantity'] ?></td>
          <td><span class="badge <?= $st['badge'] ?>"><?= $st['label'] ?></span></td>
          <td style="color:var(--text-dim)"><?= htmlspecialchars($p['location'] ?? '') ?></td>
          <td>
            <div class="action-btns">
              <a href="?edit=<?= $p['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
              <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($p['name'])) ?>\'?')">🗑</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="pagination">
    <span class="page-info">Showing 1 to <?= count($products) ?> of <?= count($products) ?> entries</span>
    <span class="page-btn active">1</span>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>

<script>
function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('imgPreview');
      const icon    = document.getElementById('uploadIcon');
      const text    = document.getElementById('uploadText');
      preview.src = e.target.result;
      preview.style.display = 'block';
      icon.style.display    = 'none';
      text.style.display    = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
<?php require '../partial/footer.php'; ?>