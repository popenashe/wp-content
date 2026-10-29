(function () {
	'use strict';
	if (typeof maplibregl === 'undefined') {
		return;
	}

	var STYLE = 'https://tiles.openfreemap.org/styles/liberty';
	var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-map-thumb]'));
	if (!nodes.length) {
		return;
	}

	var live = [];

	function start(el) {
		if (el.getAttribute('data-map-ready') === '1') {
			return;
		}
		var lat = parseFloat(el.getAttribute('data-lat'));
		var lng = parseFloat(el.getAttribute('data-lng'));
		var zoom = parseFloat(el.getAttribute('data-zoom') || '14');
		var host = el.querySelector('.map-thumb');
		if (!host || !isFinite(lat) || !isFinite(lng)) {
			return;
		}
		var map;
		try {
			map = new maplibregl.Map({
				container: host,
				style: STYLE,
				center: [lng, lat],
				zoom: zoom,
				interactive: false,
				attributionControl: false,
				fadeDuration: 0,
				pitchWithRotate: false
			});
		} catch (err) {
			return;
		}
		el.setAttribute('data-map-ready', '1');
		map.once('load', function () {
			map.resize();
			new maplibregl.Marker({ color: '#1B2A4E', scale: 0.7 }).setLngLat([lng, lat]).addTo(map);
		});
		live.push({ el: el, map: map });
	}

	function stop(el) {
		var next = [];
		live.forEach(function (item) {
			if (item.el !== el) {
				next.push(item);
				return;
			}
			try {
				item.map.remove();
			} catch (err) {}
			el.removeAttribute('data-map-ready');
			var host = el.querySelector('.map-thumb');
			if (host) {
				host.innerHTML = '';
			}
		});
		live = next;
	}

	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						start(entry.target);
					} else {
						stop(entry.target);
					}
				});
			},
			{ rootMargin: '120px' }
		);
		nodes.forEach(function (node) {
			io.observe(node);
		});
	} else {
		nodes.forEach(start);
	}
})();
