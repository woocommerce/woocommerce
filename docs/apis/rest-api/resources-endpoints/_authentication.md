# Authentication #

WooCommerce includes two ways to authenticate with the WP REST API. It is also possible to authenticate using any [WP REST API authentication](http://v3.wp-api.org/guide/authentication/) plugin or method.

## REST API keys ##

Pre-generated keys can be used to authenticate use of the REST API endpoints. New keys can be generated either through the WordPress admin interface or they can be auto-generated through an endpoint.

### Generating API keys in the WordPress admin interface ###

To create or manage keys for a specific WordPress user, go to WooCommerce > Settings > Advanced > REST API.

*Note: Keys/Apps was found at WooCommerce > Settings > API > Key/Apps prior to WooCommerce 3.4.*

![WooCommerce REST API keys settings](images/woocommerce-api-keys-settings.png)

Click the "Add Key" button. In the next screen, add a description and select the WordPress user you would like to generate the key for. Use of the REST API with the generated keys will conform to that user's WordPress roles and capabilities.

Choose the level of access for this REST API key, which can be _Read_ access, _Write_ access or _Read/Write_ access. Then click the "Generate API Key" button and WooCommerce will generate REST API keys for the selected user.

![Creating a new REST API key](images/woocommerce-creating-api-keys.png)

Now that keys have been generated, you should see two new keys, a QRCode, and a Revoke API Key button. These two keys are your Consumer Key and Consumer Secret.

![Generated REST API key](images/woocommerce-api-key-generated.png)

If the WordPress user associated with an API key is deleted, the API key will cease to function. API keys are not transferred to other users.

### Auto generating API keys using our Application Authentication Endpoint ###

This endpoint can be used by any APP to *allow users to generate API keys* for your APP. This makes integration with WooCommerce API easier because the user only needs to grant access to your APP via a URL. After being redirected back to your APP, the API keys will be sent back in a separate POST request.

The following image illustrates how this works:

![Authentication Endpoint flow](images/woocommerce-auth-endpoint-flow.png)

<aside class="warning">
	This endpoint works exclusively for users to generate API keys and facilitate integration between the WooCommerce REST API and an application. In no way is this endpoint intended to be used as login method for customers.
</aside>

#### URL parameters ####

|   Parameter    |  Type  |                                                                                  Description                                                                                  |
|----------------|--------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `app_name`     | string | Your APP name <i class="label label-info">mandatory</i>                                                                                                                       |
| `scope`        | string | Level of access. Available: `read`, `write` and `read_write` <i class="label label-info">mandatory</i>                                                                        |
| `user_id`      | string | User ID in your APP. For your internal reference, used when the user is redirected back to your APP. NOT THE USER ID IN WOOCOMMERCE <i class="label label-info">mandatory</i> |
| `return_url`   | string | URL the user will be redirected to after authentication <i class="label label-info">mandatory</i>                                                                             |
| `callback_url` | string | URL that will receive the generated API key. Note: this URL should be over **HTTPS** <i class="label label-info">mandatory</i>                                                |

#### Creating an authentication endpoint URL ####

You must use the `/wc-auth/v1/authorize` endpoint and pass the above parameters as a query string.

> Example of how to build an authentication URL:

```shell
# Bash example
STORE_URL='http://example.com'
ENDPOINT='/wc-auth/v1/authorize'
PARAMS="app_name=My App Name&scope=read_write&user_id=123&return_url=http://app.com/return-page&callback_url=https://app.com/callback-endpoint"
QUERY_STRING="$(perl -MURI::Escape -e 'print uri_escape($ARGV[0]);' "$PARAMS")"
QUERY_STRING=$(echo $QUERY_STRING | sed -e "s/%20/\+/g" -e "s/%3D/\=/g" -e "s/%26/\&/g")

echo "$STORE_URL$ENDPOINT?$QUERY_STRING"
```

```javascript
const querystring = require('querystring');

const store_url = 'http://example.com';
const endpoint = '/wc-auth/v1/authorize';
const params = {
  app_name: 'My App Name',
  scope: 'read_write',
  user_id: 123,
  return_url: 'http://app.com/return-page',
  callback_url: 'https://app.com/callback-endpoint'
};
const query_string = querystring.stringify(params).replace(/%20/g, '+');

console.log(store_url + endpoint + '?' + query_string);
```

```php
<?php
$store_url = 'http://example.com';
$endpoint = '/wc-auth/v1/authorize';
$params = [
    'app_name' => 'My App Name',
    'scope' => 'write',
    'user_id' => 123,
    'return_url' => 'http://app.com',
    'callback_url' => 'https://app.com'
];
$query_string = http_build_query( $params );

echo $store_url . $endpoint . '?' . $query_string;
?>
```

```python
from urllib.parse import urlencode

store_url = 'http://example.com'
endpoint = '/wc-auth/v1/authorize'
params = {
    "app_name": "My App Name",
    "scope": "read_write",
    "user_id": 123,
    "return_url": "http://app.com/return-page",
    "callback_url": "https://app.com/callback-endpoint"
}
query_string = urlencode(params)

print("%s%s?%s" % (store_url, endpoint, query_string))
```

```ruby
require "uri"

store_url = 'http://example.com'
endpoint = '/wc-auth/v1/authorize'
params = {
  app_name: "My App Name",
  scope: "read_write",
  user_id: 123,
  return_url: "http://app.com/return-page",
  callback_url: "https://app.com/callback-endpoint"
}
query_string = URI.encode_www_form(params)

puts "#{store_url}#{endpoint}?#{query_string}"
```

> Example of JSON posted with the API Keys

```
{
    "key_id": 1,
    "user_id": 123,
    "consumer_key": "ck_xxxxxxxxxxxxxxxx",
    "consumer_secret": "cs_xxxxxxxxxxxxxxxx",
    "key_permissions": "read_write"
}
```

Example of the screen that the user will see:

![Authentication Endpoint example](images/woocommerce-auth-endpoint-example.png)

#### Notes ####

- While redirecting the user using `return_url`, you are also sent `success` and `user_id` parameters as query strings.
- `success` sends `0` if the user denied, or `1` if authenticated successfully.
- Use `user_id` to identify the user when redirected back to the (`return_url`) and also remember to save the API Keys when your `callback_url` is posted to after auth.
- The auth endpoint will send the API Keys in JSON format to the `callback_url`, so it's important to remember that some languages such as PHP will not display it inside the `$_POST` global variable, in PHP you can access it using `$HTTP_RAW_POST_DATA` (for old PHP versions) or `file_get_contents('php://input');`.
- The URL generated must have all query string values encoded.

## Authentication over HTTPS ##

You may use [HTTP Basic Auth](http://en.wikipedia.org/wiki/Basic_access_authentication) by providing the REST API Consumer Key as the username and the REST API Consumer Secret as the password.

> HTTP Basic Auth example

```shell
curl https://www.example.com/wp-json/wc/v3/orders \
    -u consumer_key:consumer_secret
```

```javascript
const WooCommerceRestApi = require("@woocommerce/woocommerce-rest-api").default;
// import WooCommerceRestApi from "@woocommerce/woocommerce-rest-api"; // Supports ESM

const WooCommerce = new WooCommerceRestApi({
  url: 'https://example.com',
  consumerKey: 'consumer_key',
  consumerSecret: 'consumer_secret',
  version: 'wc/v3'
});
```

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

$woocommerce = new Client(
    'https://example.com',
    'consumer_key',
    'consumer_secret',
    [
        'wp_api' => true,
        'version' => 'wc/v3'
    ]
);
?>
```

```python
from woocommerce import API

wcapi = API(
    url="https://example.com",
    consumer_key="consumer_key",
    consumer_secret="consumer_secret",
    wp_api=True,
    version="wc/v3"
)
```

```ruby
require "woocommerce_api"

woocommerce = WooCommerce::API.new(
  "https://example.com",
  "consumer_key",
  "consumer_secret",
  {
    wp_json: true,
    version: "wc/v3"
  }
)
```

Occasionally some servers may not parse the Authorization header correctly (if you see a "Consumer key is missing" error when authenticating over SSL, you have a server issue). In this case, you may provide the consumer key/secret as query string parameters instead.

> Example for servers that not properly parse the Authorization header:

```shell
curl https://www.example.com/wp-json/wc/v3/orders?consumer_key=123&consumer_secret=abc
```

```javascript
const WooCommerceRestApi = require("@woocommerce/woocommerce-rest-api").default;
// import WooCommerceRestApi from "@woocommerce/woocommerce-rest-api"; // Supports ESM

const WooCommerce = new WooCommerceRestApi({
  url: 'https://example.com',
  consumerKey: 'consumer_key',
  consumerSecret: 'consumer_secret',
  version: 'wc/v3',
  queryStringAuth: true // Force Basic Authentication as query string true and using under HTTPS
});
```

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

$woocommerce = new Client(
    'https://example.com',
    'consumer_key',
    'consumer_secret',
    [
        'wp_api' => true,
        'version' => 'wc/v3',
        'query_string_auth' => true // Force Basic Authentication as query string true and using under HTTPS
    ]
);
?>
```

```python
from woocommerce import API

wcapi = API(
    url="https://example.com",
    consumer_key="consumer_key",
    consumer_secret="consumer_secret",
    wp_api=True,
    version="wc/v3",
    query_string_auth=True // Force Basic Authentication as query string true and using under HTTPS
)
```

```ruby
require "woocommerce_api"

woocommerce = WooCommerce::API.new(
  "https://example.com",
  "consumer_key",
  "consumer_secret",
  {
    wp_json: true,
    version: "wc/v3",
    query_string_auth: true // Force Basic Authentication as query string true and using under HTTPS
  }
)
```

## Authentication over HTTP ##

You must use [OAuth 1.0a "one-legged" authentication](http://tools.ietf.org/html/rfc5849) to ensure REST API credentials cannot be intercepted by an attacker. Typically you will use any standard OAuth 1.0a library in the language of your choice to handle the authentication, or generate the necessary parameters by following the following instructions.

### Creating a signature ###

#### Collect the request method and URL ####

First you need to determine the HTTP method you will be using for the request, and the URL of the request.

The **HTTP method** will be `GET` in our case.

The **Request URL** will be the endpoint you are posting to, e.g. `http://www.example.com/wp-json/wc/v3/orders`.

#### Collect parameters ####

Collect and normalize your parameters. This includes all `oauth_*` parameters except for the `oauth_signature` itself.

These values need to be encoded into a single string which will be used later on. The process to build the string is very specific:

1. [Percent encode](https://dev.twitter.com/oauth/overview/percent-encoding-parameters) every key and value that will be signed.
2. Sort the list of parameters alphabetically by encoded key.
3. For each key/value pair:
	- Append the encoded key to the output string.
	- Append the `=` character to the output string.
	- Append the encoded value to the output string.
	- If there are more key/value pairs remaining, append a `&` character to the output string.

When percent encoding in PHP for example, you would use `rawurlencode()`.

When sorting parameters in PHP for example, you would use `uksort( $params, 'strcmp' )`.

> Parameters example:

```
oauth_consumer_key=abc123&oauth_signature_method=HMAC-SHA1
```

#### Create the signature base string ####

The above values collected so far must be joined to make a single string, from which the signature will be generated. This is called the signature base string in the OAuth specification.

To encode the HTTP method, request URL, and parameter string into a single string:

1. Set the output string equal to the uppercase **HTTP Method**.
2. Append the `&` character to the output string.
3. [Percent encode](https://dev.twitter.com/oauth/overview/percent-encoding-parameters) the URL and append it to the output string.
4. Append the `&` character to the output string.
5. [Percent encode](https://dev.twitter.com/oauth/overview/percent-encoding-parameters) the parameter string and append it to the output string.

> Example signature base string:

```
GET&http%3A%2F%2Fwww.example.com%2Fwp-json%2Fwc%2Fv3%2Forders&oauth_consumer_key%3Dabc123%26oauth_signature_method%3DHMAC-SHA1
```

#### Generate the signature ####

Generate the signature using the *signature base string* and your consumer secret key with a `&` character with the HMAC-SHA1 hashing algorithm.

In PHP you can use the [hash_hmac](http://php.net/manual/en/function.hash-hmac.php) function.

HMAC-SHA1 or HMAC-SHA256 are the only accepted hash algorithms.

If you are having trouble generating a correct signature, you'll want to review the string you are signing for encoding errors. The [authentication source](https://github.com/woocommerce/woocommerce/blob/master/includes/class-wc-rest-authentication.php#L185) can also be helpful in understanding how to properly generate the signature.

### OAuth tips ###

* The OAuth parameters may be added as query string parameters or included in the Authorization header.
* Note there is no reliable cross-platform way to get the raw request headers in WordPress, so query string should be more reliable in some cases.
* The required parameters are: `oauth_consumer_key`, `oauth_timestamp`, `oauth_nonce`, `oauth_signature`, and `oauth_signature_method`. `oauth_version` is not required and should be omitted.
* The OAuth nonce can be any randomly generated 32 character (recommended) string that is unique to the consumer key.
* The OAuth timestamp should be the unix timestamp at the time of the request. The REST API will deny any requests that include a timestamp outside of a 15 minute window to prevent replay attacks.
* You must use the store URL provided by the index when forming the base string used for the signature, as this is what the server will use. (e.g. if the store URL includes a `www` sub-domain, you should use it for requests)
* Note that the request body is *not* signed as per the OAuth spec.
* If including parameters in your request, it saves a lot of trouble if you can order your items alphabetically.
* Authorization header is supported starting WooCommerce 3.0.
