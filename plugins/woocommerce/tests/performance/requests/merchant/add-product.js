import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { URLSearchParams } from "https://jslib.k6.io/url/1.0.0/index.js";
import {
	base_url,
	add_product_title,
	add_product_regular_price,
	think_time_min,
	think_time_max,
} from "../../config.js";
import {
	htmlRequestHeader,
	jsonAPIRequestHeader,
	jsonRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonPostRequestHeaders,
	contentTypeRequestHeader,
	commonAPIGetRequestHeaders,
	commonNonStandardHeaders,
	apiNonceHeader,
	allRequestHeader,
} from "../../headers.js";

// Custom metrics to add to standard results output.
let postNewTypeProductTrend = new Trend("wc_get_new_post_type_product");
let wcAdminNotesMainTrend = new Trend("wc_get_admin_notes_main");
let wcAdminNotesOtherTrend = new Trend("wc_get_admin_notes_other");
let wcAdminCESOptionsTrend = new Trend("wc_get_admin_options_ces");
let wpAdminHeartbeatAutosaveTrend = new Trend("wc_post_wp_admin_ajax_heartbeat_autosave");
let wpAdminSamplePermalinkTrend = new Trend("wc_post_wp_admin_ajax_sample_permalink");

export function addProduct() {
	let response;
	let heartbeat_nonce;
	let ajax_nonce_add_meta;
	let ajax_nonce_add_product_cat;
	let wpnonce;
	let closed_postboxes_nonce;
	let sample_permalink_nonce;
	let woocommerce_meta_nonce;
	let meta_box_order_nonce;
	let post_id;
	let api_x_wp_nonce;
	let apiNonceHeader;

	group("Add New Product", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			{ origin: `${base_url}` },
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(
			`${base_url}/wp-admin/post-new.php?post_type=product`,
			{
				headers: requestHeaders,
			}
		);
		postNewTypeProductTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Add new product' header": (response) =>
				response.body.includes("Add new product</h1>"),
		});

		// Correlate nonce values for use in subsequent requests.
		ajax_nonce_add_meta = response
			.html()
			.find("input[id=_ajax_nonce-add-meta]")
			.first()
			.attr("value");
		ajax_nonce_add_product_cat = response
			.html()
			.find("input[id=_ajax_nonce-add-product_cat]")
			.first()
			.attr("value");
		wpnonce = response
			.html()
			.find("input[id=_wpnonce]")
			.first()
			.attr("value");
		closed_postboxes_nonce = response
			.html()
			.find("input[id=closedpostboxesnonce]")
			.first()
			.attr("value");
		sample_permalink_nonce = response
			.html()
			.find("input[id=samplepermalinknonce]")
			.first()
			.attr("value");
		woocommerce_meta_nonce = response
			.html()
			.find("input[id=woocommerce_meta_nonce]")
			.first()
			.attr("value");
		meta_box_order_nonce = response
			.html()
			.find("input[id=meta-box-order-nonce]")
			.first()
			.attr("value");
		post_id = response
			.html()
			.find("input[id=post_ID]")
			.first()
			.attr("value");
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

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Autosave heartbeat", function () {
		var requestHeaders = Object.assign({},
			commonNonStandardHeaders,
			jsonAPIRequestHeader,
			commonPostRequestHeaders,
			commonRequestHeaders
		);

		response = http.post(
			`${base_url}/wp-admin/admin-ajax.php`,
			{
				_nonce: `${heartbeat_nonce}`,
				action: "heartbeat",
				"data%5Bwp-refresh-post-lock%5D%5Bpost_id%5D": `${post_id}`,
				"data%5Bwp_autosave%5D%5B_wpnonce%5D": `${wpnonce}`,
				"data%5Bwp_autosave%5D%5Bauto_draft%5D": "1",
				"data%5Bwp_autosave%5D%5Bcatslist%5D": "",
				"data%5Bwp_autosave%5D%5Bcomment_status%5D": "open",
				"data%5Bwp_autosave%5D%5Bcontent%5D": "",
				"data%5Bwp_autosave%5D%5Bexcerpt%5D": "",
				"data%5Bwp_autosave%5D%5Bpost_author%5D": "1",
				"data%5Bwp_autosave%5D%5Bpost_id%5D": `${post_id}`,
				"data%5Bwp_autosave%5D%5Bpost_title%5D": `${add_product_title}`,
				"data%5Bwp_autosave%5D%5Bpost_type%5D": "product",
				has_focus: "true",
				interval: "15",
				screen_id: "product",
			},
			{
				headers: requestHeaders,
			}
		);
		wpAdminHeartbeatAutosaveTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
		});
	});

	group("Sample Permalink", function () {
		var requestHeaders = Object.assign({},
			commonNonStandardHeaders,
			allRequestHeader,
			contentTypeRequestHeader,
			commonPostRequestHeaders
		);

		response = http.post(
			`${base_url}/wp-admin/admin-ajax.php`,
			{
				action: "sample-permalink",
				new_title: `${add_product_title}`,
				post_id: `${post_id}`,
				samplepermalinknonce: `${sample_permalink_nonce}`,
			},
			{
				headers: requestHeaders,
			}
		);
		wpAdminSamplePermalinkTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Permalink:'": (response) =>
				response.body.includes("<strong>Permalink:</strong>"),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

	group("Update New Product", function () {

		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			contentTypeRequestHeader,
			commonNonStandardHeaders
		);

		const productParams = new URLSearchParams([
			["_ajax_nonce-add-meta", `${ajax_nonce_add_meta}`],
			["_ajax_nonce-add-product_cat", `${ajax_nonce_add_product_cat}`],
			["_backorders", "no"],
			["_button_text", ""],
			["_download_expiry", ""],
			["_download_limit", ""],
			["_height", ""],
			["_length", ""],
			["_low_stock_amount", ""],
			["_original_stock", "0"],
			["_product_url", ""],
			["_purchase_note", ""],
			["_regular_price", `${add_product_regular_price}`],
			["_sale_price", ""],
			["_sale_price_dates_from", ""],
			["_sale_price_dates_to", ""],
			["_sku", ""],
			["_stock", "0"],
			["_stock_status", "instock"],
			["_thumbnail_id", "-1"],
			["_visibility", "visible"],
			["_weight", ""],
			["_width", ""],
			["_wp_http_referer", ""],
			["_wp_original_http_referer", ""],
			["_wpnonce", `${wpnonce}`],
			["aa", "2021"],
			["action", "editpost"],
			["attribute_taxonomy", ""],
			["auto_draft", ""],
			["closedpostboxesnonce", `${closed_postboxes_nonce}`],
			["comment_status", "open"],
			["content", ""],
			["cur_aa", "2021"],
			["cur_hh", "15"],
			["cur_jj", "09"],
			["cur_mm", "07"],
			["cur_mn", "48"],
			["current_featured", "no"],
			["current_visibility", "visible"],
			["excerpt", ""],
			["hh", "15"],
			["hidden_aa", "2021"],
			["hidden_hh", "15"],
			["hidden_jj", "09"],
			["hidden_mm", "07"],
			["hidden_mn", "48"],
			["hidden_post_password", ""],
			["hidden_post_status", "draft"],
			["hidden_post_visibility", "public"],
			["jj", "09"],
			["menu_order", "0"],
			["meta-box-order-nonce", `${meta_box_order_nonce}`],
			["metakeyinput", ""],
			["metakeyselect", "%2523NONE%2523"],
			["metavalue", ""],
			["mm", "07"],
			["mn", "48"],
			["newproduct_cat", "New%2520category%2520name"],
			["newproduct_cat_parent", "-1"],
			["newtag%255Bproduct_tag%255D", ""],
			["original_post_status", "auto-draft"],
			["original_post_title", ""],
			["original_publish", "Publish"],
			["originalaction", "editpost"],
			["post_ID", `${post_id}`],
			["post_author", "1"],
			["post_name", ""],
			["post_password", ""],
			["post_status", "draft"],
			["post_title", `${add_product_title}`],
			["post_type", "product"],
			["product-type", "simple"],
			["product_image_gallery", ""],
			["product_shipping_class", "-1"],
			["publish", "Publish"],
			["referredby", ""],
			["samplepermalinknonce", `${sample_permalink_nonce}`],
			["ss", "12"],
			["tax_input%255Bproduct_cat%255D%255B%255D", "0"],
			["tax_input%255Bproduct_tag%255D", ""],
			["user_ID", "1"],
			["visibility", "public"],
			["woocommerce_meta_nonce", `${woocommerce_meta_nonce}`],
			["wp-preview", ""],
		]);

		response = http.post(
			`${base_url}/wp-admin/post.php`,
			productParams.toString(),
			{
				headers: requestHeaders,
			}
		);
		postNewTypeProductTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: 'Edit product' header": (response) =>
				response.body.includes("Edit product</h1>"),
			"body contains: 'Product published' confirmation": (response) =>
				response.body.includes("Product published."),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	addProduct();
}
