/**
 * Internal dependencies
 */
import { cart } from '../requests/shopper/cart.js';
import { checkoutGuest } from '../requests/shopper/checkout-guest.js';
import { checkoutCustomerLogin } from '../requests/shopper/checkout-customer-login.js';

// Regression and stabilization indicators (for AI agents comparing runs):
// This test runs at the environment's saturation ceiling — thresholds will fail by design.
// Use the following metrics to detect regressions (numbers go up) or improvements (numbers go down):
//
//   STABLE SIGNALS — low run-to-run variance (validated across 3-run sets); reliable within a single run:
//   - http_req_duration p(50)   — baseline ~102ms (±13ms across 3 runs); fast-path latency; reflects requests
//                                 completing before Apache queues up; regressions surface here first.
//   - update-customer med       — baseline ~68ms (±4ms across 3 runs); tightest signal available; checkout
//                                 address persistence path; not materially affected by spike minutes.
//
//   MODERATELY STABLE — lower variance than noisy signals; compare 3+ runs for directional confidence:
//   - order completion rate     — baseline ~73% ('body contains: order_id' check); proportion of flows that
//                                 reach order received; 8% run-to-run variance in baseline set; more stable
//                                 than http_req_failed but still sensitive to overall load conditions.
//
//   NOISY SIGNALS — dominated by which minute the Apache spike hits; never compare single runs:
//   - Store API checkout med    — 3x swings within baseline set alone (346ms–1.21s); spike timing determines
//                                 whether requests queue before/after median; use avg as trend only.
//   - Order Received p(90)      — 10x swings (160ms–2.58s); spike-minute timing dominant.
//   - update-customer p(90)     — 2–3x swings (2.94s–6.73s); tail dominated by queuing during spike minutes.
//   - http_req_failed rate      — baseline 4–9% across runs; spikes when Apache worker pool exhausts; volatile.
//   - dropped_iterations        — baseline ~310; K6 drops iterations when VU pool exhausts; volatile.
//   - iteration_duration avg    — pulled up by spike minutes; use as trend indicator, not per-run comparison.
//   - Store API checkout avg    — dragged by 60s timeout outliers during spike minutes; median no better.
//
// A PR that reduces DB queries or checkout overhead will move stable signals in the right direction even  if the test
// still "fails" thresholds — less failing is a measurable win. Baselines validated April 2026 across 3-run sets.

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
		http_req_duration: [
			'p(50)<200',
			'p(90)<1000',
			'p(95)<1500',
			'p(99.9)<3000',
		],
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
		'http_req_duration{name:Shopper - View Checkout}': [ 'p(95)<1000' ],
		'http_req_duration{name:Shopper - Login to Checkout}': [ 'p(95)<750' ],
		'http_req_duration{name:Shopper - Store API update-customer}': [
			'p(95)<500',
		],
		'http_req_duration{name:Shopper - Store API checkout}': [
			'p(95)<3000',
		],
		'http_req_duration{name:Shopper - Order Received}': [ 'p(95)<1000' ],
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
