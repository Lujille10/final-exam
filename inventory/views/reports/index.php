<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: /inventory/index.php"); exit(); }
require_once __DIR__ . '/../../models/product.php';
require_once __DIR__ . '/../../controllers/product.php';
require_once __DIR__ . '/../../public/database.config.php';

$pc = new ProductController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);
$allProducts  = $pc->getAll();
$totalProducts= $pc->getTotalProducts();
$lowStock     = $pc->getLowStock(5);
$totalValue   = $pc->getTotalValue();

$catCounts = $availCount = $lowCount = $damagedCount = [];
foreach ($allProducts as $p) {
    if ($p['name'] === '__cat_placeholder__') continue;
    $cat = $p['category'] ?? 'Other';
    $qty = (int)$p['quantity'];
    $catCounts[$cat] = ($catCounts[$cat] ?? 0) + $qty;
    if ($qty === 0) $damagedCount[] = $p;
    elseif ($qty <= 5) $lowCount[]  = $p;
    else $availCount[]              = $p;
}
arsort($catCounts);
$available  = count($availCount);
$lowStockC  = count($lowCount);
$damaged    = count($damagedCount);
$inUse      = max(0, $totalProducts - $available - $lowStockC - $damaged);

$donutColors = ['#1e88e5','#43a047','#00acc1','#fb8c00','#8e24aa','#e53935','#00897b'];
$total       = array_sum($catCounts) ?: 1;
$catList     = array_slice($catCounts, 0, 5, true);
$r = 70; $circumference = 2 * M_PI * $r;
$offset = 0; $segments = []; $ci = 0;
foreach ($catList as $cat => $count) {
    $pct  = $count / $total;
    $dash = $pct * $circumference;
    $gap  = $circumference - $dash;
    $segments[] = ['cat'=>$cat,'count'=>$count,'pct'=>round($pct*100,1),'dash'=>$dash,'gap'=>$gap,'offset'=>$offset,'color'=>$donutColors[$ci % count($donutColors)]];
    $offset += $dash;
    $ci++;
}
$barMax = max($available, $inUse, $lowStockC, $damaged, 1);
?>
<?php require '../partial/header.php'; ?>
<div class="hero-banner" style="min-height:110px;padding:1.4rem 2.5rem 2.8rem;">
  <div class="hero-text"><h1>Reports</h1><p>Inventory analytics and stock status overview.</p></div>
  <div class="hero-creature" style="font-size:4rem;">📊</div>
  <div class="hero-wave">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 30" preserveAspectRatio="none" style="height:30px;">
      <path fill="rgba(6,15,30,0.95)" d="M0,15 C200,30 400,0 600,15 C800,30 1000,0 1200,15 L1200,30 L0,30 Z"/>
    </svg>
  </div>
</div>

