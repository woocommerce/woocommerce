# Introduction #

WooCommerce (WC) 2.6+ is fully integrated with the WordPress [REST](http://en.wikipedia.org/wiki/Representational_State_Transfer) API. This allows WC data to be created, read, updated, and deleted using requests in JSON format and using WordPress REST API Authentication methods and standard HTTP verbs which are understood by most HTTP clients.

The current WP REST API integration version is `v3` which takes a first-order position in endpoints. 

The following table shows API versions present in each major version of WooCommerce:

| API Version | WC Version     | WP Version   | Documentation             |
|-------------|----------------|--------------|---------------------------|
| `v3`        | 3.5.x or later | 4.4 or later | -                         |
| `v2`        | 3.0.x or later | 4.4 or later | [v2 docs](wp-api-v2.html) |
| `v1`        | 2.6.x or later | 4.4 or later | [v1 docs](wp-api-v1.html) |

Prior to 2.6, WooCommerce had a REST API separate from WordPress which is now known as the legacy API. You can find the documentation for the legacy API separately.

| API Version | WC Version     | WP Version   | Documentation             |
|-------------|----------------|--------------|---------------------------|
| `Legacy v3` | 2.4.x or later | 4.1 or later | [Legacy v3 docs](v3.html) |
| `Legacy v2` | 2.2.x or later | 4.1 or later | [Legacy v2 docs](v2.html) |
| `Legacy v1` | 2.1.x or later | 4.1 or later | [Legacy v1 docs](v1.html) |

## Requirements ##

To use the latest version of the REST API you must be using:

* WooCommerce 3.5+.
* WordPress 4.4+.
* Pretty permalinks in `Settings > Permalinks` so that the custom endpoints are supported. __Default permalinks will not work.__
* You may access the API over either HTTP or HTTPS, but *HTTPS is recommended where possible*.

If you use ModSecurity and see `501 Method Not Implemented` errors, see [this issue](https://github.com/woocommerce/woocommerce/issues/9838) for details.

<aside class="notice">
	Please note that you are <strong>not</strong> required to install the <a href="https://wordpress.org/plugins/rest-api/" target="_blank">WP REST API (WP API)</a> plugin.
</aside>

## Request/Response Format ##

The default response format is JSON. Requests with a message-body use plain JSON to set or update resource attributes. Successful requests will return a `200 OK` HTTP status.

Some general information about responses:

* Dates are returned in ISO8601 format: `YYYY-MM-DDTHH:MM:SS`
* Resource IDs are returned as integers.
* Any decimal monetary amount, such as prices or totals, will be returned as strings with two decimal places.
* Other amounts, such as item counts, are returned as integers.
* Blank fields are generally included as `null` or emtpy string instead of being omitted.

### JSONP Support ###

The WP REST API supports JSONP by default. JSONP responses use the `application/javascript` content-type. You can specify the callback using the `?_jsonp` parameter for `GET` requests to have the response wrapped in a JSON function:

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3?_jsonp=callback</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/products/tags/34?_jsonp=tagDetails \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("products/tags/34?_jsonp=tagDetails")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('products/tags/34', ['_jsonp' => 'tagDetails'])); ?>
```

```python
print(wcapi.get("products/tags/34?_jsonp=tagDetails").json())
```

```ruby
woocommerce.get("products/tags/34", _jsonp: "tagDetails").parsed_response
```

> Response:

```
/**/tagDetails({"id":34,"name":"Leather Shoes","slug":"leather-shoes","description":"","count":0,"_links":{"self":[{"href":"https://example.com/wp-json/wc/v3/products/tags/34"}],"collection":[{"href":"https://example.com/wp-json/wc/v3/products/tags"}]}})%
```

## Errors ##

Occasionally you might encounter errors when accessing the REST API. There are four possible types:

| Error Code                  | Error Type                                                  |
|-----------------------------|-------------------------------------------------------------|
| `400 Bad Request`           | Invalid request, e.g. using an unsupported HTTP method      |
| `401 Unauthorized`          | Authentication or permission error, e.g. incorrect API keys |
| `404 Not Found`             | Requests to resources that don't exist or are missing       |
| `500 Internal Server Error` | Server error                                                |

> WP REST API error example:

```json
{
  "code": "rest_no_route",
  "message": "No route was found matching the URL and request method",
  "data": {
    "status": 404
  }
}
```

> WooCommerce REST API error example:

```json
{
  "code": "woocommerce_rest_term_invalid",
  "message": "Resource doesn't exist.",
  "data": {
    "status": 404
  }
}
```

Errors return both an appropriate HTTP status code and response object which contains a `code`, `message` and `data` attribute.

## Parameters ##

Almost all endpoints accept optional parameters which can be passed as a HTTP query string parameter, e.g. `GET /orders?status=completed`. All parameters are documented along each endpoint.

## Pagination ##

Requests that return multiple items will be paginated to 10 items by default. This default can be changed by the site administrator by changing the `posts_per_page` option. Alternatively the items per page can be specified with the `?per_page` parameter:

`GET /orders?per_page=15`

You can specify further pages with the `?page` parameter:

`GET /orders?page=2`

You may also specify the offset from the first resource using the `?offset` parameter:

`GET /orders?offset=5`

Page number is 1-based and omitting the `?page` parameter will return the first page.

The total number of resources and pages are always included in the `X-WP-Total` and `X-WP-TotalPages` HTTP headers.

### Link Header ###

Pagination info is included in the [Link Header](http://tools.ietf.org/html/rfc5988). It's recommended that you follow these values instead of building your own URLs where possible.

```
Link: <https://www.example.com/wp-json/wc/v3/products?page=2>; rel="next",
<https://www.example.com/wp-json/wc/v3/products?page=3>; rel="last"`
```

The possible `rel` values are:

|  Value  |                       Description                        |
|---------|----------------------------------------------------------|
| `next`  | Shows the URL of the immediate next page of results.     |
| `last`  | Shows the URL of the last page of results.               |
| `first` | Shows the URL of the first page of results.              |
| `prev`  | Shows the URL of the immediate previous page of results. |

## Libraries and Tools ##

### Official libraries ###

- [JavaScript](https://www.npmjs.com/package/@woocommerce/woocommerce-rest-api) Library
- [PHP](https://packagist.org/packages/automattic/woocommerce) Library
- [Python](https://pypi.python.org/pypi/WooCommerce) Library
- [Ruby](https://rubygems.org/gems/woocommerce_api) Library

```javascript
// Install:
// npm install --save @woocommerce/woocommerce-rest-api

// Setup:
const WooCommerceRestApi = require("@woocommerce/woocommerce-rest-api").default;
// import WooCommerceRestApi from "@woocommerce/woocommerce-rest-api"; // Supports ESM

const WooCommerce = new WooCommerceRestApi({
  url: 'http://example.com', // Your store URL
  consumerKey: 'consumer_key', // Your consumer key
  consumerSecret: 'consumer_secret', // Your consumer secret
  version: 'wc/v3' // WooCommerce WP REST API version
});
```

```php
<?php
// Install:
// composer require automattic/woocommerce

// Setup:
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

$woocommerce = new Client(
    'http://example.com', // Your store URL
    'consumer_key', // Your consumer key
    'consumer_secret', // Your consumer secret
    [
        'wp_api' => true, // Enable the WP REST API integration
        'version' => 'wc/v3' // WooCommerce WP REST API version
    ]
);
?>
```

```python
# Install:
# pip install woocommerce

# Setup:
from woocommerce import API

wcapi = API(
    url="http://example.com", # Your store URL
    consumer_key="consumer_key", # Your consumer key
    consumer_secret="consumer_secret", # Your consumer secret
    wp_api=True, # Enable the WP REST API integration
    version="wc/v3" # WooCommerce WP REST API version
)
```

```ruby
# Install:
# gem install woocommerce_api

# Setup:
require "woocommerce_api"

woocommerce = WooCommerce::API.new(
  "https://example.com", # Your store URL
  "consumer_key", # Your consumer key
  "consumer_secret", # Your consumer secret
  {
    wp_api: true, # Enable the WP REST API integration
    version: "wc/v3" # WooCommerce WP REST API version
  }
)
```

<aside class="notice">
	Use the tabs in the top-right corner of this page to see how to install and use each library.
</aside>

### Third party libraries ###

- [Java](https://github.com/icoderman/wc-api-java) Library
- [.NET](https://github.com/XiaoFaye/WooCommerce.NET) Library
- [Android](https://github.com/gilokimu/WooDroid) Library

<aside class="notice">
	Note that we don't offer support for third party libraries, so if you have questions about how use any of this libraries you should contact the respective authors.
</aside>

### Tools ###

Some useful tools you can use to access the API include:

- [Insomnia](https://insomnia.rest) - Cross-platform GraphQL and REST client, available for Mac, Windows, and Linux.
- [Postman](https://www.getpostman.com/) - Cross-platform REST client, available for Mac, Windows, and Linux.
- [RequestBin](https://requestbin.com) - Allows you test webhooks.
- [Hookbin](https://hookbin.com/) - Another tool to test webhooks.

## Learn more ##

Learn more about the REST API checking the <a href="https://developer.wordpress.org/rest-api/">official WordPress REST API documentation</a>.
