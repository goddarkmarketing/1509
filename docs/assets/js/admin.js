(function () {
  var toggle = document.getElementById('adminMenuToggle');
  var overlay = document.getElementById('adminOverlay');
  if (toggle) {
    function setOpen(open) {
      document.body.classList.toggle('admin-sidebar-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    toggle.addEventListener('click', function () {
      setOpen(!document.body.classList.contains('admin-sidebar-open'));
    });

    if (overlay) {
      overlay.addEventListener('click', function () {
        setOpen(false);
      });
    }

    document.querySelectorAll('.admin-nav a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 1023px)').matches) {
          setOpen(false);
        }
      });
    });
  }
})();

(function () {
  var studentModal = document.getElementById('studentManageModal');
  if (!studentModal) return;

  var panels = studentModal.querySelectorAll('[data-student-panel]');
  var titleEl = studentModal.querySelector('[aria-labelledby]');
  var openId = window.__openStudentId || 0;

  function showStudentPanel(id) {
    var panelId = String(id);
    var active = null;
    panels.forEach(function (panel) {
      var match = panel.id === 'student-panel-' + panelId;
      panel.hidden = !match;
      if (match) active = panel;
    });
    if (titleEl && active) {
      var heading = active.querySelector('h2');
      if (heading) {
        titleEl.setAttribute('aria-labelledby', heading.id || 'studentModalTitle');
      }
    }
    studentModal.hidden = false;
    document.body.classList.add('admin-modal-open');
    if (history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('student_id', panelId);
      url.searchParams.delete('open');
      history.replaceState({}, '', url);
    }
  }

  function closeStudentModal() {
    studentModal.hidden = true;
    document.body.classList.remove('admin-modal-open');
    if (history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.delete('student_id');
      url.searchParams.delete('open');
      history.replaceState({}, '', url.pathname);
    }
  }

  document.querySelectorAll('[data-open-student]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showStudentPanel(btn.getAttribute('data-open-student'));
    });
  });

  studentModal.querySelectorAll('[data-close-student-modal]').forEach(function (el) {
    el.addEventListener('click', closeStudentModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !studentModal.hidden) {
      closeStudentModal();
    }
  });

  if (openId) {
    showStudentPanel(openId);
  }
})();

(function () {
  var couponModal = document.getElementById('couponFormModal');
  if (!couponModal) return;

  var panels = couponModal.querySelectorAll('[data-coupon-panel]');
  var titleEl = couponModal.querySelector('[aria-labelledby]');
  var openPanel = window.__openCouponPanel || '';

  function setSelectedRow(panelId) {
    document.querySelectorAll('[data-coupon-row]').forEach(function (row) {
      row.classList.toggle('is-selected', panelId !== 'new' && row.getAttribute('data-coupon-row') === panelId);
    });
  }

  function showCouponPanel(id) {
    var panelId = String(id);
    var active = null;
    panels.forEach(function (panel) {
      var match = panel.id === 'coupon-panel-' + panelId;
      panel.hidden = !match;
      if (match) active = panel;
    });
    if (titleEl && active) {
      var heading = active.querySelector('h2');
      if (heading) {
        titleEl.setAttribute('aria-labelledby', heading.id || 'couponModalTitle');
      }
      var firstInput = active.querySelector('input[name="code"]');
      if (firstInput) {
        window.setTimeout(function () {
          firstInput.focus();
        }, 0);
      }
    }
    couponModal.hidden = false;
    document.body.classList.add('admin-modal-open');
    setSelectedRow(panelId);
    if (history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.delete('id');
      url.searchParams.delete('new');
      if (panelId === 'new') {
        url.searchParams.set('new', '1');
      } else {
        url.searchParams.set('id', panelId);
      }
      history.replaceState({}, '', url);
    }
  }

  function closeCouponModal() {
    couponModal.hidden = true;
    document.body.classList.remove('admin-modal-open');
    setSelectedRow('');
    if (history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.delete('id');
      url.searchParams.delete('new');
      history.replaceState({}, '', url.pathname);
    }
  }

  document.querySelectorAll('[data-open-coupon]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      showCouponPanel(btn.getAttribute('data-open-coupon'));
    });
  });

  couponModal.querySelectorAll('[data-close-coupon-modal]').forEach(function (el) {
    el.addEventListener('click', closeCouponModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !couponModal.hidden) {
      closeCouponModal();
    }
  });

  if (openPanel) {
    showCouponPanel(openPanel);
  }
})();

