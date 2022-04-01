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
let wpAdminHeartbeatTrend = new Trend("wc_post_wp_admin_heartbeat");

export function orders() {
	let response;
	let api_x_wp_nonce;
	let apiNonceHeader;
	let heartbeat_nonce;

	group("Orders Page", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/wp-admin/edit.php?post_type=shop_order`, {
			headers: requestHeaders,
		});
		postTypeOrderTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Orders' header": (response) =>
				response.body.includes("Orders</h1>"),
		});

		// Correlate nonce values for use in subsequent requests.
		heartbeat_nonce = findBetween(
			response.body,
			'heartbeatSettings = {"nonce":"',
			'"};'
		);
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

	group("WP Admin Heartbeat", function () {
		var requestHeaders = Object.assign({},
			jsonRequestHeader,
			commonRequestHeaders,
			contentTypeRequestHeader,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${base_url}/wp-admin/admin-ajax.php`,
			`_nonce=${heartbeat_nonce}&action=heartbeat&has_focus=true&interval=15&screen_id=shop_order`,
			{
				headers: requestHeaders,
			}
		);
		wpAdminHeartbeatTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	orders();
}
