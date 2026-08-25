document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.password-field').forEach((field) => {
    const input = field.querySelector('input');
    const toggle = field.querySelector('.password-toggle');
    if (!input || !toggle) return;

    toggle.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      field.classList.toggle('is-visible', show);
      toggle.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
    });
  });

  const navToggle = document.getElementById('navToggle');
  const siteNav = document.getElementById('siteNav');
  if (navToggle && siteNav) {
    navToggle.addEventListener('click', () => {
      siteNav.classList.toggle('open');
    });
  }

  const cartToggle = document.getElementById('cartToggle');
  const cartDrawer = document.getElementById('cartDrawer');
  const cartOverlay = document.getElementById('cartOverlay');
  const cartCloseBtn = document.getElementById('cartCloseBtn');

  function setCartOpen(open) {
    if (!cartDrawer || !cartOverlay || !cartToggle) return;
    cartDrawer.classList.toggle('open', open);
    cartOverlay.classList.toggle('open', open);
    cartDrawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    cartToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (cartToggle && cartDrawer && cartOverlay) {
    cartToggle.addEventListener('click', () => {
      const isOpen = cartDrawer.classList.contains('open');
      setCartOpen(!isOpen);
    });
    cartCloseBtn?.addEventListener('click', () => setCartOpen(false));
    cartOverlay.addEventListener('click', () => setCartOpen(false));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setCartOpen(false);
    });
  }

  const courseCards = document.querySelectorAll('.course-card');
  const searchInput = document.getElementById('courseSearch');
  const categorySelect = document.getElementById('courseCategorySelect');
  const typeSelect = document.getElementById('courseTypeSelect');
  const clearBtn = document.getElementById('courseClearBtn');

  function applyCourseFilters() {
    const term = (searchInput?.value ?? '').trim().toLowerCase();
    const cat = categorySelect?.value ?? 'all';
    const type = typeSelect?.value ?? '';

    courseCards.forEach((card) => {
      const cardCat = card.dataset.category;
      const cardType = card.dataset.type ?? 'recorded';
      const text = (card.innerText ?? '').toLowerCase();

      const matchCat = cat === 'all' || cardCat === cat;
      const matchType = type === '' || cardType === type;
      const matchTerm = term === '' || text.includes(term);

      card.classList.toggle('is-hidden', !(matchCat && matchType && matchTerm));
    });
  }

  searchInput?.addEventListener('input', applyCourseFilters);
  categorySelect?.addEventListener('change', applyCourseFilters);
  typeSelect?.addEventListener('change', applyCourseFilters);
  clearBtn?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (categorySelect) categorySelect.value = 'all';
    if (typeSelect) typeSelect.value = '';
    applyCourseFilters();
  });

  applyCourseFilters();

  const heroSlider = document.getElementById('heroSlider');
  if (heroSlider) {
    const track = heroSlider.querySelector('.hero-slider-track');
    const slides = heroSlider.querySelectorAll('.hero-slide');
    const prevBtn = heroSlider.querySelector('.hero-slider-prev');
    const nextBtn = heroSlider.querySelector('.hero-slider-next');
    const dotsWrap = heroSlider.querySelector('.hero-slider-dots');
    let index = 0;
    const total = slides.length;
    let autoplayTimer = null;

    slides.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      dot.className = 'hero-slider-dot' + (i === 0 ? ' is-active' : '');
      dot.setAttribute('role', 'tab');
      dot.setAttribute('aria-label', `สไลด์ที่ ${i + 1}`);
      dot.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
      dot.addEventListener('click', () => {
        goTo(i);
        resetAutoplay();
      });
      dotsWrap?.appendChild(dot);
    });

    const dots = dotsWrap?.querySelectorAll('.hero-slider-dot') ?? [];

    function goTo(i) {
      index = (i + total) % total;
      track.style.transform = `translate3d(-${index * 100}%, 0, 0)`;
      slides.forEach((slide, si) => slide.classList.toggle('is-active', si === index));
      dots.forEach((dot, di) => {
        const active = di === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
    }

    function resetAutoplay() {
      if (autoplayTimer) window.clearInterval(autoplayTimer);
      autoplayTimer = window.setInterval(() => goTo(index + 1), 6000);
    }

    prevBtn?.addEventListener('click', () => {
      goTo(index - 1);
      resetAutoplay();
    });
    nextBtn?.addEventListener('click', () => {
      goTo(index + 1);
      resetAutoplay();
    });

    let touchStartX = 0;
    heroSlider.querySelector('.hero-slider-viewport')?.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0]?.screenX ?? 0;
    }, { passive: true });
    heroSlider.querySelector('.hero-slider-viewport')?.addEventListener('touchend', (e) => {
      const diff = (e.changedTouches[0]?.screenX ?? 0) - touchStartX;
      if (Math.abs(diff) > 50) {
        goTo(index + (diff < 0 ? 1 : -1));
        resetAutoplay();
      }
    }, { passive: true });

    heroSlider.addEventListener('mouseenter', () => {
      if (autoplayTimer) window.clearInterval(autoplayTimer);
    });
    heroSlider.addEventListener('mouseleave', resetAutoplay);

    goTo(0);
    resetAutoplay();
  }

  const reviewsSlider = document.getElementById('reviewsSlider');
  if (reviewsSlider) {
    const track = reviewsSlider.querySelector('.reviews-slider-track');
    const cards = [...reviewsSlider.querySelectorAll('.review-card')];
    const prevBtn = reviewsSlider.querySelector('.reviews-slider-prev');
    const nextBtn = reviewsSlider.querySelector('.reviews-slider-next');
    const dotsWrap = reviewsSlider.querySelector('.reviews-slider-dots');
    let index = 0;
    let autoplayTimer = null;

    const mobileOnly = reviewsSlider.classList.contains('reviews-slider--mobile');

    function isActive() {
      return !mobileOnly || window.matchMedia('(max-width: 768px)').matches;
    }

    function visibleCount() {
      const w = window.innerWidth;
      if (w <= 640) return 1;
      if (w <= 960) return 2;
      return 3;
    }

    function maxIndex() {
      return Math.max(0, cards.length - visibleCount());
    }

    function stepSize() {
      const gap = parseFloat(getComputedStyle(track).gap) || 0;
      const cardWidth = cards[0]?.getBoundingClientRect().width ?? 0;
      return cardWidth + gap;
    }

    function renderDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      const pages = maxIndex() + 1;
      for (let i = 0; i < pages; i++) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'reviews-slider-dot' + (i === index ? ' is-active' : '');
        dot.setAttribute('role', 'tab');
        dot.setAttribute('aria-label', `รีวิวหน้าที่ ${i + 1}`);
        dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
        dot.addEventListener('click', () => {
          index = i;
          update();
          resetAutoplay();
        });
        dotsWrap.appendChild(dot);
      }
      reviewsSlider.classList.toggle('is-static', !isActive() || maxIndex() === 0);
    }

    function update() {
      if (!isActive()) {
        track.style.transform = '';
        return;
      }
      index = Math.min(index, maxIndex());
      track.style.transform = `translate3d(-${index * stepSize()}px, 0, 0)`;
      dotsWrap?.querySelectorAll('.reviews-slider-dot').forEach((dot, i) => {
        const active = i === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
    }

    function go(delta) {
      if (!isActive()) return;
      index = Math.max(0, Math.min(maxIndex(), index + delta));
      update();
    }

    function resetAutoplay() {
      if (autoplayTimer) window.clearInterval(autoplayTimer);
      if (isActive() && maxIndex() > 0) {
        autoplayTimer = window.setInterval(() => {
          go(index >= maxIndex() ? -maxIndex() : 1);
        }, 5500);
      }
    }

    prevBtn?.addEventListener('click', () => {
      go(-1);
      resetAutoplay();
    });
    nextBtn?.addEventListener('click', () => {
      go(1);
      resetAutoplay();
    });

    let touchStartX = 0;
    reviewsSlider.querySelector('.reviews-slider-viewport')?.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0]?.screenX ?? 0;
    }, { passive: true });
    reviewsSlider.querySelector('.reviews-slider-viewport')?.addEventListener('touchend', (e) => {
      const diff = (e.changedTouches[0]?.screenX ?? 0) - touchStartX;
      if (Math.abs(diff) > 50) {
        go(diff < 0 ? 1 : -1);
        resetAutoplay();
      }
    }, { passive: true });

    reviewsSlider.addEventListener('mouseenter', () => {
      if (autoplayTimer) window.clearInterval(autoplayTimer);
    });
    reviewsSlider.addEventListener('mouseleave', resetAutoplay);

    let resizeTimer;
    window.addEventListener('resize', () => {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(() => {
        renderDots();
        update();
      }, 150);
    });

    renderDots();
    update();
    resetAutoplay();
  }

  const coursesSlider = document.getElementById('coursesSlider');
  if (coursesSlider) {
    const track = coursesSlider.querySelector('.reviews-slider-track');
    const cards = [...coursesSlider.querySelectorAll('.course-card')];
    const prevBtn = coursesSlider.querySelector('.reviews-slider-prev');
    const nextBtn = coursesSlider.querySelector('.reviews-slider-next');
    const dotsWrap = coursesSlider.querySelector('.reviews-slider-dots');
    let index = 0;
    let autoplayTimer = null;

    function visibleCount() {
      const w = window.innerWidth;
      if (w <= 520) return 1;
      if (w <= 900) return 2;
      if (w <= 1100) return 3;
      return 4;
    }

    function maxIndex() {
      return Math.max(0, cards.length - visibleCount());
    }

    function stepSize() {
      const gap = parseFloat(getComputedStyle(track).gap) || 0;
      const cardWidth = cards[0]?.getBoundingClientRect().width ?? 0;
      return cardWidth + gap;
    }

    function renderDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = '';
      const pages = maxIndex() + 1;
      for (let i = 0; i < pages; i++) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'reviews-slider-dot' + (i === index ? ' is-active' : '');
        dot.setAttribute('role', 'tab');
        dot.setAttribute('aria-label', `คอร์สชุดที่ ${i + 1}`);
        dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
        dot.addEventListener('click', () => {
          index = i;
          update();
          resetAutoplay();
        });
        dotsWrap.appendChild(dot);
      }
      coursesSlider.classList.toggle('is-static', maxIndex() === 0);
    }

    function update() {
      index = ((index % (maxIndex() + 1)) + (maxIndex() + 1)) % (maxIndex() + 1);
      track.style.transform = `translate3d(-${index * stepSize()}px, 0, 0)`;
      dotsWrap?.querySelectorAll('.reviews-slider-dot').forEach((dot, i) => {
        const active = i === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
    }

    function go(delta) {
      if (maxIndex() === 0) return;
      index += delta;
      if (index > maxIndex()) index = 0;
      if (index < 0) index = maxIndex();
      update();
    }

    function resetAutoplay() {
      if (autoplayTimer) window.clearInterval(autoplayTimer);
      if (maxIndex() > 0) {
        autoplayTimer = window.setInterval(() => go(1), 4200);
      }
    }

    prevBtn?.addEventListener('click', () => {
      go(-1);
      resetAutoplay();
    });
    nextBtn?.addEventListener('click', () => {
      go(1);
      resetAutoplay();
    });

    let touchStartX = 0;
    coursesSlider.querySelector('.reviews-slider-viewport')?.addEventListener('touchstart', (e) => {
      touchStartX = e.changedTouches[0]?.screenX ?? 0;
    }, { passive: true });
    coursesSlider.querySelector('.reviews-slider-viewport')?.addEventListener('touchend', (e) => {
      const diff = (e.changedTouches[0]?.screenX ?? 0) - touchStartX;
      if (Math.abs(diff) > 50) {
        go(diff < 0 ? 1 : -1);
        resetAutoplay();
      }
    }, { passive: true });

    coursesSlider.addEventListener('mouseenter', () => {
      if (autoplayTimer) window.clearInterval(autoplayTimer);
    });
    coursesSlider.addEventListener('mouseleave', resetAutoplay);

    let resizeTimer;
    window.addEventListener('resize', () => {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(() => {
        renderDots();
        update();
      }, 150);
    });

    renderDots();
    update();
    resetAutoplay();
  }

  function escapeHtml(text) {
    const el = document.createElement('div');
    el.textContent = text ?? '';
    return el.innerHTML;
  }

  function showToast(message) {
    if (!message) return;
    let stack = document.getElementById('toastStack');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'toastStack';
      stack.className = 'toast-stack';
      stack.setAttribute('aria-live', 'polite');
      stack.setAttribute('aria-atomic', 'true');
      document.body.appendChild(stack);
    }
    const toast = document.createElement('div');
    toast.className = 'toast toast-success';
    toast.setAttribute('data-toast', '');
    toast.setAttribute('role', 'status');
    toast.innerHTML = `
      <span class="toast-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M20 6 9 17l-5-5"/>
        </svg>
      </span>
      <span class="toast-text">${escapeHtml(message)}</span>
    `;
    stack.appendChild(toast);
    const hideAfter = 3800;
    const removeAfter = 4200;
    window.setTimeout(() => toast.classList.add('hide'), hideAfter);
    window.setTimeout(() => {
      toast.remove();
      if (!stack.querySelector('.toast')) stack.remove();
    }, removeAfter);
  }

  function updateCartUI(data) {
    const countEl = document.getElementById('cartCount');
    if (countEl) countEl.textContent = String(data.count ?? 0);

    const body = document.querySelector('.cart-drawer-body');
    if (body) {
      const items = data.items ?? [];
      if (!items.length) {
        body.innerHTML = '<p class="cart-empty">ยังไม่มีคอร์สในตะกร้า</p>';
      } else {
        const list = items.map((item) => `
          <li class="cart-item">
            <div class="cart-item-main">
              <div class="cart-item-title">${escapeHtml(item.title)}</div>
              <div class="cart-item-price">${escapeHtml(item.price)}</div>
            </div>
            <a class="cart-remove" href="${escapeHtml(item.removeUrl)}" title="นำออกจากตะกร้า">ลบ</a>
          </li>
        `).join('');
        body.innerHTML = `<ul class="cart-list">${list}</ul>`;
      }
    }

    const totalEl = document.querySelector('.cart-total-row strong');
    if (totalEl && data.total) totalEl.textContent = data.total;

    const checkout = document.querySelector('.cart-checkout');
    if (checkout) {
      const hasItems = (data.count ?? 0) > 0;
      checkout.style.pointerEvents = hasItems ? '' : 'none';
      checkout.style.opacity = hasItems ? '' : '.6';
      checkout.setAttribute('aria-disabled', hasItems ? 'false' : 'true');
    }
  }

  document.querySelectorAll('.js-cart-add').forEach((link) => {
    link.addEventListener('click', async (e) => {
      e.preventDefault();
      if (link.dataset.loading === '1') return;
      link.dataset.loading = '1';

      const url = new URL(link.href, window.location.origin);
      url.searchParams.set('ajax', '1');

      try {
        const res = await fetch(url.toString(), {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
        const data = await res.json();
        if (data.ok) {
          updateCartUI(data);
          showToast(data.message);
        } else {
          showToast(data.message || 'ไม่สามารถเพิ่มลงตะกร้าได้');
        }
      } catch {
        window.location.href = link.getAttribute('href') || url.pathname;
      } finally {
        delete link.dataset.loading;
      }
    });
  });

  document.querySelectorAll('[data-toast]').forEach((toast) => {
    const hideAfter = 3800;
    const removeAfter = 4200;
    window.setTimeout(() => toast.classList.add('hide'), hideAfter);
    window.setTimeout(() => {
      toast.remove();
      const stack = document.getElementById('toastStack');
      if (stack && !stack.querySelector('.toast')) stack.remove();
    }, removeAfter);
  });

  document.querySelectorAll('.js-copy-bank').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const text = btn.dataset.copy || '';
      if (!text) return;
      const label = btn.querySelector('.checkout-bank-copy-label');
      const prev = label ? label.textContent : btn.textContent;
      try {
        await navigator.clipboard.writeText(text);
        if (label) {
          label.textContent = 'คัดลอกแล้ว';
        } else {
          btn.textContent = 'คัดลอกแล้ว';
        }
        window.setTimeout(() => {
          if (label) {
            label.textContent = prev;
          } else {
            btn.textContent = prev;
          }
        }, 1800);
      } catch {
        window.prompt('คัดลอกเลขบัญชี:', text);
      }
    });
  });

  const slipInput = document.getElementById('slip_image');
  const slipDrop = document.getElementById('slipDropZone');
  const slipName = document.getElementById('slipFileName');
  if (slipInput && slipDrop) {
    const showFile = () => {
      const file = slipInput.files?.[0];
      if (file && slipName) {
        slipName.textContent = file.name;
        slipName.hidden = false;
        slipDrop.querySelector('.checkout-slip-placeholder')?.setAttribute('hidden', '');
      }
    };
    slipInput.addEventListener('change', showFile);
    ['dragenter', 'dragover'].forEach((ev) => {
      slipDrop.addEventListener(ev, (e) => {
        e.preventDefault();
        slipDrop.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach((ev) => {
      slipDrop.addEventListener(ev, (e) => {
        e.preventDefault();
        slipDrop.classList.remove('is-dragover');
      });
    });
    slipDrop.addEventListener('drop', (e) => {
      const file = e.dataTransfer?.files?.[0];
      if (file) {
        slipInput.files = e.dataTransfer.files;
        showFile();
      }
    });
  }

  const courseSelect = document.getElementById('course_id');
  const amountInput = document.getElementById('amount');
  const totalDisplay = document.getElementById('checkoutTotalDisplay');
  if (courseSelect && amountInput) {
    courseSelect.addEventListener('change', () => {
      const opt = courseSelect.selectedOptions[0];
      const price = opt?.dataset.price;
      if (price && parseFloat(price) > 0) {
        amountInput.value = price;
        if (totalDisplay) {
          totalDisplay.textContent = `${Number(price).toLocaleString('th-TH')} บาท`;
        }
      }
    });
  }

  const contactFab = document.getElementById('contactFab');
  const contactFabTrigger = document.getElementById('contactFabTrigger');
  const contactFabPanel = document.getElementById('contactFabPanel');
  const contactFabClose = document.getElementById('contactFabClose');
  const contactFabBubble = document.getElementById('contactFabBubble');
  const contactFabBubbleClose = document.getElementById('contactFabBubbleClose');
  let contactFabBubbleTimer = null;

  function setContactFabBubbleVisible(visible) {
    if (!contactFabBubble) return;
    contactFabBubble.classList.toggle('is-visible', visible);
    contactFabBubble.setAttribute('aria-hidden', visible ? 'false' : 'true');
  }

  function hideContactFabBubble(permanent = false) {
    if (contactFabBubbleTimer) {
      window.clearTimeout(contactFabBubbleTimer);
      contactFabBubbleTimer = null;
    }
    if (permanent && contactFabBubble) {
      contactFabBubble.classList.add('is-dismissed');
    }
    setContactFabBubbleVisible(false);
  }

  function showContactFabBubble() {
    if (!contactFabBubble || contactFab?.classList.contains('is-open')) return;
    if (contactFabBubble.classList.contains('is-dismissed')) return;
    setContactFabBubbleVisible(true);
    if (contactFabBubbleTimer) window.clearTimeout(contactFabBubbleTimer);
    contactFabBubbleTimer = window.setTimeout(hideContactFabBubble, 12000);
  }

  function setContactFabOpen(open) {
    if (!contactFab || !contactFabTrigger || !contactFabPanel) return;
    contactFab.classList.toggle('is-open', open);
    contactFabTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    contactFabPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) hideContactFabBubble();
  }

  if (contactFab && contactFabTrigger && contactFabPanel) {
    const toggleContactFab = () => {
      const isOpen = contactFab.classList.contains('is-open');
      setContactFabOpen(!isOpen);
    };
    contactFabTrigger.addEventListener('click', toggleContactFab);
    contactFabClose?.addEventListener('click', () => setContactFabOpen(false));
    contactFabBubbleClose?.addEventListener('click', (e) => {
      e.stopPropagation();
      hideContactFabBubble(true);
    });
    document.addEventListener('click', (e) => {
      if (!contactFab.classList.contains('is-open')) return;
      if (!contactFab.contains(e.target)) setContactFabOpen(false);
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') setContactFabOpen(false);
    });
    window.setTimeout(showContactFabBubble, 1800);
  }

  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        siteNav?.classList.remove('open');
      }
    });
  });
});
