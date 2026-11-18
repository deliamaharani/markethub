/* MarketHub — script.js */
(function () {
  'use strict';

  // Konfirmasi sebelum aksi berbahaya: <form data-confirm="Yakin?">
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); }
  });

  // Validasi client-side (Bootstrap) + cek kecocokan password
  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    var pw = form.querySelector('[data-pw]');
    var pc = form.querySelector('[data-pw-confirm]');
    function check() {
      if (pw && pc) { pc.setCustomValidity(pc.value !== pw.value ? 'Konfirmasi password tidak cocok' : ''); }
    }
    if (pw && pc) { pw.addEventListener('input', check); pc.addEventListener('input', check); }
    form.addEventListener('submit', function (e) {
      check();
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  });

  // Pengatur jumlah: <div class="mh-qty" data-qty data-max="12"> ... </div>
  document.querySelectorAll('[data-qty]').forEach(function (box) {
    var input = box.querySelector('input');
    var max = parseInt(box.getAttribute('data-max'), 10) || 1;
    function set(v) { v = Math.max(1, Math.min(max, v || 1)); input.value = v; }
    box.querySelectorAll('[data-act]').forEach(function (b) {
      b.addEventListener('click', function () {
        set((parseInt(input.value, 10) || 1) + (b.getAttribute('data-act') === 'plus' ? 1 : -1));
      });
    });
    input.addEventListener('change', function () { set(parseInt(input.value, 10)); });
  });

  // Slug otomatis dari nama kategori
  var src = document.querySelector('[data-slug-source]');
  var dst = document.querySelector('[data-slug-target]');
  if (src && dst) {
    var manual = dst.value !== '' && dst.value !== slug(src.value);
    dst.addEventListener('input', function () { manual = true; });
    src.addEventListener('input', function () { if (!manual) { dst.value = slug(src.value); } });
  }
  function slug(s) { return s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''); }

  // Validasi + pratinjau gambar produk
  var file = document.querySelector('input[type=file][data-preview]');
  if (file) {
    var box = document.querySelector(file.getAttribute('data-preview'));
    var err = document.querySelector('[data-file-error]');
    file.addEventListener('change', function () {
      var f = file.files[0];
      if (err) { err.textContent = ''; }
      if (!f) { return; }
      if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) {
        file.value = ''; if (err) { err.textContent = 'Format harus JPG, PNG, atau WEBP.'; } return;
      }
      if (f.size > 2 * 1024 * 1024) {
        file.value = ''; if (err) { err.textContent = 'Ukuran gambar maksimal 2 MB.'; } return;
      }
      if (box) {
        var r = new FileReader();
        r.onload = function (ev) { box.innerHTML = '<img src="' + ev.target.result + '" alt="Pratinjau gambar" style="width:100%;height:100%;object-fit:cover">'; };
        r.readAsDataURL(f);
      }
    });
  }

  // Tampilkan / sembunyikan password
  document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.querySelector(btn.getAttribute('data-toggle-pw'));
      if (!input) { return; }
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
    });
  });

  // Hero carousel: autoplay + typing headline + navigasi manual
  (function () {
    var root = document.querySelector('[data-hc]');
    if (!root) { return; }
    var slides = [].slice.call(root.querySelectorAll('.hc-slide'));
    var dots = [].slice.call(root.querySelectorAll('.hc-dot'));
    var prog = root.querySelector('.hc-prog i');
    if (slides.length < 2) { return; }
    var reduce = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
    var interval = parseInt(root.getAttribute('data-interval'), 10) || 4800;
    var cur = 0, typeTimer = 0, startTimer = 0, caretTimer = 0, hover = false, focusIn = false, hidden = document.hidden;
    root.style.setProperty('--hc-iv', interval + 'ms');

    function stopTyping() { clearTimeout(typeTimer); clearTimeout(startTimer); clearTimeout(caretTimer); }
    function typeHeadline(slide, instant) {
      var h = slide.querySelector('.hc-h'), live = h && h.querySelector('.ty-live');
      if (!live) { return; }
      var text = h.getAttribute('data-text') || '';
      stopTyping();
      if (instant || reduce) { live.textContent = text; h.classList.remove('is-typing'); return; }
      live.textContent = '';
      h.classList.add('is-typing');
      var i = 0;
      (function step() {
        i += 1;
        live.textContent = text.slice(0, i);
        if (i < text.length) { typeTimer = setTimeout(step, 32 + (text.charAt(i - 1) === ' ' ? 18 : 0)); }
        else { caretTimer = setTimeout(function () { h.classList.remove('is-typing'); }, 1400); }
      })();
    }
    function restartProgress() {
      root.classList.remove('is-run');
      if (reduce || !prog) { return; }
      void root.offsetWidth;            // paksa reflow agar animasi mulai ulang
      root.classList.add('is-run');
    }
    function updatePause() { root.classList.toggle('is-paused', hover || focusIn || hidden); }

    function go(n, instant) {
      n = (n + slides.length) % slides.length;
      var old = slides[cur];
      if (old) {
        old.classList.remove('is-active'); old.setAttribute('aria-hidden', 'true');
        var oh = old.querySelector('.hc-h'); if (oh) { oh.classList.remove('is-typing'); }
      }
      cur = n;
      var s = slides[cur];
      s.classList.add('is-active'); s.removeAttribute('aria-hidden');
      root.setAttribute('data-theme', s.getAttribute('data-theme'));
      dots.forEach(function (d, i) {
        d.classList.toggle('on', i === cur);
        if (i === cur) { d.setAttribute('aria-current', 'true'); } else { d.removeAttribute('aria-current'); }
      });
      stopTyping();
      var live = s.querySelector('.ty-live');
      if (live && !instant && !reduce) { live.textContent = ''; }
      startTimer = setTimeout(function () { typeHeadline(s, instant); }, instant ? 0 : 280);
      restartProgress();
    }

    root.querySelector('.hc-prev').addEventListener('click', function () { go(cur - 1); });
    root.querySelector('.hc-next').addEventListener('click', function () { go(cur + 1); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { if (i !== cur) { go(i); } else { restartProgress(); } }); });
    if (prog) {
      prog.addEventListener('animationend', function (e) { if (e.animationName === 'hcProg' && !reduce) { go(cur + 1); } });
    }
    root.addEventListener('mouseenter', function () { hover = true; updatePause(); });
    root.addEventListener('mouseleave', function () { hover = false; updatePause(); });
    root.addEventListener('focusin', function (e) {   // jeda hanya untuk fokus keyboard, bukan setelah klik mouse/sentuh
      var kb = false; try { kb = e.target.matches(':focus-visible'); } catch (err) { kb = false; }
      focusIn = kb; updatePause();
    });
    root.addEventListener('focusout', function () { focusIn = false; updatePause(); });
    document.addEventListener('visibilitychange', function () { hidden = document.hidden; updatePause(); });
    root.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { go(cur - 1); } else if (e.key === 'ArrowRight') { go(cur + 1); }
    });
    // Geser (swipe) pada layar sentuh
    var sx = null;
    root.addEventListener('pointerdown', function (e) { sx = e.pointerType === 'touch' ? e.clientX : null; });
    root.addEventListener('pointerup', function (e) {
      if (sx === null) { return; }
      var dx = e.clientX - sx; sx = null;
      if (Math.abs(dx) > 50) { go(dx < 0 ? cur + 1 : cur - 1); }
    });

    root.classList.add('is-ready');
    typeHeadline(slides[0], false);
    restartProgress();
  })();

  // Login/Register: efek mengetik pada headline panel kiri ([data-typing])
  (function () {
    var heads = document.querySelectorAll('[data-typing]');
    if (!heads.length) { return; }
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
    heads.forEach(function (h) {
      var text = h.textContent.trim();
      if (reduce || !h.offsetHeight) { return; }     // dilewati bila animasi dikurangi atau panel tersembunyi (HP)
      h.setAttribute('aria-label', text);
      h.style.minHeight = h.offsetHeight + 'px';       // cadangkan tinggi agar layout tidak bergeser
      h.textContent = '';
      h.classList.add('is-typing');
      var i = 0;
      setTimeout(function step() {
        i += 1;
        h.textContent = text.slice(0, i);
        if (i < text.length) { setTimeout(step, 30); }
        else { setTimeout(function () { h.classList.remove('is-typing'); }, 1400); }
      }, 450);
    });
  })();

  // Hero: efek parallax halus mengikuti pointer (hanya perangkat dengan hover; dilewati bila reduce-motion)
  (function () {
    var hero = document.querySelector('[data-hero]');
    if (!hero || !window.matchMedia) { return; }
    if (matchMedia('(prefers-reduced-motion: reduce)').matches || !matchMedia('(hover: hover) and (pointer: fine)').matches) { return; }
    var layers = hero.querySelectorAll('[data-depth]');
    if (!layers.length) { return; }
    var tx = 0, ty = 0, raf = 0;
    function paint() {
      raf = 0;
      layers.forEach(function (el) {
        var d = parseFloat(el.getAttribute('data-depth')) / 100;
        el.style.translate = (tx * d * 1).toFixed(1) + 'px ' + (ty * d * 1).toFixed(1) + 'px';
      });
    }
    hero.addEventListener('pointermove', function (e) {
      var r = hero.getBoundingClientRect();
      tx = ((e.clientX - r.left) / r.width - .5) * 40;
      ty = ((e.clientY - r.top) / r.height - .5) * 28;
      if (!raf) { raf = requestAnimationFrame(paint); }
    });
    hero.addEventListener('pointerleave', function () { tx = ty = 0; if (!raf) { raf = requestAnimationFrame(paint); } });
  })();
})();