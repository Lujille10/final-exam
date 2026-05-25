<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: /inventory/index.php");
  exit();
}
require_once __DIR__ . '/../../models/product.php';
require_once __DIR__ . '/../../controllers/product.php';
require_once __DIR__ . '/../../public/database.config.php';

$pc            = new ProductController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$totalProducts = $pc->getTotalProducts();
$lowStock      = $pc->getLowStock(5);
$totalValue    = $pc->getTotalValue();
$categories    = $pc->getCategories();
$recentProducts = array_slice($pc->getAll(), 0, 5);

// Category breakdown for donut chart
$catCounts = [];
foreach ($pc->getAll() as $p) {
  $cat = $p['category'] ?? 'Other';
  $catCounts[$cat] = ($catCounts[$cat] ?? 0) + $p['quantity'];
}
arsort($catCounts);

// Palette for donut
$donutColors = ['#1e88e5','#43a047','#00acc1','#fb8c00','#8e24aa','#e53935','#00897b'];
?>
<?php require '../partial/header.php'; ?>

<!-- HERO BANNER -->
<div class="hero-banner">
  <div class="hero-text">
    <h1>We Are The Oceans</h1>
    <p>Protecting marine resources through organized inventory management.</p>
  </div>
  <div class="hero-wave">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 40" preserveAspectRatio="none" style="height:40px;">
      <path fill="<?= urlencode('#f0f6ff') ?>" d="M0,20 C200,40 400,0 600,20 C800,40 1000,0 1200,20 L1200,40 L0,40 Z"/>
    </svg>
  </div>
</div>

<!-- PAGE BODY -->
<div class="page-body">

  <!-- STAT CARDS -->
  <div class="stats-row">
    <div class="stat-card blue">
      <div class="stat-info">
        <div class="stat-label">Total Equipment</div>
        <div class="stat-value"><?= $totalProducts ?></div>
        <div class="stat-sub">All equipment in inventory</div>
      </div>
      <div class="stat-icon-wrap">📦</div>
    </div>

    <div class="stat-card green">
      <div class="stat-info">
        <div class="stat-label">Available Items</div>
        <div class="stat-value"><?= max(0, $totalProducts - $lowStock) ?></div>
        <div class="stat-sub">Ready for use</div>
      </div>
      <div class="stat-icon-wrap">✅</div>
    </div>

    <div class="stat-card orange">
      <div class="stat-info">
        <div class="stat-label">Low Stock</div>
        <div class="stat-value"><?= $lowStock ?></div>
        <div class="stat-sub">Quantity less than 5</div>
      </div>
      <div class="stat-icon-wrap">⚠️</div>
    </div>

    <div class="stat-card red">
      <div class="stat-info">
        <div class="stat-label">Damaged Items</div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Need repair / replacement</div>
      </div>
      <div class="stat-icon-wrap">🔧</div>
    </div>
  </div>

  <!-- BOTTOM GRID: Recent Equipment + Category Overview -->
  <div class="dashboard-bottom">

    <!-- RECENT EQUIPMENT TABLE -->
    <div class="card">
      <div class="card-header">
        <h2>Recent Equipment</h2>
        <a href="/inventory/views/products/index.php" class="view-all">View All →</a>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Equipment Name</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Status</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentProducts)): ?>
              <tr><td colspan="5" style="text-align:center;color:var(--text-light);padding:2rem;">
                No equipment yet. <a href="/inventory/views/products/index.php?add=1">Add the first one!</a>
              </td></tr>
            <?php else: ?>
              <?php
              $statusMap = [
                'available'   => ['label'=>'Available',   'badge'=>'badge-available'],
                'in use'      => ['label'=>'In Use',      'badge'=>'badge-inuse'],
                'low stock'   => ['label'=>'Low Stock',   'badge'=>'badge-low'],
                'maintenance' => ['label'=>'Maintenance', 'badge'=>'badge-maint'],
              ];
              $icons = ['📷','🪣','🔬','🧤','🚤','🔭','⚓'];
              $locs  = ['Storage A','Boat 1','Storage B','Lab Room','Harbor'];
              $i = 0;
              foreach ($recentProducts as $p):
                $qty    = (int)$p['quantity'];
                $status = $qty === 0 ? 'out of stock' : ($qty <= 5 ? 'low stock' : 'available');
                $si     = $statusMap[$status] ?? ['label'=>ucfirst($status),'badge'=>'badge-gray'];
              ?>
              <tr>
                <td>
                  <span class="equip-icon"><?= $icons[$i % count($icons)] ?></span>
                  <strong><?= htmlspecialchars($p['name']) ?></strong>
                </td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td><?= $qty ?></td>
                <td><span class="badge <?= $si['badge'] ?>"><?= $si['label'] ?></span></td>
                <td style="color:var(--text-light)"><?= $locs[$i % count($locs)] ?></td>
              </tr>
              <?php $i++; endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CATEGORY OVERVIEW DONUT -->
    <div class="card">
      <div class="card-header">
        <h2>Category Overview</h2>
      </div>
      <div class="donut-wrap">
        <?php
        $total = array_sum($catCounts) ?: 1;
        $catList = array_slice($catCounts, 0, 5, true);
        // Build SVG donut
        $r = 70; $cx = 80; $cy = 80;
        $circumference = 2 * M_PI * $r;
        $offset = 0;
        $segments = [];
        $ci = 0;
        foreach ($catList as $cat => $count) {
          $pct  = $count / $total;
          $dash = $pct * $circumference;
          $gap  = $circumference - $dash;
          $segments[] = ['cat'=>$cat,'count'=>$count,'pct'=>round($pct*100,1),'dash'=>$dash,'gap'=>$gap,'offset'=>$offset,'color'=>$donutColors[$ci % count($donutColors)]];
          $offset += $dash;
          $ci++;
        }
        ?>
        <div class="donut-svg-wrap">
          <svg width="160" height="160" viewBox="0 0 160 160">
            <!-- Background circle -->
            <circle cx="80" cy="80" r="70" fill="none" stroke="#f0f4f8" stroke-width="22"/>
            <?php foreach ($segments as $seg): ?>
            <circle
              cx="80" cy="80" r="70"
              fill="none"
              stroke="<?= $seg['color'] ?>"
              stroke-width="22"
              stroke-dasharray="<?= round($seg['dash'],2) ?> <?= round($seg['gap'],2) ?>"
              stroke-dashoffset="<?= round($circumference - $seg['offset'],2) ?>"
              transform="rotate(-90 80 80)"
            />
            <?php endforeach; ?>
          </svg>
          <div class="donut-center">
            <div class="dc-value"><?= $totalProducts ?></div>
            <div class="dc-label">Total</div>
          </div>
        </div>

        <div class="donut-legend">
          <?php foreach ($segments as $seg): ?>
          <div class="legend-item">
            <span class="legend-dot" style="background:<?= $seg['color'] ?>"></span>
            <span class="legend-name"><?= htmlspecialchars($seg['cat']) ?></span>
            <span class="legend-count"><?= $seg['count'] ?> (<?= $seg['pct'] ?>%)</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div><!-- end dashboard-bottom -->
</div><!-- end page-body -->

<?php require '../partial/footer.php'; ?>