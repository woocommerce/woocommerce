import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { findBetween } from 'https://jslib.k6.io/k6-utils/1.1.0/index.js';
import {
  base_url,
  addresses_customer_billing_first_name,
  addresses_customer_billing_last_name,
  addresses_customer_billing_company,
  addresses_customer_billing_country,
  addresses_customer_billing_address_1,
  addresses_customer_billing_address_2,
  addresses_customer_billing_city,
  addresses_customer_billing_state,
  addresses_customer_billing_postcode,
  addresses_customer_billing_phone,
  addresses_customer_billing_email,
  payment_method,
  product_sku,
  product_id,
  think_time_min,
  think_time_max
} from '../../config.js';
import {
  htmlRequestHeader,
  jsonRequestHeader,
  allRequestHeader,
  commonRequestHeaders,
  commonGetRequestHeaders,
  commonPostRequestHeaders,
  commonNonStandardHeaders
} from '../../headers.js';

/* add custom metrics for each step to the standard output */
let addToCartTrend = new Trend('_step_04_add_to_cart_duration');
let viewCartTrend = new Trend('_step_05_view_cart_duration');
let proceedCheckoutTrend = new Trend('_step_06_proceed_checkout_duration');
let placeOrderTrend1 = new Trend('_step_07_place_order_update_order_review_duration');
let placeOrderTrend2 = new Trend('_step_08_place_order_checkout_duration');
let orderReceivedTrend1 = new Trend('_step_09_order_received_duration');
let orderReceivedTrend2 = new Trend('_step_10_order_received_duration');

export function CheckoutFlow() {

  let response;

  group("Product Page Add to cart", function () {
    var requestheaders = Object.assign(jsonRequestHeader, commonRequestHeaders, commonPostRequestHeaders, commonNonStandardHeaders)

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
        'is status 200': (r) => r.status === 200,
    });
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

  group("View Cart", function () {
    var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

    response = http.get(`${base_url}/cart`, {
      headers: requestheaders,
    });
    viewCartTrend.add(response.timings.duration);
    check(response, {
      'is status 200': (r) => r.status === 200,
      "body does not contain 'your cart is currently empty'": response =>
        !response.body.includes("Your cart is currently empty."),
    });
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

  group("Proceed to checkout", function () {
    var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

    response = http.get(`${base_url}/checkout`, {
      headers: requestheaders,
    });
    proceedCheckoutTrend.add(response.timings.duration);
    check(response, {
        'is status 200': (r) => r.status === 200,
        "body conatins checkout class": response =>
            response.body.includes('class="checkout woocommerce-checkout"'),
    });
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

  const woocommerce_process_checkout_nonce = response.html().find("input[name=woocommerce-process-checkout-nonce]").first().attr("value");

  const update_order_review_nonce = findBetween(response.body, 'update_order_review_nonce":"', '","apply_coupon_nonce');

  group("Place Order", function () {
    var requestheaders = Object.assign(allRequestHeader, commonRequestHeaders, commonPostRequestHeaders, commonNonStandardHeaders)

    response = http.post(
      `${base_url}/?wc-ajax=update_order_review`,
      {
        security: `${update_order_review_nonce}`,
        payment_method: `${payment_method}`,
        country: `${addresses_customer_billing_country}`,
        state: `${addresses_customer_billing_state}`,
        postcode: `${addresses_customer_billing_postcode}`,
        city: `${addresses_customer_billing_city}`,
        address: `${addresses_customer_billing_address_1}`,
        address_2: `${addresses_customer_billing_address_2}`,
        s_country: `${addresses_customer_billing_country}`,
        s_state: `${addresses_customer_billing_state}`,
        s_postcode: `${addresses_customer_billing_postcode}`,
        s_city: `${addresses_customer_billing_city}`,
        s_address: `${addresses_customer_billing_address_1}`,
        s_address_2: `${addresses_customer_billing_address_2}`,
        has_full_address: "true",
      },
      {
        headers: requestheaders,
      }
    );
    placeOrderTrend1.add(response.timings.duration);
    check(response, {
        'is status 200': (r) => r.status === 200,
    });

    var requestheaders = Object.assign(jsonRequestHeader, commonRequestHeaders, commonPostRequestHeaders, commonNonStandardHeaders)

    response = http.post(
      `${base_url}/?wc-ajax=checkout`,
      {
        billing_first_name: `${addresses_customer_billing_first_name}`,
        billing_last_name: `${addresses_customer_billing_last_name}`,
        billing_company: `${addresses_customer_billing_company}`,
        billing_country: `${addresses_customer_billing_country}`,
        billing_address_1: `${addresses_customer_billing_address_1}`,
        billing_address_2: `${addresses_customer_billing_address_2}`,
        billing_city: `${addresses_customer_billing_city}`,
        billing_state: `${addresses_customer_billing_state}`,
        billing_postcode: `${addresses_customer_billing_postcode}`,
        billing_phone: `${addresses_customer_billing_phone}`,
        billing_email: `${addresses_customer_billing_email}`,
        order_comments: "",
        payment_method: `${payment_method}`,
        "woocommerce-process-checkout-nonce": `${woocommerce_process_checkout_nonce}`,
        _wp_http_referer: "%2F%3Fwc-ajax%3Dupdate_order_review",
      },
      {
        headers: requestheaders,
      }
    );
    placeOrderTrend2.add(response.timings.duration);
    check(response, {
        'is status 200': (r) => r.status === 200,
        "body conatins order-received": response =>
            response.body.includes('order-received'),
    });
  });

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));

  group("Order received", function () {
    var requestheaders = Object.assign(htmlRequestHeader, commonRequestHeaders, commonGetRequestHeaders, commonNonStandardHeaders)

    response = http.get(
      `${base_url}/checkout/order-received/`,
      {
        headers: requestheaders,
      }
    );
    check(response, {
      "body contains 'Thank you. Your order has been received.'": response =>
        response.body.includes("Thank you. Your order has been received."),
    });
    orderReceivedTrend1.add(response.timings.duration);

    var requestheaders = Object.assign(allRequestHeader, commonRequestHeaders, commonPostRequestHeaders, commonNonStandardHeaders)

    response = http.post(
      `${base_url}/?wc-ajax=get_refreshed_fragments`,
      {
        headers: requestheaders,
      }
    );
    orderReceivedTrend2.add(response.timings.duration);
    check(response, {
        'is status 200': (r) => r.status === 200,
    });
  }
  );

  sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
  CheckoutFlow();
}
