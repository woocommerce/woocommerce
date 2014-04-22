# Introduction

With v2.1, WooCommerce includes a REST API that allows store data to be accessed in either JSON or XML format. The current version is read-only (with a single exception for updating the status of an order), but future versions will allow updating, creating, and deleting resources.

## Requirements

You must be using WooCommerce 2.1 and the REST API must be enabled under WooCommerce > Settings. You must enable pretty permalinks (default permalinks will not work).

## Schema

The API is accessible via this endpoint:

`https://www.example.com/wc-api/v1/`

You may access the API over either HTTP or HTTPS. HTTPS is recommended where possible, and the API index will declare if the site supports SSL or not.

## Version

The current version is `v1` and takes a first-order position in endpoint URLs. This will only change for major releases.

## Responses

The default response format is JSON. You can change this to XML by setting the HTTP `ACCEPT` header to either `application/xml` or `text/xml`. Successful requests will return a `200 OK` HTTP status. Note that XML responses are slightly different in structure.

Some general information about responses:

* Dates are returned in [RFC3339](http://www.ietf.org/rfc/rfc3339.txt) format in UTC timezone: `YYYY-MM-DDTHH:MM:SSZ`

* Resource IDs are returned as integers.

* Any decimal monetary amount, such as prices or totals, are returned as strings with two decimal places. The decimal separator (typically either `.` or `,`) is controlled by the site and is included in the API index. This is by design, in order to make localization of API data easier for the client. You may need to account for this in your implemetation if you will be doing calculations with the returned data (e.g. convert string amounts with commas as the decimal place before performing any calculations)

* Other amounts, such as item counts, are returned as integers.

* Blank fields are generally included as `null` instead of being blank strings or omitted.

## Authentication

There are two aways to authenticate with the API, depending on whether the site supports SSL or not.  Remember that the Index endpoint will indicate if the site supports SSL or not.

### Over HTTPS

You may use [HTTP Basic Auth](http://en.wikipedia.org/wiki/Basic_access_authentication) by providing the API Consumer Key as the username and the API Consumer Secret as the password:

```
$ curl https://www.example.com/wc-api/v1/orders \
	-u consumer_key:consumer_secret
```

Occasionally some servers may not properly parse the Authorization header (if you see a "Consumer key is missing" error when authenticating over SSL, you have a server issue). In WooCommerce 2.1.7+, you may provide the consumer key/secret as query string parameters:

```
$ curl https://www.example.com/wc-api/v1/orders?consumer_key=123&consumer_secret=abc
```

### Over HTTP
You must use [OAuth 1.0a "one-legged" authentication](http://tools.ietf.org/html/rfc5849) to ensure API credentials cannot be intercepted. Typically you may use any standard OAuth 1.0a library in your language of choice to handle the authentication, or generate the necessary parameters by following these instructions.

#### Generating an OAuth signature

1) Set the HTTP method for the request:

`GET`

2) Set your base request URI -- this is the full request URI without query string parameters -- and URL encode according to RFC 3986:

```
http://www.example.com/wc-api/v1/orders
```

when encoded:

```
http%3A%2F%2Fwww.example.com%2Fwc-api%2Fv1%2Forders
```

3) Collect and normalize your query string parameters. This includes all `oauth_*` parameters except for the signature. Parameters should be normalized by URL encoding according to RFC 3986 (`rawurlencode` in PHP) and percent(`%`) characters should be double-encoded (e.g. `%` becomes `%25`.

4) Sort the parameters in byte-order (`uksort( $params, 'strcmp' )` in PHP)

5) Join each parameter with an encoded equals sign (`%3D`):

`oauth_signature_method%3DHMAC-SHA1`

6) Join each parameter key/value with an encoded ampersand (`%26`):

`oauth_consumer_key%3Dabc123%26oauth_signature_method%3DHMAC-SHA1`

7) Form the string to sign by joining the HTTP method, encoded base request URI, and encoded parameter string with an unencoded ampersand symbol (&):

`GET&http%3A%2F%2Fwww.example.com%2Fwc-api%2Fv1%2Forders&oauth_consumer_key%3Dabc123%26oauth_signature_method%3DHMAC-SHA1`

8) Generate the signature using the string to key and your consumer secret key

If you are having trouble generating a correct signature, you'll want to review your string to sign for errors with encoding. The [authentication source](https://github.com/woothemes/woocommerce/blob/master/includes/api/class-wc-api-authentication.php#L177) can also be helpful in understanding how to properly generate the signature.

#### OAuth Tips

* The OAuth parameters must be added as query string parameters and *not* include in the Authorization header.

* The require parameters are: `oauth_consumer_key`, `oauth_timestamp`, `oauth_nonce`, `oauth_signature`, and `oauth_signature_method`. `oauth_version` is not required and must be omitted.

* HMAC-SHA1 or HMAC-SHA256 are the only accepted hash algorithms.

* The OAuth nonce can be any randomly generated 32 character (recommended) string that is unique to the consumer key. Read more suggestions on [generating a nonce](https://dev.twitter.com/discussions/12445) on the Twitter API forums.

* The OAuth timestamp should be the unix timestamp at the time of the request. The API will deny any requests that include a timestamp that is outside of a 15 minute window to prevent replay attacks.

* You must use the store URL provided by the index when forming the base string used for the signature, as this is what the server will use. (e.g. if the store URL includes a `www` sub-domain, you should use it for requests)

* Some OAuth libraries add an ampersand to the provided secret key before generating the signature. This does not adhere to the OAuth spec and the ampersand should be removed prior to generating the signature.

* You may test your generated signature using LinkedIn's [OAuth test console](http://developer.linkedin.com/oauth-test-console) -- leave the member token/secret blank.

* Twitter has great instructions on [generating a signature](https://dev.twitter.com/docs/auth/creating-signature) with OAuth 1.0a, but remember tokens are not used with this implementation.

* Note that the request body is *not* signed as per the OAuth spec, see [Google's OAuth 1.0 extension](https://oauth.googlecode.com/svn/spec/ext/body_hash/1.0/oauth-bodyhash.html) for details on why.

## Parameters

API endpoints may take optional parameters which can be passed as an HTTP query string parameter:

`GET /orders?status=completed`

All endpoints accept a `filter` parameter that scopes individual filters using brackets, like date filtering:

`GET /orders?filter[created_at_min]=2013-11-01`

Multiple `filter` parameters can be included and intermixed with other parameters:

`GET /orders?status=completed&filter[created_at_min]=2013-11-01&filter[created_at_max]=2013-11-30`

You can do a keyword search using the `q` filter parameter:

`GET /products?filter[q]=search-keyword`

Resource meta is excluded by default, but can be included with the `meta` filter parameter:

`GET /orders?filter[meta]=true`

Protected meta (meta whose key is prefixed with an underscore) is not included in the response. The `reports` endpoint does not support meta.

You may limit the fields returned in the response using the `fields` parameter:

`GET /orders?fields=ids`

You can specify sub-fields using dot-notation:

`GET /orders?fields=payment_details.method_title`

Sub-fields can't be limited for resources that have multiple structs, like an order's line items. For example, this will return just the line items, but each line item will have the full set of information, not just the product ID:

`GET /orders?fields=line_items.product_id`

Some general guidelines when using parameters:

* Dates should be provided in [RFC3339](http://www.ietf.org/rfc/rfc3339.txt) format in UTC timezone: `YYYY-MM-DDTHH:MM:SSZ`. You may omit the time and timezone if desired.

* When using the `q` filter for searching, search terms should be URL-encoded as they will be decoded internally with [`urldecode`](http://us3.php.net/manual/en/function.urldecode.php)

## Errors

Occasionally you might encounter errors when accessing the API. There are four possible types:

* Invalid requests, such as using an unsupported HTTP method will result in `400 Bad Request`:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_unsupported_method",
      "message" : "Unsupported request method"
    }
  ]
}
```

* Authentication or permission errors, such as incorrect API keys will result in `401 Unauthorized`:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_authentication_error",
      "message" : "Consumer Key is invalid"
    }
  ]
}
```

* Requests to resources that don't exist or are missing required parameters will result in `404 Not Found`:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_invalid_order",
      "message" : "Invalid order"
    }
  ]
}
```

* Requests that cannot be processed due to a server error will result in `500 Internal Server Error`:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_invalid_handler",
      "message" : "The handler for the route is invalid"
    }
  ]
}
```

