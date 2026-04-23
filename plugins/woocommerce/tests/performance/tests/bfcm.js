/**
 * Internal dependencies
 */
import { cart } from '../requests/shopper/cart.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';

export const options = {
	// Saturation profile (M4 Pro, wp-env defaults):
	// - Bottleneck: Apache prefork MaxRequestWorkers=150 (not DB — MariaDB Threads_running stays at 1).
	// - At 0.8 checkout/s workers plateau at ~37/150, spike to ~56/150, then self-correct — bounded, no cascade.
	// - At 1.0/s workers climb unboundedly to ~117/150, causing request queuing and timeouts.
	// - Requires a clean Apache baseline: restart the WordPress container between runs (init-environment.sh).
	// - To raise the ceiling: increase MaxRequestWorkers in Apache MPM prefork config and re-profile.
	scenarios: {
		// Guest checkout: 60% of checkouts.
		checkout_guest_bfcm: {
			executor: 'ramping-arrival-rate',
			exec: 'checkoutGuestFlow',
			startRate: 1,
			timeUnit: '10s',
			preAllocatedVUs: 6,
			maxVUs: 15,
			stages: [
				{ duration: '4m', target: 5 }, // Ramp to peak.
				{ duration: '8m', target: 5 }, // Sustain peak.
				{ duration: '2m', target: 0 }, // Ramp down.
			],
		},
		// Authenticated checkout: 40% of checkouts.
		checkout_customer_bfcm: {
			executor: 'ramping-arrival-rate',
			exec: 'checkoutCustomerLoginFlow',
			startRate: 1,
			timeUnit: '10s',
			preAllocatedVUs: 4,
			maxVUs: 10,
			stages: [
				{ duration: '4m', target: 3 }, // Ramp to peak.
				{ duration: '8m', target: 3 }, // Sustain peak.
				{ duration: '2m', target: 0 }, // Ramp down.
			],
		},
	},
	thresholds: {
		// Aggregate thresholds across all requests.
		http_req_duration: [ 'p(50)<200', 'p(90)<1000', 'p(95)<1500', 'p(99.9)<3000' ],
		http_req_failed:   [ 'rate<0.01' ],

		// Per-request thresholds: cart workflow.
		'http_req_duration{name:Shopper - wc-ajax=add_to_cart}':             [ 'p(95)<500' ],
		'http_req_duration{name:Shopper - wc-ajax=get_refreshed_fragments}': [ 'p(95)<200' ],
		'http_req_duration{name:Shopper - View Cart}':                       [ 'p(95)<500' ],

		// Per-request thresholds: checkout workflow.
		'http_req_duration{name:Shopper - View Checkout}':             [ 'p(95)<1000' ],
		'http_req_duration{name:Shopper - Login to Checkout}':         [ 'p(95)<750' ],
		'http_req_duration{name:Shopper - Store API update-customer}': [ 'p(95)<500' ],
		'http_req_duration{name:Shopper - Store API checkout}':        [ 'p(95)<3000' ],
		'http_req_duration{name:Shopper - Order Received}':            [ 'p(95)<1000' ],
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
