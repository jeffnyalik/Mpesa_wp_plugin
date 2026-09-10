/**
 * Checkout Blocks payment method for Plug One M-Pesa.
 */
(function () {
	if (typeof window.wc === 'undefined' || !wc.wcBlocksRegistry || !wc.wcSettings) {
		return;
	}

	const { registerPaymentMethod } = wc.wcBlocksRegistry;
	const { getSetting } = wc.wcSettings;
	const { createElement, useState, useEffect } = wp.element;
	const { decodeEntities } = wp.htmlEntities;
	const { __ } = wp.i18n;

	const settings = getSetting('plug_one_mpesa_data', {});
	const label = decodeEntities(settings.title || 'M-Pesa');

	const PhoneField = (props) => {
		const { eventRegistration, emitResponse } = props;
		const [phone, setPhone] = useState(settings.billingPhone || '');
		const { onPaymentSetup } = eventRegistration;

		useEffect(() => {
			const unsubscribe = onPaymentSetup(async () => {
				if (!String(phone || '').replace(/\D/g, '')) {
					return {
						type: emitResponse.responseTypes.ERROR,
						message: __('Enter a valid Kenyan M-Pesa mobile number.', 'plug-one-lipa-na-m-pesa'),
					};
				}
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							plug_one_phone: phone,
						},
					},
				};
			});
			return unsubscribe;
		}, [phone, onPaymentSetup, emitResponse]);

		return createElement(
			'div',
			{ className: 'plug-one-blocks-phone' },
			settings.description
				? createElement('p', { className: 'plug-one-description' }, decodeEntities(settings.description))
				: null,
			createElement(
				'label',
				{ htmlFor: 'plug-one-blocks-phone' },
				__('M-Pesa phone number', 'plug-one-lipa-na-m-pesa')
			),
			createElement('input', {
				id: 'plug-one-blocks-phone',
				type: 'tel',
				className: 'plug-one-phone-input',
				value: phone,
				placeholder: '0712 345 678',
				autoComplete: 'tel',
				onChange: (event) => setPhone(event.target.value),
			})
		);
	};

	registerPaymentMethod({
		name: 'plug_one_mpesa',
		label: createElement('span', null, label),
		ariaLabel: label,
		canMakePayment: () => true,
		content: createElement(PhoneField),
		edit: createElement(PhoneField),
		supports: {
			features: settings.supports || ['products'],
		},
	});
})();
