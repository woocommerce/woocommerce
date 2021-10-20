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
	allRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let myAccountTrend = new Trend("wc_get_myaccount");
let myAccountLoginTrend = new Trend("wc_post_myaccount");
let myAccountLoginTrend2 = new Trend("wc_post_wc-ajax_get_refreshed_fragments");

export function myAccountMerchantLogin() {
	let response;
	let woocommerce_login_nonce;

	group("My Account", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/my-account`, {
			headers: requestHeaders,
		});
		myAccountTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'My account' title": (response) =>
				response.body.includes(
					'<h1 class="entry-title">My account</h1>'
				),
		});

		// Correlate nonce value for use in subsequent requests.
		woocommerce_login_nonce = response
			.html()
			.find("input[name=woocommerce-login-nonce]")
			.first()
			.attr("value");
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("My Account Login", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(
			`${base_url}/my-account`,
			{
				username: `${admin_username}`,
				password: `${admin_password}`,
				"woocommerce-login-nonce": `${woocommerce_login_nonce}`,
				_wp_http_referer: "/my-account",
				login: "Log%20in",
			},
			{
				headers: {
					headers: requestHeaders,
				},
			}
		);
		myAccountLoginTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: my account welcome message": (response) =>
				response.body.includes(
					"From your account dashboard you can view"
				),
		});

		var requestHeaders = Object.assign({},
			allRequestHeader,
			commonRequestHeaders,
			commonPostRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.post(`${base_url}/?wc-ajax=get_refreshed_fragments`, {
			headers: requestHeaders,
		});
		myAccountLoginTrend2.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	myAccountMerchantLogin();
}
