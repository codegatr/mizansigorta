/* Mizan Sigorta - Admin JS  /assets/js/admin.js */
(function () {
  'use strict';

  // Sidebar toggle (mobil)
  var btn = document.getElementById('mzSidebarToggle');
  var sb  = document.getElementById('adminSidebar');
  var bd  = document.getElementById('mzSidebarBackdrop');
  if (btn && sb) {
    btn.addEventListener('click', function () {
      sb.classList.toggle('show');
      if (bd) bd.classList.toggle('show');
    });
  }
  if (bd && sb) {
    bd.addEventListener('click', function () {
      sb.classList.remove('show');
      bd.classList.remove('show');
    });
  }

  // Telefon mask (admin formlari icin)
  function formatPhone(v) {
    var d = v.replace(/\D/g, '').slice(0, 11);
    if (d.startsWith('90')) d = '0' + d.slice(2);
    if (!d.startsWith('0') && d.length > 0) d = '0' + d.slice(0, 10);
    var p1 = d.slice(0, 4), p2 = d.slice(4, 7), p3 = d.slice(7, 9), p4 = d.slice(9, 11);
    var out = p1;
    if (p2) out += ' ' + p2;
    if (p3) out += ' ' + p3;
    if (p4) out += ' ' + p4;
    return out;
  }
  document.querySelectorAll('input[type="tel"]').forEach(function (inp) {
    inp.addEventListener('input', function () { inp.value = formatPhone(inp.value); });
  });

  // Toplu secim (master checkbox)
  document.querySelectorAll('[data-mz-master]').forEach(function (master) {
    var sel = master.getAttribute('data-mz-master');
    master.addEventListener('change', function () {
      document.querySelectorAll(sel).forEach(function (cb) { cb.checked = master.checked; });
    });
  });

  // Auto-dismiss alert (5 saniye sonra)
  document.querySelectorAll('.alert.alert-dismissible').forEach(function (a) {
    setTimeout(function () {
      var btn = a.querySelector('.btn-close');
      if (btn) btn.click();
    }, 6000);
  });

  // Submit spinner
  document.querySelectorAll('form:not([data-no-spinner])').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.dataset.noSpinner) {
        if (form.checkValidity && !form.checkValidity()) return;
        btn.dataset.origLabel = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>İşleniyor...';
      }
    });
  });

  // Slug oto-uretim (data-mz-slug-from="kaynak_field_name")
  document.querySelectorAll('[data-mz-slug-from]').forEach(function (target) {
    if (target.value) return;
    var srcName = target.getAttribute('data-mz-slug-from');
    var src = document.querySelector('[name="' + srcName + '"]');
    if (!src) return;
    var manuallyEdited = false;
    target.addEventListener('input', function () { manuallyEdited = true; });
    src.addEventListener('input', function () {
      if (manuallyEdited) return;
      target.value = src.value
        .toLowerCase()
        .replace(/ç/g, 'c').replace(/ğ/g, 'g').replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ş/g, 's').replace(/ü/g, 'u')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    });
  });

  // Konfirme onayli butonlar (data-mz-confirm="..." attr)
  document.querySelectorAll('[data-mz-confirm]').forEach(function (b) {
    b.addEventListener('click', function (e) {
      if (!confirm(b.getAttribute('data-mz-confirm'))) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });
})();
