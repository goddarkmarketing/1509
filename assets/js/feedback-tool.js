(function () {
  'use strict';

  var boot = window.__FEEDBACK_BOOT__;
  if (!boot || !boot.enabled) return;

  var state = {
    mode: false,
    selected: null,
    hoverEl: null,
    html2canvas: null,
    saving: false
  };

  var IGNORE_TAGS = { HTML: 1, BODY: 1, SCRIPT: 1, STYLE: 1, LINK: 1, META: 1, HEAD: 1 };

  function el(tag, className, attrs) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === 'text') node.textContent = attrs[k];
        else if (k === 'html') node.innerHTML = attrs[k];
        else node.setAttribute(k, attrs[k]);
      });
    }
    return node;
  }

  function iconPen() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
  }

  function iconX() {
    return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
  }

  var root = el('div', 'fb-root');
  root.setAttribute('data-feedback-ui', '1');

  var fab = el('button', 'fb-fab', { type: 'button', 'aria-label': 'แจ้งแก้ไข' });
  fab.innerHTML = iconPen() + '<span>แจ้งแก้ไข</span>';

  var banner = el('div', 'fb-banner');
  banner.innerHTML =
    '<div><strong>โหมดแจ้งแก้ไข</strong> — ชี้แล้วคลิกส่วนที่ต้องการแก้ (ปุ่ม/ลิงก์ถูกระงับชั่วคราว)</div>' +
    '<div class="fb-banner-actions">' +
    '<button type="button" data-fb-exit>ปิดโหมด</button>' +
    '</div>';

  var highlight = el('div', 'fb-highlight');
  var highlightLabel = el('div', 'fb-highlight-label');
  highlight.appendChild(highlightLabel);

  var overlay = el('div', 'fb-modal-overlay');
  overlay.innerHTML =
    '<div class="fb-modal" role="dialog" aria-modal="true" aria-labelledby="fbModalTitle">' +
    '<div class="fb-modal-head">' +
    '<h2 id="fbModalTitle">แจ้งแก้ไขส่วนนี้</h2>' +
    '<button type="button" class="fb-modal-close" data-fb-cancel aria-label="ปิด">' + iconX() + '</button>' +
    '</div>' +
    '<div class="fb-modal-body">' +
    '<div class="fb-meta" data-fb-meta></div>' +
    '<div class="fb-field"><label for="fbComment">รายละเอียดที่ต้องการแก้ไข</label>' +
    '<textarea id="fbComment" data-fb-comment required placeholder="อธิบายสิ่งที่ต้องการให้แก้ เช่น เปลี่ยนข้อความ / ขยับปุ่ม / สีไม่ตรง"></textarea></div>' +
    '<div class="fb-row">' +
    '<div class="fb-field"><label for="fbCategory">ประเภท</label><select id="fbCategory" data-fb-category></select></div>' +
    '<div class="fb-field"><label for="fbPriority">ความสำคัญ</label><select id="fbPriority" data-fb-priority></select></div>' +
    '</div>' +
    '<div class="fb-field"><label for="fbClient">ชื่อลูกค้า</label><input id="fbClient" type="text" data-fb-client placeholder="ชื่อของคุณ"></div>' +
    '<div class="fb-actions">' +
    '<button type="button" class="fb-btn" data-fb-cancel>ยกเลิก</button>' +
    '<button type="button" class="fb-btn fb-btn-primary" data-fb-save>บันทึก</button>' +
    '</div>' +
    '</div></div>';

  var toast = el('div', 'fb-toast');

  root.appendChild(banner);
  root.appendChild(highlight);
  root.appendChild(fab);
  root.appendChild(overlay);
  root.appendChild(toast);
  document.body.appendChild(root);

  var categorySelect = overlay.querySelector('[data-fb-category]');
  var prioritySelect = overlay.querySelector('[data-fb-priority]');
  Object.keys(boot.categories || {}).forEach(function (key) {
    var opt = el('option', '', { value: key, text: boot.categories[key] });
    categorySelect.appendChild(opt);
  });
  Object.keys(boot.priorities || {}).forEach(function (key) {
    var opt = el('option', '', { value: key, text: boot.priorities[key] });
    if (key === 'medium') opt.selected = true;
    prioritySelect.appendChild(opt);
  });

  function showToast(msg, isError) {
    toast.textContent = msg;
    toast.classList.toggle('is-error', !!isError);
    toast.classList.add('is-open');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(function () {
      toast.classList.remove('is-open');
    }, 2800);
  }

  function isFeedbackUi(node) {
    return !!(node && node.closest && node.closest('[data-feedback-ui]'));
  }

  function cssEscape(value) {
    if (window.CSS && CSS.escape) return CSS.escape(value);
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function buildSelector(node) {
    if (!node || node.nodeType !== 1) return '';
    if (node.getAttribute('data-feedback-id')) {
      return '[data-feedback-id="' + node.getAttribute('data-feedback-id') + '"]';
    }
    if (node.id) return '#' + cssEscape(node.id);
    var parts = [];
    var current = node;
    var depth = 0;
    while (current && current.nodeType === 1 && depth < 5 && current !== document.body) {
      var part = current.tagName.toLowerCase();
      if (current.getAttribute('data-feedback-id')) {
        parts.unshift('[data-feedback-id="' + current.getAttribute('data-feedback-id') + '"]');
        break;
      }
      if (current.classList && current.classList.length) {
        var cls = Array.prototype.slice.call(current.classList)
          .filter(function (c) { return c && c.indexOf('fb-') !== 0; })
          .slice(0, 2)
          .map(function (c) { return '.' + cssEscape(c); })
          .join('');
        part += cls;
      }
      var parent = current.parentElement;
      if (parent) {
        var siblings = parent.children;
        var same = 0;
        var index = 0;
        for (var i = 0; i < siblings.length; i++) {
          if (siblings[i].tagName === current.tagName) {
            same++;
            if (siblings[i] === current) index = same;
          }
        }
        if (same > 1) part += ':nth-of-type(' + index + ')';
      }
      parts.unshift(part);
      current = parent;
      depth++;
    }
    return parts.join(' > ');
  }

  function guessComponentName(node) {
    if (!node) return '';
    var cur = node;
    for (var i = 0; i < 6 && cur; i++) {
      var fid = cur.getAttribute && cur.getAttribute('data-feedback-id');
      if (fid) return fid;
      var cn = cur.getAttribute && (cur.getAttribute('data-component') || cur.getAttribute('data-section'));
      if (cn) return cn;
      cur = cur.parentElement;
    }
    if (node.classList && node.classList.length) return node.classList[0];
    return node.tagName ? node.tagName.toLowerCase() : '';
  }

  function nearestFeedbackId(node) {
    var cur = node;
    while (cur && cur !== document.body) {
      if (cur.getAttribute && cur.getAttribute('data-feedback-id')) {
        return cur.getAttribute('data-feedback-id');
      }
      cur = cur.parentElement;
    }
    return '';
  }

  function collectMeta(node) {
    var rect = node.getBoundingClientRect();
    var text = (node.innerText || node.textContent || '').replace(/\s+/g, ' ').trim();
    if (text.length > 180) text = text.slice(0, 180) + '…';
    var path = location.pathname || '';
    var route = path;
    if (boot.appUrl) {
      try {
        var basePath = new URL(boot.appUrl).pathname.replace(/\/$/, '');
        if (basePath && route.indexOf(basePath) === 0) {
          route = route.slice(basePath.length) || '/';
        }
      } catch (e) {}
    }
    return {
      page: document.title || '',
      url: location.href,
      route: route,
      section: nearestFeedbackId(node) || guessComponentName(node),
      feedbackId: nearestFeedbackId(node),
      selector: buildSelector(node),
      elementTag: (node.tagName || '').toLowerCase(),
      elementText: text,
      componentName: guessComponentName(node),
      boundingRect: {
        x: Math.round(rect.x + window.scrollX),
        y: Math.round(rect.y + window.scrollY),
        width: Math.round(rect.width),
        height: Math.round(rect.height)
      },
      viewport: { width: window.innerWidth, height: window.innerHeight },
      scrollPosition: { x: Math.round(window.scrollX), y: Math.round(window.scrollY) }
    };
  }

  function updateHighlight(node) {
    if (!node) {
      highlight.classList.remove('is-visible');
      return;
    }
    var rect = node.getBoundingClientRect();
    highlight.style.left = rect.left + window.scrollX + 'px';
    highlight.style.top = rect.top + window.scrollY + 'px';
    highlight.style.width = Math.max(rect.width, 4) + 'px';
    highlight.style.height = Math.max(rect.height, 4) + 'px';
    highlightLabel.textContent = nearestFeedbackId(node) || (node.tagName || '').toLowerCase();
    highlight.classList.add('is-visible');
  }

  function setMode(on) {
    state.mode = !!on;
    document.body.classList.toggle('fb-mode-on', state.mode);
    fab.classList.toggle('is-active', state.mode);
    banner.classList.toggle('is-open', state.mode);
    if (!state.mode) {
      state.hoverEl = null;
      updateHighlight(null);
    }
  }

  function openModal(meta) {
    state.selected = meta;
    overlay.querySelector('[data-fb-meta]').textContent =
      (meta.feedbackId ? meta.feedbackId + ' · ' : '') +
      meta.elementTag +
      (meta.elementText ? ' — ' + meta.elementText : '') +
      '\n' + meta.selector;
    overlay.querySelector('[data-fb-comment]').value = '';
    overlay.querySelector('[data-fb-client]').value = localStorage.getItem('fb_client_name') || '';
    overlay.classList.add('is-open');
    setTimeout(function () {
      overlay.querySelector('[data-fb-comment]').focus();
    }, 50);
  }

  function closeModal() {
    overlay.classList.remove('is-open');
    state.selected = null;
  }

  function loadHtml2Canvas() {
    if (state.html2canvas) return Promise.resolve(state.html2canvas);
    if (window.html2canvas) {
      state.html2canvas = window.html2canvas;
      return Promise.resolve(state.html2canvas);
    }
    return new Promise(function (resolve, reject) {
      var s = document.createElement('script');
      s.src = boot.html2canvasUrl;
      s.async = true;
      s.onload = function () {
        state.html2canvas = window.html2canvas;
        resolve(state.html2canvas);
      };
      s.onerror = function () { reject(new Error('โหลด html2canvas ไม่สำเร็จ')); };
      document.head.appendChild(s);
    });
  }

  function maskSensitiveBeforeCapture() {
    document.body.classList.add('fb-capturing');
    var nodes = document.querySelectorAll('input[type="password"], [data-sensitive], input[name*="password" i], input[name*="token" i], input[name*="secret" i], input[name*="api" i]');
    nodes.forEach(function (n) {
      n.classList.add('fb-mask-sensitive');
    });
  }

  function unmaskAfterCapture() {
    document.body.classList.remove('fb-capturing');
    document.querySelectorAll('.fb-mask-sensitive').forEach(function (n) {
      n.classList.remove('fb-mask-sensitive');
    });
  }

  function captureScreenshot(meta) {
    return loadHtml2Canvas().then(function (html2canvas) {
      maskSensitiveBeforeCapture();
      var uiNodes = root.querySelectorAll('.fb-fab, .fb-banner, .fb-modal-overlay, .fb-toast');
      uiNodes.forEach(function (n) { n.style.visibility = 'hidden'; });
      highlight.classList.add('is-visible');
      return html2canvas(document.body, {
        useCORS: true,
        allowTaint: true,
        scale: Math.min(window.devicePixelRatio || 1, 1.5),
        width: window.innerWidth,
        height: window.innerHeight,
        x: window.scrollX,
        y: window.scrollY,
        scrollX: 0,
        scrollY: 0,
        windowWidth: window.innerWidth,
        windowHeight: window.innerHeight,
        ignoreElements: function (node) {
          return !!(node && node.getAttribute && node.getAttribute('data-feedback-ui') === '1' && node !== highlight && !highlight.contains(node));
        }
      }).then(function (canvas) {
        var ctx = canvas.getContext('2d');
        var rect = meta.boundingRect;
        var x = rect.x - window.scrollX;
        var y = rect.y - window.scrollY;
        var scaleX = canvas.width / window.innerWidth;
        var scaleY = canvas.height / window.innerHeight;
        ctx.save();
        ctx.strokeStyle = '#b45309';
        ctx.lineWidth = 3 * scaleX;
        ctx.strokeRect(x * scaleX, y * scaleY, rect.width * scaleX, rect.height * scaleY);
        var label = 'FB';
        ctx.fillStyle = '#b45309';
        var lw = 44 * scaleX;
        var lh = 22 * scaleY;
        var lx = Math.max(0, x * scaleX);
        var ly = Math.max(0, y * scaleY - lh - 4);
        ctx.fillRect(lx, ly, lw, lh);
        ctx.fillStyle = '#fff';
        ctx.font = 'bold ' + Math.round(12 * scaleX) + 'px sans-serif';
        ctx.fillText(label, lx + 8 * scaleX, ly + 15 * scaleY);
        ctx.restore();
        return canvas.toDataURL('image/png');
      }).finally(function () {
        uiNodes.forEach(function (n) { n.style.visibility = ''; });
        unmaskAfterCapture();
      });
    });
  }

  function saveFeedback() {
    if (!state.selected || state.saving) return;
    var comment = overlay.querySelector('[data-fb-comment]').value.trim();
    if (!comment) {
      showToast('กรุณากรอกรายละเอียด', true);
      return;
    }
    var clientName = overlay.querySelector('[data-fb-client]').value.trim();
    if (clientName) localStorage.setItem('fb_client_name', clientName);

    state.saving = true;
    var saveBtn = overlay.querySelector('[data-fb-save]');
    saveBtn.disabled = true;
    saveBtn.textContent = 'กำลังบันทึก...';

    captureScreenshot(state.selected)
      .catch(function () { return ''; })
      .then(function (dataUrl) {
        var payload = Object.assign({}, state.selected, {
          comment: comment,
          category: categorySelect.value,
          priority: prioritySelect.value,
          clientName: clientName,
          screenshotDataUrl: dataUrl || '',
          csrf: boot.csrf
        });
        return fetch(boot.submitUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify(payload)
        }).then(function (res) {
          return res.json().then(function (data) {
            if (!res.ok || !data.ok) throw new Error((data && data.error) || 'บันทึกไม่สำเร็จ');
            return data;
          });
        });
      })
      .then(function (data) {
        showToast('บันทึกแล้ว: ' + ((data.item && data.item.id) || 'FB'));
        closeModal();
      })
      .catch(function (err) {
        showToast(err.message || 'บันทึกไม่สำเร็จ', true);
      })
      .finally(function () {
        state.saving = false;
        saveBtn.disabled = false;
        saveBtn.textContent = 'บันทึก';
      });
  }

  fab.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    setMode(!state.mode);
  });

  banner.addEventListener('click', function (e) {
    if (e.target && e.target.getAttribute('data-fb-exit') !== null) {
      setMode(false);
    }
  });

  overlay.addEventListener('click', function (e) {
    if (e.target === overlay || (e.target && e.target.getAttribute('data-fb-cancel') !== null) || (e.target.closest && e.target.closest('[data-fb-cancel]'))) {
      closeModal();
    }
    if (e.target && e.target.getAttribute('data-fb-save') !== null) {
      saveFeedback();
    }
  });

  document.addEventListener('mousemove', function (e) {
    if (!state.mode || overlay.classList.contains('is-open')) return;
    var target = e.target;
    if (!target || isFeedbackUi(target) || IGNORE_TAGS[target.tagName]) {
      state.hoverEl = null;
      updateHighlight(null);
      return;
    }
    state.hoverEl = target;
    updateHighlight(target);
  }, true);

  document.addEventListener('click', function (e) {
    if (!state.mode) return;
    if (isFeedbackUi(e.target)) return;
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    var target = e.target;
    if (!target || IGNORE_TAGS[target.tagName]) return;
    openModal(collectMeta(target));
  }, true);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (overlay.classList.contains('is-open')) closeModal();
      else if (state.mode) setMode(false);
    }
  });

  window.addEventListener('scroll', function () {
    if (state.mode && state.hoverEl) updateHighlight(state.hoverEl);
  }, { passive: true });
})();