<div class="page-body">
  <div class="page-header">
    <div>
      <h1>Reports</h1>
      <div class="breadcrumb"><a href="/inventory/views/dashboard/index.php">Dashboard</a> / Reports</div>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center;">
      <div class="form-control" style="width:auto;display:inline-flex;align-items:center;gap:.5rem;padding:.45rem .9rem;font-size:.78rem;">
        📅 <?= date('M j, Y') ?> ▾
      </div>
    </div>
  </div>

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
        <div class="stat-value"><?= $available ?></div>
        <div class="stat-sub">Ready for use</div>
      </div>
      <div class="stat-icon-wrap">✅</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-info">
        <div class="stat-label">Low Stock Items</div>
        <div class="stat-value"><?= $lowStockC ?></div>
        <div class="stat-sub">Quantity less than 5</div>
      </div>
      <div class="stat-icon-wrap">⚠️</div>
    </div>
    <div class="stat-card red">
      <div class="stat-info">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value"><?= $damaged ?></div>
        <div class="stat-sub">Need restocking</div>
      </div>
      <div class="stat-icon-wrap">🔧</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin-top:0.2rem;">
    <!-- Donut -->
    <div class="card">
      <div class="card-header"><h2>Equipment by Category</h2></div>
      <div class="donut-wrap">
        <div class="donut-svg-wrap">
          <svg width="160" height="160" viewBox="0 0 160 160">
            <circle cx="80" cy="80" r="70" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="22"/>
            <?php foreach ($segments as $seg): ?>
            <circle cx="80" cy="80" r="70" fill="none"
              stroke="<?= $seg['color'] ?>" stroke-width="22"
              stroke-dasharray="<?= round($seg['dash'],2) ?> <?= round($seg['gap'],2) ?>"
              stroke-dashoffset="<?= round($circumference - $seg['offset'],2) ?>"
              transform="rotate(-90 80 80)"
              style="filter:drop-shadow(0 0 4px <?= $seg['color'] ?>88);transition:stroke-width .3s;cursor:pointer;"
              onmouseover="this.style.strokeWidth='26'" onmouseout="this.style.strokeWidth='22'"/>
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
            <span class="legend-dot" style="background:<?= $seg['color'] ?>;box-shadow:0 0 6px <?= $seg['color'] ?>;"></span>
            <span class="legend-name"><?= htmlspecialchars($seg['cat']) ?></span>
            <span class="legend-count"><?= $seg['count'] ?> (<?= $seg['pct'] ?>%)</span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($segments)): ?>
          <div class="legend-item"><span class="legend-name" style="color:var(--text-muted);">No data yet</span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Bar chart -->
    <div class="card">
      <div class="card-header"><h2>Stock Status Overview</h2></div>
      <div class="card-body" style="padding-bottom:1.5rem;">
        <div style="display:flex;align-items:flex-end;gap:0;height:180px;padding:0 1rem;position:relative;">
          <?php foreach([0,20,40,60,80] as $gl): ?>
          <div style="position:absolute;left:0;right:0;bottom:<?= $gl ?>px;height:1px;background:rgba(0,229,255,0.07);"></div>
          <?php endforeach; ?>
          <?php
          $bars = [
            ['label'=>'Available', 'value'=>$available,  'color'=>'#43a047','glow'=>'rgba(67,160,71,0.4)'],
            ['label'=>'In Use',    'value'=>$inUse,       'color'=>'#1e88e5','glow'=>'rgba(30,136,229,0.4)'],
            ['label'=>'Low Stock', 'value'=>$lowStockC,   'color'=>'#fb8c00','glow'=>'rgba(251,140,0,0.4)'],
            ['label'=>'Out',       'value'=>$damaged,     'color'=>'#e53935','glow'=>'rgba(229,57,53,0.4)'],
          ];
          foreach ($bars as $bar):
            $h = max(4, round(($bar['value'] / $barMax) * 140));
          ?>
          <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:.4rem;justify-content:flex-end;">
            <span style="font-family:'Montserrat',sans-serif;font-size:.8rem;font-weight:800;color:#fff;"><?= $bar['value'] ?></span>
            <div class="bar-animate" data-h="<?= $h ?>"
              style="width:52px;height:0;background:linear-gradient(180deg,<?= $bar['color'] ?>,<?= $bar['color'] ?>88);border-radius:8px 8px 0 0;box-shadow:0 0 16px <?= $bar['glow'] ?>;transition:height .9s cubic-bezier(.22,1,.36,1);">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:0;padding:0.5rem 1rem 0;border-top:1px solid rgba(0,229,255,0.08);">
          <?php foreach ($bars as $bar): ?>
          <div style="flex:1;text-align:center;font-size:.7rem;color:var(--text-muted);"><?= $bar['label'] ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.addEventListener('load', () => {
  document.querySelectorAll('.bar-animate').forEach(bar => {
    setTimeout(() => { bar.style.height = bar.dataset.h + 'px'; }, 200);
  });
});
document.addEventListener('mousemove', e => {
  document.querySelectorAll('.stat-card').forEach(card => {
    const r  = card.getBoundingClientRect();
    const dx = (e.clientX - (r.left + r.width/2))  / window.innerWidth  * 8;
    const dy = (e.clientY - (r.top  + r.height/2)) / window.innerHeight * 8;
    card.style.transform = `translateY(-4px) rotateX(${-dy}deg) rotateY(${dx}deg)`;
  });
});
document.addEventListener('mouseleave', () => {
  document.querySelectorAll('.stat-card').forEach(c => c.style.transform = '');
});
</script>
<?php require '../partial/footer.php'; ?>