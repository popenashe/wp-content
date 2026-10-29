(function () {
  'use strict';
  var main = document.getElementById('main');
  if (!main || !main.querySelector('.tabs') || !window.fetch || !window.DOMParser) return;

  var cache = {};

  function get(url) {
    if (!cache[url]) {
      cache[url] = fetch(url, { credentials: 'same-origin' }).then(function (r) {
        if (!r.ok) throw new Error(r.status);
        return r.text();
      }).then(function (html) {
        return new DOMParser().parseFromString(html, 'text/html');
      });
      cache[url].catch(function () { delete cache[url]; });
    }
    return cache[url];
  }

  // Mirrors the filter and news search behaviour in site/js/main.js for swapped-in content.
  function bind(scope) {
    scope.querySelectorAll('[data-filter-group]').forEach(function (group) {
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
    var newsSearch = scope.querySelector('#news-search');
    if (newsSearch) newsSearch.addEventListener('input', function () {
      var q = newsSearch.value.trim().toLowerCase();
      document.querySelectorAll('#releases [data-cat]').forEach(function (el) { el.hidden = q && el.textContent.toLowerCase().indexOf(q) === -1; });
    });
  }

  function show(url, push) {
    var tabs = main.querySelector('.tabs');
    var before = tabs ? tabs.getBoundingClientRect().top : 0;
    return get(url).then(function (doc) {
      var next = doc.getElementById('main');
      if (!next || !next.querySelector('.tabs')) throw new Error('no tabs');
      main.innerHTML = next.innerHTML;
      document.title = doc.title;
      if (push) history.pushState({ tabs: true }, '', url);
      bind(main);
      var now = main.querySelector('.tabs');
      window.scrollBy(0, now.getBoundingClientRect().top - before);
      var current = now.querySelector('[aria-current="page"]');
      if (current) current.focus({ preventScroll: true });
    }).catch(function () { location.href = url; });
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('.tabs a');
    if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (a.origin !== location.origin || a.getAttribute('aria-current') === 'page') {
      if (a.getAttribute('aria-current') === 'page') e.preventDefault();
      return;
    }
    e.preventDefault();
    show(a.href, true);
  });

  window.addEventListener('popstate', function (e) {
    if (e.state && e.state.tabs) show(location.href, false);
  });

  history.replaceState({ tabs: true }, '', location.href);
  main.querySelectorAll('.tabs a').forEach(function (a) { if (a.origin === location.origin) get(a.href); });
})();
