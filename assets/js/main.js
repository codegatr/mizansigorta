/* Mizan Sigorta - Public JS  /assets/js/main.js */
(function () {
  'use strict';

  // Mobile menu Bootstrap zaten hallediyor, ek: link tiklaninca kapat
  document.querySelectorAll('.mz-navbar .navbar-collapse .nav-link').forEach(function (a) {
    a.addEventListener('click', function () {
      var nav = document.querySelector('.mz-navbar .navbar-collapse');
      if (nav && nav.classList.contains('show')) {
        var btn = document.querySelector('.mz-navbar .navbar-toggler');
        if (btn) btn.click();
      }
    });
  });

  // Telefon mask: TR formatı 0XXX XXX XX XX
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
  document.querySelectorAll('input[type="tel"], input[name="telefon"]').forEach(function (inp) {
    inp.addEventListener('input', function () { inp.value = formatPhone(inp.value); });
    inp.addEventListener('blur', function () { inp.value = formatPhone(inp.value); });
  });

  // Form gonderim sirasinda spinner
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"], button:not([type])');
      if (btn && !btn.dataset.noSpinner) {
        var orig = btn.innerHTML;
        btn.dataset.origLabel = orig;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gönderiliyor...';
        // Validation hata olursa geri donsun
        setTimeout(function () {
          if (form.checkValidity && !form.checkValidity()) {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.origLabel;
          }
        }, 50);
      }
    });
  });

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      var el = document.querySelector(id);
      if (el) {
        e.preventDefault();
        window.scrollTo({ top: el.offsetTop - 80, behavior: 'smooth' });
      }
    });
  });

  // Math captcha doğrulayıcı (form submit öncesi)
  document.querySelectorAll('[data-mz-captcha]').forEach(function (wrap) {
    var aEl = wrap.querySelector('[data-mz-cap-a]');
    var bEl = wrap.querySelector('[data-mz-cap-b]');
    var input = wrap.querySelector('[data-mz-cap-input]');
    var hidden = wrap.querySelector('[data-mz-cap-correct]');
    if (!aEl || !bEl || !input || !hidden) return;
    var a = Math.floor(Math.random() * 9) + 1;
    var b = Math.floor(Math.random() * 9) + 1;
    aEl.textContent = a;
    bEl.textContent = b;
    hidden.value = a + b;
  });

  // Sayfa kaydirilinca navbar shadow
  var nav = document.querySelector('.mz-navbar');
  if (nav) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 8) nav.classList.add('shadow');
      else nav.classList.remove('shadow');
    });
  }
})();
