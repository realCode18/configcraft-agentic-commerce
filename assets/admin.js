(function () {
	'use strict';

	var scanPanel = document.querySelector('[data-dxaic-auto-refresh="1"]');
	if (!scanPanel) {
		return;
	}

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
}());
