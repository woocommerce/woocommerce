import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { base_url, think_time_min, think_time_max } from "../../config.js";
import {
	htmlRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	contentTypeRequestHeader,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Nonce and cookie jar to be used in subsequent iterations
let heartbeat_nonce;
let jar;
// Custom metrics to add to standard results output.
let postTypeOrderTrend = new Trend("wc_get_post_type_order");
let wpAdminHeartbeatTrend = new Trend("wc_post_wp_admin_heartbeat");

export function ordersHeartbeat() {
	let response;

	// Request orders list only on first iteration to correlate heartbeat nonce
	if (__ITER == 0) {
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

			// Cookie jar for subsequent iterations so cookies won't be reset
			jar = http.cookieJar();
		});

		sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
	}

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
				jar
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
	ordersHeartbeat();
}