Errors return both an appropriate HTTP status code and response object which contains a `code` and `message` attribute. If an endpoint has any custom errors, they are documented with that endpoint.

## HTTP Verbs

The API uses the appropriate HTTP verb for each action:

* `HEAD` - Can be used for any endpoint to return just the HTTP header information
*  `GET` - Used for retrieving resources
*  `PUT` - Used for updating resources, currently only supported for the `orders/#{id}` endpoint.

In future version of the API, `POST` and `DELETE` will be supported.

## Pagination

Requests that return multiple items will be paginated to 10 items by default. This default can be changed by the site administrator by changing the `posts_per_page` option. Alternatively the items per page can be specifed with the `?filter[limit]` parameter:

`GET /orders?filter[limit]=15`

You can specify further pages with the `?page` parameter:

`GET /orders?page=2`

You may also specify the offset from the first resource using the `?filter[offset]` parameter:

`GET /orders?filter[offset]=5`

Page number is 1-based and ommiting the `?page` parameter will return the first page.

The total number of resources and pages are always included in the `X-WC-Total` and `X-WC-TotalPages` HTTP headers.

### Link Header

Pagination info is included in the [Link Header](http://tools.ietf.org/html/rfc5988). It's recommended that you follow these values instead of building your own URLs where possible.

```
Link: <https://www.example.com/wc-api/v1/products?page=2>; rel="next",
<https://www.example.com/wc-api/v1/products?page=3>; rel="last"`
```

*Linebreak included for readability*

The possible `rel` values are:

* `next` - Shows the URL of the immediate next page of results
* `last` - Shows the URL of the last page of results
* `first` - Shows the URL of the first page of results
* `prev` - Shows the URL of the immediate previous page of results

## JSON-P Support

The API supports JSON-P by default. You can specify the callback using the `?_jsonp` parameter for `GET` requests to have the response wrapped in a JSON function:

```
GET /orders/count?_jsonp=ordersCount

ordersCount({"count":8})
```

If the site administrator has chosen to disable it, you will receive a`400 Bad Request` error:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_jsonp_disabled",
      "message" : "JSONP support is disabled on this site"
    }
  ]
}
```
If your callback contains invalid characters, you will receive a `400 Bad Request` error:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_jsonp_callback_invalid",
      "message" : "The JSONP callback function is invalid"
    }
  ]
}
```

# Endpoints

The API supports 5 primary resources, each with a related set of endpoints.

## Index

The API index provides information about the endpoints available for the site, as well as store-specific information. No authentication is required to access the API index, however if the REST API is disabled, you will receive a `404 Not Found` error:

```
{
  "errors" : [
    {
      "code" : "woocommerce_api_disabled",
      "message" : "The WooCommerce API is disabled on this site"
    }
  ]
}
```

### Store Properties

* `routes`: a list of available endpoints for the site keyed by relative URL. Each endpoint specifies the HTTP methods supported as well as the canonical URL.
* `dimension_unit`: the unit set for product dimensions. Valid units are `cm`, `m`, `cm`, `mm`, `in`, and `yd`
* `tax_included`: true if prices include tax, false otherwise
* `ssl_enabled`: true if SSL is enabled for the site, false otherwise
* `timezone`: the site's timezone
*  `currency_format`: the currency symbol, HTML encoded
*  `weight_unit`: the unit set for product weights. Valid units are `kg`, `g`, `lbs`, `oz`
*  `description`: the site's description
*  `name`: the name of the site
*  `URL`: the site's URL
*  `permalinks_enabled`: whether pretty permalinks are enabled on the site, if this is false, the API will not function correctly
*  `wc_version`: the active WooCommerce version

### `GET /`

Retrieve a set of store information:

```
{
  "store" : {
    "routes" : {
      "/customers" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/customers"
        }
      },
      "/coupons/count" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/coupons/count"
        }
      },
      "/orders/count" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/orders/count"
        }
      },
      "/products/<id>/reviews" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/coupons/code/<code>" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/orders/<id>/notes" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/customers/count" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/customers/count"
        }
      },
      "/customers/<id>" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/reports" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/reports"
        }
      },
      "/reports/sales" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/reports/sales"
        }
      },
      "/products/count" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/products/count"
        }
      },
      "/coupons/<id>" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/"
        }
      },
      "/products" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/products"
        }
      },
      "/orders/<id>" : {
        "supports" : [
          "HEAD",
          "GET",
          "POST",
          "PUT",
          "PATCH"
        ],
        "accepts_data" : true
      },
      "/customers/<id>/orders" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/products/<id>" : {
        "supports" : [
          "HEAD",
          "GET"
        ]
      },
      "/orders" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/orders"
        }
      },
      "/coupons" : {
        "supports" : [
          "HEAD",
          "GET"
        ],
        "meta" : {
          "self" : "https://www.example.com/wc-api/v1/coupons"
        }
      }
    },
    "meta" : {
      "dimension_unit" : "in",
      "tax_included" : false,
      "ssl_enabled" : true,
      "timezone" : "America/New_York",
      "currency_format" : "&#36;",
      "weight_unit" : "oz",
      "links" : {
        "help" : "http://docs.woothemes.com/document/woocommerce-rest-api/"
      },
      "currency" : "USD",
      "permalinks_enabled" : true,

    },
    "description" : "WooCommerce All the Things!",
    "name" : "WooCommerce",
    "URL" : "http://www.example.com"
  }
}
```

## Coupons

### Coupon Properties

* `expiry_date`: the date the coupon is expired
* `individual_use`: true if the coupon may only be used individually, false otherwise
* `exclude_product_category_ids`: a list of product category IDs that this coupon cannot be applied to
* `amount`: the amount of the coupon
* `code`: the coupon's code that is entered at the cart/checkout page to apply the discount

@TODO

### `GET /coupons`

Retrieve a list of coupons:

```
"coupons" : [
    {
      "expiry_date" : "2013-11-22T00:00:00Z",
      "individual_use" : false,
      "exclude_product_category_ids" : [],
      "amount" : "5.00",
      "code" : "test123",
      "product_category_ids" : [
        22
      ],
      "updated_at" : "2013-11-23T21:08:10Z",
      "limit_usage_to_x_items" : 0,
      "product_ids" : [
        73
      ],
      "exclude_sale_items" : false,
      "type" : "fixed_cart",
      "apply_before_tax" : false,
      "minimum_amount" : "0.00",
      "id" : 137,
      "exclude_product_ids" : [],
      "usage_limit" : null,
      "usage_count" : 0,
      "created_at" : "2013-11-18T15:38:53Z",
      "usage_limit_per_user" : null,
      "enable_free_shipping" : false,
      "customer_emails" : []
    }
  ]
}
```

### `GET /coupons/count`

Retrieve a count of all coupons:

```
{
  "count" : 3
}
```

### `GET /coupons/#{id}`
### `GET /coupons/code/{code}`

