document.addEventListener('DOMContentLoaded', () => {
  const grid = document.querySelector('#product-grid');
  const cards = Array.from(document.querySelectorAll('.product-card'));
  const filters = Array.from(document.querySelectorAll('.filter-button'));
  const sortControl = document.querySelector('#product-sort');
  const visibleCount = document.querySelector('#visible-count');
  const emptyResults = document.querySelector('#empty-results');
  const bagToggle = document.querySelector('.bag-toggle');
  const panel = document.querySelector('#enquiry-panel');
  const panelClose = document.querySelector('.panel-close');
  const backdrop = document.querySelector('.panel-backdrop');
  const mobileMenu = document.querySelector('.mobile-nav-overlay');
  const mobileMenuToggle = document.querySelector('.mobile-nav-toggle');
  const bagItems = document.querySelector('.bag-items');
  const bagEmpty = document.querySelector('.bag-empty');
  const sendEnquiry = document.querySelector('.send-enquiry');
  const bagCounts = Array.from(document.querySelectorAll('.bag-count, .panel-count'));
  const storageKey = 'urth-shop-enquiry-bag';
  let activeFilter = 'All';
  let enquiryBag = loadBag();
  const hero = document.querySelector('.shop-hero');

  requestAnimationFrame(() => hero.classList.add('is-ready'));

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-entering');
        entry.target.addEventListener('animationend', () => {
          entry.target.classList.remove('is-entering');
        }, { once: true });
        revealObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -35px 0px' });

    cards.forEach((card, index) => {
      card.style.setProperty('--card-delay', `${(index % 4) * 75}ms`);
      revealObserver.observe(card);
    });
  }

  function animateVisibleCards() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    cards.forEach((card, index) => {
      if (card.hidden) return;
      card.classList.remove('is-entering');
      card.style.setProperty('--card-delay', `${(index % 4) * 55}ms`);
      void card.offsetWidth;
      card.classList.add('is-entering');
      card.addEventListener('animationend', () => {
        card.classList.remove('is-entering');
      }, { once: true });
    });
  }

  function loadBag() {
    try {
      const stored = JSON.parse(localStorage.getItem(storageKey) || '[]');
      if (!Array.isArray(stored)) return [];
      return stored.filter(item => item && typeof item.id === 'string' && typeof item.name === 'string' && typeof item.category === 'string');
    } catch (error) {
      console.error('Unable to load the enquiry bag from local storage.', error);
      return [];
    }
  }

  function saveBag() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(enquiryBag));
    } catch (error) {
      console.error('Unable to save the enquiry bag to local storage.', error);
    }
  }

  function updateBag() {
    bagCounts.forEach(count => {
      const previousCount = count.textContent;
      count.textContent = String(enquiryBag.length);
      if (count.classList.contains('bag-count') && previousCount !== String(enquiryBag.length)) {
        count.classList.remove('is-updated');
        void count.offsetWidth;
        count.classList.add('is-updated');
      }
    });
    bagItems.replaceChildren();

    enquiryBag.forEach(item => {
      const row = document.createElement('li');
      row.className = 'bag-item';

      const details = document.createElement('span');
      details.textContent = item.name;
      const category = document.createElement('span');
      category.className = 'bag-item-category';
      category.textContent = item.category;
      details.append(category);

      const remove = document.createElement('button');
      remove.className = 'remove-item';
      remove.type = 'button';
      remove.textContent = 'Remove';
      remove.setAttribute('aria-label', `Remove ${item.name} from enquiry bag`);
      remove.addEventListener('click', () => {
        enquiryBag = enquiryBag.filter(bagItem => bagItem.id !== item.id);
        saveBag();
        updateBag();
      });

      row.append(details, remove);
      bagItems.append(row);
    });

    const isEmpty = enquiryBag.length === 0;
    bagEmpty.hidden = !isEmpty;
    sendEnquiry.classList.toggle('is-disabled', isEmpty);
    sendEnquiry.setAttribute('aria-disabled', String(isEmpty));
    if (!isEmpty) {
      const message = enquiryBag.map(item => `${item.name} (${item.category})`).join(', ');
      sendEnquiry.href = `https://wa.me/94768008687?text=${encodeURIComponent(`Hello URTH, I would like to enquire about: ${message}`)}`;
      sendEnquiry.tabIndex = 0;
    } else {
      sendEnquiry.removeAttribute('href');
      sendEnquiry.tabIndex = -1;
    }
  }

  function filterAndSort() {
    const sort = sortControl.value;
    const orderedCards = [...cards];

    if (sort === 'name-asc') {
      orderedCards.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
    } else if (sort === 'name-desc') {
      orderedCards.sort((a, b) => b.dataset.name.localeCompare(a.dataset.name));
    }

    orderedCards.forEach(card => grid.append(card));

    let shown = 0;
    cards.forEach(card => {
      const matchesFilter = activeFilter === 'All' || card.dataset.category === activeFilter;
      const isVisible = matchesFilter;
      card.hidden = !isVisible;
      if (isVisible) shown += 1;
    });

    visibleCount.textContent = String(shown);
    emptyResults.hidden = shown !== 0;
    animateVisibleCards();
  }

  function openPanel() {
    panel.classList.add('is-open');
    panel.setAttribute('aria-hidden', 'false');
    bagToggle.setAttribute('aria-expanded', 'true');
    backdrop.hidden = false;
    panelClose.focus();
  }

  function closePanel() {
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    bagToggle.setAttribute('aria-expanded', 'false');
    backdrop.hidden = true;
    bagToggle.focus();
  }

  filters.forEach(button => {
    button.addEventListener('click', () => {
      activeFilter = button.dataset.filter;
      filters.forEach(filter => {
        const isActive = filter === button;
        filter.classList.toggle('is-active', isActive);
        filter.setAttribute('aria-pressed', String(isActive));
      });
      filterAndSort();
    });
  });

  sortControl.addEventListener('change', filterAndSort);
  bagToggle.addEventListener('click', openPanel);
  panelClose.addEventListener('click', closePanel);
  backdrop.addEventListener('click', closePanel);
  mobileMenuToggle.addEventListener('click', () => {
    const isOpen = mobileMenu.classList.contains('active');
    mobileMenu.setAttribute('aria-hidden', String(!isOpen));
    mobileMenuToggle.setAttribute('aria-expanded', String(isOpen));
    mobileMenuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (panel.classList.contains('is-open')) {
      closePanel();
    } else if (mobileMenu.classList.contains('active')) {
      mobileMenuToggle.click();
    }
  });

  cards.forEach(card => {
    const addButton = card.querySelector('.add-to-bag');
    addButton.addEventListener('click', () => {
      const item = {
        id: card.dataset.productId,
        name: card.dataset.name,
        category: card.dataset.category,
      };
      if (!enquiryBag.some(bagItem => bagItem.id === item.id)) enquiryBag.push(item);
      saveBag();
      updateBag();
      openPanel();
    });
  });

  updateBag();
  filterAndSort();
});
