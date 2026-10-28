document.addEventListener('DOMContentLoaded', () => {
  const currentProduct = window.CURRENT_PRODUCT || null;

  // Gallery Thumbnails
  const mainProductImg = document.querySelector('#mainProductImg');
  const thumbBtns = document.querySelectorAll('.gallery-thumb-btn');

  thumbBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      thumbBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const newSrc = btn.getAttribute('data-img');
      if (mainProductImg && newSrc) {
        mainProductImg.style.opacity = '0.4';
        setTimeout(() => {
          mainProductImg.src = newSrc;
          mainProductImg.style.opacity = '1';
        }, 150);
      }
    });
  });

  // Available Options Selector
  let selectedOption = '';
  const optionPills = document.querySelectorAll('.option-pill-btn');
  const selectedOptionText = document.querySelector('#selectedOptionText');

  if (optionPills.length > 0) {
    selectedOption = optionPills[0].getAttribute('data-value') || '';
    optionPills.forEach(pill => {
      pill.addEventListener('click', () => {
        optionPills.forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        selectedOption = pill.getAttribute('data-value') || '';
        if (selectedOptionText) selectedOptionText.textContent = selectedOption;
      });
    });
  }

  // Quantity Stepper
  const qtyMinus = document.querySelector('#qtyMinus');
  const qtyPlus = document.querySelector('#qtyPlus');
  const productQtyInput = document.querySelector('#productQty');

  if (qtyMinus && productQtyInput) {
    qtyMinus.addEventListener('click', () => {
      let val = parseInt(productQtyInput.value, 10) || 1;
      if (val > 1) productQtyInput.value = val - 1;
    });
  }
  if (qtyPlus && productQtyInput) {
    qtyPlus.addEventListener('click', () => {
      let val = parseInt(productQtyInput.value, 10) || 1;
      if (val < 99) productQtyInput.value = val + 1;
    });
  }

  // Tabs Switching
  const tabBtns = document.querySelectorAll('.product-tab-btn');
  const tabPanels = document.querySelectorAll('.product-tab-panel');

  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-tab');
      tabBtns.forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      tabPanels.forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      btn.setAttribute('aria-selected', 'true');
      const targetPanel = document.querySelector(`#${targetId}`);
      if (targetPanel) targetPanel.classList.add('active');
    });
  });

  // Notice Bar Helpers
  const cartNotice = document.querySelector('#cartNotice');
  const noticeCloseBtn = document.querySelector('#noticeCloseBtn');
  if (noticeCloseBtn && cartNotice) {
    noticeCloseBtn.addEventListener('click', () => {
      cartNotice.style.display = 'none';
    });
  }

  // Cart Management
  let cart = loadCart();
  const cartBadge = document.querySelector('#cartBadge');
  const cartDrawer = document.querySelector('#cartDrawer');
  const cartBackdrop = document.querySelector('#cartBackdrop');
  const cartTrigger = document.querySelector('#cartTrigger');
  const cartCloseBtn = document.querySelector('#cartCloseBtn');
  const cartItemsList = document.querySelector('#cartItemsList');
  const cartEmptyState = document.querySelector('#cartEmptyState');
  const cartDrawerFooter = document.querySelector('#cartDrawerFooter');
  const cartSubtotalAmount = document.querySelector('#cartSubtotalAmount');
  const addToCartBtn = document.querySelector('#addToCartBtn');

  function loadCart() {
    try {
      const data = localStorage.getItem('prague_shop_cart');
      return data ? JSON.parse(data) : [];
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

  function openCart() {
    renderCartUI();
    if (cartDrawer) {
      cartDrawer.classList.add('is-open');
      cartDrawer.setAttribute('aria-hidden', 'false');
    }
    if (cartBackdrop) {
      cartBackdrop.hidden = false;
      cartBackdrop.classList.add('is-visible');
    }
    document.body.style.overflow = 'hidden';
  }

  function closeCart() {
    if (cartDrawer) {
      cartDrawer.classList.remove('is-open');
      cartDrawer.setAttribute('aria-hidden', 'true');
    }
    if (cartBackdrop) {
      cartBackdrop.classList.remove('is-visible');
      setTimeout(() => {
        cartBackdrop.hidden = true;
      }, 300);
    }
    document.body.style.overflow = '';
  }

  if (cartTrigger) cartTrigger.addEventListener('click', openCart);
  if (cartCloseBtn) cartCloseBtn.addEventListener('click', closeCart);
  if (cartBackdrop) cartBackdrop.addEventListener('click', closeCart);

  function renderCartUI() {
    const totalCount = cart.reduce((sum, item) => sum + (parseInt(item.qty, 10) || 1), 0);
    if (cartBadge) cartBadge.textContent = String(totalCount);
    const drawerCount = document.querySelector('.cart-drawer-count');
    if (drawerCount) drawerCount.textContent = `(${totalCount})`;

    if (cart.length === 0) {
      if (cartItemsList) cartItemsList.innerHTML = '';
      if (cartEmptyState) cartEmptyState.hidden = false;
      if (cartDrawerFooter) cartDrawerFooter.hidden = true;
      return;
    }

    if (cartEmptyState) cartEmptyState.hidden = true;
    if (cartDrawerFooter) cartDrawerFooter.hidden = false;

    let subtotal = 0;
    if (cartItemsList) {
      cartItemsList.innerHTML = cart.map(item => {
        const itemPrice = parseFloat(item.price) || 0;
        const itemQty = parseInt(item.qty, 10) || 1;
        subtotal += itemPrice * itemQty;
        const optText = item.option ? `<span style="font-size: 11px; color: #888; display: block; margin-top: 2px;">Option: ${item.option}</span>` : '';

        return `
          <li class="cart-item" data-id="${item.id}" data-opt="${encodeURIComponent(item.option || '')}">
            <div class="cart-item-image">
              <img src="${item.image}" alt="${item.name}">
            </div>
            <div class="cart-item-details">
              <h4 class="cart-item-title"><a href="product.php?id=${item.id}" style="color:inherit; text-decoration:none;">${item.name}</a></h4>
              ${optText}
              <div class="cart-item-price">${item.currency || '£'}${itemPrice.toFixed(2)}</div>
              <div class="cart-item-qty-row">
                <button type="button" class="drawer-qty-btn drawer-qty-minus" data-id="${item.id}" data-opt="${encodeURIComponent(item.option || '')}">&minus;</button>
                <span class="drawer-qty-val">${itemQty}</span>
                <button type="button" class="drawer-qty-btn drawer-qty-plus" data-id="${item.id}" data-opt="${encodeURIComponent(item.option || '')}">&plus;</button>
              </div>
            </div>
            <button type="button" class="cart-item-remove" data-id="${item.id}" data-opt="${encodeURIComponent(item.option || '')}" aria-label="Remove item">&times;</button>
          </li>
        `;
      }).join('');

      // Stepper & Remove listeners in drawer
      cartItemsList.querySelectorAll('.drawer-qty-minus').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const opt = decodeURIComponent(btn.getAttribute('data-opt') || '');
          const idx = cart.findIndex(i => String(i.id) === String(id) && (i.option || '') === opt);
          if (idx !== -1) {
            if (cart[idx].qty > 1) {
              cart[idx].qty--;
            } else {
              cart.splice(idx, 1);
            }
            saveCart();
            renderCartUI();
          }
        });
      });

      cartItemsList.querySelectorAll('.drawer-qty-plus').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const opt = decodeURIComponent(btn.getAttribute('data-opt') || '');
          const item = cart.find(i => String(i.id) === String(id) && (i.option || '') === opt);
          if (item) {
            item.qty++;
            saveCart();
            renderCartUI();
          }
        });
      });

      cartItemsList.querySelectorAll('.cart-item-remove').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const opt = decodeURIComponent(btn.getAttribute('data-opt') || '');
          cart = cart.filter(i => !(String(i.id) === String(id) && (i.option || '') === opt));
          saveCart();
          renderCartUI();
        });
      });
    }

    if (cartSubtotalAmount) {
      const curr = (cart[0] && cart[0].currency) ? cart[0].currency : '£';
      cartSubtotalAmount.textContent = `${curr}${subtotal.toFixed(2)}`;
    }
  }

  // Initial cart count render
  renderCartUI();

  // Add to Cart Action
  if (addToCartBtn && currentProduct) {
    addToCartBtn.addEventListener('click', () => {
      const qty = parseInt(productQtyInput ? productQtyInput.value : 1, 10) || 1;
      const opt = selectedOption || '';

      const existingIndex = cart.findIndex(item => String(item.id) === String(currentProduct.id) && (item.option || '') === opt);
      if (existingIndex !== -1) {
        cart[existingIndex].qty += qty;
      } else {
        cart.push({
          id: String(currentProduct.id),
          name: currentProduct.name,
          sku: currentProduct.sku,
          price: parseFloat(currentProduct.price) || 0,
          currency: currentProduct.currency || '£',
          image: currentProduct.image,
          product_type: currentProduct.product_type || 'furniture_object',
          book_format: currentProduct.book_format || '',
          option: opt,
          qty: qty
        });
      }

      saveCart();
      renderCartUI();

      // Show notice
      if (cartNotice) {
        cartNotice.style.display = 'flex';
        cartNotice.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }

      // Open drawer after a brief moment
      setTimeout(() => {
        openCart();
      }, 400);
    });
  }

  // WhatsApp Inquiry Logic
  function buildWhatsAppUrl() {
    const phone = "94768008687";
    const title = currentProduct ? currentProduct.name : "URTH Piece";
    const sku = currentProduct ? currentProduct.sku : "";
    const opt = selectedOption ? `\n• Selected Option: ${selectedOption}` : "";
    const priceText = currentProduct ? `\n• Price: ${currentProduct.formattedPrice}` : "";
    const url = window.location.href;

    const message = `Hello URTH Studio,\n\nI would like to inquire about this piece:\n• Title: ${title}\n• SKU: ${sku}${priceText}${opt}\n• Link: ${url}\n\nCould you please advise on lead time and order arrangements?`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
  }

  const inquireWhatsAppBtn = document.querySelector('#inquireWhatsAppBtn');
  if (inquireWhatsAppBtn) {
    inquireWhatsAppBtn.addEventListener('click', (e) => {
      e.preventDefault();
      window.open(buildWhatsAppUrl(), '_blank');
    });
  }

  // Share Product Logic
  const shareProductBtn = document.querySelector('#shareProductBtn');
  const shareToast = document.querySelector('#shareToast');
  const shareToastMessage = document.querySelector('#shareToastMessage');

  function showToast(msg) {
    if (shareToast) {
      if (shareToastMessage) shareToastMessage.textContent = msg;
      shareToast.classList.add('show');
      setTimeout(() => {
        shareToast.classList.remove('show');
      }, 3000);
    }
  }

  if (shareProductBtn && currentProduct) {
    shareProductBtn.addEventListener('click', async () => {
      const shareData = {
        title: `${currentProduct.name} — URTH Studio`,
        text: `Explore "${currentProduct.name}" by URTH Architecture Studio.`,
        url: window.location.href
      };

      if (navigator.share && /mobile|android|iphone/i.test(navigator.userAgent)) {
        try {
          await navigator.share(shareData);
          return;
        } catch (err) {
          // User cancelled or unsupported, fallback to copy
        }
      }

      // Clipboard fallback
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(window.location.href).then(() => {
          showToast('Shareable product link copied to clipboard!');
        }).catch(() => {
          showToast('Link copied: ' + window.location.href);
        });
      } else {
        showToast('Link: ' + window.location.href);
      }
    });
  }
});