Retrieve a single coupon specified by it's ID or code.

Note that coupon codes may contain spaces, dashes and underscores and should be URL-encoded.

```
{
  "coupon" : {
    "expiry_date" : "2013-11-22T00:00:00Z",
    "individual_use" : false,
    "exclude_product_category_ids" : [],
    "amount" : "5.00",
    "code" : "test123",
    "product_category_ids" : [
      22
    ],
    "updated_at" : "2013-11-23T21:08:10Z",
    "limit_usage_to_x_items" : 0,
    "product_ids" : [
      73
    ],
    "exclude_sale_items" : false,
    "type" : "fixed_cart",
    "apply_before_tax" : false,
    "minimum_amount" : "0.00",
    "id" : 137,
    "exclude_product_ids" : [],
    "usage_limit" : null,
    "usage_count" : 0,
    "created_at" : "2013-11-18T15:38:53Z",
    "usage_limit_per_user" : null,
    "enable_free_shipping" : false,
    "customer_emails" : []
  }
}
```

## Customers

All endpoints (except for customer orders) support  date filtering via `created_at_min` and `created_at_max` as `?filter[]` parameters. e.g. `?filter[created_at_min]=2013-12-01`

### Customer Properties

@TODO

### `GET /customers`

Retrieve a list of customers:

```
{
  "customers" : [
    {
      "id" : 4,
      "last_order_date" : "2013-12-10T18:58:00Z",
      "avatar_url" : "https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96",
      "total_spent" : "0.00",
      "created_at" : "2013-12-10T18:58:07Z",
      "orders_count" : 0,
      "billing_address" : {
        "phone" : "215-523-4132",
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "email" : "thedon@mailinator.com",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "shipping_address" : {
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "first_name" : "Don",
      "username" : "thedon",
      "last_name" : "Draper",
      "last_order_id" : "113",
      "email" : "thedon@mailinator.com"
    }
  ]
}
```

### `GET /customers/count`

Retrieve a count of all customers:

```
{
  "count" : 18
}
```

### `GET /customers/#{id}`

Retrieve a single customer specified by their ID:

```
{
  "customer" : {
    "id" : 4,
    "last_order_date" : "2013-12-10T18:58:00Z",
    "avatar_url" : "https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96",
    "total_spent" : "0.00",
    "created_at" : "2013-12-10T18:58:07Z",
    "orders_count" : 0,
    "billing_address" : {
      "phone" : "215-523-4132",
      "city" : "New York",
      "country" : "US",
      "address_1" : "512 First Avenue",
      "last_name" : "Draper",
      "company" : "SDCP",
      "postcode" : "12534",
      "email" : "thedon@mailinator.com",
      "address_2" : "",
      "state" : "NY",
      "first_name" : "Don"
    },
    "shipping_address" : {
      "city" : "New York",
      "country" : "US",
      "address_1" : "512 First Avenue",
      "last_name" : "Draper",
      "company" : "SDCP",
      "postcode" : "12534",
      "address_2" : "",
      "state" : "NY",
      "first_name" : "Don"
    },
    "first_name" : "Don",
    "username" : "thedon",
    "last_name" : "Draper",
    "last_order_id" : "113",
    "email" : "thedon@mailinator.com"
  }
}
```

### `GET /customers/#{id}/orders`

Retrieve a list of orders for a customer specified by their ID:

```
{
  "orders" : [
    {
      "completed_at" : "2013-12-10T18:59:30Z",
      "tax_lines" : [],
      "status" : "processing",
      "total" : "20.00",
      "cart_discount" : "0.00",
      "customer_ip" : "127.0.0.1",
      "total_discount" : "0.00",
      "updated_at" : "2013-12-10T18:59:30Z",
      "currency" : "USD",
      "total_shipping" : "0.00",
      "customer_user_agent" : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36",
      "line_items" : [
        {
          "product_id" : 31,
          "quantity" : 1,
          "id" : 7,
          "subtotal" : "20.00",
          "tax_class" : null,
          "sku" : "",
          "total" : "20.00",
          "name" : "Ninja Silhouette",
          "total_tax" : "0.00"
        }
      ],
      "customer_id" : "4",
      "total_tax" : "0.00",
      "order_number" : "#113",
      "shipping_methods" : "Free Shipping",
      "shipping_address" : {
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "payment_details" : {
        "method_title" : "Cheque Payment",
        "method_id" : "cheque",
        "paid" : false
      },
      "id" : 113,
      "shipping_tax" : "0.00",
      "cart_tax" : "0.00",
      "fee_lines" : [],
      "total_line_items_quantity" : 1,
      "shipping_lines" : [
        {
          "method_title" : "Free Shipping",
          "id" : 8,
          "method_id" : "free_shipping",
          "total" : "0.00"
        }
      ],
      "customer" : {
        "id" : 4,
        "last_order_date" : "2013-12-10T18:58:00Z",
        "avatar_url" : "https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96",
        "total_spent" : "0.00",
        "created_at" : "2013-12-10T18:58:07Z",
        "orders_count" : 0,
        "billing_address" : {
          "phone" : "215-523-4132",
          "city" : "New York",
          "country" : "US",
          "address_1" : "512 First Avenue",
          "last_name" : "Draper",
          "company" : "SDCP",
          "postcode" : "12534",
          "email" : "thedon@mailinator.com",
          "address_2" : "",
          "state" : "NY",
          "first_name" : "Don"
        },
        "shipping_address" : {
          "city" : "New York",
          "country" : "US",
          "address_1" : "512 First Avenue",
          "last_name" : "Draper",
          "company" : "SDCP",
          "postcode" : "12534",
          "address_2" : "",
          "state" : "NY",
          "first_name" : "Don"
        },
        "first_name" : "Don",
        "username" : "thedon",
        "last_name" : "Draper",
        "last_order_id" : "113",
        "email" : "thedon@mailinator.com"
      },
      "note" : "",
      "coupon_lines" : [],
      "order_discount" : "0.00",
      "created_at" : "2013-12-10T18:58:00Z",
      "view_order_url" : "https://www.example.com/my-account/view-order/113",
      "billing_address" : {
        "phone" : "215-523-4132",
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "email" : "thedon@mailinator.com",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      }
    }
  ]
}
```

