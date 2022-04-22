import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { base_url, think_time_min, think_time_max } from "../../config.js";
import {
	htmlRequestHeader,
	jsonAPIRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	contentTypeRequestHeader,
	commonPostRequestHeaders,
	commonAPIGetRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let postTypeOrderTrend = new Trend("wc_get_post_type_order");
let wcAdminNotesMainTrend = new Trend("wc_get_admin_notes_main");
let wcAdminNotesOtherTrend = new Trend("wc_get_admin_notes_other");
let wcAdminCESOptionsTrend = new Trend("wc_get_admin_options_ces");
let wcAdminOrdersActivityTrend = new Trend("wc_get_analytics_orders");
let wcAdminProductsActivityTrend = new Trend("wc_get_analytics_products");
let wcAdminReviewsActivityTrend = new Trend("wc_get_analytics_reviews");

export function homeWCAdmin() {
	let response;
	let api_x_wp_nonce;
	let apiNonceHeader;
	let heartbeat_nonce;

	group("WC Home Page", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/wp-admin/admin.php?page=wc-admin`, {
			headers: requestHeaders,
		});
		postTypeOrderTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: current page is 'Home'": (response) =>
				response.body.includes('aria-current="page">Home</a>'),
		});

		// Correlate nonce values for use in subsequent requests.
		api_x_wp_nonce = findBetween(
			response.body,
			'wp-json\\/","nonce":"',
			'",'
		);

		// Create request header with nonce value for use in subsequent requests.
		apiNonceHeader = {
			"x-wp-nonce": `${api_x_wp_nonce}`,
		};
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Inbox Notes", function () {
		var requestHeaders = Object.assign({},
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonAPIGetRequestHeaders,
			apiNonceHeader,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-json/wc-analytics/admin/notes?page=1&per_page=25&` +
			`status=unactioned&type%5B0%5D=info&type%5B1%5D=marketing&type%5B2%5D=survey&type%5B3%5D=warning&` +
			`orderby=date&order=desc&_locale=user`,
			{
				headers: requestHeaders,
			}
		);
		wcAdminNotesMainTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});

		response = http.get(
			`${base_url}/wp-json/wc-analytics/admin/notes?page=1&per_page=25&` +
			`type=error%2Cupdate&status=unactioned&_locale=user`,
			{
				headers: requestHeaders,
			}
		);
		wcAdminNotesOtherTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	group("Orders Activity", function () {
		var requestHeaders = Object.assign({},
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonAPIGetRequestHeaders,
			apiNonceHeader,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-json/wc-analytics/orders?page=1&per_page=1&status%5B0%5D=processing&` +
			`status%5B1%5D=on-hold&_fields%5B0%5D=id&_locale=user`,
			{
				headers: requestHeaders,
			}
		);
		wcAdminOrdersActivityTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	group("Reviews Activity", function () {
		var requestHeaders = Object.assign({},
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonAPIGetRequestHeaders,
			apiNonceHeader,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-json/wc-analytics/products/reviews?page=1&per_page=1&status=hold&` +
			`_embed=1&_fields%5B0%5D=id&_locale=user`,
			{
				headers: requestHeaders,
			}
		);
		wcAdminReviewsActivityTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	group("Products Activity", function () {
		var requestHeaders = Object.assign({},
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonAPIGetRequestHeaders,
			apiNonceHeader,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-json/wc-analytics/products/low-in-stock?page=1&per_page=1&` +
			`low_in_stock=true&status=publish&_fields%5B0%5D=id&_locale=user`,
			{
				headers: requestHeaders,
			}
		);
		wcAdminProductsActivityTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	group("CES Options", function () {
		var requestHeaders = Object.assign({},
			jsonAPIRequestHeader,
			commonRequestHeaders,
			commonAPIGetRequestHeaders,
			apiNonceHeader,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-json/wc-admin/options?options=woocommerce_ces_tracks_queue&_locale=user`,
			{
				headers: requestHeaders,
			}
		);
		wcAdminCESOptionsTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	homeWCAdmin();
}
