import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { base_url, think_time_min, think_time_max } from "../../config.js";
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let postTypeOrderSearchTrend = new Trend("wc_get_post_type_order_search");

export function ordersSearch() {
	let response;

	group("Orders Search", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-admin/edit.php?s=${product_search_term}&` +
			`post_status=all&post_type=shop_order&action=-1&m=0&_customer_user&` +
			`paged=1&action2=-1`,
			{
				headers: requestHeaders,
			}
		);
		postTypeOrderSearchTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Orders' header": (response) =>
				response.body.includes("Search results for:"),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	ordersSearch();
}
