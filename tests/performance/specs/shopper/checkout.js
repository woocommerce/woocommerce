import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from 'k6/metrics';
import { 
    base_url,
    base_host,
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
    product_url,
    product_id
   } from '../../config.js';

/* add custom metrics for each step to the standard output */
let homePageTrend = new Trend('_step_01_home_page_duration');
let shopPageTrend = new Trend('_step_02_shop_page_duration');
let productPageTrend = new Trend('_step_03_product_page_duration');
let addToCartTrend = new Trend('_step_04_add_to_cart_duration');
let viewCartTrend = new Trend('_step_05_view_cart_duration');
let proceedCheckoutTrend = new Trend('_step_06_proceed_checkout_duration');
let placeOrderTrend1 = new Trend('_step_07_place_order_update_order_review_duration');
let placeOrderTrend2 = new Trend('_step_08_place_order_checkout_duration');
let orderReceivedTrend1 = new Trend('_step_09_order_received_duration');
let orderReceivedTrend2 = new Trend('_step_10_order_received_duration');

export default function main() {
  let response;

  const vars = {};

  group("Home Page", function () {
    response = http.get(`${base_url}/`, 
    {
      headers: {
        accept:
          "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        //"accept-encoding": "gzip, deflate, br",
        "accept-language": "en-US,en;q=0.9",
        connection: "keep-alive",
        host: `${base_host}`,
        "sec-fetch-dest": "document",
        "sec-fetch-mode": "navigate",
        "sec-fetch-site": "same-origin",
        "sec-fetch-user": "?1",
        "upgrade-insecure-requests": "1",
        "sec-ch-ua":
          '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
        "sec-ch-ua-mobile": "?0",
      },
    });
    homePageTrend.add(response.timings.duration);
  });

  group("Shop Page", function () {
    response = http.get(`${base_url}/shop`, {
      headers: {
        accept:
          "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        //"accept-encoding": "gzip, deflate, br",
        "accept-language": "en-US,en;q=0.9",
        connection: "keep-alive",
        host: `${base_host}`,
        "sec-fetch-dest": "document",
        "sec-fetch-mode": "navigate",
        "sec-fetch-site": "same-origin",
        "sec-fetch-user": "?1",
        "upgrade-insecure-requests": "1",
        "sec-ch-ua":
          '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
        "sec-ch-ua-mobile": "?0",
      },
    });
    shopPageTrend.add(response.timings.duration);
  });

  group("Product Page", function () {
    response = http.get(`${base_url}/product/${product_url}`, {
      headers: {
        accept:
          "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        //"accept-encoding": "gzip, deflate, br",
        "accept-language": "en-US,en;q=0.9",
        connection: "keep-alive",
        host: `${base_host}`,
        "sec-fetch-dest": "document",
        "sec-fetch-mode": "navigate",
        "sec-fetch-site": "same-origin",
        "sec-fetch-user": "?1",
        "upgrade-insecure-requests": "1",
        "sec-ch-ua":
          '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
        "sec-ch-ua-mobile": "?0",
      },
    });
    productPageTrend.add(response.timings.duration);
  });

  group("Product Page Add to cart", function () {
    response = http.post(
      `${base_url}/?wc-ajax=add_to_cart`,
      {
        product_sku: `${product_sku}`,
        product_id: `${product_id}`,
        quantity: "1",
      },
      {
        headers: {
          accept: "application/json, text/javascript, */*; q=0.01",
          //"accept-encoding": "gzip, deflate, br",
          "accept-language": "en-US,en;q=0.9",
          connection: "keep-alive",
          "content-type":
            "application/x-www-form-urlencoded;type=content-type;mimeType=application/x-www-form-urlencoded",
          host: `${base_host}`,
          origin: `${base_url}`,
          "sec-fetch-dest": "empty",
          "sec-fetch-mode": "cors",
          "sec-fetch-site": "same-origin",
          "x-requested-with": "XMLHttpRequest",
          "sec-ch-ua":
            '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
          "sec-ch-ua-mobile": "?0",
        },
      }
    );
    addToCartTrend.add(response.timings.duration);
  });

  group("View Cart", function () {
    response = http.get(`${base_url}/cart`, {
      headers: {
        accept:
          "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        //"accept-encoding": "gzip, deflate, br",
        "accept-language": "en-US,en;q=0.9",
        connection: "keep-alive",
        host: `${base_host}`,
        "sec-fetch-dest": "document",
        "sec-fetch-mode": "navigate",
        "sec-fetch-site": "same-origin",
        "sec-fetch-user": "?1",
        "upgrade-insecure-requests": "1",
        "sec-ch-ua":
          '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
        "sec-ch-ua-mobile": "?0",
      },
    });
    viewCartTrend.add(response.timings.duration);
    check(response, {
      "body does not contain 'your cart is currently empty'": response =>
        !response.body.includes("Your cart is currently empty."),
    });
  });

  group("Proceed to checkout", function () {
    response = http.get(`${base_url}/checkout`, {
      headers: {
        accept:
          "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9",
        //"accept-encoding": "gzip, deflate, br",
        "accept-language": "en-US,en;q=0.9",
        connection: "keep-alive",
        host: `${base_host}`,
        "sec-fetch-dest": "document",
        "sec-fetch-mode": "navigate",
        "sec-fetch-site": "same-origin",
        "sec-fetch-user": "?1",
        "upgrade-insecure-requests": "1",
        "sec-ch-ua":
          '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
        "sec-ch-ua-mobile": "?0",
      },
    });
    proceedCheckoutTrend.add(response.timings.duration);
  });

  vars["woocommerce-process-checkout-nonce"] = response
    .html()
    .find("input[name=woocommerce-process-checkout-nonce]")
    .first()
    .attr("value");

  group("Place Order", function () {
    response = http.post(
      `${base_url}/?wc-ajax=update_order_review`,
      {
        security: "ae0d006f28", //TODO: Correlate this value from update_order_review_nonce on ${base_url}/checkout. Test works with it hard coded for now.
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
        //post_data: //TODO: Test works without post_data, need to confirm if should be included for more realistic results.
      },
      {
        headers: {
          accept: "*//*",
          //"accept-encoding": "gzip, deflate, br",
          "accept-language": "en-US,en;q=0.9",
          connection: "keep-alive",
          "content-type":
            "application/x-www-form-urlencoded;type=content-type;mimeType=application/x-www-form-urlencoded",
          host: `${base_host}`,
          origin: `${base_url}`,
          "sec-fetch-dest": "empty",
          "sec-fetch-mode": "cors",
          "sec-fetch-site": "same-origin",
          "x-requested-with": "XMLHttpRequest",
          "sec-ch-ua":
            '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
          "sec-ch-ua-mobile": "?0",
        },
      }
    );
    placeOrderTrend1.add(response.timings.duration);

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
        "woocommerce-process-checkout-nonce": `${vars["woocommerce-process-checkout-nonce"]}`,
        _wp_http_referer: "%2F%3Fwc-ajax%3Dupdate_order_review",
      },
      {
        headers: {
          accept: "application/json, text/javascript, *//*; q=0.01",
          //"accept-encoding": "gzip, deflate, br",
          "accept-language": "en-US,en;q=0.9",
          connection: "keep-alive",
          "content-type":
            "application/x-www-form-urlencoded;type=content-type;mimeType=application/x-www-form-urlencoded",
          host: `${base_host}`,
          origin: `${base_url}`,
          "sec-fetch-dest": "empty",
          "sec-fetch-mode": "cors",
          "sec-fetch-site": "same-origin",
          "x-requested-with": "XMLHttpRequest",
          "sec-ch-ua":
            '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
          "sec-ch-ua-mobile": "?0",
        },
      }
    );
    placeOrderTrend2.add(response.timings.duration);
  });

  group("Order received", function () {
      response = http.get(
        `${base_url}/checkout/order-received/`,
        {
          headers: {
            accept:
              "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*//*;q=0.8,application/signed-exchange;v=b3;q=0.9",
            //"accept-encoding": "gzip, deflate, br",
            "accept-language": "en-US,en;q=0.9",
            connection: "keep-alive",
            host: `${base_host}`,
            "sec-fetch-dest": "document",
            "sec-fetch-mode": "navigate",
            "sec-fetch-site": "same-origin",
            "sec-fetch-user": "?1",
            "upgrade-insecure-requests": "1",
            "sec-ch-ua":
              '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
            "sec-ch-ua-mobile": "?0",
          },
        }
      );
      check(response, {
        "body contains 'Thank you. Your order has been received.'": response =>
          response.body.includes("Thank you. Your order has been received."),
      });
      orderReceivedTrend1.add(response.timings.duration);

      response = http.post(
        `${base_url}/?wc-ajax=get_refreshed_fragments`,
        {
          headers: {
            accept: "*//*",
           // "accept-encoding": "gzip, deflate, br",
            "accept-language": "en-US,en;q=0.9",
            connection: "keep-alive",
            "content-type":
              "application/x-www-form-urlencoded;type=content-type;mimeType=application/x-www-form-urlencoded",
            host: `${base_host}`,
            origin: `${base_url}`,
            "sec-fetch-dest": "empty",
            "sec-fetch-mode": "cors",
            "sec-fetch-site": "same-origin",
            "x-requested-with": "XMLHttpRequest",
            "sec-ch-ua":
              '"Chromium";v="88", "Google Chrome";v="88", ";Not A Brand";v="99"',
            "sec-ch-ua-mobile": "?0",
          },
        }
      );
      orderReceivedTrend2.add(response.timings.duration);
    }
  );

  sleep(1);
}
