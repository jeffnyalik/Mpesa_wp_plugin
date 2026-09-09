(function ($) {
	function notify($el, ok, message) {
		$el.removeClass('plug-one-ok plug-one-err');
		$el.addClass(ok ? 'plug-one-ok' : 'plug-one-err');
		$el.text(message);
	}

	$(document).on('click', '.plug-one-admin-action', function (event) {
		event.preventDefault();
		const $btn = $(this);
		const $box = $btn.closest('#plug-one-mpesa, .plug-one-admin-panel');
		const $result = $box.find('.plug-one-admin-result');

		$btn.prop('disabled', true);
		$.post(plugOneAdmin.ajaxUrl, {
			action: $btn.data('action'),
			nonce: plugOneAdmin.nonce,
			order_id: $btn.data('order'),
		})
			.done(function (res) {
				const ok = !!(res && res.success);
				const message = (res && res.data && res.data.message) || (ok ? 'Done.' : 'Request failed.');
				notify($result.length ? $result : $(), ok, message);
				if (ok) {
					window.setTimeout(function () {
						window.location.reload();
					}, 800);
				}
			})
			.fail(function () {
				notify($result, false, 'Request failed.');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});

	$(document).on('click', '#plug-one-test-connection', function (event) {
		event.preventDefault();
		const $btn = $(this);
		const $result = $('#plug-one-test-result');
		$btn.prop('disabled', true);
		$result.text('Testing…');
		$.post(plugOneAdmin.ajaxUrl, {
			action: 'plug_one_test_connection',
			nonce: plugOneAdmin.nonce,
		})
			.done(function (res) {
				const ok = !!(res && res.success);
				const message = (res && res.data && res.data.message) || (ok ? 'OK' : 'Failed');
				$result.toggleClass('plug-one-ok', ok).toggleClass('plug-one-err', !ok).text(message);
			})
			.fail(function () {
				$result.removeClass('plug-one-ok').addClass('plug-one-err').text('Request failed.');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});
})(jQuery);
