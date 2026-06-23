/**
 * Cart page interactions
 */
(function () {
  'use strict';

  const baseUrl = window.MLM?.baseUrl || '';

  async function cartRequest(action, productId, qty) {
    const res = await fetch(baseUrl + 'api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, product_id: productId, qty }),
    });
    return res.json();
  }

  function formatPrice(amount) {
    return 'Rs. ' + Number(amount).toLocaleString('en-LK', { maximumFractionDigits: 0 });
  }

  document.querySelectorAll('.cart-qty-btn').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const input = document.querySelector(`.cart-qty-input[data-id="${id}"]`);
      if (!input) return;

      let qty = parseInt(input.value, 10) || 1;
      const max = parseInt(input.max, 10) || 99;

      if (btn.dataset.action === 'plus' && qty < max) qty++;
      if (btn.dataset.action === 'minus' && qty > 1) qty--;

      input.value = qty;
      await updateCartItem(id, qty);
    });
  });

  document.querySelectorAll('.cart-qty-input').forEach((input) => {
    input.addEventListener('change', async () => {
      const id = input.dataset.id;
      let qty = parseInt(input.value, 10) || 1;
      const max = parseInt(input.max, 10) || 99;
      qty = Math.max(1, Math.min(qty, max));
      input.value = qty;
      await updateCartItem(id, qty);
    });
  });

  document.querySelectorAll('.cart-remove').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      const data = await cartRequest('remove', id);
      if (data.success) {
        document.querySelector(`[data-cart-item="${id}"]`)?.remove();
        window.MLM?.updateCartBadge(data.cart_count);
        updateTotals(data.subtotal);
        if (data.cart_count === 0) location.reload();
      }
    });
  });

  async function updateCartItem(id, qty) {
    const data = await cartRequest('update', id, qty);
    if (data.success) {
      window.MLM?.updateCartBadge(data.cart_count);
      updateTotals(data.subtotal);
      const row = document.querySelector(`[data-cart-item="${id}"]`);
      const priceText = row?.querySelector('.cart-item__price')?.textContent;
      const unitMatch = priceText?.match(/[\d,]+/);
      if (unitMatch && row) {
        const unit = parseFloat(unitMatch[0].replace(/,/g, ''));
        const lineTotal = row.querySelector('.line-total');
        if (lineTotal) lineTotal.textContent = formatPrice(unit * qty);
      }
    }
  }

  function updateTotals(subtotal) {
    const formatted = formatPrice(subtotal);
    document.getElementById('cartSubtotal')?.textContent && (document.getElementById('cartSubtotal').textContent = formatted);
    document.getElementById('cartTotal')?.textContent && (document.getElementById('cartTotal').textContent = formatted);
  }
})();
