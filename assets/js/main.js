/**
 * Modern Lanka Motors — Main Frontend JS
 */
(function () {
  'use strict';

  const baseUrl = document.querySelector('script[src*="main.js"]')?.src.replace(/assets\/js\/main\.js.*$/, '') || '';

  // Header scroll effect
  const header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', () => {
      header.classList.toggle('scrolled', window.scrollY > 40);
    }, { passive: true });
  }

  // Mobile menu
  const toggle = document.getElementById('mobileToggle');
  const navLinks = document.getElementById('topNavLinks');
  if (toggle && navLinks) {
    toggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      toggle.classList.toggle('active');
    });
  }

  // Scroll animations
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    },
    { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
  );
  document.querySelectorAll('.animate-on-scroll').forEach((el) => observer.observe(el));

  // Toast auto-dismiss
  const toast = document.querySelector('[data-toast]');
  if (toast) {
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  }

  // Product qty on detail page
  const qtyInput = document.getElementById('productQty');
  if (qtyInput) {
    document.querySelectorAll('.qty-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const max = parseInt(qtyInput.max, 10) || 99;
        let val = parseInt(qtyInput.value, 10) || 1;
        if (btn.dataset.action === 'plus' && val < max) val++;
        if (btn.dataset.action === 'minus' && val > 1) val--;
        qtyInput.value = val;
      });
    });
  }

  // Add to cart (delegated)
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-add-cart');
    if (!btn || btn.disabled) return;

    const productId = btn.dataset.id;
    const qtyEl = document.getElementById('productQty');
    const qty = qtyEl ? parseInt(qtyEl.value, 10) : 1;

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Adding...';

    try {
      const res = await fetch(baseUrl + 'api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'add', product_id: productId, qty }),
      });
      const data = await res.json();

      if (data.success) {
        updateCartBadge(data.cart_count);
        showToast('Added to cart', 'success');
        btn.textContent = 'Added ✓';
        setTimeout(() => { btn.textContent = originalText; btn.disabled = false; }, 1500);
      } else {
        showToast(data.message || 'Could not add to cart', 'error');
        btn.textContent = originalText;
        btn.disabled = false;
      }
    } catch {
      showToast('Network error. Please try again.', 'error');
      btn.textContent = originalText;
      btn.disabled = false;
    }
  });

  function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (badge) badge.textContent = count;
  }

  function showToast(message, type) {
    const existing = document.querySelector('.toast-dynamic');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast toast--${type} toast-dynamic`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      setTimeout(() => toast.remove(), 400);
    }, 3000);
  }

  window.MLM = { updateCartBadge, showToast, baseUrl };
})();
