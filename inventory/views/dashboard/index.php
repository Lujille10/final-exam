<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /index.php"); exit(); }
require_once __DIR__ . '/../../models/product.php';
require_once __DIR__ . '/../../controllers/product.php';
require_once __DIR__ . '/../../public/database.config.php';

$pc = new ProductController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

// Exclude __cat_placeholder__ from all counts and listings
$allProducts = array_filter($pc->getAll(), fn($p) => $p['name'] !== '__cat_placeholder__');

$totalProducts  = count($allProducts);
$lowStockCount  = 0;
$availableCount = 0;
$outOfStock     = 0;

foreach ($allProducts as $p) {
    $qty = (int)$p['quantity'];
    if ($qty === 0)     $outOfStock++;
    elseif ($qty <= 5)  $lowStockCount++;
    else                $availableCount++;
}

$recentProducts = array_slice(array_values($allProducts), 0, 5);

// Category breakdown for donut (exclude placeholders)
$catCounts = [];
foreach ($allProducts as $p) {
    $cat = $p['category'] ?? 'Other';
    $catCounts[$cat] = ($catCounts[$cat] ?? 0) + (int)$p['quantity'];
}
arsort($catCounts);

$donutColors = ['#1e88e5','#43a047','#00acc1','#fb8c00','#8e24aa','#e53935','#00897b'];
?>
<?php require '../partial/header.php'; ?>

<div class="hero-banner">
  <div class="hero-text">
    <h1>We Are The Oceans</h1>
    <p>Protecting marine resources through organized inventory management.</p>
  </div>
  <div class="hero-wave">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 40" preserveAspectRatio="none" style="height:40px;">
      <path fill="rgba(6,15,30,0.95)" d="M0,20 C200,40 400,0 600,20 C800,40 1000,0 1200,20 L1200,40 L0,40 Z"/>
    </svg>
  </div>
</div>

<div class="page-body">

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
        <div class="stat-value"><?= $availableCount ?></div>
        <div class="stat-sub">Ready for use</div>
      </div>
      <div class="stat-icon-wrap">✅</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-info">
        <div class="stat-label">Low Stock</div>
        <div class="stat-value"><?= $lowStockCount ?></div>
        <div class="stat-sub">Quantity less than 5</div>
      </div>
      <div class="stat-icon-wrap">⚠️</div>
    </div>
    <div class="stat-card red">
      <div class="stat-info">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value"><?= $outOfStock ?></div>
        <div class="stat-sub">Need restocking</div>
      </div>
      <div class="stat-icon-wrap">🔧</div>
    </div>
  </div>

  <div class="dashboard-bottom">

    <!-- RECENT EQUIPMENT TABLE -->
    <div class="card">
      <div class="card-header">
        <h2>Recent Equipment</h2>
        <a href="/views/products/index.php" class="view-all">View All →</a>
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
                No equipment yet. <a href="/views/products/index.php?add=1">Add the first one!</a>
              </td></tr>
            <?php else: ?>
              <?php
              $icons = ['📷','🪣','🔬','🧤','🚤','🔭','⚓'];
              $i = 0;
              foreach ($recentProducts as $p):
                $qty    = (int)$p['quantity'];
                if ($qty === 0)    { $badge = 'badge-red';       $label = 'Out of stock'; }
                elseif ($qty <= 5) { $badge = 'badge-low';       $label = 'Low Stock'; }
                else               { $badge = 'badge-available'; $label = 'Available'; }
              ?>
              <tr>
                <td>
                  <span class="equip-icon"><?= $icons[$i % count($icons)] ?></span>
                  <strong><?= htmlspecialchars($p['name']) ?></strong>
                </td>
                <td><?= htmlspecialchars($p['category']) ?></td>
                <td><?= $qty ?></td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                <td style="color:var(--text-light)"><?= htmlspecialchars($p['location'] ?? '—') ?></td>
              </tr>
              <?php $i++; endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CATEGORY OVERVIEW DONUT -->
    <div class="card">
      <div class="card-header"><h2>Category Overview</h2></div>
      <div class="donut-wrap">
        <?php
        $total   = array_sum($catCounts) ?: 1;
        $catList = array_slice($catCounts, 0, 5, true);
        $r = 70; $circumference = 2 * M_PI * $r;
        $offset = 0; $segments = []; $ci = 0;
        foreach ($catList as $cat => $count) {
            $pct  = $count / $total;
            $dash = $pct * $circumference;
            $gap  = $circumference - $dash;
            $segments[] = ['cat'=>$cat,'count'=>$count,'pct'=>round($pct*100,1),'dash'=>$dash,'gap'=>$gap,'offset'=>$offset,'color'=>$donutColors[$ci % count($donutColors)]];
            $offset += $dash; $ci++;
        }
        ?>
        <div class="donut-svg-wrap">
          <svg width="160" height="160" viewBox="0 0 160 160">
            <circle cx="80" cy="80" r="70" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="22"/>
            <?php foreach ($segments as $seg): ?>
            <circle cx="80" cy="80" r="70" fill="none"
              stroke="<?= $seg['color'] ?>" stroke-width="22"
              stroke-dasharray="<?= round($seg['dash'],2) ?> <?= round($seg['gap'],2) ?>"
              stroke-dashoffset="<?= round($circumference - $seg['offset'],2) ?>"
              transform="rotate(-90 80 80)"/>
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

  </div>
</div>

<?php require '../partial/footer.php'; ?>