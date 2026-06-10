---
post_title: 'Reference: exceptions'
sidebar_label: 'Exceptions'
sidebar_position: 5
---

# Reference: exceptions

Throwing an exception from `execute()` or `authorize()` is how the code API surfaces errors. The framework translates each into a GraphQL error with a machine-readable `extensions.code` and a matching HTTP status. All built-in exceptions live in `Automattic\WooCommerce\Api`.

## The base: `ApiException`

```php
public function __construct(
    string $message,
    private readonly string $error_code = 'INTERNAL_ERROR',
    private readonly array $extensions = array(),
    int $status_code = 500,
    ?\Throwable $previous = null,
)
```

It extends `\RuntimeException` and exposes `getErrorCode()`, `getExtensions()`, and `getStatusCode()`. The controller merges your `extensions` with `{ code: <error_code> }` (the code can't be overridden by an extensions entry), and uses `status_code` as the HTTP status.

## Built-in subclasses

Each fixes a `(code, status)` pair; all share the signature `( string $message = <default>, array $extensions = [], ?\Throwable $previous = null )`.

| Class | `extensions.code` | HTTP | Use when |
| --- | --- | --- | --- |
| `UnauthorizedException` | `UNAUTHORIZED` | 401 | Authentication is required but missing; or a generic auth denial where re-authenticating might help. |
| `InvalidTokenException` | `INVALID_TOKEN` | 401 | Credentials were supplied but rejected (bad/expired token, malformed header). |
| `ForbiddenException` | `FORBIDDEN` | 403 | Authenticated, but lacks permission ("I know who you are, but you can't do this") |
| `NotFoundException` | `NOT_FOUND` | 404 | The resource doesn't exist. (When existence is sensitive, prefer `UnauthorizedException` to avoid leaking it.) |
| `ValidationException` | `VALIDATION_ERROR` | 422 | Input is well-formed but fails a business rule. |

## Other translated throwables

| Thrown | Becomes |
| --- | --- |
| `\InvalidArgumentException` | `INVALID_ARGUMENT` / 400 - use for malformed/structural input (wrong type, contradictory args). |
| any other `\Throwable` | `INTERNAL_ERROR` / 500 - message masked; the original is attached as `previous` and shown only in debug mode. |

The framework also maps engine-level issues itself (e.g. an out-of-range `Int` output → `BAD_USER_INPUT` / 400; depth/complexity violations → 400).

## Authorization-failure status

When an authorization gate denies (rather than throwing), the framework picks the status from the principal: **401 `UNAUTHORIZED`** for anonymous principals (`is_authenticated()` is `false`), **403 `FORBIDDEN`** for authenticated ones or principals that don't expose `is_authenticated()`. See [Authentication and authorization](../authentication-and-authorization.md).

## Creating a custom exception (in a plugin or core)

Extend `ApiException` (or a subclass when its behavior fits) and pin your own code and status:

```php
namespace Automattic\MyPlugin\Api;

use Automattic\WooCommerce\Api\ApiException;

class QuotaExceededException extends ApiException {
    public function __construct( string $message = 'Quota exceeded.', array $extensions = array(), ?\Throwable $previous = null ) {
        parent::__construct( $message, 'QUOTA_EXCEEDED', $extensions, 429, $previous );
    }
}
```

Throw it from a command; the `code` and `status_code` surface automatically, and any `extensions` you pass appear alongside `code` in the response. Use a sensible standard HTTP status for your domain.
