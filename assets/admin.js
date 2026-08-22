(function () {
	'use strict';

	var scanPanel = document.querySelector('[data-ccac-auto-refresh="1"]');
	if (!scanPanel) {
		return;
	}

	window.setTimeout(function () {
		window.location.reload();
	}, 5000);
}());