## Orders

All endpoints (except for order notes) support the full set of date filters (`created_at_min`, `created_at_max`, `updated_at_min`, `updated_at_max`) as `?filter[]` parameters. e.g. `?filter[created_at_min]=2013-12-01`

### Order Properties

@TODO

### `GET /orders`

Retrieve a list of orders

You can use the `?status?` parameter to limit the orders returned to a specific order status. The default WooCommerce order statuses are `pending`, `on-hold`, `processing`, `completed`, `refunded`, `failed`, and `cancelled`. Custom order statuses are supported.

```
{
  "orders" : [
    {
      "completed_at" : "2013-12-10T18:59:30Z",
      "tax_lines" : [],
      "status" : "processing",
      "total" : "20.00",
      "cart_discount" : "0.00",
      "customer_ip" : "127.0.0.1",
      "total_discount" : "0.00",
      "updated_at" : "2013-12-10T18:59:30Z",
      "currency" : "USD",
      "total_shipping" : "0.00",
      "customer_user_agent" : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36",
      "line_items" : [
        {
          "product_id" : 31,
          "quantity" : 1,
          "id" : 7,
          "subtotal" : "20.00",
          "tax_class" : null,
          "sku" : "",
          "total" : "20.00",
          "name" : "Ninja Silhouette",
          "total_tax" : "0.00"
        }
      ],
      "customer_id" : "4",
      "total_tax" : "0.00",
      "order_number" : "#113",
      "shipping_methods" : "Free Shipping",
      "shipping_address" : {
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "payment_details" : {
        "method_title" : "Cheque Payment",
        "method_id" : "cheque",
        "paid" : false
      },
      "id" : 113,
      "shipping_tax" : "0.00",
      "cart_tax" : "0.00",
      "fee_lines" : [],
      "total_line_items_quantity" : 1,
      "shipping_lines" : [
        {
          "method_title" : "Free Shipping",
          "id" : 8,
          "method_id" : "free_shipping",
          "total" : "0.00"
        }
      ],
      "customer" : {
        "id" : 4,
        "last_order_date" : "2013-12-10T18:58:00Z",
        "avatar_url" : "https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96",
        "total_spent" : "0.00",
        "created_at" : "2013-12-10T18:58:07Z",
        "orders_count" : 0,
        "billing_address" : {
          "phone" : "215-523-4132",
          "city" : "New York",
          "country" : "US",
          "address_1" : "512 First Avenue",
          "last_name" : "Draper",
          "company" : "SDCP",
          "postcode" : "12534",
          "email" : "thedon@mailinator.com",
          "address_2" : "",
          "state" : "NY",
          "first_name" : "Don"
        },
        "shipping_address" : {
          "city" : "New York",
          "country" : "US",
          "address_1" : "512 First Avenue",
          "last_name" : "Draper",
          "company" : "SDCP",
          "postcode" : "12534",
          "address_2" : "",
          "state" : "NY",
          "first_name" : "Don"
        },
        "first_name" : "Don",
        "username" : "thedon",
        "last_name" : "Draper",
        "last_order_id" : "113",
        "email" : "thedon@mailinator.com"
      },
      "note" : "",
      "coupon_lines" : [],
      "order_discount" : "0.00",
      "created_at" : "2013-12-10T18:58:00Z",
      "view_order_url" : "https://www.example.com/my-account/view-order/113",
      "billing_address" : {
        "phone" : "215-523-4132",
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "email" : "thedon@mailinator.com",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      }
    }
  ]
}
```

### `GET /orders/count`

Retrieve a count of all orders:

```
{
  "count" : 27
}
```

### `GET /orders/#{id}`

Retrieve a single order specified by it's ID:

```
{
  "order" : {
    "completed_at" : "2013-12-10T18:59:30Z",
    "tax_lines" : [],
    "status" : "processing",
    "total" : "20.00",
    "cart_discount" : "0.00",
    "customer_ip" : "127.0.0.1",
    "total_discount" : "0.00",
    "updated_at" : "2013-12-10T18:59:30Z",
    "currency" : "USD",
    "total_shipping" : "0.00",
    "customer_user_agent" : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36",
    "line_items" : [
      {
        "product_id" : 31,
        "quantity" : 1,
        "id" : 7,
        "subtotal" : "20.00",
        "tax_class" : null,
        "sku" : "",
        "total" : "20.00",
        "name" : "Ninja Silhouette",
        "total_tax" : "0.00"
      }
    ],
    "customer_id" : "4",
    "total_tax" : "0.00",
    "order_number" : "#113",
    "shipping_methods" : "Free Shipping",
    "shipping_address" : {
      "city" : "New York",
      "country" : "US",
      "address_1" : "512 First Avenue",
      "last_name" : "Draper",
      "company" : "SDCP",
      "postcode" : "12534",
      "address_2" : "",
      "state" : "NY",
      "first_name" : "Don"
    },
    "payment_details" : {
      "method_title" : "Cheque Payment",
      "method_id" : "cheque",
      "paid" : false
    },
    "id" : 113,
    "shipping_tax" : "0.00",
    "cart_tax" : "0.00",
    "fee_lines" : [],
    "total_line_items_quantity" : 1,
    "shipping_lines" : [
      {
        "method_title" : "Free Shipping",
        "id" : 8,
        "method_id" : "free_shipping",
        "total" : "0.00"
      }
    ],
    "customer" : {
      "id" : 4,
      "last_order_date" : "2013-12-10T18:58:00Z",
      "avatar_url" : "https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96",
      "total_spent" : "0.00",
      "created_at" : "2013-12-10T18:58:07Z",
      "orders_count" : 0,
      "billing_address" : {
        "phone" : "215-523-4132",
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "email" : "thedon@mailinator.com",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "shipping_address" : {
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "first_name" : "Don",
      "username" : "thedon",
      "last_name" : "Draper",
      "last_order_id" : "113",
      "email" : "thedon@mailinator.com"
    },
    "note" : "",
    "coupon_lines" : [],
    "order_discount" : "0.00",
    "created_at" : "2013-12-10T18:58:00Z",
    "view_order_url" : "https://www.example.com/my-account/view-order/113",
    "billing_address" : {
      "phone" : "215-523-4132",
      "city" : "New York",
      "country" : "US",
      "address_1" : "512 First Avenue",
      "last_name" : "Draper",
      "company" : "SDCP",
      "postcode" : "12534",
      "email" : "thedon@mailinator.com",
      "address_2" : "",
      "state" : "NY",
      "first_name" : "Don"
    }
  }
}
```

