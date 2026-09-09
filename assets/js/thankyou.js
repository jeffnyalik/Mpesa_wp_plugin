/**
 * Poll M-Pesa order status on the thank-you page (3s × 24 ≈ 72s, same as sing_africa).
 */
(function () {
	const config = window.plugOneThankyou;
	if (!config || !config.restUrl) {
		return;
	}

	const statusEl = document.querySelector('.plug-one-waiting__status');
	let attempts = 0;

	function setMessage(text) {
		if (statusEl) {
			statusEl.textContent = text;
		}
	}

	function poll() {
		attempts += 1;
		const url = config.restUrl + (config.restUrl.indexOf('?') === -1 ? '?' : '&') + 'key=' + encodeURIComponent(config.orderKey);

		fetch(url, { credentials: 'same-origin' })
			.then((res) => (res.ok ? res.json() : Promise.reject()))
			.then((data) => {
				if (data.paid) {
					setMessage(data.message || 'Payment received.');
					window.location.reload();
					return;
				}

				if (data.status && data.status !== 'pending') {
					setMessage(data.message || 'Payment was not completed.');
					return;
				}

				if (attempts >= config.maxAttempts) {
					setMessage('Still waiting for M-Pesa. Keep this page open or check your order email.');
					return;
				}

				window.setTimeout(poll, config.pollMs || 3000);
			})
			.catch(() => {
				if (attempts >= config.maxAttempts) {
					return;
				}
				window.setTimeout(poll, config.pollMs || 3000);
			});
	}

	window.setTimeout(poll, config.pollMs || 3000);
})();
