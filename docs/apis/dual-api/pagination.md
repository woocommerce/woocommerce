---
post_title: 'Relay-style pagination'
sidebar_label: 'Pagination'
sidebar_position: 3
---

# Relay-style pagination

List queries in the dual API paginate with **cursor-based connections** following the [Relay Cursor Connections specification](https://relay.dev/graphql/connections.htm). You write a command that returns a `Connection`; the builder generates the matching GraphQL `Connection`, `Edge`, and shared `PageInfo` types. The building blocks live in `Automattic\WooCommerce\Api\Pagination` and are reused by core and plugins alike.

## The connection shape

For a node type `Coupon`, a `#[ConnectionOf( Coupon::class )]` query produces this GraphQL shape:

```graphql
type CouponConnection {
  edges: [CouponEdge!]!   # each item paired with its cursor
  nodes: [Coupon!]!       # the items alone, a convenience shortcut
  page_info: PageInfo!
  total_count: Int!       # total matches before the page window
}

type CouponEdge {
  cursor: String!
  node: Coupon!
}

type PageInfo {
  has_next_page: Boolean!
  has_previous_page: Boolean!
  start_cursor: String
  end_cursor: String
}
```

`edges` and `nodes` carry the same items; `edges` adds the per-item `cursor`, while `nodes` is there for clients that just want the data. `PageInfo` is a single shared type across every connection.

## Writing a paginated query

Place the query under `Queries/`, return a `Connection`, and annotate `execute()` with `#[ConnectionOf( <NodeType>::class )]`. Take an argument of type `PaginationParams` - this type carries `#[Unroll]`, so its properties expand into individual GraphQL arguments rather than a nested input object:

```php
#[Name( 'coupons' )]
#[Description( 'List coupons with cursor-based pagination.' )]
#[RequiredCapability( 'read_private_shop_coupons' )]
class ListCoupons {
    #[ConnectionOf( Coupon::class )]
    public function execute( PaginationParams $pagination, ?CouponStatus $status = null ): Connection {
        // 1. query your data store, fetching one extra row to detect a next page
        // 2. build an Edge per item (cursor + node)
        // 3. populate a PageInfo and total_count
        // 4. return the Connection
    }
}
```

The resulting field accepts the four standard arguments plus any others you declare (like `status` above):

```graphql
coupons(first: Int, last: Int, after: String, before: String, status: CouponStatus) { ... }
```

## The pagination arguments

`PaginationParams` defines the forward/backward window:

| Argument | Meaning |
| --- | --- |
| `first` | Return the first N items (forward pagination). |
| `after` | Return items after this cursor. |
| `last` | Return the last N items (backward pagination). |
| `before` | Return items before this cursor. |

Bounds are enforced: `first`/`last` must be between `0` and `PaginationParams::MAX_PAGE_SIZE`; a negative or over-cap value throws `INVALID_ARGUMENT` (HTTP 400). When neither `first` nor `last` is given, `PaginationParams::get_default_page_size()` applies. The same bounds are enforced on nested connection fields via `PaginationParams::validate_args()`, so a deeply nested `first: 1000` can't slip past the cap.

These maximum and default page sizes are currently hardcoded to 100, but may become configurable in future versions of WooCommerce.

## Cursors

Cursors are **opaque strings** to the client, never construct or parse them on the client side. Beyond that opacity, the engine mandates nothing about their format: any stable, encodable key works. The current core proof-of-concept happens to encode the node's numeric id as base64 (`base64_encode( (string) $id )`) and decode it with `IdCursorFilter::decode_id_cursor()`, which validates the input and throws `INVALID_ARGUMENT` (400) on a malformed cursor rather than silently returning unfiltered results. That scheme is a choice of the PoC code, not a requirement; your own connections are free to use a different encoding - just keep cursors opaque and validate them on decode.

`IdCursorFilter` (in the `Api\Pagination` namespace) is a helper the PoC uses to window WordPress post queries on the `ID` column, via a lazy `posts_where` filter and two query vars:

- `IdCursorFilter::AFTER_ID` (`wc_api_after_id`) → `AND ID > X`
- `IdCursorFilter::BEFORE_ID` (`wc_api_before_id`) → `AND ID < X`

Set whichever you need on your `WP_Query` args and call `IdCursorFilter::ensure_registered()` once before running the query. None of this is mandated by the engine: a plugin paginating its own post-backed data may find it useful to reuse `IdCursorFilter` (or follow the same `ID`-cursor pattern), but it's specific to `WP_Query` sources, and a connection over any other data store won't touch it.

## PageInfo semantics

- `start_cursor` / `end_cursor` are the cursors of the first and last edges in the returned page (or `null` for an empty page).
- `has_next_page` / `has_previous_page` follow the Relay rules. In **forward** pagination (`first`), `has_next_page` is true when more items exist after the window - the common "fetch N+1 and check" trick. In **backward** pagination (`last`), the roles mirror. The framework computes these for you when it slices; if you pre-slice, you set them yourself.

## Building the Connection: two paths

`Connection` supports both a performant pre-paginated path and a slice-it-for-me path, and it guards against being sliced twice (so it's safe whether or not the generated resolver also calls `slice()`):

- **`Connection::pre_sliced( array $edges, PageInfo $page_info, int $total_count )`**: use when your data store already applied the limits (the recommended path for real databases: push `first`/`after` into the SQL query). The returned connection is marked sliced, so the framework leaves it untouched.
- **`$connection->slice( array $args )`**: build a `Connection` over a larger (or full) result set and let it apply the Relay algorithm: narrow by `after`, then `before`, then take `first` or `last`. It recomputes `PageInfo` and returns a new, sliced connection. Convenient for in-memory or small result sets.

## Nested connections

A `Connection`-typed **property** on an output type, annotated with `#[ConnectionOf]`, becomes a paginated field on that type; for example `Product.reviews`:

```php
#[Description( 'Customer reviews for this product.' )]
#[ConnectionOf( ProductReview::class )]
public Connection $reviews;
```

The generated resolver slices the property per the field's own pagination arguments, enforcing the same `MAX_PAGE_SIZE` cap as top-level queries.

## Complexity

Connection fields contribute to a query's computed complexity: a connection's cost multiplies its children's cost by the requested page size. This is what the **Maximum query complexity** limit guards against, see [Settings and caching](./caching-and-settings.md).

## Reusing the building blocks

`Connection`, `Edge`, `PageInfo`, and `PaginationParams` are part of the public `Api\Pagination` surface, so a plugin can return them directly without redefining its own. The [`woocommerce-simple-events`](https://github.com/woocommerce/woocommerce-simple-events) plugin's `eventsConnection` query is a minimal, in-memory working example (it builds edges over the full set and calls `slice()`); core's `ListCoupons` shows the `WP_Query` + `IdCursorFilter` database path.
