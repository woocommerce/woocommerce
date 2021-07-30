import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
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

export function cart() {
	let response;

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
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	cart();
}
