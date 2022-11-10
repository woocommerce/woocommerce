# Rate Limiting for Store API endpoints  <!-- omit in toc -->

## Table of Contents <!-- omit in toc -->

- [Limit information](#limit-information)
- [Methods restricted by Rate Limiting](#methods-restricted-by-rate-limiting)
- [Rate Limiting options filter](#rate-limiting-options-filter)
- [Proxy standard support](#proxy-standard-support)
- [Limit usage information observability](#limit-usage-information-observability)
    - [Response headers example](#response-headers-example)
- [Tracking limit abuses](#tracking-limit-abuses)
    - [Custom tracking usage example](#custom-tracking-usage-example)

[Rate Limiting](https://github.com/woocommerce/woocommerce-blocks/pull/5962) is available for Store API endpoints. This is optional and disabled by default. It can be enabled by following [these instructions](#rate-limiting-options-filter).

The main purpose prevent abuse on endpoints from excessive calls and performance degradation on the machine running the store.

Rate limit tracking is controlled by either `USER ID` (logged in) or `IP ADDRESS` (unauthenticated requests).

It also offers standard support for running behind a proxy, load balancer, etc. This also optional and disabled by default.


## Limit information

A default maximum of 25 requests can be made within a 10-second time frame. These can be changed through an [options filter](#rate-limiting-options-filter).

## Methods restricted by Rate Limiting

`POST`, `PUT`, `PATCH`, and `DELETE`

## Rate Limiting options filter

A filter is available for setting options for rate limiting:

```php
add_filter( 'woocommerce_store_api_rate_limit_options', function() {
	return [
		'enabled' => false, // enables/disables Rate Limiting. Default: false
		'proxy_support' => false, // enables/disables Proxy support. Default: false
		'limit' => 25, // limit of request per timeframe. Default: 25
		'seconds' => 10, // timeframe in seconds. Default: 10
	];
} );
```

## Proxy standard support

If the Store is running behind a proxy, load balancer, cache service, CDNs, etc. keying limits by IP is supported through standard IP forwarding headers, namely:

- `X_REAL_IP`|`CLIENT_IP` *Custom popular implementations that simplify obtaining the origin IP for the request*
- `X_FORWARDED_FOR` *De-facto standard header for identifying the originating IP, [Documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Forwarded-For)*
- `X_FORWARDED` *[Documentation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Forwarded), [RFC 7239](https://datatracker.ietf.org/doc/html/rfc7239)*

This is disabled by default.

## Limit usage information observability

Current limit information can be observed via custom response headers:

- `RateLimit-Limit` *Maximum requests per time frame.*
- `RateLimit-Remaining` *Requests available during current time frame.*
- `RateLimit-Reset` *Unix timestamp of next time frame reset.*
- `RateLimit-Retry-After` *Seconds until requests are unblocked again. Only shown when the limit is reached.*

### Response headers example

```http
RateLimit-Limit: 5
RateLimit-Remaining: 0
RateLimit-Reset: 1654880642
RateLimit-Retry-After: 28
```

## Tracking limit abuses

This uses a modified wc_rate_limit table with an additional remaining column for tracking the request count in any given request window.
A custom action `woocommerce_store_api_rate_limit_exceeded` was implemented for extendability in tracking such abuses.

### Custom tracking usage example

```php
add_action(
    'woocommerce_store_api_rate_limit_exceeded',
    function ( $offending_ip ) { /* Custom tracking implementation */ }
);
```

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/rate-limiting.md)

<!-- /FEEDBACK -->
<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./src/StoreApi/docs/rate-limiting.md)

<!-- /FEEDBACK -->

