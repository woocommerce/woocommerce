/**
 * Internal dependencies
 */
import { cart } from '../requests/shopper/cart.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';

export const options = {
	// These figures represent the maximum standard wp-env container usage without causing
	// timeouts (on Mac M4 Pro). More powerful servers will have different thresholds.
	scenarios: {
		// Guest checkout: 60% of checkouts.
		checkout_guest_bfcm: {
			executor: 'ramping-arrival-rate',
			exec: 'checkoutGuestFlow',
			startRate: 1,
			timeUnit: '10s',
			preAllocatedVUs: 10,
			maxVUs: 40,
			stages: [
				{ duration: '4m', target: 6 }, // Ramp to peak.
				{ duration: '8m', target: 6 }, // Sustain peak.
				{ duration: '2m', target: 0 }, // Ramp down.
			],
		},
		// Authenticated checkout: 40% of checkouts.
		checkout_customer_bfcm: {
			executor: 'ramping-arrival-rate',
			exec: 'checkoutCustomerLoginFlow',
			startRate: 1,
			timeUnit: '10s',
			preAllocatedVUs: 8,
			maxVUs: 30,
			stages: [
				{ duration: '4m', target: 4 }, // Ramp to peak.
				{ duration: '8m', target: 4 }, // Sustain peak.
				{ duration: '2m', target: 0 }, // Ramp down.
			],
		},
	},
	thresholds: {
		http_req_duration: [
			'p(50)<200',
			'p(90)<1000',
			'p(95)<1500',
			'p(99.9)<3000',
		],
		http_req_failed: [ 'rate<0.01' ],
	},
};

export function checkoutGuestFlow() {
	cart();
	checkoutGuest();
}

export function checkoutCustomerLoginFlow() {
	cart();
	checkoutCustomerLogin();
}
