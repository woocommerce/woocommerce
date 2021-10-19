import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
	base_url,
	product_sku,
	product_id,
	coupon_code,
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
	contentTypeRequestHeader,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let addToCartTrend = new Trend("wc_post_wc-ajax_add_to_cart");
let viewCartTrend = new Trend("wc_get_cart");
let applyCouponTrend = new Trend("wc_post_wc-ajax_apply_coupon");
let applyCouponCartTrend = new Trend("wc_post_cart");

export function cartApplyCoupon() {
	let response;
	let apply_coupon_nonce;
	let item_name;
	let woocommerce_cart_nonce;

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
		apply_coupon_nonce = findBetween(
			response.body,
			'apply_coupon_nonce":"',
			'","'
		);
		item_name = findBetween(
			response.body,
			'name="cart[',
			'][qty]'
		);
		woocommerce_cart_nonce = response
			.html()
			.find("input[name=woocommerce-cart-nonce]")
			.first()
			.attr("value");
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Apply Coupon", function () {
		var requestheaders = Object.assign({},
			jsonRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders,
			contentTypeRequestHeader
		);

		response = http.post(
			`${base_url}/?wc-ajax=apply_coupon`,
			{
				coupon_code: `${coupon_code}`,
				security: `${apply_coupon_nonce}`,
			},
			{
				headers: requestheaders,
			}
		);

		applyCouponTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Coupon code applied successfully'": (response) =>
				response.body.includes("Coupon code applied successfully"),
		});

		response = http.post(`${base_url}/cart`,
		{
        	_wp_http_referer: "%2Fcart",
			//"cart["+`${item_name}`+"][qty]": "1",
			coupon_code: "",
        	"woocommerce-cart-nonce": `${woocommerce_cart_nonce}`,
		},
		{
			headers: requestheaders,
		}
		);
		applyCouponCartTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'woocommerce-remove-coupon' class": (response) =>
				response.body.includes('class="woocommerce-remove-coupon"'),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	cartApplyCoupon();
}
