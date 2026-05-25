</div><!-- end .main-content -->
</div><!-- end .app-layout -->

<script>
/* ── Global parallax on stat cards & cards ── */
(function() {
  let ticking = false;
  document.addEventListener('mousemove', e => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      // Stat cards — strong 3D tilt
      document.querySelectorAll('.stat-card').forEach(card => {
        const r  = card.getBoundingClientRect();
        const cx = r.left + r.width  / 2;
        const cy = r.top  + r.height / 2;
        const dx = (e.clientX - cx) / window.innerWidth  * 10;
        const dy = (e.clientY - cy) / window.innerHeight * 10;
        card.style.transition = 'transform 0.15s ease, box-shadow 0.35s';
        card.style.transform  = `translateY(-6px) rotateX(${-dy}deg) rotateY(${dx}deg) scale(1.02)`;
      });
      // Glassmorphism cards — gentle tilt
      document.querySelectorAll('.card, .form-card').forEach(card => {
        const r  = card.getBoundingClientRect();
        const cx = r.left + r.width  / 2;
        const cy = r.top  + r.height / 2;
        const dx = (e.clientX - cx) / window.innerWidth  * 3;
        const dy = (e.clientY - cy) / window.innerHeight * 3;
        card.style.transition = 'transform 0.2s ease, border-color 0.3s, box-shadow 0.3s';
        card.style.transform  = `rotateX(${-dy}deg) rotateY(${dx}deg)`;
      });
      // Sidebar brand icon float
      const icon = document.querySelector('.brand-icon');
      if (icon) {
        const dx = (e.clientX / window.innerWidth  - 0.5) * 6;
        const dy = (e.clientY / window.innerHeight - 0.5) * 6;
        icon.style.transform = `translate(${dx}px, ${dy}px)`;
      }
      ticking = false;
    });
  });

  document.addEventListener('mouseleave', () => {
    document.querySelectorAll('.stat-card').forEach(c => {
      c.style.transition = 'transform 0.4s ease, box-shadow 0.35s';
      c.style.transform  = '';
    });
    document.querySelectorAll('.card, .form-card').forEach(c => {
      c.style.transition = 'transform 0.4s ease';
      c.style.transform  = '';
    });
    const icon = document.querySelector('.brand-icon');
    if (icon) icon.style.transform = '';
  });

  /* ── Sidebar nav hover ripple ── */
  document.querySelectorAll('.sidebar nav a').forEach(link => {
    link.addEventListener('mouseenter', function() {
      this.style.transition = 'all 0.25s cubic-bezier(.4,0,.2,1)';
    });
  });

  /* ── Scroll-driven particle depth ── */
  window.addEventListener('scroll', () => {
    const sy = window.scrollY;
    const pl = document.querySelector('.particles-layer');
    if (pl) pl.style.transform = `translateY(${sy * 0.15}px)`;
    const bl = document.querySelector('.bubbles-layer');
    if (bl) bl.style.transform = `translateY(${sy * 0.08}px)`;
  }, { passive: true });

  /* ── Stagger card reveal on load ── */
  window.addEventListener('load', () => {
    document.querySelectorAll('.stat-card, .card, .form-card').forEach((el, i) => {
      el.style.animationDelay = (i * 0.07) + 's';
    });
  });
})();
</script>
</body>
</html>