### `PUT /orders/#{id}?status={status}`

Update the status for an order specified by it's ID

The request body should be JSON:

```
{
	"status":"completed"
}
```

If successful, the updated order is returned:

```
{
  "order" : {
    "completed_at" : "2013-12-10T18:59:30Z",
    "tax_lines" : [],
    "status" : "completed",
    "total" : "20.00",
    "cart_discount" : "0.00",
    "customer_ip" : "127.0.0.1",
    "total_discount" : "0.00",
    "updated_at" : "2013-12-10T18:59:30Z",
    "currency" : "USD",
    "total_shipping" : "0.00",
    "customer_user_agent" : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36",
    "line_items" : [
      {
        "product_id" : 31,
        "quantity" : 1,
        "id" : 7,
        "subtotal" : "20.00",
        "tax_class" : null,
        "sku" : "",
        "total" : "20.00",
        "name" : "Ninja Silhouette",
        "total_tax" : "0.00"
      }
    ],
    "customer_id" : "4",
    "total_tax" : "0.00",
    "order_number" : "#113",
    "shipping_methods" : "Free Shipping",
    "shipping_address" : {
      "city" : "New York",
      "country" : "US",
      "address_1" : "512 First Avenue",
      "last_name" : "Draper",
      "company" : "SDCP",
      "postcode" : "12534",
      "address_2" : "",
      "state" : "NY",
      "first_name" : "Don"
    },
    "payment_details" : {
      "method_title" : "Cheque Payment",
      "method_id" : "cheque",
      "paid" : false
    },
    "id" : 113,
    "shipping_tax" : "0.00",
    "cart_tax" : "0.00",
    "fee_lines" : [],
    "total_line_items_quantity" : 1,
    "shipping_lines" : [
      {
        "method_title" : "Free Shipping",
        "id" : 8,
        "method_id" : "free_shipping",
        "total" : "0.00"
      }
    ],
    "customer" : {
      "id" : 4,
      "last_order_date" : "2013-12-10T18:58:00Z",
      "avatar_url" : "https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=96",
      "total_spent" : "0.00",
      "created_at" : "2013-12-10T18:58:07Z",
      "orders_count" : 0,
      "billing_address" : {
        "phone" : "215-523-4132",
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "email" : "thedon@mailinator.com",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "shipping_address" : {
        "city" : "New York",
        "country" : "US",
        "address_1" : "512 First Avenue",
        "last_name" : "Draper",
        "company" : "SDCP",
        "postcode" : "12534",
        "address_2" : "",
        "state" : "NY",
        "first_name" : "Don"
      },
      "first_name" : "Don",
      "username" : "thedon",
      "last_name" : "Draper",
      "last_order_id" : "113",
      "email" : "thedon@mailinator.com"
    },
    "note" : "",
    "coupon_lines" : [],
    "order_discount" : "0.00",
    "created_at" : "2013-12-10T18:58:00Z",
    "view_order_url" : "https://www.example.com/my-account/view-order/113",
    "billing_address" : {
      "phone" : "215-523-4132",
      "city" : "New York",
      "country" : "US",
      "address_1" : "512 First Avenue",
      "last_name" : "Draper",
      "company" : "SDCP",
      "postcode" : "12534",
      "email" : "thedon@mailinator.com",
      "address_2" : "",
      "state" : "NY",
      "first_name" : "Don"
    }
  }
}
```

### `GET /orders/#{id}/notes`

Retrieve a list of notes for an order specified by it's ID:

```
{
  "order_notes" : [
    {
      "note" : "Order status changed from processing to completed.",
      "id" : "47",
      "created_at" : "2013-12-10T20:08:06Z",
      "customer_note" : false
    },
    {
      "note" : "Order status changed from on-hold to processing.",
      "id" : "46",
      "created_at" : "2013-12-10T18:59:30Z",
      "customer_note" : false
    },
    {
      "note" : "Awaiting cheque payment Order status changed from pending to on-hold.",
      "id" : "45",
      "created_at" : "2013-12-10T18:58:23Z",
      "customer_note" : false
    }
  ]
}
```

## Products

All endpoints (except for reviews) support the full set of date filters (`created_at_min`, `created_at_max`, `updated_at_min`, `updated_at_max`) as `?filter[]` parameters. e.g. `?filter[created_at_min]=2013-12-01`

### Product Properties

@TODO

### `GET /products`

Retrieve a list of products

You can use the `?type` parameter to specify that only certain product types should be returned. The default WooCommerce product types are: `simple`, `variable`, `grouped`,  and `external`. Custom product types are supported.

