(function () {
  var STORAGE_KEY = 'leona_admin_guide_tour';
  var PAD = 8;
  var GAP = 14;
  var tours = window.__ADMIN_GUIDE_TOURS__ || [];
  if (!Array.isArray(tours) || !tours.length) return;

  var root = null;
  var backdropEl = null;
  var highlightEl = null;
  var cardEl = null;
  var arrowEl = null;
  var state = null;
  var activeTarget = null;
  var rafPlace = 0;
  var timers = [];
  var boundReposition = null;

  function byId(id) {
    for (var i = 0; i < tours.length; i++) {
      if (tours[i].id === id) return tours[i];
    }
    return null;
  }

  function currentPageKey() {
    var path = window.location.pathname || '';
    var file = path.split('/').pop() || '';
    return file.replace(/\.php$/i, '') || 'dashboard';
  }

  function saveState(next) {
    state = next;
    try {
      if (next) sessionStorage.setItem(STORAGE_KEY, JSON.stringify(next));
      else sessionStorage.removeItem(STORAGE_KEY);
    } catch (e) {}
  }

  function loadState() {
    try {
      var raw = sessionStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function later(fn, ms) {
    var id = setTimeout(fn, ms);
    timers.push(id);
    return id;
  }

  function clearTimers() {
    timers.forEach(clearTimeout);
    timers = [];
  }

  function sidebarRight() {
    var side = document.querySelector('.admin-sidebar');
    if (!side) return 0;
    var r = side.getBoundingClientRect();
    return r.right > 0 ? Math.round(r.right) : 0;
  }

  function findTarget(selector) {
    if (!selector) return null;
    try {
      return document.querySelector(selector);
    } catch (e) {
      return null;
    }
  }

  function clearActiveMarks() {
    document.querySelectorAll('[data-guide-active="1"]').forEach(function (el) {
      el.removeAttribute('data-guide-active');
    });
  }

  function ensureUi() {
    if (root) return;
    root = document.createElement('div');
    root.className = 'admin-guide-root';
    root.innerHTML =
      '<div class="admin-guide-backdrop is-hidden" aria-hidden="true"></div>' +
      '<div class="admin-guide-highlight" hidden></div>' +
      '<div class="admin-guide-card" role="dialog" aria-modal="true" aria-labelledby="adminGuideTitle">' +
      '<span class="admin-guide-arrow" hidden data-side="right"></span>' +
      '<p class="admin-guide-progress" id="adminGuideProgress"></p>' +
      '<h3 id="adminGuideTitle"></h3>' +
      '<p class="admin-guide-body" id="adminGuideBody"></p>' +
      '<div class="admin-guide-actions">' +
      '<button type="button" class="btn btn-outline btn-sm" data-guide-action="prev">ย้อนกลับ</button>' +
      '<button type="button" class="btn btn-primary btn-sm" data-guide-action="next">ถัดไป</button>' +
      '<button type="button" class="btn btn-outline btn-sm admin-guide-skip" data-guide-action="skip">ปิดทัวร์</button>' +
      '</div></div>';
    document.body.appendChild(root);
    backdropEl = root.querySelector('.admin-guide-backdrop');
    highlightEl = root.querySelector('.admin-guide-highlight');
    cardEl = root.querySelector('.admin-guide-card');
    arrowEl = root.querySelector('.admin-guide-arrow');

    root.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-guide-action]');
      if (!btn) return;
      ev.preventDefault();
      ev.stopPropagation();
      var action = btn.getAttribute('data-guide-action');
      if (action === 'next') go(1);
      else if (action === 'prev') go(-1);
      else if (action === 'skip') stopTour(true);
    });

    window.addEventListener('keydown', function (ev) {
      if (!state) return;
      if (ev.key === 'Escape') stopTour(true);
      if (ev.key === 'ArrowRight') go(1);
      if (ev.key === 'ArrowLeft') go(-1);
    });

    boundReposition = function () {
      if (state) queuePlace();
    };
    window.addEventListener('resize', boundReposition);
    window.addEventListener('scroll', boundReposition, true);
  }

  function focusRectFor(el) {
    if (!el || !el.getBoundingClientRect) return null;
    var rect = el.getBoundingClientRect();
    if ((!rect.width && !rect.height) || (rect.width < 2 && rect.height < 2)) return null;

    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var huge = rect.width > vw * 0.7 || rect.height > vh * 0.5;
    if (!huge) {
      return {
        top: rect.top,
        left: rect.left,
        width: rect.width,
        height: rect.height,
        right: rect.right,
        bottom: rect.bottom
      };
    }

    var head = null;
    if (el.hasAttribute && el.hasAttribute('data-guide-focus')) head = el;
    if (!head) head = el.querySelector('[data-guide-focus], .admin-card-header, .admin-card-toolbar, h2, .btn-primary');
    if (head) {
      var hr = head.getBoundingClientRect();
      if (hr.width > 2 && hr.height > 2) {
        return {
          top: hr.top,
          left: hr.left,
          width: hr.width,
          height: hr.height,
          right: hr.right,
          bottom: hr.bottom
        };
      }
    }

    var top = Math.max(rect.top, 8);
    var left = Math.max(rect.left, sidebarRight() + 8);
    var width = Math.min(rect.right, vw - 8) - left;
    var height = Math.min(rect.height, Math.min(vh * 0.32, 180));
    return {
      top: top,
      left: left,
      width: Math.max(48, width),
      height: Math.max(40, height),
      right: left + Math.max(48, width),
      bottom: top + Math.max(40, height)
    };
  }

  function setHighlight(rect) {
    if (!rect) {
      highlightEl.hidden = true;
      backdropEl.classList.remove('is-hidden');
      return;
    }
    backdropEl.classList.add('is-hidden');
    highlightEl.hidden = false;
    highlightEl.style.top = Math.max(0, rect.top - PAD) + 'px';
    highlightEl.style.left = Math.max(0, rect.left - PAD) + 'px';
    highlightEl.style.width = Math.max(28, rect.width + PAD * 2) + 'px';
    highlightEl.style.height = Math.max(28, rect.height + PAD * 2) + 'px';
  }

  function clamp(n, min, max) {
    return Math.max(min, Math.min(max, n));
  }

  function placeFloatingCard(rect) {
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var minL = Math.max(12, sidebarRight() + 12);
    // Force layout so width/height are real
    cardEl.style.visibility = 'hidden';
    cardEl.style.top = '0px';
    cardEl.style.left = '0px';
    var cardW = Math.max(280, cardEl.offsetWidth || 360);
    var cardH = Math.max(160, cardEl.offsetHeight || 220);
    cardEl.style.visibility = '';
    var margin = 12;

    if (!rect) {
      if (arrowEl) arrowEl.hidden = true;
      cardEl.style.top = Math.round((vh - cardH) / 2) + 'px';
      cardEl.style.left = Math.round(Math.max(minL, (vw - cardW) / 2)) + 'px';
      cardEl.style.right = 'auto';
      cardEl.style.bottom = 'auto';
      cardEl.style.transform = 'none';
      return;
    }

    // For wide targets (headers/tables), anchor the card toward the right
    // where action buttons usually sit, so the popup feels attached to the point.
    var anchorLeft = rect.width > 420
      ? Math.max(rect.left, rect.right - cardW)
      : rect.left;

    var candidates = [
      { side: 'right', top: rect.top, left: rect.right + GAP },
      { side: 'bottom', top: rect.bottom + GAP, left: anchorLeft },
      { side: 'top', top: rect.top - GAP - cardH, left: anchorLeft },
      { side: 'left', top: rect.top, left: rect.left - GAP - cardW }
    ];

    function score(c) {
      var top = c.top;
      var left = c.left;
      var overflow =
        Math.max(0, margin - top) +
        Math.max(0, top + cardH - (vh - margin)) +
        Math.max(0, minL - left) +
        Math.max(0, left + cardW - (vw - margin));
      var preference = { right: 0, bottom: 6, top: 12, left: 18 }[c.side] || 20;
      return overflow * 120 + preference;
    }

    candidates.sort(function (a, b) {
      return score(a) - score(b);
    });
    var best = candidates[0];
    var top = clamp(best.top, margin, Math.max(margin, vh - cardH - margin));
    var left = clamp(best.left, minL, Math.max(minL, vw - cardW - margin));

    cardEl.style.top = Math.round(top) + 'px';
    cardEl.style.left = Math.round(left) + 'px';
    cardEl.style.right = 'auto';
    cardEl.style.bottom = 'auto';
    cardEl.style.transform = 'none';

    if (arrowEl) {
      arrowEl.hidden = false;
      arrowEl.setAttribute('data-side', best.side);
      var tcy = rect.top + rect.height / 2;
      var tcx = rect.left + rect.width / 2;
      if (best.side === 'right' || best.side === 'left') {
        var oy = clamp(tcy - top - 7, 18, Math.max(18, cardH - 28));
        cardEl.style.setProperty('--guide-arrow-offset', Math.round(oy) + 'px');
      } else {
        var ox = clamp(tcx - left - 7, 18, Math.max(18, cardW - 28));
        cardEl.style.setProperty('--guide-arrow-offset', Math.round(ox) + 'px');
      }
    }
  }

  function syncPlacement() {
    if (!state || !root) return;
    var tour = byId(state.tourId);
    if (!tour) return;
    var step = tour.steps[state.step];
    if (!activeTarget || !document.body.contains(activeTarget)) {
      activeTarget = findTarget(step && step.target);
      clearActiveMarks();
      if (activeTarget) activeTarget.setAttribute('data-guide-active', '1');
    }
    var rect = activeTarget ? focusRectFor(activeTarget) : null;
    setHighlight(rect);
    placeFloatingCard(rect);
  }

  function queuePlace() {
    if (rafPlace) cancelAnimationFrame(rafPlace);
    rafPlace = requestAnimationFrame(function () {
      rafPlace = requestAnimationFrame(function () {
        rafPlace = 0;
        syncPlacement();
      });
    });
  }

  function navigateToStep(tour, stepIndex) {
    var step = tour.steps[stepIndex];
    if (!step) return false;
    var page = step.page || '';
    var href = step.href || '';
    if (page && page !== currentPageKey() && href) {
      var url = new URL(href, window.location.origin);
      url.searchParams.set('tour', tour.id);
      url.searchParams.set('step', String(stepIndex));
      window.location.href = url.toString();
      return true;
    }
    return false;
  }

  function renderStep(shouldScroll) {
    if (!state) return;
    var tour = byId(state.tourId);
    if (!tour || !tour.steps || !tour.steps[state.step]) {
      stopTour(true);
      return;
    }

    ensureUi();
    clearTimers();
    clearActiveMarks();
    root.classList.add('is-active');
    root.removeAttribute('aria-hidden');
    if (cardEl) cardEl.hidden = false;
    document.body.classList.add('admin-guide-open');

    var step = tour.steps[state.step];
    var total = tour.steps.length;
    root.querySelector('#adminGuideProgress').textContent =
      'ทัวร์: ' + tour.title + ' · ขั้นที่ ' + (state.step + 1) + '/' + total;
    root.querySelector('#adminGuideTitle').textContent = step.title || '';
    root.querySelector('#adminGuideBody').textContent = step.body || '';
    root.querySelector('[data-guide-action="prev"]').disabled = state.step <= 0;
    root.querySelector('[data-guide-action="next"]').textContent =
      state.step >= total - 1 ? 'เสร็จสิ้น' : 'ถัดไป';

    activeTarget = findTarget(step.target);
    if (activeTarget) activeTarget.setAttribute('data-guide-active', '1');

    // Place immediately, then again after scroll/layout.
    queuePlace();

    if (shouldScroll && activeTarget) {
      try {
        activeTarget.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
      } catch (e) {
        activeTarget.scrollIntoView(true);
      }
      later(queuePlace, 120);
      later(queuePlace, 320);
      later(queuePlace, 520);
    } else {
      later(queuePlace, 80);
      later(queuePlace, 200);
    }
  }

  function startTour(tourId, stepIndex) {
    var tour = byId(tourId);
    if (!tour) return;
    var idx = Math.max(0, Math.min(stepIndex || 0, tour.steps.length - 1));
    saveState({ tourId: tourId, step: idx });
    if (navigateToStep(tour, idx)) return;
    renderStep(true);
  }

  function stopTour(clearUrl) {
    saveState(null);
    activeTarget = null;
    clearActiveMarks();
    clearTimers();
    if (rafPlace) cancelAnimationFrame(rafPlace);
    rafPlace = 0;
    if (root) {
      root.classList.remove('is-active');
      root.setAttribute('aria-hidden', 'true');
      if (highlightEl) highlightEl.hidden = true;
      if (backdropEl) backdropEl.classList.add('is-hidden');
      if (arrowEl) arrowEl.hidden = true;
      if (cardEl) cardEl.hidden = true;
    }
    document.body.classList.remove('admin-guide-open');
    if (clearUrl && history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.delete('tour');
      url.searchParams.delete('step');
      var q = url.searchParams.toString();
      history.replaceState({}, '', url.pathname + (q ? '?' + q : '') + url.hash);
    }
  }

  function go(delta) {
    if (!state) return;
    var tour = byId(state.tourId);
    if (!tour) return stopTour(true);
    var next = state.step + delta;
    if (next < 0) return;
    if (next >= tour.steps.length) {
      stopTour(true);
      return;
    }
    saveState({ tourId: state.tourId, step: next });
    if (navigateToStep(tour, next)) return;
    renderStep(true);
  }

  function bootFromQueryOrStorage() {
    var params = new URLSearchParams(window.location.search);
    var qTour = params.get('tour');
    var qStep = parseInt(params.get('step') || '0', 10);
    if (qTour && byId(qTour)) {
      startTour(qTour, isNaN(qStep) ? 0 : qStep);
      return;
    }
    var saved = loadState();
    if (saved && byId(saved.tourId)) {
      var tour = byId(saved.tourId);
      var step = tour.steps[saved.step];
      if (step && (!step.page || step.page === currentPageKey())) {
        startTour(saved.tourId, saved.step);
      }
    }
  }

  document.addEventListener('click', function (ev) {
    var startBtn = ev.target.closest('[data-guide-start]');
    if (!startBtn) return;
    ev.preventDefault();
    startTour(startBtn.getAttribute('data-guide-start'), 0);
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootFromQueryOrStorage);
  } else {
    bootFromQueryOrStorage();
  }
})();