(function () {
  var slipModal = document.getElementById('paymentSlipModal');
  if (!slipModal) return;

  var slipImage = document.getElementById('paymentSlipImage');
  var slipPdf = document.getElementById('paymentSlipPdf');
  var slipMeta = document.getElementById('paymentSlipModalMeta');
  var slipOpenTab = document.getElementById('paymentSlipOpenTab');
  var slipDialog = slipModal.querySelector('.payment-slip-dialog');
  var slipFrame = document.getElementById('paymentSlipFrame');
  var slipBody = slipModal.querySelector('.payment-slip-modal-body');

  function resetSlipLayout() {
    [slipDialog, slipBody, slipFrame, slipImage].forEach(function (el) {
      if (!el) return;
      el.style.width = '';
      el.style.height = '';
    });
    if (slipImage) {
      slipImage.style.width = '';
      slipImage.style.height = '';
    }
  }

  function fitSlipImage(img) {
    if (!img || !img.naturalWidth) return;
    var padX = 48;
    var padY = 130;
    var maxW = Math.min(window.innerWidth * 0.9 - padX, 720);
    var maxH = Math.min(window.innerHeight * 0.82 - padY, 780);
    var scale = Math.min(1, maxW / img.naturalWidth, maxH / img.naturalHeight);
    var w = Math.round(img.naturalWidth * scale);
    var h = Math.round(img.naturalHeight * scale);
    img.style.width = w + 'px';
    img.style.height = h + 'px';
    if (slipFrame) {
      slipFrame.style.width = w + 8 + 'px';
      slipFrame.style.height = h + 8 + 'px';
    }
    if (slipDialog) {
      slipDialog.style.width = w + padX + 'px';
    }
  }

  function closeSlipModal() {
    slipModal.hidden = true;
    document.body.classList.remove('admin-modal-open');
    if (slipDialog) {
      slipDialog.classList.remove('is-pdf');
    }
    resetSlipLayout();
    if (slipImage) {
      slipImage.hidden = true;
      slipImage.removeAttribute('src');
    }
    if (slipPdf) {
      slipPdf.hidden = true;
      slipPdf.removeAttribute('src');
    }
  }

  function openSlipModal(url, isImage, payer) {
    if (!url) return;
    resetSlipLayout();
    if (slipMeta) {
      slipMeta.textContent = payer ? 'จาก: ' + payer : '';
    }
    if (slipOpenTab) {
      slipOpenTab.href = url;
    }
    if (slipDialog) {
      slipDialog.classList.toggle('is-pdf', !isImage);
    }
    if (isImage && slipImage) {
      slipImage.onload = function () {
        fitSlipImage(slipImage);
      };
      slipImage.src = url;
      slipImage.hidden = false;
      if (slipImage.complete && slipImage.naturalWidth) {
        fitSlipImage(slipImage);
      }
      if (slipPdf) {
        slipPdf.hidden = true;
        slipPdf.removeAttribute('src');
      }
    } else if (slipPdf) {
      slipPdf.src = url;
      slipPdf.hidden = false;
      if (slipImage) {
        slipImage.hidden = true;
        slipImage.removeAttribute('src');
      }
    }
    slipModal.hidden = false;
    document.body.classList.add('admin-modal-open');
  }

  document.querySelectorAll('[data-open-slip]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      openSlipModal(
        btn.getAttribute('data-slip-url') || '',
        btn.getAttribute('data-slip-image') === '1',
        btn.getAttribute('data-slip-payer') || ''
      );
    });
  });

  slipModal.querySelectorAll('[data-close-slip-modal]').forEach(function (el) {
    el.addEventListener('click', closeSlipModal);
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !slipModal.hidden) {
      closeSlipModal();
    }
  });
})();
