jQuery(function ($) {
	// PayPal JS SDK script
	// eslint-disable-next-line max-len
	var PAYPAL_SCRIPT = 'https://www.sandbox.paypal.com/sdk/js?client-id=' + paypal_standard.client_id + '&currency=USD&intent=capture&buyer-country=US&locale=en_US&merchant-id=sb-reivw44753332@business.example.com';
	var script = document.createElement('script');
	script.setAttribute('src', PAYPAL_SCRIPT);
	script.setAttribute('data-page-type', 'checkout')
	document.head.appendChild(script);

	// Check if the container exists
	const container = document.getElementById('paypal-standard-container');
	if ( !container ) {
		return;
	}

	// Wait for PayPal SDK to be loaded.
	function waitForPayPal() {
		return new Promise((resolve) => {
			if ( typeof paypal !== 'undefined' ) {
				resolve();
				return;
			} 

			const checkPayPal = setInterval(() => {
				if ( typeof paypal !== 'undefined' ) {
					clearInterval(checkPayPal);
					resolve();
				}
			}, 5000);
		});
	}

	waitForPayPal().then(() => {

		paypal.Buttons({
			style: {
				layout: 'vertical',
				color: 'gold',
				shape: 'rect',
				label: 'paypal'
			},

			async createOrder() {
				// TODO: Add createOrder logic here
			},

			async onApprove(data) {
				// TODO: Add onApprove logic here
			},

			onError: function (err) {
				// TODO: Add onError logic here
				console.error( 'PayPal error:', err );
			},

			onCancel(data) {
				// TODO: Add onCancel logic here
			},

			onClick() {
				// TODO: Add onClick logic here
			},

		}).render('#paypal-standard-container')
			.catch(function (error) {
				console.error( 'Error rendering PayPal button:', error );
			});
	});

});