```
{
  "products" : [
    {
      "related_ids" : [
        87,
        93,
        96,
        93,
        83
      ],
      "variations" : [],
      "categories" : [
        "Music",
        "Singles"
      ],
      "shipping_required" : true,
      "id" : 99,
      "parent" : [],
      "regular_price" : "3.00",
      "weight" : null,
      "total_sales" : 0,
      "sku" : "",
      "rating_count" : 2,
      "managing_stock" : false,
      "title" : "Woo Single #2",
      "backordered" : false,
      "on_sale" : true,
      "status" : "publish",
      "download_limit" : 0,
      "taxable" : false,
      "reviews_allowed" : true,
      "description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
      "purchaseable" : true,
      "sale_price" : "2.00",
      "type" : "simple",
      "permalink" : "https://www.example.com/product/woo-single-2/",
      "catalog_visibility" : "visible",
      "download_expiry" : 0,
      "dimensions" : {
        "length" : "",
        "height" : "",
        "unit" : "in",
        "width" : ""
      },
      "cross_sell_ids" : [],
      "price" : "2.00",
      "updated_at" : "2013-06-07T11:38:12Z",
      "attributes" : [],
      "shipping_class" : "",
      "virtual" : false,
      "downloadable" : false,
      "upsell_ids" : [],
      "created_at" : "2013-06-07T11:38:12Z",
      "tax_class" : "",
      "tags" : [],
      "price_html" : "<del><span class=\"amount\">&#36;3</span></del> <ins><span class=\"amount\">&#36;2</span></ins>",
      "in_stock" : true,
      "sold_individually" : false,
      "short_description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
      "downloads" : [],
      "tax_status" : "taxable",
      "average_rating" : "4.50",
      "download_type" : "",
      "shipping_taxable" : true,
      "purchase_note" : "",
      "shipping_class_id" : null,
      "visible" : true,
      "backorders_allowed" : false,
      "images" : [
        {
          "position" : 0,
          "id" : 100,
          "created_at" : "2013-06-07T11:37:51Z",
          "src" : "https://www.example.com/wp-content/uploads/2013/06/cd_6_angle.jpg",
          "title" : "cd_6_angle",
          "alt" : "",
          "updated_at" : "2013-06-07T11:37:51Z"
        },
        {
          "position" : 1,
          "id" : 101,
          "created_at" : "2013-06-07T11:38:03Z",
          "src" : "https://www.example.com/wp-content/uploads/2013/06/cd_6_flat.jpg",
          "title" : "cd_6_flat",
          "alt" : "",
          "updated_at" : "2013-06-07T11:38:03Z"
        }
      ],
      "stock_quantity" : 0,
      "featured" : false
    }
  ]
}
```

### `GET /products/count`

Retrieve a count of all products

You can use the `?type` parameter to specify that only certain product types should be returned. The default WooCommerce product types are: `simple`, `variable`, `grouped`,  and `external`. Custom product types are supported.

```
{
  "count" : 23
}
```

### `GET /products/#{id}`

Retrieve a product specified by it's ID

Simple, Grouped, and External products will return a blank array for the `variations` property:

```
{
  "product" : {
    "related_ids" : [
      93,
      90,
      93,
      87,
      83
    ],
    "variations" : [],
    "categories" : [
      "Music",
      "Singles"
    ],
    "shipping_required" : true,
    "id" : 99,
    "parent" : [],
    "regular_price" : "3.00",
    "weight" : null,
    "total_sales" : 0,
    "sku" : "",
    "rating_count" : 2,
    "managing_stock" : false,
    "title" : "Woo Single #2",
    "backordered" : false,
    "on_sale" : true,
    "status" : "publish",
    "download_limit" : 0,
    "taxable" : false,
    "reviews_allowed" : true,
    "description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
    "purchaseable" : true,
    "sale_price" : "2.00",
    "type" : "simple",
    "permalink" : "https://www.example.com/product/woo-single-2/",
    "catalog_visibility" : "visible",
    "download_expiry" : 0,
    "dimensions" : {
      "length" : "",
      "height" : "",
      "unit" : "in",
      "width" : ""
    },
    "cross_sell_ids" : [],
    "price" : "2.00",
    "updated_at" : "2013-06-07T11:38:12Z",
    "attributes" : [],
    "shipping_class" : "",
    "virtual" : false,
    "downloadable" : false,
    "upsell_ids" : [],
    "created_at" : "2013-06-07T11:38:12Z",
    "tax_class" : "",
    "tags" : [],
    "price_html" : "<del><span class=\"amount\">&#36;3</span></del> <ins><span class=\"amount\">&#36;2</span></ins>",
    "in_stock" : true,
    "sold_individually" : false,
    "short_description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
    "downloads" : [],
    "tax_status" : "taxable",
    "average_rating" : "4.50",
    "download_type" : "",
    "shipping_taxable" : true,
    "purchase_note" : "",
    "shipping_class_id" : null,
    "visible" : true,
    "backorders_allowed" : false,
    "images" : [
      {
        "position" : 0,
        "id" : 100,
        "created_at" : "2013-06-07T11:37:51Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/cd_6_angle.jpg",
        "title" : "cd_6_angle",
        "alt" : "",
        "updated_at" : "2013-06-07T11:37:51Z"
      },
      {
        "position" : 1,
        "id" : 101,
        "created_at" : "2013-06-07T11:38:03Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/cd_6_flat.jpg",
        "title" : "cd_6_flat",
        "alt" : "",
        "updated_at" : "2013-06-07T11:38:03Z"
      }
    ],
    "stock_quantity" : 0,
    "featured" : false
  }
}
```

Variable products will return individual variations in the `variations` property:

