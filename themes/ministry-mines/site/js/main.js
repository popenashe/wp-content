(function () {
  'use strict';
  var root = document.documentElement;

  function store(key, value) {
    try { if (value === undefined) return localStorage.getItem(key); localStorage.setItem(key, value); } catch (e) { return null; }
  }

  // Language switcher (visual state only)
  document.querySelectorAll('[data-lang]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('[data-lang]').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); });
      root.lang = b.dataset.lang;
    });
  });

  // Mobile menu
  var toggle = document.querySelector('.menu-toggle');
  if (toggle) toggle.addEventListener('click', function () {
    var list = document.getElementById('primary-nav');
    var open = list.classList.toggle('open');
    toggle.setAttribute('aria-expanded', String(open));
  });

  // Hero carousel
  var hero = document.querySelector('.hero, .hero2');
  if (hero) {
    var slides = hero.querySelectorAll('.hero-slide');
    var dots = hero.querySelectorAll('.dots button');
    var thumbs = hero.querySelectorAll('.thumbs button');
    var label = hero.querySelector('[data-slide-label]');
    var pause = hero.querySelector('.pause');
    var i = 0, timer = null;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function show(n) {
      i = (n + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('active', k === i); });
      dots.forEach(function (d, k) { d.classList.toggle('active', k === i); d.setAttribute('aria-current', String(k === i)); });
      thumbs.forEach(function (t, k) { t.classList.toggle('active', k === i); });
      if (label) label.textContent = (i + 1) + ' of ' + slides.length + ' — ' + slides[i].dataset.caption;
    }
    function play() {
      var ms = parseInt(hero.getAttribute('data-rotate') || '6', 10);
      if (isNaN(ms) || ms < 3) ms = 6;
      timer = setInterval(function () { show(i + 1); }, ms * 1000);
      pause.setAttribute('aria-label', 'Pause slideshow');
      pause.innerHTML = '&#10074;&#10074;';
    }
    function stop() { clearInterval(timer); timer = null; pause.setAttribute('aria-label', 'Play slideshow'); pause.innerHTML = '&#9654;'; }
    dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); }); });
    thumbs.forEach(function (t, k) { t.addEventListener('click', function () { show(k); }); });
    pause.addEventListener('click', function () { timer ? stop() : play(); });
    show(0);
    if (!reduce) play(); else stop();
  }

  // Filter pills (generic): [data-filter-group] > button[data-filter], items [data-cat]
  document.querySelectorAll('[data-filter-group]').forEach(function (group) {
    var target = document.querySelector(group.dataset.filterGroup);
    group.querySelectorAll('[data-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        group.querySelectorAll('[data-filter]').forEach(function (b) { b.setAttribute('aria-pressed', String(b === btn)); });
        var f = btn.dataset.filter;
        target.querySelectorAll('[data-cat]').forEach(function (el) {
          el.hidden = !(f === 'all' || el.dataset.cat.split(' ').indexOf(f) > -1);
        });
      });
    });
  });

  // FAQ search
  var faqSearch = document.getElementById('faq-search');
  if (faqSearch) faqSearch.addEventListener('input', function () {
    var q = faqSearch.value.trim().toLowerCase();
    document.querySelectorAll('details.faq').forEach(function (d) { d.hidden = q && d.textContent.toLowerCase().indexOf(q) === -1; });
  });

  // Resources: text search + category (select or tile) filter; honours ?q= from the header search
  var docSearch = document.getElementById('doc-q');
  if (docSearch) {
    var docCat = document.getElementById('doc-cat');
    var tiles = document.querySelectorAll('[data-cat-filter]');
    var filterDocs = function () {
      var q = docSearch.value.trim().toLowerCase();
      var c = docCat ? docCat.value : '';
      document.querySelectorAll('#documents tbody tr').forEach(function (r) {
        r.hidden = (q && r.textContent.toLowerCase().indexOf(q) === -1) || (c && r.dataset.cat !== c);
      });
      document.querySelectorAll('.staff-forms a').forEach(function (a) { a.hidden = q && a.textContent.toLowerCase().indexOf(q) === -1; });
    };
    docSearch.addEventListener('input', filterDocs);
    if (docCat) docCat.addEventListener('change', filterDocs);
    tiles.forEach(function (t) {
      t.addEventListener('click', function () {
        tiles.forEach(function (x) { x.classList.toggle('active', x === t); });
        if (docCat && t.dataset.catFilter !== 'Staff forms') { docCat.value = t.dataset.catFilter; filterDocs(); }
      });
    });
    var initial = new URLSearchParams(location.search).get('q');
    if (initial) { docSearch.value = initial; filterDocs(); }
  }

  // Departments: text search, and "Technical departments" shortcut
  var deptSearch = document.getElementById('dept-search');
  if (deptSearch) deptSearch.addEventListener('input', function () {
    var q = deptSearch.value.trim().toLowerCase();
    document.querySelectorAll('#dept-list .dept-card').forEach(function (c) { c.hidden = q && c.textContent.toLowerCase().indexOf(q) === -1; });
  });
  document.querySelectorAll('[data-jump]').forEach(function (b) {
    b.addEventListener('click', function () {
      var btn = document.querySelector('[data-filter="' + b.dataset.jump + '"]');
      if (btn) { btn.click(); btn.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    });
  });

  // News search
  var newsSearch = document.getElementById('news-search');
  if (newsSearch) newsSearch.addEventListener('input', function () {
    var q = newsSearch.value.trim().toLowerCase();
    document.querySelectorAll('#releases [data-cat]').forEach(function (el) { el.hidden = q && el.textContent.toLowerCase().indexOf(q) === -1; });
  });

  // Page feedback
  document.querySelectorAll('[data-feedback]').forEach(function (b) {
    b.addEventListener('click', function () {
      var box = b.closest('.wrap');
      box.querySelector('[data-feedback-q]').textContent = 'Thank you for your feedback.';
      box.querySelectorAll('[data-feedback]').forEach(function (x) { x.remove(); });
    });
  });

  // Gallery albums filled in from wp-admin
  var albumYear = document.getElementById('g-year');
  var albumEvent = document.getElementById('g-event');
  if (albumYear && albumEvent && document.querySelector('[data-album]')) {
    var albumEmpty = document.getElementById('album-empty');
    var applyAlbums = function () {
      var year = albumYear.value;
      var slug = albumEvent.value;
      var visible = 0;
      document.querySelectorAll('[data-album]').forEach(function (el) {
        var show = (year === 'all' || el.getAttribute('data-year') === year || el.getAttribute('data-year') === '') && (slug === 'all' || el.getAttribute('data-slug') === slug);
        el.hidden = !show;
        if (show && el.classList.contains('album-card')) visible += 1;
      });
      if (albumEmpty) albumEmpty.hidden = visible !== 0;
    };
    albumYear.addEventListener('change', applyAlbums);
    albumEvent.addEventListener('change', applyAlbums);
  }

  // Character counter
  var msg = document.getElementById('message');
  if (msg) {
    var counter = document.getElementById('msg-count');
    msg.addEventListener('input', function () { counter.textContent = msg.value.length + ' / 2,000 characters'; });
  }

  // Service cards: preview on the list, details in a dialog
  var svcDialog = document.createElement('div');
  svcDialog.className = 'svc-dialog';
  svcDialog.hidden = true;
  svcDialog.innerHTML = '<div class="svc-dialog-panel" role="dialog" aria-modal="true"><button class="btn btn-sm btn-outline" type="button" data-svc-close>Close</button><div data-svc-body></div></div>';
  document.body.appendChild(svcDialog);
  var svcBody = svcDialog.querySelector('[data-svc-body]');
  function closeSvc() { svcDialog.hidden = true; }
  svcDialog.addEventListener('click', function (e) {
    if (e.target === svcDialog || e.target.closest('[data-svc-close]')) closeSvc();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSvc(); });
  document.querySelectorAll('article.svc').forEach(function (card) {
    function openSvc() {
      var title = card.querySelector('h3');
      var detail = card.querySelector('.svc-detail');
      var excerpt = card.querySelector('p');
      svcBody.innerHTML = (title ? '<h2>' + title.textContent + '</h2>' : '') + (excerpt ? '<p>' + excerpt.textContent + '</p>' : '') + (detail ? detail.innerHTML : '');
      svcDialog.hidden = false;
    }
    card.addEventListener('click', function (e) {
      if (e.target.closest('a')) return;
      openSvc();
    });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openSvc();
      }
    });
  });
})();
