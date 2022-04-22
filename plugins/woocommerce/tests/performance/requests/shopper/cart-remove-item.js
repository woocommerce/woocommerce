import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
	base_url,
	product_sku,
	product_id,
	think_time_min,
	think_time_max,
} from "../../config.js";
import {
	htmlRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let addToCartTrend = new Trend("wc_post_wc-ajax_add_to_cart");
let viewCartTrend = new Trend("wc_get_cart");
let removeItemCartTrend = new Trend("wc_get_cart_remove_item");

export function cartRemoveItem() {
	let response;
	let item_to_remove;
	let wpnonce;

	group("Product Page Add to cart", function () {
		var requestheaders = Object.assign({},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${base_url}/?wc-ajax=add_to_cart`,
			{
				product_sku: `${product_sku}`,
				product_id: `${product_id}`,
				quantity: "1",
			},
			{
				headers: requestheaders,
			}
		);
		addToCartTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("View Cart", function () {
		var requestheaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/cart`, {
			headers: requestheaders,
		});
		viewCartTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body does not contain: 'your cart is currently empty'": (
				response
			) => !response.body.includes("Your cart is currently empty."),
		});

		// Correlate cart item value for use in subsequent requests.
		item_to_remove = findBetween(
			response.body,
			'?remove_item=',
			'&'
		);
		wpnonce = findBetween(
			response.body,
			'_wpnonce=',
			'" class="remove"'
		);
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Remove item from cart", function () {
		var requestheaders = Object.assign({},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/cart?remove_item=${item_to_remove}&_wpnonce=${wpnonce}`, {
			headers: requestheaders,
		});
		removeItemCartTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'removed'": (response) =>
				response.body.includes(" removed."),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	cartRemoveItem();
}