```
{
  "product" : {
    "related_ids" : [
      50,
      60,
      53,
      19,
      15
    ],
    "variations" : [
      {
        "attributes" : [
          {
            "option" : "black",
            "name" : "Color"
          }
        ],
        "weight" : null,
        "sku" : "",
        "backordered" : false,
        "shipping_class" : "",
        "image" : [
          {
            "position" : 0,
            "id" : 43,
            "created_at" : "2013-06-07T10:59:40Z",
            "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_7_front.jpg",
            "title" : "hoodie_7_front",
            "alt" : "",
            "updated_at" : "2013-06-07T10:59:40Z"
          }
        ],
        "updated_at" : "2013-06-07T11:00:28Z",
        "downloads" : [],
        "downloadable" : false,
        "regular_price" : "35.00",
        "permalink" : "https://www.example.com/product/ship-your-idea-2/?attribute_pa_color=black",
        "stock_quantity" : 0,
        "shipping_class_id" : null,
        "taxable" : false,
        "tax_status" : "taxable",
        "download_expiry" : 0,
        "id" : 41,
        "virtual" : false,
        "on_sale" : false,
        "download_limit" : 0,
        "in_stock" : true,
        "sale_price" : null,
        "created_at" : "2013-06-07T11:00:28Z",
        "price" : "35.00",
        "visible" : true,
        "dimensions" : {
          "length" : "",
          "height" : "",
          "unit" : "in",
          "width" : ""
        },
        "tax_class" : "",
        "purchaseable" : true
      },
      {
        "attributes" : [
          {
            "option" : "blue",
            "name" : "Color"
          }
        ],
        "weight" : null,
        "sku" : "",
        "backordered" : false,
        "shipping_class" : "",
        "image" : [
          {
            "position" : 0,
            "id" : 46,
            "created_at" : "2013-06-07T11:00:01Z",
            "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_1_front.jpg",
            "title" : "hoodie_1_front",
            "alt" : "",
            "updated_at" : "2013-06-07T11:00:01Z"
          }
        ],
        "updated_at" : "2013-06-07T11:00:28Z",
        "downloads" : [],
        "downloadable" : false,
        "regular_price" : "35.00",
        "permalink" : "https://www.example.com/product/ship-your-idea-2/?attribute_pa_color=blue",
        "stock_quantity" : 0,
        "shipping_class_id" : null,
        "taxable" : false,
        "tax_status" : "taxable",
        "download_expiry" : 0,
        "id" : 42,
        "virtual" : false,
        "on_sale" : true,
        "download_limit" : 0,
        "in_stock" : true,
        "sale_price" : "30.00",
        "created_at" : "2013-06-07T11:00:28Z",
        "price" : "30.00",
        "visible" : true,
        "dimensions" : {
          "length" : "",
          "height" : "",
          "unit" : "in",
          "width" : ""
        },
        "tax_class" : "",
        "purchaseable" : true
      }
    ],
    "categories" : [
      "Clothing",
      "Hoodies"
    ],
    "shipping_required" : true,
    "id" : 40,
    "parent" : [],
    "regular_price" : "0.00",
    "weight" : null,
    "total_sales" : 1,
    "sku" : "",
    "rating_count" : 3,
    "managing_stock" : false,
    "title" : "Ship Your Idea",
    "backordered" : false,
    "on_sale" : true,
    "status" : "publish",
    "download_limit" : 0,
    "taxable" : false,
    "reviews_allowed" : true,
    "description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
    "purchaseable" : true,
    "sale_price" : null,
    "type" : "variable",
    "permalink" : "https://www.example.com/product/ship-your-idea-2/",
    "catalog_visibility" : "visible",
    "download_expiry" : 0,
    "dimensions" : {
      "length" : "",
      "height" : "",
      "unit" : "in",
      "width" : ""
    },
    "cross_sell_ids" : [
      22
    ],
    "price" : "30.00",
    "updated_at" : "2013-06-07T11:00:28Z",
    "attributes" : [
      {
        "position" : "0",
        "visible" : false,
        "variation" : true,
        "options" : [
          "Black",
          "Blue"
        ],
        "name" : "Color"
      }
    ],
    "shipping_class" : "",
    "virtual" : false,
    "downloadable" : false,
    "upsell_ids" : [],
    "created_at" : "2013-06-07T11:00:28Z",
    "tax_class" : "",
    "tags" : [],
    "price_html" : "<span class=\"from\">From: </span><del><span class=\"amount\">&#36;35</span></del> <ins><span class=\"amount\">&#36;30</span></ins>",
    "in_stock" : true,
    "sold_individually" : false,
    "short_description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
    "downloads" : [],
    "tax_status" : "taxable",
    "average_rating" : "4.00",
    "download_type" : "",
    "shipping_taxable" : true,
    "purchase_note" : "",
    "shipping_class_id" : null,
    "visible" : true,
    "backorders_allowed" : false,
    "images" : [
      {
        "position" : 0,
        "id" : 43,
        "created_at" : "2013-06-07T10:59:40Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_7_front.jpg",
        "title" : "hoodie_7_front",
        "alt" : "",
        "updated_at" : "2013-06-07T10:59:40Z"
      },
      {
        "position" : 1,
        "id" : 44,
        "created_at" : "2013-06-07T10:59:54Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_7_back.jpg",
        "title" : "hoodie_7_back",
        "alt" : "",
        "updated_at" : "2013-06-07T10:59:54Z"
      },
      {
        "position" : 2,
        "id" : 45,
        "created_at" : "2013-06-07T11:00:00Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_1_back.jpg",
        "title" : "hoodie_1_back",
        "alt" : "",
        "updated_at" : "2013-06-07T11:00:00Z"
      },
      {
        "position" : 3,
        "id" : 46,
        "created_at" : "2013-06-07T11:00:01Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_1_front.jpg",
        "title" : "hoodie_1_front",
        "alt" : "",
        "updated_at" : "2013-06-07T11:00:01Z"
      }
    ],
    "stock_quantity" : 0,
    "featured" : false
  }
}
```

Individual product variations will return a slightly different format than a regular product, with the parent variable product data in the `parent` attribute:

