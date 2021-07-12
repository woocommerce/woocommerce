import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';
import {
    base_url,
    think_time_min,
    think_time_max
} from '../../config.js';
import {
    htmlRequestHeader,
    jsonAPIRequestHeader,
    jsonRequestHeader,
    commonRequestHeaders,
    commonGetRequestHeaders,
    commonPostRequestHeaders,
    commonAPIGetRequestHeaders,
    commonNonStandardHeaders
} from '../../headers.js';

/* add custom metrics for each step to the standard output */
let postTypeProductTrend = new Trend('wc_get_post_type_product');
let wcAdminNotesMainTrend = new Trend('wc_get_admin_notes_main');
let wcAdminNotesOtherTrend = new Trend('wc_get_admin_notes_other');
let wcAdminCESOptionsTrend = new Trend('wc_get_admin_options_ces');
let wpAdminHeartbeatTrend = new Trend('wc_post_wp_admin_heartbeat');

export function Products() {

    let response;
    let api_x_wp_nonce;
    let apiNonceHeader;
    let heartbeat_nonce;

    group("Products Page", function () {
        var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

        response = http.get(`${base_url}/wp-admin/edit.php?post_type=product`,
            {
                headers: requestheaders,
            });
        postTypeProductTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
            "body conatins products header": response =>
                response.body.includes('Products</h1>'),
        });

        heartbeat_nonce = findBetween(response.body, 'heartbeatSettings = {"nonce":"', '"};');

        api_x_wp_nonce = findBetween(response.body, 'wp-json\\/","nonce":"', '",');
        apiNonceHeader = {
            'x-wp-nonce': `${api_x_wp_nonce}`
        };
    });

    sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

    group("Inbox Notes", function () {
        var requestheaders = Object.assign(jsonAPIRequestHeader, commonRequestHeaders, commonAPIGetRequestHeaders, apiNonceHeader, commonNonStandardHeaders)

        response = http.get(
            `${base_url}/wp-json/wc-analytics/admin/notes?page=1&per_page=25&status=unactioned&type%5B0%5D=info&type%5B1%5D=marketing&type%5B2%5D=survey&type%5B3%5D=warning&orderby=date&order=desc&_locale=user`,
            {
                headers: requestheaders,
            }
        );
        wcAdminNotesMainTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
        });

        response = http.get(
            `${base_url}/wp-json/wc-analytics/admin/notes?page=1&per_page=25&type=error%2Cupdate&status=unactioned&_locale=user`,
            {
                headers: requestheaders,
            }
        );
        wcAdminNotesOtherTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
        });
    });

    group("CES Options", function () {
        var requestheaders = Object.assign(jsonAPIRequestHeader, commonRequestHeaders, commonAPIGetRequestHeaders, apiNonceHeader, commonNonStandardHeaders)

        response = http.get(
            `${base_url}/wp-json/wc-admin/options?options=woocommerce_ces_tracks_queue&_locale=user`,
            {
                headers: requestheaders,
            }
        );
        wcAdminCESOptionsTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
        });
    });

    group("WP Admin Heartbeat", function () {
        var requestheaders = Object.assign(jsonRequestHeader, commonRequestHeaders, {"content-type": "application/x-www-form-urlencoded; charset=UTF-8",}, commonPostRequestHeaders, commonNonStandardHeaders)

        response = http.post(
            `${base_url}/wp-admin/admin-ajax.php`,
            `_nonce=${heartbeat_nonce}&action=heartbeat&has_focus=true&interval=15&screen_id=edit-product`,
            {
                headers: requestheaders,
            }
        );
        wpAdminHeartbeatTrend.add(response.timings.duration);
        check(response, {
            'is status 200': (r) => r.status === 200,
        });
    });
}

export default function () {
    Products();
}
