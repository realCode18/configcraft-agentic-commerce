(function () {
	'use strict';

	var scanPanel = document.querySelector('[data-dxaic-auto-refresh="1"]');
	if (scanPanel) {
		var refreshToggle = scanPanel.querySelector('.dxaic-auto-refresh-toggle');
		var refreshTimer = null;
		var refreshDelay = 5000;

		function scheduleRefresh() {
			refreshTimer = window.setTimeout(function () {
				window.location.reload();
			}, refreshDelay);
		}

		function pauseRefresh() {
			if (refreshTimer) {
				window.clearTimeout(refreshTimer);
				refreshTimer = null;
			}
			if (refreshToggle) {
				refreshToggle.textContent = refreshToggle.dataset.resumeLabel;
				refreshToggle.setAttribute('aria-pressed', 'true');
			}
		}

		function resumeRefresh() {
			if (!refreshTimer) {
				scheduleRefresh();
			}
			if (refreshToggle) {
				refreshToggle.textContent = refreshToggle.dataset.pauseLabel;
				refreshToggle.setAttribute('aria-pressed', 'false');
			}
		}

		if (refreshToggle) {
			refreshToggle.hidden = false;
			refreshToggle.addEventListener('click', function () {
				if (refreshTimer) {
					pauseRefresh();
				} else {
					resumeRefresh();
				}
			});
		}

		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			pauseRefresh();
		} else {
			resumeRefresh();
		}
	}

	if (!document.querySelector('.dxaic-results-panel') || !window.fetch || !window.DOMParser || !window.URL || !window.URLSearchParams) {
		return;
	}

	function addResultsAnchor(url) {
		var fallbackUrl = new window.URL(url, window.location.href);
		fallbackUrl.hash = 'dxaic-catalog-results';
		return fallbackUrl.toString();
	}

	function updateResults(url, updateHistory) {
		var currentPanel = document.querySelector('.dxaic-results-panel');
		if (!currentPanel) {
			window.location.assign(addResultsAnchor(url));
			return;
		}

		var status = currentPanel.querySelector('.dxaic-filter-status');
		var submit = currentPanel.querySelector('.dxaic-filters button[type="submit"]');
		var previousTop = currentPanel.getBoundingClientRect().top;

		currentPanel.classList.add('is-loading');
		currentPanel.setAttribute('aria-busy', 'true');
		if (status) {
			status.textContent = currentPanel.dataset.dxaicFilteringLabel;
		}
		if (submit) {
			submit.disabled = true;
		}

		window.fetch(url, {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		}).then(function (response) {
			if (!response.ok) {
				throw new Error('Catalog filter request failed.');
			}
			return response.text();
		}).then(function (html) {
			var parsed = new window.DOMParser().parseFromString(html, 'text/html');
			var nextPanel = parsed.querySelector('.dxaic-results-panel');
			if (!nextPanel) {
				throw new Error('Catalog results were unavailable.');
			}

			currentPanel.replaceWith(nextPanel);
			window.scrollBy(0, nextPanel.getBoundingClientRect().top - previousTop);
			if (updateHistory && window.history && window.history.pushState) {
				window.history.pushState({ dxaicFilters: true }, '', url);
			}

			var nextStatus = nextPanel.querySelector('.dxaic-filter-status');
			if (nextStatus) {
				nextStatus.textContent = nextPanel.dataset.dxaicUpdatedLabel;
			}
		}).catch(function () {
			window.location.assign(addResultsAnchor(url));
		});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('.dxaic-filters');
		if (!form) {
			return;
		}

		event.preventDefault();
		var target = new window.URL(form.action, window.location.href);
		var parameters = new window.URLSearchParams(new window.FormData(form));
		parameters.delete('dxaic_paged');
		target.search = parameters.toString();
		updateResults(target.toString(), true);
	});

	document.addEventListener('click', function (event) {
		var link = event.target.closest('.dxaic-clear-filters, .dxaic-results-panel .tablenav-pages a');
		if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
			return;
		}

		event.preventDefault();
		updateResults(link.href, true);
	});

	window.addEventListener('popstate', function () {
		if (document.querySelector('.dxaic-results-panel')) {
			updateResults(window.location.href, false);
		}
	});
}());