```
{
  "product" : {
    "related_ids" : [
      60,
      50,
      53,
      47,
      19
    ],
    "variations" : [],
    "categories" : [
      "Clothing",
      "Hoodies"
    ],
    "shipping_required" : true,
    "id" : 41,
    "parent" : {
      "related_ids" : [
        56,
        19,
        34,
        60,
        19
      ],
      "variations" : [],
      "categories" : [
        "Clothing",
        "Hoodies"
      ],
      "shipping_required" : true,
      "id" : 40,
      "parent" : [],
      "regular_price" : "0.00",
      "weight" : null,
      "total_sales" : 1,
      "sku" : "",
      "rating_count" : 3,
      "managing_stock" : false,
      "title" : "Ship Your Idea",
      "backordered" : false,
      "on_sale" : true,
      "status" : "publish",
      "download_limit" : 0,
      "taxable" : false,
      "reviews_allowed" : true,
      "description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
      "purchaseable" : true,
      "sale_price" : null,
      "type" : "variable",
      "permalink" : "https://www.example.com/product/ship-your-idea-2/",
      "catalog_visibility" : "visible",
      "download_expiry" : 0,
      "dimensions" : {
        "length" : "",
        "height" : "",
        "unit" : "in",
        "width" : ""
      },
      "cross_sell_ids" : [
        22
      ],
      "price" : "30.00",
      "updated_at" : "2013-06-07T11:00:28Z",
      "attributes" : [
        {
          "position" : "0",
          "visible" : false,
          "variation" : true,
          "options" : [
            "Black",
            "Blue"
          ],
          "name" : "Color"
        }
      ],
      "shipping_class" : "",
      "virtual" : false,
      "downloadable" : false,
      "upsell_ids" : [],
      "created_at" : "2013-06-07T11:00:28Z",
      "tax_class" : "",
      "tags" : [],
      "price_html" : "<span class=\"from\">From: </span><del><span class=\"amount\">&#36;35</span></del> <ins><span class=\"amount\">&#36;30</span></ins>",
      "in_stock" : true,
      "sold_individually" : false,
      "short_description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
      "downloads" : [],
      "tax_status" : "taxable",
      "average_rating" : "4.00",
      "download_type" : "",
      "shipping_taxable" : true,
      "purchase_note" : "",
      "shipping_class_id" : null,
      "visible" : true,
      "backorders_allowed" : false,
      "images" : [
        {
          "position" : 0,
          "id" : 43,
          "created_at" : "2013-06-07T10:59:40Z",
          "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_7_front.jpg",
          "title" : "hoodie_7_front",
          "alt" : "",
          "updated_at" : "2013-06-07T10:59:40Z"
        },
        {
          "position" : 1,
          "id" : 44,
          "created_at" : "2013-06-07T10:59:54Z",
          "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_7_back.jpg",
          "title" : "hoodie_7_back",
          "alt" : "",
          "updated_at" : "2013-06-07T10:59:54Z"
        },
        {
          "position" : 2,
          "id" : 45,
          "created_at" : "2013-06-07T11:00:00Z",
          "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_1_back.jpg",
          "title" : "hoodie_1_back",
          "alt" : "",
          "updated_at" : "2013-06-07T11:00:00Z"
        },
        {
          "position" : 3,
          "id" : 46,
          "created_at" : "2013-06-07T11:00:01Z",
          "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_1_front.jpg",
          "title" : "hoodie_1_front",
          "alt" : "",
          "updated_at" : "2013-06-07T11:00:01Z"
        }
      ],
      "stock_quantity" : 0,
      "featured" : false
    },
    "regular_price" : "35.00",
    "weight" : null,
    "total_sales" : 1,
    "sku" : "",
    "rating_count" : 3,
    "managing_stock" : false,
    "title" : "Ship Your Idea",
    "backordered" : false,
    "on_sale" : false,
    "status" : "publish",
    "download_limit" : 0,
    "taxable" : false,
    "reviews_allowed" : true,
    "description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
    "purchaseable" : true,
    "sale_price" : null,
    "type" : "variation",
    "permalink" : "https://www.example.com/product/ship-your-idea-2/?attribute_pa_color=black",
    "catalog_visibility" : "visible",
    "download_expiry" : 0,
    "dimensions" : {
      "length" : "",
      "height" : "",
      "unit" : "in",
      "width" : ""
    },
    "cross_sell_ids" : [
      22
    ],
    "price" : "35.00",
    "updated_at" : "2013-06-07T11:00:28Z",
    "attributes" : [
      {
        "option" : "black",
        "name" : "Color"
      }
    ],
    "shipping_class" : "",
    "virtual" : false,
    "downloadable" : false,
    "upsell_ids" : [],
    "created_at" : "2013-06-07T11:00:28Z",
    "tax_class" : "",
    "tags" : [],
    "price_html" : "<span class=\"amount\">&#36;35</span>",
    "in_stock" : true,
    "sold_individually" : false,
    "short_description" : "<p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>\n",
    "downloads" : [],
    "tax_status" : "taxable",
    "average_rating" : "4.00",
    "download_type" : "",
    "shipping_taxable" : true,
    "purchase_note" : "",
    "shipping_class_id" : null,
    "visible" : true,
    "backorders_allowed" : false,
    "images" : [
      {
        "position" : 0,
        "id" : 43,
        "created_at" : "2013-06-07T10:59:40Z",
        "src" : "https://www.example.com/wp-content/uploads/2013/06/hoodie_7_front.jpg",
        "title" : "hoodie_7_front",
        "alt" : "",
        "updated_at" : "2013-06-07T10:59:40Z"
      }
    ],
    "stock_quantity" : 0,
    "featured" : false
  }
}
```

### `GET /products/#{id}/reviews`

Retrieve a list of reviews for a product specified by it's ID:

```
{
  "product_reviews" : [
    {
      "review" : "Ship it!",
      "rating" : "3",
      "id" : "13",
      "created_at" : "2013-06-07T15:53:31Z",
      "verified" : false,
      "reviewer_name" : "Maria",
      "reviewer_email" : "maria@woothemes.com"
    },
    {
      "review" : "This hoodie gets me lots of looks while out in public, I got the blue one and it's awesome. Not sure if people are looking at my hoodie only, or also at my rocking bod.",
      "rating" : "5",
      "id" : "12",
      "created_at" : "2013-06-07T13:24:52Z",
      "verified" : false,
      "reviewer_name" : "Ryan",
      "reviewer_email" : "ryan@woothemes.com"
    },
    {
      "review" : "Another great quality product that anyone who see's me wearing has asked where to purchase one of their own.",
      "rating" : "4",
      "id" : "11",
      "created_at" : "2013-06-07T13:03:29Z",
      "verified" : false,
      "reviewer_name" : "Stuart",
      "reviewer_email" : "stuart@woothemes.com"
    }
  ]
}
```

## Reports

### Report Properties

@TODO

### `GET /reports`

Retrieve a simple list of available reports:

```
{
  "reports" : [
    "sales"
  ]
}
```

### `GET /reports/sales`

Retrieve a sales report

You can specify either the period for which to retrieve sales or specify a start/end date. The supported periods are: `week`, `month`, `last_month`, and `year`. If you use an invalid period, `week` is used. If you don't specify a period, the current day is used.

`GET /reports/sales?filter[period]=month` will return sales for the current month

To return sales for a specific start/end date, set the `start_date` and `end_date` filter parameter:

`GET /reports/sales?filter[start_date]=2013-12-01&filter[end_date]=2013-12-09` will return sales between December 1st and December 9th, inclusive.

If you don't specify an end date, the current date will be used.

```
{
  "sales" : {
    "total_shipping" : "0.00",
    "total_orders" : 3,
    "total_sales" : "87.00",
    "totals_grouped_by" : "day",
    "total_discount" : "0.00",
    "totals" : {
      "2013-12-02" : {
        "orders" : 2,
        "shipping" : "0.00",
        "sales" : "67.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 4
      },
      "2013-12-08" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-01" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-07" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-06" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-05" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-04" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-10" : {
        "orders" : 1,
        "shipping" : "0.00",
        "sales" : "20.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 1
      },
      "2013-12-03" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      },
      "2013-12-09" : {
        "orders" : 0,
        "shipping" : "0.00",
        "sales" : "0.00",
        "tax" : "0.00",
        "discount" : "0.00",
        "items" : 0
      }
    },
    "total_items" : 5,
    "total_customers" : 1,
    "average_sales" : "8.70",
    "total_tax" : "0.00"
  }
}
```

# Tools

* [WooCommerce REST API Client Library](https://github.com/kloon/WooCommerce-REST-API-Client-Library) - A simple PHP client library by Gerhard Potgieter
* [CocoaRestClient](https://code.google.com/p/cocoa-rest-client/) - A free, easy to use Mac OS X GUI client for interacting with the API, most useful when your test store has SSL enabled.
* [Paw HTTP Client](https://itunes.apple.com/us/app/paw-http-client/id584653203?mt=12) - Another excellent HTTP client for Mac OS X

# Learn More

* [Initial REST API Implementation](https://github.com/woothemes/woocommerce/pull/4055) - the GitHub issue for the initial implementation
