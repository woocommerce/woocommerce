import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import {
	base_url,
	admin_username,
	admin_password,
	think_time_min,
	think_time_max,
} from "../../config.js";
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let wpLoginTrend = new Trend("wc_get_wp_login");
let wpLoginWPAdminTrend = new Trend("wc_post_wp_login");

export function wpLogin() {
	let response;
	let testcookie;

	group("Login Page", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/wp-login.php`, {
			headers: requestHeaders,
		});
		wpLoginTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Log in' title": (response) =>
				response.body.includes("<title>Log In"),
		});

		// Correlate cookie value for use in subsequent requests.
		testcookie = response
			.html()
			.find("input[name=testcookie]")
			.first()
			.attr("value");
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Login to WP Admin", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${base_url}/wp-login.php`,
			{
				log: `${admin_username}`,
				pwd: `${admin_password}`,
				"wp-submit": "Log%20In",
				redirect_to: `${base_url}/wp-admin`,
				testcookie: `${testcookie}`,
			},
			{
				headers: requestHeaders,
			}
		);
		wpLoginWPAdminTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: wp-admin 'Dashboard' header": (response) =>
				response.body.includes("<h1>Dashboard</h1>"),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	wpLogin();
}
