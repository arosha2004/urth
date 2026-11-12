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
  const checkoutError = document.querySelector('#checkoutError');
  const cartWhatsAppOrderBtn = document.querySelector('#cartWhatsAppOrderBtn');
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
    return 0;
  }

  function getShippingMethodName() {
    return 'Confirm after order review';
  }

  function getCurrencyCode(currency) {
    if (!currency || currency === '£' || currency === 'GBP') return 'LKR';
    const codes = { '£': 'GBP', GBP: 'GBP', '$': 'USD', USD: 'USD', '€': 'EUR', EUR: 'EUR', LKR: 'LKR', 'Rs': 'LKR', 'Rs.': 'LKR' };
    return codes[currency] || String(currency).toUpperCase();
  }

  function formatMoney(amount, currency) {
    const symbols = { GBP: '£', USD: '$', EUR: '€', LKR: 'LKR' };
    const code = getCurrencyCode(currency);
    if (code === 'LKR') {
      return `LKR ${Number(amount).toFixed(2)}`;
    }
    return `${symbols[code] || currency || ''}${Number(amount).toFixed(2)} ${code}`;
  }

  function requiresDeliveryAddress() {
    return cart.some(item => {
      if (['digital_asset', 'course_workshop', 'design_session'].includes(item.product_type)) return false;
      if (item.product_type === 'book_planner') return /print|physical/i.test(item.book_format || '');
      return true;
    });
  }

  function updateCheckoutRequirements() {
    const addressField = document.querySelector('#custAddress');
    const addressLabel = document.querySelector('label[for="custAddress"]');
    if (addressField && addressLabel) {
      const required = requiresDeliveryAddress();
      addressField.required = required;
      addressLabel.textContent = required ? 'Delivery Address *' : 'Delivery Address (not needed for digital products or services)';
    }

    const filesInput = document.querySelector('#customerFiles');
    const filesField = filesInput ? filesInput.closest('.checkout-field') : null;
    if (filesField) {
      filesField.hidden = !cart.some(item => ['furniture_object', 'design_session'].includes(item.product_type));
    }
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
              <span class="cart-prod-price">${formatMoney(price, item.currency)}</span>
            </td>
            <td>
              <div class="cart-qty-stepper">
                <button type="button" class="btn-qty-minus" data-id="${item.id}">&minus;</button>
                <input type="number" class="input-qty" data-id="${item.id}" value="${qty}" min="1" max="99">
                <button type="button" class="btn-qty-plus" data-id="${item.id}">&plus;</button>
              </div>
            </td>
            <td style="text-align: right;">
              <span class="cart-prod-subtotal">${formatMoney(lineTotal, item.currency)}</span>
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

    const currencies = Array.from(new Set(cart.map(item => getCurrencyCode(item.currency))));
    const currency = currencies.length === 1 ? currencies[0] : 'Multiple currencies';
    if (cartSubtotalVal) cartSubtotalVal.textContent = currency === 'Multiple currencies' ? currency : formatMoney(subtotal, currency);
    if (cartGrandTotalVal) cartGrandTotalVal.textContent = currency === 'Multiple currencies'
      ? currency
      : `${formatMoney(grandTotal, currency)} (provisional)`;
    if (submitOrderBtn) {
      submitOrderBtn.disabled = currencies.length > 1;
      submitOrderBtn.title = currencies.length > 1 ? 'Place separate orders for products with different currencies.' : '';
    }
    updateCheckoutRequirements();
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

  // Direct WhatsApp Checkout Order Placement
  if (checkoutForm) {
    checkoutForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (cart.length === 0) {
        if (checkoutError) {
          checkoutError.textContent = 'Your cart is empty. Please add items before placing an order.';
          checkoutError.hidden = false;
        }
        return;
      }
      if (!checkoutForm.reportValidity()) return;

      const currencies = Array.from(new Set(cart.map(item => getCurrencyCode(item.currency))));
      if (currencies.length !== 1) {
        if (checkoutError) {
          checkoutError.textContent = 'Products in different currencies must be ordered separately.';
          checkoutError.hidden = false;
        }
        return;
      }

      const custName = document.querySelector('#custName').value.trim();
      const custPhone = document.querySelector('#custPhone').value.trim();
      const custEmail = document.querySelector('#custEmail').value.trim();
      const custAddress = document.querySelector('#custAddress').value.trim();
      const custNotes = document.querySelector('#custNotes').value.trim();

      const shippingCost = getShippingCost();
      const shippingMethod = getShippingMethodName();
      let subtotal = cart.reduce((sum, item) => sum + ((parseFloat(item.price) || 0) * item.qty), 0);
      let grandTotal = subtotal + shippingCost;

      if (submitOrderBtn) {
        submitOrderBtn.disabled = true;
        submitOrderBtn.innerHTML = `
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="animation: spin 1s linear infinite; margin-right: 8px;"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>
          Submitting Order Request...
        `;
      }

      let orderRef = '';
      let totalFormatted = grandTotal.toFixed(2);
      let orderCurrency = (cart[0] && cart[0].currency && cart[0].currency !== '£' && cart[0].currency !== 'GBP') ? cart[0].currency : 'LKR';
      let emailStatus = 'pending';
      let ordersEmail = 'sales@shop.urthspaces.com';

      if (checkoutError) {
        checkoutError.hidden = true;
        checkoutError.textContent = '';
      }

      try {
        const payload = new FormData();
        payload.append('customer_name', custName);
        payload.append('customer_phone', custPhone);
        payload.append('customer_email', custEmail);
        payload.append('delivery_address', custAddress);
        payload.append('order_notes', custNotes);
        payload.append('shipping_method', shippingMethod);
        payload.append('payment_method', document.querySelector('#custPaymentMethod').value);
        payload.append('terms_accepted', document.querySelector('#orderTermsConsent').checked ? '1' : '0');
        payload.append('website', document.querySelector('#checkoutWebsite').value);
        payload.append('items', JSON.stringify(cart));
        const customerFiles = document.querySelector('#customerFiles');
        if (customerFiles) {
          Array.from(customerFiles.files).forEach(file => payload.append('customer_files[]', file));
        }

        const response = await fetch('api/place_order.php', {
          method: 'POST',
          body: payload
        });

        const result = await response.json().catch(() => null);
        if (!result) {
          throw new Error('The shop is temporarily unavailable. Please try again shortly or contact the studio.');
        }
        if (!response.ok || result.status !== 'success' || !result.order_number) {
          throw new Error(result.message || 'Order could not be submitted.');
        }
        orderRef = result.order_number;
        orderCurrency = result.currency || orderCurrency;
        emailStatus = result.email_status || emailStatus;
        if (result.orders_email) ordersEmail = result.orders_email;
        if (result.subtotal) subtotal = Number(result.subtotal);
        if (result.total) {
          totalFormatted = result.total;
          grandTotal = Number(result.total);
        }
        if (result.items) {
          cart = result.items;
        }
      } catch (err) {
        console.error('Order submission failed:', err);
        if (checkoutError) {
          checkoutError.textContent = err instanceof TypeError
            ? 'We could not reach the shop server. Check your connection and try again.'
            : (err.message || 'We could not submit your order. Please try again or contact the studio.');
          checkoutError.hidden = false;
        }
        if (submitOrderBtn) {
          submitOrderBtn.disabled = false;
          submitOrderBtn.textContent = 'Try Again';
        }
        return;
      }

      // Clear cart
      cart = [];
      saveCart();
      renderCart();

      // Show confirmation modal
      if (modalOrderNumber) modalOrderNumber.textContent = orderRef;
      if (modalOrderSummaryText) {
        modalOrderSummaryText.textContent = `Provisional item total: ${formatMoney(grandTotal, orderCurrency)} • Payment pending. ${emailStatus === 'sent' ? `A confirmation email has been sent to ${custEmail}.` : 'The confirmation email could not be sent yet; URTH will follow up.'}`;
      }
      const whatsAppText = `Hello URTH, I'd like to talk about my order ${orderRef}.`;
      const modalWhatsAppChatBtn = document.querySelector('#modalWhatsAppChatBtn');
      if (modalWhatsAppChatBtn) {
        modalWhatsAppChatBtn.href = `https://wa.me/94768008687?text=${encodeURIComponent(whatsAppText)}`;
      }
      const modalEmailOrderBtn = document.querySelector('#modalEmailOrderBtn');
      if (modalEmailOrderBtn) {
        const emailSubject = `Order ${orderRef}`;
        const emailBody = `Hello URTH,\n\nI'd like to talk about my order ${orderRef}.\n\n\n${custName}\n${custPhone}`;
        modalEmailOrderBtn.href = `mailto:${ordersEmail}?subject=${encodeURIComponent(emailSubject)}&body=${encodeURIComponent(emailBody)}`;
      }
      if (orderSuccessModal) {
        orderSuccessModal.classList.add('is-visible');
      }

      // Restore submit button state
      if (submitOrderBtn) {
        submitOrderBtn.disabled = false;
        submitOrderBtn.innerHTML = `
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Submit Order Request
        `;
      }
    });
  }

  // Initial render
  renderCart();
});
