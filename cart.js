document.addEventListener('DOMContentLoaded', () => {
  // Elements
  const cartTableBody = document.querySelector('#cartTableBody');
  const cartEmptyState = document.querySelector('#cartEmptyState');
  const cartActiveContent = document.querySelector('#cartActiveContent');
  const cartBadge = document.querySelector('#cartBadge');
  const cartSubtotalVal = document.querySelector('#cartSubtotalVal');
  const cartGrandTotalVal = document.querySelector('#cartGrandTotalVal');
  const clearCartBtn = document.querySelector('#clearCartBtn');
  const checkoutForm = document.querySelector('#checkoutForm');
  const submitOrderBtn = document.querySelector('#submitOrderBtn');
  const cartWhatsAppOrderBtn = document.querySelector('#cartWhatsAppOrderBtn');
  const shippingRadios = document.querySelectorAll('input[name="shipping_option"]');

  // Success Modal
  const orderSuccessModal = document.querySelector('#orderSuccessModal');
  const modalOrderNumber = document.querySelector('#modalOrderNumber');
  const modalOrderSummaryText = document.querySelector('#modalOrderSummaryText');

  let cart = loadCart();

  function loadCart() {
    try {
      const data = localStorage.getItem('prague_shop_cart');
      const items = data ? JSON.parse(data) : [];
      // Safety guard: coming soon products must not accept payment or order dispatch
      return items.filter(item => {
        const avail = (item.availability || 'available').toLowerCase();
        return avail !== 'coming_soon';
      });
    } catch (e) {
      console.error('Error loading cart:', e);
      return [];
    }
  }

  function saveCart() {
    try {
      localStorage.setItem('prague_shop_cart', JSON.stringify(cart));
    } catch (e) {
      console.error('Error saving cart:', e);
    }
  }

  function getShippingCost() {
    const selected = document.querySelector('input[name="shipping_option"]:checked');
    return selected ? parseFloat(selected.value) || 0 : 0;
  }

  function getShippingMethodName() {
    const selected = document.querySelector('input[name="shipping_option"]:checked');
    return selected ? selected.getAttribute('data-name') || 'Standard' : 'Standard';
  }

  function renderCart() {
    const totalCount = cart.reduce((sum, item) => sum + (parseInt(item.qty, 10) || 1), 0);
    if (cartBadge) cartBadge.textContent = String(totalCount);

    if (cart.length === 0) {
      if (cartEmptyState) cartEmptyState.style.display = 'block';
      if (cartActiveContent) cartActiveContent.style.display = 'none';
      return;
    }

    if (cartEmptyState) cartEmptyState.style.display = 'none';
    if (cartActiveContent) cartActiveContent.style.display = 'grid';

    let subtotal = 0;

    if (cartTableBody) {
      cartTableBody.innerHTML = cart.map(item => {
        const price = parseFloat(item.price) || 0;
        const qty = parseInt(item.qty, 10) || 1;
        const lineTotal = price * qty;
        subtotal += lineTotal;

        const imgPath = item.image || 'img/shop/prod-1.jpg';

        return `
          <tr data-id="${item.id}">
            <td class="cart-col-remove">
              <button type="button" class="cart-remove-btn" data-id="${item.id}" aria-label="Remove ${item.name}">&times;</button>
            </td>
            <td class="cart-col-thumb">
              <div class="cart-thumb-box">
                <img src="${imgPath}" alt="${item.name}" loading="lazy">
              </div>
            </td>
            <td>
              <a href="product.php?id=${encodeURIComponent(item.id)}" class="cart-prod-title">${item.name}</a>
              <span class="cart-prod-sku">SKU: ${item.sku || 'PR' + item.id}</span>
            </td>
            <td>
              <span class="cart-prod-price">£${price.toFixed(2)}</span>
            </td>
            <td>
              <div class="cart-qty-stepper">
                <button type="button" class="btn-qty-minus" data-id="${item.id}">&minus;</button>
                <input type="number" class="input-qty" data-id="${item.id}" value="${qty}" min="1" max="99">
                <button type="button" class="btn-qty-plus" data-id="${item.id}">&plus;</button>
              </div>
            </td>
            <td style="text-align: right;">
              <span class="cart-prod-subtotal">£${lineTotal.toFixed(2)}</span>
            </td>
          </tr>
        `;
      }).join('');

      // Stepper and Remove event listeners
      cartTableBody.querySelectorAll('.btn-qty-minus').forEach(btn => {
        btn.addEventListener('click', () => updateQty(btn.getAttribute('data-id'), -1));
      });
      cartTableBody.querySelectorAll('.btn-qty-plus').forEach(btn => {
        btn.addEventListener('click', () => updateQty(btn.getAttribute('data-id'), 1));
      });
      cartTableBody.querySelectorAll('.input-qty').forEach(inp => {
        inp.addEventListener('change', () => {
          let val = parseInt(inp.value, 10);
          if (isNaN(val) || val < 1) val = 1;
          setQty(inp.getAttribute('data-id'), val);
        });
      });
      cartTableBody.querySelectorAll('.cart-remove-btn').forEach(btn => {
        btn.addEventListener('click', () => removeItem(btn.getAttribute('data-id')));
      });
    }

    // Update Totals
    const shipping = getShippingCost();
    const grandTotal = subtotal + shipping;

    if (cartSubtotalVal) cartSubtotalVal.textContent = `£${subtotal.toFixed(2)}`;
    if (cartGrandTotalVal) cartGrandTotalVal.textContent = `£${grandTotal.toFixed(2)}`;
  }

  function updateQty(productId, delta) {
    const item = cart.find(i => String(i.id) === String(productId));
    if (!item) return;
    item.qty = (parseInt(item.qty, 10) || 1) + delta;
    if (item.qty <= 0) {
      cart = cart.filter(i => String(i.id) !== String(productId));
    }
    saveCart();
    renderCart();
  }

  function setQty(productId, newQty) {
    const item = cart.find(i => String(i.id) === String(productId));
    if (!item) return;
    item.qty = Math.max(1, newQty);
    saveCart();
    renderCart();
  }

  function removeItem(productId) {
    cart = cart.filter(i => String(i.id) !== String(productId));
    saveCart();
    renderCart();
  }

  if (clearCartBtn) {
    clearCartBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to clear your entire shopping cart?')) {
        cart = [];
        saveCart();
        renderCart();
      }
    });
  }

  // Shipping selection change
  shippingRadios.forEach(radio => {
    radio.addEventListener('change', renderCart);
  });

  // Direct WhatsApp Checkout Order Placement
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (cart.length === 0) {
        alert('Your cart is empty. Please add items from our collection before placing an order.');
        return;
      }

      const custName = document.querySelector('#custName').value.trim();
      const custPhone = document.querySelector('#custPhone').value.trim();
      const custEmail = document.querySelector('#custEmail').value.trim();
      const custAddress = document.querySelector('#custAddress').value.trim();
      const custNotes = document.querySelector('#custNotes').value.trim();

      if (!custName || !custPhone) {
        alert('Please fill in your Full Name and Phone Number.');
        return;
      }

      const shippingCost = getShippingCost();
      const shippingMethod = getShippingMethodName();
      const subtotal = cart.reduce((sum, item) => sum + ((parseFloat(item.price) || 0) * item.qty), 0);
      const grandTotal = subtotal + shippingCost;

      if (submitOrderBtn) {
        submitOrderBtn.disabled = true;
        submitOrderBtn.innerHTML = `
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="animation: spin 1s linear infinite; margin-right: 8px;"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
          Preparing WhatsApp Order...
        `;
      }

      let orderRef = 'URTH-' + new Date().toISOString().slice(2, 10).replace(/-/g, '') + '-' + Math.random().toString(36).substring(2, 6).toUpperCase();
      let totalFormatted = grandTotal.toFixed(2);

      // Record in MySQL backend
      try {
        const payload = {
          customer_name: custName,
          customer_phone: custPhone,
          customer_email: custEmail,
          delivery_address: custAddress,
          order_notes: custNotes,
          shipping_method: shippingMethod,
          shipping_cost: shippingCost,
          items: cart
        };

        const response = await fetch('api/place_order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.status === 'success' && result.order_number) {
          orderRef = result.order_number;
          if (result.total) totalFormatted = result.total;
        }
      } catch (err) {
        console.warn('Could not record order in database, continuing with WhatsApp dispatch:', err);
      }

      // Build structured WhatsApp message with all customer and order details
      const itemsListText = cart.map((i, index) => {
        const p = parseFloat(i.price) || 0;
        const lineTotal = (p * i.qty).toFixed(2);
        return `${index + 1}. *${i.name}*\n   Qty: ${i.qty} | Price: £${p.toFixed(2)} | Subtotal: £${lineTotal}`;
      }).join('\n\n');

      const waMsgLines = [
        `*NEW ORDER - URTH ARCHITECTURE STUDIO*`,
        `*Order Reference:* ${orderRef}`,
        ``,
        `━━━━━━━━━━━━━━━━━━━━`,
        `*CUSTOMER DETAILS*`,
        `━━━━━━━━━━━━━━━━━━━━`,
        `• *Full Name:* ${custName}`,
        `• *Phone / WhatsApp:* ${custPhone}`,
        custEmail ? `• *Email:* ${custEmail}` : null,
        custAddress ? `• *Delivery Address:* ${custAddress}` : null,
        custNotes ? `• *Order Notes / Customizations:* ${custNotes}` : null,
        ``,
        `━━━━━━━━━━━━━━━━━━━━`,
        `*ORDERED ITEMS*`,
        `━━━━━━━━━━━━━━━━━━━━`,
        itemsListText,
        ``,
        `━━━━━━━━━━━━━━━━━━━━`,
        `*PAYMENT & DELIVERY SUMMARY*`,
        `━━━━━━━━━━━━━━━━━━━━`,
        `• *Subtotal:* £${subtotal.toFixed(2)}`,
        `• *Delivery:* ${shippingMethod} (£${shippingCost.toFixed(2)})`,
        `• *Grand Total:* *£${totalFormatted}*`,
        ``,
        `Please confirm availability, dispatch details, and order acceptance. Thank you!`
      ].filter(line => line !== null);

      const waMessage = waMsgLines.join('\n');
      const waUrl = `https://wa.me/94768008687?text=${encodeURIComponent(waMessage)}`;

      // Open WhatsApp chat directly
      const waTab = window.open(waUrl, '_blank');
      if (!waTab) {
        window.location.href = waUrl;
      }

      // Clear cart
      cart = [];
      saveCart();
      renderCart();

      // Show confirmation modal
      if (modalOrderNumber) modalOrderNumber.textContent = orderRef;
      if (modalOrderSummaryText) {
        modalOrderSummaryText.innerHTML = `Order Reference: <strong>${orderRef}</strong> &bull; Total: <strong>£${totalFormatted}</strong><br><span style="color:#25d366; font-size:12px; margin-top:6px; display:inline-block; font-weight:600;">✓ Form details and order summary prepared in WhatsApp</span>`;
      }
      const modalWhatsAppChatBtn = document.querySelector('#modalWhatsAppChatBtn');
      if (modalWhatsAppChatBtn) {
        modalWhatsAppChatBtn.href = waUrl;
      }
      if (orderSuccessModal) {
        orderSuccessModal.classList.add('is-visible');
      }

      // Restore submit button state
      if (submitOrderBtn) {
        submitOrderBtn.disabled = false;
        submitOrderBtn.innerHTML = `
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Send Order via WhatsApp
        `;
      }
    });
  }

  // Initial render
  renderCart();
});
