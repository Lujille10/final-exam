<?php
$current = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>We Are The Oceans — Inventory System</title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="/public/styles.css">
</head>
<body>
<div class="ocean-bg"></div>
<div class="bubbles-layer">
 <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
 <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
 <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
 <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
</div>
<div class="particles-layer">
 <div class="particle"></div><div class="particle"></div><div class="particle"></div>
 <div class="particle"></div><div class="particle"></div><div class="particle"></div>
 <div class="particle"></div><div class="particle"></div>
</div>
<div class="waves-layer">
 <svg class="wave-svg wave-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60" preserveAspectRatio="none" style="height:60px;">
   <path fill="rgba(0,229,255,0.06)" d="M0,30 C240,60 480,0 720,30 C960,60 1200,0 1440,30 C1440,0 1200,60 960,30 C720,0 480,60 240,30 C120,15 60,45 0,30 Z"/>
 </svg>
 <svg class="wave-svg wave-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 50" preserveAspectRatio="none" style="height:50px;margin-top:-20px;">
   <path fill="rgba(0,150,200,0.05)" d="M0,25 C360,50 720,0 1080,25 C1260,38 1380,12 1440,25 L1440,50 L0,50 Z"/>
 </svg>
</div>

<div class="app-layout">
 <aside class="sidebar">
   <div class="sidebar-brand">
     <div class="brand-icon">🌊</div>
     <div class="brand-text">
       <h2>We Are The Oceans</h2>
       <p>Inventory System</p>
     </div>
   </div>
   <nav>
     <a href="/views/dashboard/index.php" class="<?= $current_dir === 'dashboard' ? 'active' : '' ?>">
       <span class="nav-icon">🏠</span> Dashboard
     </a>
     <a href="/views/products/index.php" class="<?= ($current_dir === 'products' && !isset($_GET['add'])) ? 'active' : '' ?>">
       <span class="nav-icon">📦</span> Equipment
     </a>
     <a href="/views/products/index.php?add=1" class="<?= ($current_dir === 'products' && isset($_GET['add'])) ? 'active' : '' ?>">
       <span class="nav-icon">➕</span> Add Equipment
     </a>
     <a href="/views/categories/index.php" class="<?= $current_dir === 'categories' ? 'active' : '' ?>">
       <span class="nav-icon">🏷</span> Categories
     </a>
     <a href="/views/reports/index.php" class="<?= $current_dir === 'reports' ? 'active' : '' ?>">
       <span class="nav-icon">📊</span> Reports
     </a>
     <a href="/views/activity/index.php" class="<?= $current_dir === 'activity' ? 'active' : '' ?>">
       <span class="nav-icon">📋</span> Activity Logs
     </a>
   </nav>
 </aside>

 <div class="main-content">
   <div class="topbar">
     <div class="topbar-left">
       <button class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('sidebar-open')">☰</button>
     </div>
     <div class="topbar-right">
       <a href="#" class="topbar-bell">🔔<span class="badge-dot"></span></a>
       <div class="topbar-user" id="userMenuToggle" onclick="toggleUserMenu()" style="position:relative;">
         <div class="avatar">👤</div>
         <div class="user-info">
           <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
           <div class="user-role">Administrator</div>
         </div>
         <span class="chevron" id="userChevron">▾</span>
         <div id="userDropdown" style="display:none;position:absolute;top:calc(100% + 10px);right:0;min-width:180px;background:linear-gradient(160deg,rgba(0,20,50,0.97) 0%,rgba(0,40,80,0.97) 100%);backdrop-filter:blur(20px);border:1px solid rgba(0,229,255,0.2);border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,0.5);overflow:hidden;z-index:500;animation:dropdownReveal 0.2s cubic-bezier(.22,1,.36,1) both;">
           <div style="padding:0.75rem 1rem 0.6rem;border-bottom:1px solid rgba(0,229,255,0.1);">
             <div style="font-size:0.82rem;font-weight:700;color:#fff;"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
             <div style="font-size:0.68rem;color:var(--text-muted);margin-top:1px;">Administrator</div>
           </div>
           <a href="/views/settings/index.php?tab=profile" style="display:flex;align-items:center;gap:0.55rem;padding:0.65rem 1rem;color:var(--text-dim);text-decoration:none;font-size:0.8rem;font-weight:500;transition:background 0.2s,color 0.2s;" onmouseover="this.style.background='rgba(0,229,255,0.08)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--text-dim)'">
             <span>👤</span> My Profile
           </a>
           <a href="/views/settings/index.php" style="display:flex;align-items:center;gap:0.55rem;padding:0.65rem 1rem;color:var(--text-dim);text-decoration:none;font-size:0.8rem;font-weight:500;transition:background 0.2s,color 0.2s;" onmouseover="this.style.background='rgba(0,229,255,0.08)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--text-dim)'">
             <span>⚙️</span> Settings
           </a>
           <a href="/views/users/index.php" style="display:flex;align-items:center;gap:0.55rem;padding:0.65rem 1rem;color:var(--text-dim);text-decoration:none;font-size:0.8rem;font-weight:500;transition:background 0.2s,color 0.2s;" onmouseover="this.style.background='rgba(0,229,255,0.08)';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='var(--text-dim)'">
             <span>👥</span> Users
           </a>
           <div style="height:1px;background:rgba(0,229,255,0.08);margin:0.2rem 0;"></div>
           <a href="/index.php?logout=1" style="display:flex;align-items:center;gap:0.55rem;padding:0.65rem 1rem;color:#ff6b6b;text-decoration:none;font-size:0.8rem;font-weight:600;transition:background 0.2s;" onmouseover="this.style.background='rgba(229,57,53,0.1)'" onmouseout="this.style.background='transparent'">
             <span>🚪</span> Logout
           </a>
         </div>
       </div>
     </div>
   </div>
<style>
@keyframes dropdownReveal { from{opacity:0;transform:translateY(-8px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.topbar-user{cursor:pointer;user-select:none;}
</style>
<script>
function toggleUserMenu(){
  const d=document.getElementById('userDropdown');
  const c=document.getElementById('userChevron');
  const open=d.style.display==='block';
  d.style.display=open?'none':'block';
  c.style.transform=open?'':'rotate(180deg)';
  c.style.transition='transform 0.2s';
}
document.addEventListener('click',function(e){
  const t=document.getElementById('userMenuToggle');
  const d=document.getElementById('userDropdown');
  if(t&&!t.contains(e.target)){d.style.display='none';document.getElementById('userChevron').style.transform='';}
});
</script>