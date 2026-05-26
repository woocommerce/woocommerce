/**
 * Internal dependencies
 */
import { cart } from '../requests/shopper/cart.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';

// Sub-saturation checkout DB performance test.
//
// Purpose: measure PHP/DB latency on the checkout path (order saves, customer saves, etc.)
// without Apache worker exhaustion masking the signal. At 0.3 checkout/s Apache workers
// stay well below the spike zone (~10–20/150), so median and p(90) reflect actual
// server-side processing time rather than queue wait time.
//
// Contrast with bfcm-infra-hammered.js, which runs at the saturation ceiling (0.8/s) where Apache
// dominates and only p(50) / update-customer median are reliable signals.
//
// All signals below are stable at this load level (validated across 3-run sets, April 2026):
//
//   - p(50) overall             — baseline ~77ms (±1ms); reflects fast-path latency; minimal variation.
//   - Store API checkout med    — baseline ~472ms (±67ms); most sensitive to order-path optimizations
//                                 (DB writes, order creation); stable here unlike at saturation.
//   - Store API checkout p(90)  — baseline ~2.99s (±1.73s); noisier than median due to occasional
//                                 outliers (WP-cron, cache misses); directionally reliable across 3+ runs.
//   - update-customer med       — baseline ~65ms (±1ms); tightest signal; checkout address persistence path.
//   - update-customer p(90)     — baseline ~77ms (±5ms); reliable here (no Apache queuing to inflate tail).
//   - Order Received p(90)      — baseline ~121ms (±7ms); downstream of checkout; stable.
//   - order completion rate     — baseline 100%; any drop below 100% indicates a correctness regression.
//   - http_req_failed           — baseline 0%; meaningful signal here (not spike noise as in hammered.js).
//   - dropped_iterations        — baseline ~3; near-zero confirms sub-saturation; spike = VU config issue.
//
// Note: Store API checkout p(95) fails the 1000ms threshold in both baseline and less-writes sets
// (range 2.5–4.8s) due to infrequent outlier requests. This threshold is aspirational; watch the
// median and p(90) for directional signals, not p(95).
//
// Requires a clean Apache baseline — restart the WordPress container between runs:
//   bash plugins/woocommerce/tests/performance/utils/init-environment.sh
//
// Comparing runs: a single run is sufficient for directional confidence at this load level.
// Run 2–3 times if you want variance bounds on noisy signals (checkout p(90)).

export const options = {
	// Sub-saturation profile (M4 Pro, wp-env defaults):
	// - Total: 2/10s guest + 1/10s customer = 0.3 checkout/s.
	// - Apache workers stay at ~10–20/150; no spike zone, no request queuing.
	// - At this rate all iterations complete; dropped_iterations should be near zero.
	// - See bfcm-infra-hammered.js for the saturation ceiling profile and its measurement limitations.
	scenarios: {
		// Guest checkout: 60% of checkouts.
		checkout_guest: {
			executor: 'ramping-arrival-rate',
			exec: 'checkoutGuestFlow',
			startRate: 1,
			timeUnit: '10s',
			preAllocatedVUs: 3,
			maxVUs: 6,
			stages: [
				{ duration: '4m', target: 2 }, // Ramp to peak.
				{ duration: '8m', target: 2 }, // Sustain peak.
				{ duration: '2m', target: 0 }, // Ramp down.
			],
		},
		// Authenticated checkout: 40% of checkouts.
		checkout_customer: {
			executor: 'ramping-arrival-rate',
			exec: 'checkoutCustomerLoginFlow',
			startRate: 1,
			timeUnit: '10s',
			preAllocatedVUs: 2,
			maxVUs: 4,
			stages: [
				{ duration: '4m', target: 1 }, // Ramp to peak.
				{ duration: '8m', target: 1 }, // Sustain peak.
				{ duration: '2m', target: 0 }, // Ramp down.
			],
		},
	},
	thresholds: {
		// Aggregate thresholds across all requests.
		http_req_duration: [ 'p(50)<200', 'p(90)<500', 'p(95)<1000' ],
		http_req_failed: [ 'rate<0.01' ],

		// Per-request thresholds: cart workflow.
		'http_req_duration{name:Shopper - wc-ajax=add_to_cart}': [
			'p(95)<500',
		],
		'http_req_duration{name:Shopper - wc-ajax=get_refreshed_fragments}': [
			'p(95)<200',
		],
		'http_req_duration{name:Shopper - View Cart}': [ 'p(95)<500' ],

		// Per-request thresholds: checkout workflow.
		'http_req_duration{name:Shopper - View Checkout}': [ 'p(95)<500' ],
		'http_req_duration{name:Shopper - Login to Checkout}': [ 'p(95)<500' ],
		'http_req_duration{name:Shopper - Store API update-customer}': [
			'p(95)<200',
		],
		'http_req_duration{name:Shopper - Store API checkout}': [
			'p(95)<1000',
		],
		'http_req_duration{name:Shopper - Order Received}': [ 'p(95)<500' ],
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
