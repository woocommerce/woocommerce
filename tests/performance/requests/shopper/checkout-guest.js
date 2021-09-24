import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
	base_url,
	addresses_guest_billing_first_name,
	addresses_guest_billing_last_name,
	addresses_guest_billing_company,
	addresses_guest_billing_country,
	addresses_guest_billing_address_1,
	addresses_guest_billing_address_2,
	addresses_guest_billing_city,
	addresses_guest_billing_state,
	addresses_guest_billing_postcode,
	addresses_guest_billing_phone,
	addresses_guest_billing_email,
	payment_method,
	think_time_min,
	think_time_max,
} from "../../config.js";
import {
	htmlRequestHeader,
	jsonRequestHeader,
	allRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let proceedCheckoutTrend1 = new Trend("wc_get_checkout");
let proceedCheckoutTrend2 = new Trend("wc_post_wc-ajax_update_order_review");
let placeOrderTrend = new Trend("wc_post_wc-ajax_checkout");
let orderReceivedTrend1 = new Trend("wc_get_order_received");
let orderReceivedTrend2 = new Trend("wc_post_wc-ajax_get_refreshed_fragments");

export function checkoutGuest() {
	let response;
	let woocommerce_process_checkout_nonce_guest;
	let update_order_review_nonce_guest;

	group("Proceed to checkout", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/checkout`, {
			headers: requestHeaders,
		});
		proceedCheckoutTrend1.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'woocommerce-checkout' class": (response) =>
				response.body.includes('class="checkout woocommerce-checkout"'),
		});

		// Correlate nonce values for use in subsequent requests.
		woocommerce_process_checkout_nonce_guest = response
			.html()
			.find("input[name=woocommerce-process-checkout-nonce]")
			.first()
			.attr("value");
		update_order_review_nonce_guest = findBetween(
			response.body,
			'update_order_review_nonce":"',
			'","apply_coupon_nonce'
		);

		var requestHeaders = Object.assign({},
			allRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${base_url}/?wc-ajax=update_order_review`,
			{
				security: `${update_order_review_nonce_guest}`,
				payment_method: `${payment_method}`,
				country: `${addresses_guest_billing_country}`,
				state: `${addresses_guest_billing_state}`,
				postcode: `${addresses_guest_billing_postcode}`,
				city: `${addresses_guest_billing_city}`,
				address: `${addresses_guest_billing_address_1}`,
				address_2: `${addresses_guest_billing_address_2}`,
				s_country: `${addresses_guest_billing_country}`,
				s_state: `${addresses_guest_billing_state}`,
				s_postcode: `${addresses_guest_billing_postcode}`,
				s_city: `${addresses_guest_billing_city}`,
				s_address: `${addresses_guest_billing_address_1}`,
				s_address_2: `${addresses_guest_billing_address_2}`,
				has_full_address: "true",
			},
			{
				headers: requestHeaders,
			}
		);
		proceedCheckoutTrend2.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Place Order", function () {
		var requestHeaders = Object.assign({},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${base_url}/?wc-ajax=checkout`,
			{
				billing_first_name: `${addresses_guest_billing_first_name}`,
				billing_last_name: `${addresses_guest_billing_last_name}`,
				billing_company: `${addresses_guest_billing_company}`,
				billing_country: `${addresses_guest_billing_country}`,
				billing_address_1: `${addresses_guest_billing_address_1}`,
				billing_address_2: `${addresses_guest_billing_address_2}`,
				billing_city: `${addresses_guest_billing_city}`,
				billing_state: `${addresses_guest_billing_state}`,
				billing_postcode: `${addresses_guest_billing_postcode}`,
				billing_phone: `${addresses_guest_billing_phone}`,
				billing_email: `${addresses_guest_billing_email}`,
				order_comments: "",
				payment_method: `${payment_method}`,
				"woocommerce-process-checkout-nonce": `${woocommerce_process_checkout_nonce_guest}`,
				_wp_http_referer: "%2F%3Fwc-ajax%3Dupdate_order_review",
			},
			{
				headers: requestHeaders,
			}
		);
		placeOrderTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: order-received": (response) =>
				response.body.includes("order-received"),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Order received", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/checkout/order-received/`, {
			headers: requestHeaders,
		});
		check(response, {
			"body contains: 'Thank you. Your order has been received.'": (
				response
			) =>
				response.body.includes(
					"Thank you. Your order has been received."
				),
		});
		orderReceivedTrend1.add(response.timings.duration);

		var requestHeaders = Object.assign({},
			allRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(`${base_url}/?wc-ajax=get_refreshed_fragments`, {
			headers: requestHeaders,
		});
		orderReceivedTrend2.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	checkoutGuest();
}
