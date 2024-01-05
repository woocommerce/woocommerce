# WooCommerce Performance Tests

Automated k6 performance tests for WooCommerce. To be used for benchmarking performance (both single user and under load) by simulating and measuring the response time of HTTP requests.

## Table of contents

- [Pre-requisites](#pre-requisites)
  - [Install k6](#install-k6)
- [Configuration](#configuration)
  - [Test Environment](#test-environment)
  - [Config Variables](#config-variables)
- [Scenarios](#scenarios)
- [Running Tests](#running-tests)
  - [Running Individual Tests](#running-individual-tests)
  - [Running Scenarios](#running-scenarios)
  - [Debugging Tests](#debugging-tests)
  - [User Agent](#user-agent)
- [Results Output](#results-output)
  - [Basic Results](#basic-results)
  - [InfluxDB and Grafana](influxdb-and-grafana)
- [Writing Tests](#writing-tests)
  - [Capturing Requests](#capturing-requests)
  - [Static Assets](#static-assets)
  - [Request Headers](#request-headers)
  - [Correlation](#correlation)
  - [Test Setup](#test-setup)
  - [Cookies](#cookies)
  - [Groups](#groups)
  - [Checks](#checks)
  - [Custom Metrics](#custom-metrics)
- [Other Resources](#other-resources)

---
---
## Pre-requisites
---
### Install k6

To install k6 on macOS using [Homebrew](https://brew.sh/)

`brew install k6`

[For other platforms please see the k6 installation guide.](https://k6.io/docs/getting-started/installation/)

Alternatively the k6 docker image can be used to execute tests.

`docker pull loadimpact/k6`

---
---
## Configuration
---
### Test Environment

Before using the tests a test environment is needed to run against.

We first spin up an environment using `wp-env` and configure that environment with the necessary plugins and data using the Initialization Script [`init-sample-products.sh`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/tests/performance/bin/init-sample-products.sh) that will set up a shop with sample products imported and the shop settings (payment method, permalinks, address etc) needed for the tests already set. It is recommended using this to just see the tests in action.

```sh
pnpm env:dev --filter=@woocommerce/plugin-woocommerce
pnpm env:performance-init --filter=@woocommerce/plugin-woocommerce
```

If using a different environment the details can be changed in `config.js`.

---
### Config Variables

`config.js` comes with some example values for using with the suggested local test environment. If using a different environment be sure to update the values.

#### Config Variables List

Variable | Used for | Is also env variable?
--- | --- | ---
base_url | base URL of the test environment | yes `__ENV.URL`
base_host | base host of the test environment (for use in headers) | yes `__ENV.HOST`
admin_username | username for admin user | yes `__ENV.A_USER`
admin_password | password for admin user | yes `__ENV.A_PW`
admin_acc_login | set to true if site needs to use my account for admin login | yes `__ENV.A_ACC_LOGIN`
customer_username | username for customer user | yes `__ENV.C_USER`
customer_password | password for customer user | yes `__ENV.C_PW`
customer_user_id | user id for customer user | yes `__ENV.C_UID`
cot_status | set to true if site is using order tables | yes `__ENV.COT`
admin_orders_base_url | url part for order urls when posts table is used | no
cot_admin_orders_base_url | url part for order urls when orders table is used | no
addresses_customer_billing_* | billing address details for existing customer user | no
addresses_guest_billing_* | billing address details for guest customer user | no
payment_method | payment method (currently only `cod` supported) | no
product_sku | SKU of product to be used in cart and checkout flow | yes `__ENV.P_SKU`
product_url | the `product-name` portion of product permalink of the product to be used in cart and checkout flow | yes `__ENV.P_URL`
product_id | the product ID of of product to be used in cart and checkout flow | yes `__ENV.P_ID`
product_search_term | search term to return product to be used in cart and checkout flow | yes `__ENV.P_TERM`
product_category | category of product to be used for browsing category products | yes `__ENV.P_CAT`
coupon_code | coupon code to be used in applying coupon flow | yes `__ENV.P_COUPON`
add_product_title | title of product to be added in merchant add product flow | no
add_product_regular_price | regular price of product to be added in merchant add product flow | no
think_time_min | minimum sleep time (in seconds) between each request | no
think_time_max | maximum sleep time (in seconds) between each request | no


---
---
## Running Tests

When referring to running k6 tests usually this means executing the test scenario. The test scenario file in turn determines which requests we run and how much load will be applied to them. It is also possible to execute individual test files containing requests and pass in scenario config as a CLI flag but scenario files allow for more configuration options.

---
### Running Individual Tests

To execute an individual test file (for example `requests/shopper/shop-page.js`)  containing requests.

CLI `k6 run requests/shopper/shop-page.js`

Docker `docker run --network="host" -v /[YOUR LOCAL WC DIRECTORY FULL PATH]/tests:/tests -it loadimpact/k6 run /tests/performance/requests/shopper/shop-page.js`

This will run the individual test for 1 iteration. 

---
### Running Scenarios

Included in the `tests` folder are some sample scenarios that can be ran or used as a starting point to be modified to suit the context in which tests are being ran.

`simple-all-requests.js` is an included scenario that uses the `per-vu-iterations` scenario executor type to run each request sequentially for 1 iteration to make sure they are working.

`example-all-requests-ramping-vus.js` and `example-all-requests-arrival-rate.js` are included example scenarios for load testing that use the `ramping-vus` and `ramping-arrival-rate` scenario executor types to run all the requests under load of multiple virtual users. These scenarios can be modified to suit the load profile for the context that the tests will be ran.

Another aspect that affects the traffic pattern of the tests is the amount of “Think Time” in between requests. In real world usage of an application users will spend some amount of time before doing some action. For some situations there will be a need to simulate this as part of the test traffic and it is common to have this as part of load tests.

To do this a sleep step is included between each request ``sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`))``.
The amount of think time can be controlled from `config.js`.

>**_Note: It’s important to note to be very careful when adding load to a scenario. By accident a dangerous amount of load could be ran against the test environment that could effectively be like a denial-of-service attack on the test environment. Also important to consider any other consequences of running large load such as triggering of emails._**

To execute a test scenario (for example `tests/simple-all-requests.js`).

CLI `k6 run tests/simple-all-requests.js`

Docker `docker run --network="host" -v /[YOUR LOCAL WC DIRECTORY FULL PATH]/tests:/tests -it loadimpact/k6 run /tests/simple-all-requests.js`

---
### Debugging Tests

To help with getting a test working, the `--http-debug="full"` flag prints to console the full log of requests and their responses. It is also useful to use `console.log()` to print messages when debugging.

---
### User Agent

k6 adds a user agent to requests, for example `User-Agent: k6/0.33.0 (https://k6.io/)` which makes it easier to understand what load is coming from k6 synthetically generating it versus real user load when looking at server logs.

---
---
## Results Output

---
### Basic Results

By default when running the test using `k6 run`, there is an aggregated summary report at the end of the test.

---
### InfluxDB and Grafana

[See this guide for more details](https://k6.io/docs/results-visualization/influxdb-+-grafana/)

For example these steps can be used to install and setup InfluxDB and Grafana locally.

Install InfluxDB

`brew install influxdb@1`

Run InfluxDB

`brew services start influxdb@1`

InfluxDB can now be accessed at http://localhost:8086/

Add this option to the `k6 run` command to send results to the InfluxDB instance into a database named `myk6db`. If this database does not exist, k6 will create it automatically.

`--out influxdb=http://localhost:8086/myk6db`

Install Grafana (this command uses 6.7.4 for compatibility with the imported dashboard mentioned below).

`curl -O https://dl.grafana.com/oss/release/grafana-6.7.4.darwin-amd64.tar.gz`

`tar -zxvf grafana-6.7.4.darwin-amd64.tar.gz`

Run Grafana

`cd grafana-6.7.4`

`./bin/grafana-server web`

Grafana can now be accessed at http://localhost:3000/

Create a custom Grafana dashboard using [these instructions](https://k6.io/docs/results-visualization/influxdb-+-grafana/#custom-grafana-dashboard) or import [this dashboard](https://grafana.com/grafana/dashboards/2587) using [these instructions](https://grafana.com/docs/grafana/v7.5/dashboards/export-import/#importing-a-dashboard).

---
---
## Writing Tests

---
### Capturing Requests

k6 tests rely on HTTP requests in order to test the backend. They can either be constructed from scratch, by using the k6 recorder, or by converting a HAR file.

The k6 recorder is a browser extension which captures http requests generated as you perform actions in a tab. It generates a test with all the HTTP requests from your actions which can then be modified to make it executable.

Alternatively any application which captures HTTP requests can be used to figure out the requests to include in a test such as the network section within browser developer tools.

Tests could also be created to simulate API requests.

---
### Static Assets

The tests only simulate the HTTP requests and do not include static assets requests (fonts, images, css, js etc).

Including them would increase the size and complexity of the tests.

Although static resources do affect the bandwidth and the overall response time for the user, they should have a smaller impact on the server load and aren’t usually as intensive on the server as running code.

In additional any use of CDNs, cache settings, and themes used would also vary from site to site.

---
### Request Headers

Every HTTP requests tested includes the headers. 

To make it easier to manage the headers they have been moved to a separate file so any changes can be made to all the requests at once. 
In `headers.js` the common headers are grouped by type and then can be imported in for use in the requests. However if an individual request uniquely needs a specific header this can still be added in as an extra member of the headers object literal of that request.

---
### Correlation

To make a test work so it can be ran reliably multiple times usually there is a need to correlate any dynamic data in the test. This means extracting one or more values from the response of one request and then reusing them in subsequent requests.

An example of this is the `woocommerce-process-checkout-nonce` . This is returned in the response of the `/checkout` `GET` request and is passed into the `/?wc-ajax=checkout` `POST` request.

A value can be correlated by using k6 selector `.find` to extract the data from a response by matching an element. Alternatively for extracting data from a response unsuitable for using selectors k6 has a `findBetween` utility that makes its easier by just having to provide the left and right boundaries of the data.

---
### Groups

Groups are used to organize common logic in the test scripts and can help with the test result analysis. For example the `group` ``"Proceed to checkout"`` groups together multiple requests triggered by this action.

---
### Checks

Checks are like asserts but they don’t stop the tests if they record a failure (for example in a load test with 1000s of iterations of a request this allows for an isolated flakey iteration to not stop test execution).

All requests have had checks for at least a `200` http status response added and most also have an additional check for a string contained in the response body.

---
## Other Resources

[k6 documentation](https://k6.io/docs/) is a very useful resource for test creation and execution.
