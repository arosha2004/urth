  document.addEventListener('DOMContentLoaded', () => {
    // Load products dynamically from window.INITIAL_PRODUCTS (rendered from MySQL)
    let allProducts = Array.isArray(window.INITIAL_PRODUCTS) && window.INITIAL_PRODUCTS.length > 0
      ? window.INITIAL_PRODUCTS
      : [];

    // If initial products weren't injected, fallback to API fetch
    if (allProducts.length === 0) {
      fetch('api/get_products.php')
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success' && Array.isArray(data.products)) {
            allProducts = data.products;
            renderProducts();
          }
        })
        .catch(err => console.error('Error fetching products:', err));
    }

    // State
    let currentPage = 1;
    const itemsPerPage = 8;
    let currentSort = 'menu_order';
    let currentCategory = 'all';
    let currentAvailability = 'all';
    let currentLabel = 'all';
    let searchQuery = '';
    let cart = loadCart();

    // Elements
    const productsGrid = document.querySelector('#productsGrid');
    const resultCount = document.querySelector('#resultCount');
    const orderBySelect = document.querySelector('#orderBySelect');
    const paginationNav = document.querySelector('#paginationNav');
    const paginationList = document.querySelector('#paginationList');
    const cartTrigger = document.querySelector('#cartTrigger');
    const cartBadge = document.querySelector('#cartBadge');
    const cartDrawer = document.querySelector('#cartDrawer');
    const cartBackdrop = document.querySelector('#cartBackdrop');
    const cartCloseBtn = document.querySelector('#cartCloseBtn');
    const cartItemsList = document.querySelector('#cartItemsList');
    const cartEmptyState = document.querySelector('#cartEmptyState');
    const cartDrawerFooter = document.querySelector('#cartDrawerFooter');
    const cartSubtotalAmount = document.querySelector('#cartSubtotalAmount');
    const cartCheckoutBtn = document.querySelector('#cartCheckoutBtn');
    const cartNotice = document.querySelector('#cartNotice');
    const noticeMessage = document.querySelector('#noticeMessage');
    const noticeCartBtn = document.querySelector('#noticeCartBtn');
    const noticeCloseBtn = document.querySelector('#noticeCloseBtn');

    // Search & Filter Toolbar Elements
    const shopSearchInput = document.querySelector('#shopSearchInput');
    const shopSearchClear = document.querySelector('#shopSearchClear');
    const availabilityFilter = document.querySelector('#availabilityFilter');
    const labelFilter = document.querySelector('#labelFilter');

    // Product Modal Elements
    const productModalBackdrop = document.querySelector('#productModalBackdrop');
    const productModalClose = document.querySelector('#productModalClose');
    const modalProductImage = document.querySelector('#modalProductImage');
    const modalProductCategory = document.querySelector('#modalProductCategory');
    const modalProductFormat = document.querySelector('#modalProductFormat');
    const modalProductAvail = document.querySelector('#modalProductAvail');
    const modalAvailWarning = document.querySelector('#modalAvailWarning');
    const modalProductTitle = document.querySelector('#modalProductTitle');
    const modalProductPrice = document.querySelector('#modalProductPrice');
    const modalProductSku = document.querySelector('#modalProductSku');
    const modalProductDesc = document.querySelector('#modalProductDesc');
    const modalActionsWrap = document.querySelector('#modalActionsWrap');
    const modalQtyWrap = document.querySelector('#modalQtyWrap');
    const modalQtyInput = document.querySelector('#modalQtyInput');
    const modalQtyMinus = document.querySelector('#modalQtyMinus');
    const modalQtyPlus = document.querySelector('#modalQtyPlus');
    const modalAddToCartBtn = document.querySelector('#modalAddToCartBtn');
    const modalWhatsAppBtn = document.querySelector('#modalWhatsAppBtn');
    let activeModalProduct = null;

    // Helper: Normalize category strings into URL/slug format (matching PHP slugify)
    function slugify(text) {
      return (text || '').toString().toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    }

    // Category matching helper: matches by slug, name, or normalized slug (with or without 'and')
    function matchesCategory(prodCategory, targetCategory) {
      if (!targetCategory || targetCategory === 'all') return true;
      if (!prodCategory) return false;

      const pCatLower = prodCategory.toLowerCase().trim();
      const tCatLower = targetCategory.toLowerCase().trim();
      if (pCatLower === tCatLower) return true;

      const pSlug = slugify(prodCategory);
      const tSlug = slugify(targetCategory);
      if (pSlug === tSlug) return true;

      // Handle variations with or without "-and-" vs "-"
      const pNorm = pSlug.replace(/-and-/g, '-');
      const tNorm = tSlug.replace(/-and-/g, '-');
      if (pNorm === tNorm) return true;

      // Match against categories list if available
      const catList = Array.isArray(window.INITIAL_CATEGORIES) ? window.INITIAL_CATEGORIES : [];
      for (const c of catList) {
        if (!c) continue;
        const cSlug = (c.slug || '').toLowerCase();
        const cName = (c.name || '').toLowerCase();
        if (cSlug === tSlug || cSlug === targetCategory.toLowerCase() || cName === tCatLower) {
          if (pCatLower === cName || pSlug === cSlug || pNorm === cSlug.replace(/-and-/g, '-')) {
            return true;
          }
        }
      }

      return false;
    }

    // Sorting & Filtering (Category, Search, Availability, Format Label)
    function getSortedProducts() {
      let list = [...allProducts];

      // Filter by search query
      if (searchQuery) {
        const q = searchQuery.toLowerCase().trim();
        list = list.filter(p => {
          const nameMatch = (p.name || '').toLowerCase().includes(q);
          const descMatch = (p.description || '').toLowerCase().includes(q);
          const skuMatch = (p.sku || '').toLowerCase().includes(q);
          const catMatch = (p.category || '').toLowerCase().includes(q);
          const labelMatch = (p.product_label || '').toLowerCase().includes(q);
          return nameMatch || descMatch || skuMatch || catMatch || labelMatch;
        });
      }

      // Filter by category
      if (currentCategory && currentCategory !== 'all') {
        list = list.filter(p => matchesCategory(p.category, currentCategory));
      }

      // Filter by availability
      if (currentAvailability && currentAvailability !== 'all') {
        list = list.filter(p => {
          const pAvail = (p.availability || 'available').toLowerCase().replace('-', '_');
          return pAvail === currentAvailability.toLowerCase().replace('-', '_');
        });
      }

      // Filter by format label
      if (currentLabel && currentLabel !== 'all') {
        list = list.filter(p => {
          return (p.product_label || '').toLowerCase() === currentLabel.toLowerCase();
        });
      }

      switch (currentSort) {
        case 'popularity':
          return list.sort((a, b) => (b.popularity || 0) - (a.popularity || 0));
        case 'date':
          return list.sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
        case 'price-asc':
          return list.sort((a, b) => (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0));
        case 'price-desc':
          return list.sort((a, b) => (parseFloat(b.price) || 0) - (parseFloat(a.price) || 0));
        case 'name':
          return list.sort((a, b) => (a.name || '').localeCompare(b.name || '', undefined, { sensitivity: 'base' }));
        case 'menu_order':
        default:
          return list.sort((a, b) => (a.display_order || 0) - (b.display_order || 0));
      }
    }

    // Update Category Badge Counts dynamically
    function updateCategoryBadges() {
      const totalCount = allProducts.length;
      const allBadge = document.querySelector('#catCountAll');
      if (allBadge) allBadge.textContent = String(totalCount);

      document.querySelectorAll('.category-pill-btn[data-category]').forEach(btn => {
        const catKey = btn.getAttribute('data-category');
        if (catKey === 'all') return;
        const catName = btn.getAttribute('data-name') || btn.querySelector('.cat-name')?.textContent || '';
        const count = allProducts.filter(p => matchesCategory(p.category, catKey) || (catName && matchesCategory(p.category, catName))).length;
        const badge = btn.querySelector('.cat-badge');
        if (badge) badge.textContent = String(count);
      });
    }

    // Switch Active Category
    window.setCategory = function(catKey) {
      currentCategory = catKey || 'all';
      currentPage = 1;

      document.querySelectorAll('.category-pill-btn').forEach(btn => {
        const bCat = btn.getAttribute('data-category');
        const bName = btn.getAttribute('data-name') || btn.querySelector('.cat-name')?.textContent || '';
        if (currentCategory === 'all') {
          if (bCat === 'all') {
            btn.classList.add('active');
          } else {
            btn.classList.remove('active');
          }
        } else {
          if (bCat === currentCategory || matchesCategory(bName, currentCategory) || matchesCategory(bCat, currentCategory)) {
            btn.classList.add('active');
          } else {
            btn.classList.remove('active');
          }
        }
      });

      renderProducts();

      if (window.history && window.history.replaceState) {
        const newQuery = currentCategory === 'all' ? '' : ('?category=' + encodeURIComponent(currentCategory));
        window.history.replaceState(null, null, window.location.pathname + newQuery);
      }
    };

    // Render Products
    function renderProducts() {
      if (!productsGrid) return;

      const sorted = getSortedProducts();
      const totalItems = sorted.length;

      if (totalItems === 0) {
        const activeBtn = document.querySelector(`.category-pill-btn.active`);
        const catName = activeBtn ? (activeBtn.querySelector('.cat-name') ? activeBtn.querySelector('.cat-name').textContent : activeBtn.textContent.trim()) : 'this category';
        productsGrid.innerHTML = `
          <li style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #888;">
            <h3 style="font-family: var(--prague-font); font-size: 20px; color: #111; margin-bottom: 8px;">No products currently in ${catName}</h3>
            <p style="font-size: 14px; color: #666; max-width: 480px; margin: 0 auto 24px; line-height: 1.6;">We are currently curating new additions for this collection. Please check back soon or browse all our available pieces.</p>
            <button type="button" onclick="window.setCategory('all')" style="display: inline-block; padding: 12px 26px; background-color: #111; color: #fff; border: 1px solid #111; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; font-weight: 600; cursor: pointer; transition: all 0.25s;">
              Browse All Products
            </button>
          </li>
        `;
        if (resultCount) resultCount.textContent = '0 products found';
        if (paginationNav) paginationNav.style.display = 'none';
        return;
      }

      const totalPages = Math.ceil(totalItems / itemsPerPage);
      if (currentPage > totalPages) currentPage = totalPages;
      if (currentPage < 1) currentPage = 1;

      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
      const visibleProducts = sorted.slice(startIndex, endIndex);

      // Update Result Count text
      if (resultCount) {
        resultCount.textContent = `${totalItems} ${totalItems === 1 ? 'piece' : 'pieces'}`;
      }

      // Render Grid
      productsGrid.innerHTML = visibleProducts.map(p => {
        const avail = (p.availability || 'available').toLowerCase();
        const availClass = 'avail-' + avail.replace('_', '-');
        let availText = 'Available';
        if (avail === 'made_to_order') availText = 'Made to Order';
        else if (avail === 'coming_soon') availText = 'Coming Soon';
        else if (avail === 'unavailable') availText = 'Unavailable';

        let priceHtml = '';
        if (p.price_on_request || parseFloat(p.price) <= 0) {
          priceHtml = `<span class="price-on-request">Price on request</span>`;
        } else if (p.sale_price) {
          priceHtml = `
            <span class="price">
              <del style="opacity: 0.5; margin-right: 5px;">${p.formattedPrice || ('LKR ' + parseFloat(p.price).toFixed(2))}</del>
              <ins style="text-decoration: none; font-weight: 700;">${p.formattedSalePrice}</ins>
            </span>
          `;
        } else {
          priceHtml = `<span class="price">${p.formattedPrice || ('LKR ' + parseFloat(p.price).toFixed(2))}</span>`;
        }

        let btnHtml = '';
        if (avail === 'coming_soon') {
          btnHtml = `<button type="button" class="button btn-coming-soon product-add" disabled aria-disabled="true" title="Coming soon — payments are not accepted for upcoming items">+</button>`;
        } else if (avail === 'unavailable') {
          btnHtml = `<button type="button" class="button btn-unavailable product-add" disabled aria-disabled="true">+</button>`;
        } else if (p.price_on_request) {
          btnHtml = `<button type="button" class="button btn-inquire product-add" onclick="window.inquireProduct('${p.id}')" aria-label="Inquire about ${p.name}">+</button>`;
        } else {
          btnHtml = `<button type="button" class="button add_to_cart_button product-add" data-id="${p.id}" aria-label="Add &ldquo;${p.name}&rdquo; to your bag">+</button>`;
        }

        return `
          <li class="product-item" data-id="${p.id}">
            <div class="product-image-box">
              <a href="product.php?id=${encodeURIComponent(p.id)}" class="product-thumb-link">
                <img src="${p.image}" alt="${p.name}" loading="lazy" width="300" height="300">
              </a>
              <span class="product-ref">${p.sku || ('PR' + p.id)}</span>
              <button type="button" class="product-quick-view" onclick="window.openProductModal('${p.id}')">Quick view</button>
              ${btnHtml}
            </div>
            <div class="product-card-caption">
              <span class="product-cat-tag">${p.category || 'Furniture & Objects'}</span>
              <a href="product.php?id=${encodeURIComponent(p.id)}">
                <h3 class="woocommerce-loop-product__title">${p.name}</h3>
              </a>
              ${priceHtml}
            </div>
          </li>
        `;
      }).join('');

      // Attach Add to Cart listeners
      productsGrid.querySelectorAll('.add_to_cart_button').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const id = btn.getAttribute('data-id');
          addToCart(id, 1, btn);
        });
      });

      // Render Pagination
      renderPagination(totalPages);
    }

    // Render Pagination
    function renderPagination(totalPages) {
      if (!paginationNav || !paginationList) return;

      if (totalPages <= 1) {
        paginationNav.style.display = 'none';
        return;
      }
      paginationNav.style.display = '';

      let html = '';
      for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
          html += `<li><span class="page-numbers current" aria-current="page">${i}</span></li>`;
        } else {
          html += `<li><button type="button" class="page-numbers" data-page="${i}">${i}</button></li>`;
        }
      }
      if (currentPage < totalPages) {
        html += `<li><button type="button" class="page-numbers next" data-page="next" aria-label="Next page">&rarr;</button></li>`;
      } else if (currentPage > 1) {
        html = `<li><button type="button" class="page-numbers prev" data-page="prev" aria-label="Previous page">&larr;</button></li>` + html;
      }
      paginationList.innerHTML = html;

      paginationList.querySelectorAll('button[data-page]').forEach(btn => {
        btn.addEventListener('click', () => {
          const p = btn.getAttribute('data-page');
          if (p === 'next') {
            currentPage++;
          } else if (p === 'prev') {
            currentPage--;
          } else {
            currentPage = parseInt(p, 10);
          }
          renderProducts();
          const shopMain = document.querySelector('#shopMain');
          if (shopMain) {
            shopMain.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      });
    }

    // Bind category button clicks
    document.querySelectorAll('.category-pill-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const cat = btn.getAttribute('data-category');
        window.setCategory(cat);
      });
    });

    // Check URL query param or hash on initial load
    const urlParams = new URLSearchParams(window.location.search);
    const initialCatParam = urlParams.get('category') || (window.location.hash ? window.location.hash.replace('#', '') : null);
    if (initialCatParam) {
      window.setCategory(initialCatParam);
    } else {
      updateCategoryBadges();
    }

    // Sort change listener
    if (orderBySelect) {
      orderBySelect.addEventListener('change', () => {
        currentSort = orderBySelect.value;
        currentPage = 1;
        renderProducts();
      });
    }

    // Search input listener
    if (shopSearchInput) {
      shopSearchInput.addEventListener('input', (e) => {
        searchQuery = e.target.value.trim();
        if (shopSearchClear) {
          shopSearchClear.style.display = searchQuery ? 'block' : 'none';
        }
        currentPage = 1;
        renderProducts();
      });
    }

    // Search clear listener
    if (shopSearchClear) {
      shopSearchClear.addEventListener('click', () => {
        if (shopSearchInput) shopSearchInput.value = '';
        searchQuery = '';
        shopSearchClear.style.display = 'none';
        currentPage = 1;
        renderProducts();
        if (shopSearchInput) shopSearchInput.focus();
      });
    }

    // Availability filter listener
    if (availabilityFilter) {
      availabilityFilter.addEventListener('change', (e) => {
        currentAvailability = e.target.value;
        currentPage = 1;
        renderProducts();
      });
    }

    // Format label filter listener
    if (labelFilter) {
      labelFilter.addEventListener('change', (e) => {
        currentLabel = e.target.value;
        currentPage = 1;
        renderProducts();
      });
    }

    // ==========================================================================
    // PRODUCT QUICK VIEW / DETAILS MODAL
    // Inquire Product direct WhatsApp handler
    window.inquireProduct = function(productId) {
      const prod = allProducts.find(p => String(p.id) === String(productId));
      if (!prod) return;
      const msg = encodeURIComponent(`Hello URTH Studio,\n\nI would like to inquire regarding pricing and specifications for:\n- Product: ${prod.name} (SKU: ${prod.sku || 'N/A'})\n- Category: ${prod.category || 'N/A'}\n\nPlease share price and acquisition details.`);
      window.open(`https://wa.me/94768008687?text=${msg}`, '_blank');
    };

    // ==========================================================================
    // PRODUCT DETAILS QUICK VIEW MODAL
    // ==========================================================================
    window.openProductModal = function(productId) {
      const prod = allProducts.find(p => String(p.id) === String(productId));
      if (!prod) return;

      activeModalProduct = prod;

      if (modalProductImage) {
        modalProductImage.src = prod.image;
        modalProductImage.alt = prod.name;
      }
      if (modalProductCategory) {
        modalProductCategory.textContent = prod.category || 'Accessories';
      }
      if (modalProductTitle) {
        modalProductTitle.textContent = prod.name;
      }

      // Availability check & warning
      const avail = (prod.availability || 'available').toLowerCase();
      const availText = avail === 'made_to_order' ? 'Made to Order'
        : avail === 'coming_soon' ? 'Coming Soon'
          : avail === 'unavailable' ? 'Unavailable'
            : 'Available';

      if (modalAvailWarning) {
        modalAvailWarning.className = 'modal-avail-warning';
        if (avail === 'coming_soon') {
          modalAvailWarning.classList.add('coming-soon');
          modalAvailWarning.textContent = 'Coming Soon: This product is currently in studio preparation. In accordance with studio policy, orders and payments cannot be accepted at this time.';
        } else if (avail === 'unavailable') {
          modalAvailWarning.classList.add('unavailable');
          modalAvailWarning.textContent = 'This product is currently unavailable / out of stock.';
        } else {
          modalAvailWarning.textContent = '';
        }
      }

      if (modalAddToCartBtn) {
        if (avail === 'coming_soon') {
          modalAddToCartBtn.disabled = true;
          modalAddToCartBtn.style.opacity = '0.6';
          modalAddToCartBtn.style.cursor = 'not-allowed';
          modalAddToCartBtn.textContent = 'COMING SOON (NO PAYMENT)';
          if (modalQtyWrap) modalQtyWrap.style.display = 'none';
        } else if (avail === 'unavailable') {
          modalAddToCartBtn.disabled = true;
          modalAddToCartBtn.style.opacity = '0.6';
          modalAddToCartBtn.style.cursor = 'not-allowed';
          modalAddToCartBtn.textContent = 'OUT OF STOCK';
          if (modalQtyWrap) modalQtyWrap.style.display = 'none';
        } else {
          modalAddToCartBtn.disabled = false;
          modalAddToCartBtn.style.opacity = '1';
          modalAddToCartBtn.style.cursor = 'pointer';
          modalAddToCartBtn.textContent = 'ADD TO CART';
          if (modalQtyWrap) modalQtyWrap.style.display = 'flex';
        }
      }

      if (modalProductPrice) {
        if (prod.price_on_request || parseFloat(prod.price) <= 0) {
          modalProductPrice.innerHTML = '<span class="price-on-request">Price on request</span>';
        } else if (prod.sale_price) {
          modalProductPrice.innerHTML = `<del style="opacity: 0.5; font-size: 16px; margin-right: 8px;">${prod.formattedPrice}</del>${prod.formattedSalePrice}`;
        } else {
          modalProductPrice.textContent = prod.formattedPrice || ('LKR ' + parseFloat(prod.price).toFixed(2));
        }
      }
      if (modalProductSku) {
        modalProductSku.textContent = 'SKU: ' + (prod.sku || 'PR' + prod.id);
      }
      if (modalProductDesc) {
        modalProductDesc.textContent = prod.description || 'Modern architecture and design piece handcrafted with minimalist aesthetics and premium materials.';
      }
      if (modalQtyInput) {
        modalQtyInput.value = 1;
      }

      // Direct WhatsApp link
      if (modalWhatsAppBtn) {
        const pText = prod.price_on_request ? 'Price on request' : (prod.formattedPrice || ('LKR ' + prod.price));
        const msg = encodeURIComponent(`Hello URTH Studio,\n\nI would like to inquire about the following product:\n- Product: ${prod.name} (SKU: ${prod.sku || 'N/A'})\n- Price: ${pText}\n- Availability: ${availText}\n\nCould you please share acquisition and delivery details?`);
        modalWhatsAppBtn.href = `https://wa.me/94768008687?text=${msg}`;
      }

      if (productModalBackdrop) {
        productModalBackdrop.hidden = false;
        void productModalBackdrop.offsetWidth;
        productModalBackdrop.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
      }
    };

    window.closeProductModal = function() {
      if (productModalBackdrop) {
        productModalBackdrop.classList.remove('is-visible');
        document.body.style.overflow = '';
        setTimeout(() => {
          productModalBackdrop.hidden = true;
        }, 300);
      }
    };

    if (productModalClose) {
      productModalClose.addEventListener('click', window.closeProductModal);
    }
    if (productModalBackdrop) {
      productModalBackdrop.addEventListener('click', (e) => {
        if (e.target === productModalBackdrop) {
          window.closeProductModal();
        }
      });
    }
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && productModalBackdrop && productModalBackdrop.classList.contains('is-visible')) {
        window.closeProductModal();
      }
    });

    // Modal Qty buttons
    if (modalQtyMinus) {
      modalQtyMinus.addEventListener('click', () => {
        if (!modalQtyInput) return;
        let val = parseInt(modalQtyInput.value, 10) || 1;
        if (val > 1) {
          modalQtyInput.value = val - 1;
        }
      });
    }
    if (modalQtyPlus) {
      modalQtyPlus.addEventListener('click', () => {
        if (!modalQtyInput) return;
        let val = parseInt(modalQtyInput.value, 10) || 1;
        modalQtyInput.value = val + 1;
      });
    }

    // Modal Add to Cart
    if (modalAddToCartBtn) {
      modalAddToCartBtn.addEventListener('click', () => {
        if (!activeModalProduct) return;
        const qty = parseInt(modalQtyInput ? modalQtyInput.value : 1, 10) || 1;
        addToCart(activeModalProduct.id, qty, modalAddToCartBtn);
        setTimeout(() => {
          window.closeProductModal();
          openCart();
        }, 500);
      });
    }

    // ==========================================================================
    // SHOPPING CART & STORAGE
    // ==========================================================================
    function loadCart() {
      try {
        const data = localStorage.getItem('prague_shop_cart');
        return data ? JSON.parse(data) : [];
      } catch (e) {
        console.error(e);
        return [];
      }
    }

    function saveCart() {
      try {
        localStorage.setItem('prague_shop_cart', JSON.stringify(cart));
      } catch (e) {
        console.error(e);
      }
    }

    function updateCartUI() {
      const totalCount = cart.reduce((sum, item) => sum + item.qty, 0);
      if (cartBadge) cartBadge.textContent = String(totalCount);
      const drawerCount = document.querySelector('.cart-drawer-count');
      if (drawerCount) drawerCount.textContent = `(${totalCount})`;

      if (cart.length === 0) {
        if (cartItemsList) cartItemsList.innerHTML = '';
        if (cartEmptyState) cartEmptyState.hidden = false;
        if (cartDrawerFooter) cartDrawerFooter.hidden = true;
      } else {
        if (cartEmptyState) cartEmptyState.hidden = true;
        if (cartDrawerFooter) cartDrawerFooter.hidden = false;

        let subtotal = 0;
        if (cartItemsList) {
          cartItemsList.innerHTML = cart.map(item => {
            const itemPrice = parseFloat(item.price) || 0;
            subtotal += itemPrice * item.qty;
            return `
              <li class="cart-item" data-id="${item.id}">
                <div class="cart-item-img">
                  <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="cart-item-info">
                  <div class="cart-item-title">${item.name}</div>
                  <div class="cart-item-price">${item.qty} &times; ${item.formattedPrice || ('LKR ' + itemPrice.toFixed(2))}</div>
                  <div class="cart-item-qty">
                    <button type="button" class="qty-btn qty-minus" data-id="${item.id}">&minus;</button>
                    <span class="qty-count">${item.qty}</span>
                    <button type="button" class="qty-btn qty-plus" data-id="${item.id}">&plus;</button>
                  </div>
                </div>
                <button type="button" class="cart-item-remove" data-id="${item.id}" aria-label="Remove item">&times;</button>
              </li>
            `;
          }).join('');

          // Quantity controls
          cartItemsList.querySelectorAll('.qty-minus').forEach(b => {
            b.addEventListener('click', () => changeQty(b.getAttribute('data-id'), -1));
          });
          cartItemsList.querySelectorAll('.qty-plus').forEach(b => {
            b.addEventListener('click', () => changeQty(b.getAttribute('data-id'), 1));
          });
          cartItemsList.querySelectorAll('.cart-item-remove').forEach(b => {
            b.addEventListener('click', () => removeFromCart(b.getAttribute('data-id')));
          });
        }

        if (cartSubtotalAmount) {
          cartSubtotalAmount.textContent = `LKR ${subtotal.toFixed(2)}`;
        }

        // WhatsApp Enquiry Link
        if (cartCheckoutBtn) {
          const orderSummary = cart.map(i => `${i.name} (Qty: ${i.qty}) - LKR ${((parseFloat(i.price) || 0) * i.qty).toFixed(2)}`).join('%0A');
          const whatsappMsg = `Hello URTH Studio,%0A%0AI would like to inquire about ordering the following furniture pieces:%0A${orderSummary}%0A%0ATotal: LKR ${subtotal.toFixed(2)}`;
          cartCheckoutBtn.href = `https://wa.me/94768008687?text=${whatsappMsg}`;
        }
      }
    }

    function addToCart(productId, quantity = 1, btnElement = null) {
      const prod = allProducts.find(p => String(p.id) === String(productId));
      if (!prod) return;

      const avail = (prod.availability || 'available').toLowerCase();
      // Safety guard: Coming soon products must not accept payment or ordering
      if (avail === 'coming_soon') {
        alert(`"${prod.name}" is coming soon. Payments and orders cannot be accepted at this time.`);
        return;
      }
      if (avail === 'unavailable') {
        alert(`"${prod.name}" is currently unavailable.`);
        return;
      }
      if (prod.price_on_request) {
        window.inquireProduct(prod.id);
        return;
      }

      const qtyToAdd = Math.max(1, parseInt(quantity, 10) || 1);
      const existing = cart.find(i => String(i.id) === String(productId));
      if (existing) {
        existing.qty += qtyToAdd;
      } else {
        cart.push({ ...prod, qty: qtyToAdd });
      }
      saveCart();
      updateCartUI();

      // Button feedback
      if (btnElement) {
        const isIconButton = btnElement.classList.contains('product-add');

        // Clear any existing reset timer if user clicks repeatedly
        if (btnElement._resetTimer) {
          clearTimeout(btnElement._resetTimer);
        } else {
          btnElement._originalContent = isIconButton ? '+' : btnElement.textContent.trim();
        }

        // Re-trigger animation cleanly even on fast repeated clicks
        btnElement.classList.remove('is-added');
        void btnElement.offsetWidth; // Force DOM reflow to restart CSS animation
        btnElement.classList.add('is-added');

        if (isIconButton) {
          btnElement.innerHTML = '<svg class="add-check-icon" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';
          btnElement.setAttribute('aria-label', 'Added to bag');
        } else {
          btnElement.textContent = 'ADDED ✓';
        }

        btnElement._resetTimer = setTimeout(() => {
          btnElement.classList.remove('is-added');
          if (isIconButton) {
            btnElement.textContent = '+';
            btnElement.setAttribute('aria-label', `Add “${prod.name}” to your bag`);
          } else {
            btnElement.textContent = btnElement._originalContent || 'ADD TO CART';
          }
          btnElement._resetTimer = null;
          btnElement._originalContent = null;
        }, 1500);
      }

      // Notice notification
      if (cartNotice && noticeMessage) {
        noticeMessage.innerHTML = `&ldquo;${prod.name}&rdquo; has been added to your cart.`;
        cartNotice.hidden = false;
        cartNotice.removeAttribute('hidden');
        cartNotice.classList.add('is-visible');
        cartNotice.style.display = 'flex';

        clearTimeout(window._cartNoticeTimer);
        window._cartNoticeTimer = setTimeout(() => {
          cartNotice.classList.remove('is-visible');
          cartNotice.style.display = 'none';
          cartNotice.hidden = true;
        }, 5000);
      }
    }

    if (noticeCloseBtn && cartNotice) {
      noticeCloseBtn.addEventListener('click', () => {
        clearTimeout(window._cartNoticeTimer);
        cartNotice.classList.remove('is-visible');
        cartNotice.style.display = 'none';
        cartNotice.hidden = true;
      });
    }

    function changeQty(productId, delta) {
      const item = cart.find(i => String(i.id) === String(productId));
      if (!item) return;
      item.qty += delta;
      if (item.qty <= 0) {
        cart = cart.filter(i => String(i.id) !== String(productId));
      }
      saveCart();
      updateCartUI();
    }

    function removeFromCart(productId) {
      cart = cart.filter(i => String(i.id) !== String(productId));
      saveCart();
      updateCartUI();
    }

    // Cart Drawer open/close
    function openCart() {
      if (!cartDrawer || !cartBackdrop) return;
      cartDrawer.classList.add('is-open');
      cartBackdrop.hidden = false;
      void cartBackdrop.offsetWidth;
      cartBackdrop.classList.add('is-visible');
      document.body.style.overflow = 'hidden';
    }

    function closeCart() {
      if (!cartDrawer || !cartBackdrop) return;
      cartDrawer.classList.remove('is-open');
      cartBackdrop.classList.remove('is-visible');
      document.body.style.overflow = '';
      setTimeout(() => {
        cartBackdrop.hidden = true;
      }, 300);
    }

    if (cartTrigger) cartTrigger.addEventListener('click', openCart);
    if (cartCloseBtn) cartCloseBtn.addEventListener('click', closeCart);
    if (cartBackdrop) cartBackdrop.addEventListener('click', closeCart);

    const cartBrowseBtn = document.querySelector('#cartBrowseBtn');
    if (cartBrowseBtn) {
      cartBrowseBtn.addEventListener('click', (e) => {
        e.preventDefault();
        closeCart();
        const shopMain = document.querySelector('#shopMain');
        if (shopMain) shopMain.scrollIntoView({ behavior: 'smooth' });
      });
    }

    // Initial render
    renderProducts();
    updateCartUI();
  });

  // "Order with the studio" steps: show one detail panel per selected step
  document.addEventListener('DOMContentLoaded', () => {
    const tabs = Array.from(document.querySelectorAll('#orderingProcess .order-step[role="tab"]'));
    if (!tabs.length) return;

    const selectStep = (tab, focus) => {
      tabs.forEach(t => {
        const active = t === tab;
        t.classList.toggle('is-active', active);
        t.setAttribute('aria-selected', active ? 'true' : 'false');
        t.tabIndex = active ? 0 : -1;
        const panel = document.getElementById(t.getAttribute('aria-controls'));
        if (panel) {
          panel.hidden = !active;
          panel.classList.toggle('is-active', active);
        }
      });
      if (focus) tab.focus();
    };

    tabs.forEach((tab, i) => {
      tab.addEventListener('click', () => selectStep(tab, false));
      tab.addEventListener('keydown', e => {
        let next = null;
        if (e.key === 'ArrowDown' || e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
        else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
        else if (e.key === 'Home') next = tabs[0];
        else if (e.key === 'End') next = tabs[tabs.length - 1];
        if (next) {
          e.preventDefault();
          selectStep(next, true);
        }
      });
    });
  });
