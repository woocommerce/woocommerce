# Product Feed framework

The Product Feed framework provides reusable building blocks for exposing the product catalog to external systems. It separates two concerns that integrations frequently need:

1. **Product-shape mapping** — turning a `WC_Product` into an array shape that a consumer understands.
2. **Feed delivery** — assembling the mapped shapes into a file (JSON, CSV, …) and handing it off to a destination.

Push-feed integrations (for example, the built-in POS Catalog integration or the Stripe Agentic Commerce feed) use both. Pull/live-query integrations (for example, UCP catalog endpoints that run a fresh query on every request) only need the mapping concern and can use it without taking any dependency on file or CSV delivery.

## Directory layout

```text
ProductFeed/
├── ProductFeed.php                  # Entry point; integration registration
├── Mapping/
│   └── ProductShapeMapperInterface.php  # Delivery-agnostic mapping contract
├── Feed/
│   ├── ProductMapperInterface.php   # Deprecated alias of the mapping contract
│   ├── FeedInterface.php            # Feed assembly (start / add_entry / end)
│   ├── FeedValidatorInterface.php   # Per-entry validation for feeds
│   ├── ProductWalker.php            # Batched iteration of the catalog into a feed
│   ├── ProductLoader.php            # Thin wrapper around wc_get_products()
│   └── WalkerProgress.php           # Progress data for walker callbacks
├── Integrations/
│   ├── IntegrationInterface.php     # Contract for push-feed integrations
│   ├── IntegrationRegistry.php      # Holds registered integrations
│   └── POSCatalog/                  # Built-in POS catalog integration
├── Storage/
│   └── JsonFileFeed.php             # File-backed JSON feed implementation
└── Utils/                           # Memory and string helpers
```

## Product-shape mapping

`Mapping\ProductShapeMapperInterface` is the minimal contract shared by all consumers:

```php
use Automattic\WooCommerce\Internal\ProductFeed\Mapping\ProductShapeMapperInterface;

class MyCatalogMapper implements ProductShapeMapperInterface {
	public function map_product( \WC_Product $product ): array {
		return array(
			'id'    => (string) $product->get_id(),
			'title' => $product->get_name(),
			// ... whatever shape your consumer needs.
		);
	}
}
```

The interface carries no delivery semantics. The returned array can be a feed row, a REST payload, a live query result, or anything else. This makes a mapper implementation reusable across delivery models:

- **Pull/live-query consumers** (REST controllers that query products per request) type against `ProductShapeMapperInterface` directly and call `map_product()` on each result of their own query. They never touch `FeedInterface`, validators, or files.
- **Push-feed integrations** also implement `ProductShapeMapperInterface`; the framework's walker and feed machinery consume the mapper through this contract. The older `Feed\ProductMapperInterface` (which extends `ProductShapeMapperInterface` without adding methods) is deprecated: existing implementations keep working during the transition window, but new code should implement `ProductShapeMapperInterface` directly.

## Push-feed integrations

A push-feed integration implements `Integrations\IntegrationInterface`, which composes the mapping contract with feed-specific collaborators:

- `get_product_mapper(): ProductShapeMapperInterface` — the mapper producing one row per product.
- `get_feed_validator(): FeedValidatorInterface` — validates each mapped row; rows with issues are skipped.
- `create_feed(): FeedInterface` — the feed being assembled (`Storage\JsonFileFeed` is a ready-made file-backed implementation).
- `get_product_feed_query_args(): array` — extra `wc_get_products()` arguments scoping which products are included.

`Feed\ProductWalker` ties these together: it iterates the catalog in batches (with memory management and progress reporting), runs every product through the mapper, drops rows the validator rejects, and writes the rest to the feed:

```php
use Automattic\WooCommerce\Internal\ProductFeed\Feed\ProductWalker;

$feed   = $integration->create_feed();
$walker = ProductWalker::from_integration( $integration, $feed );
$walker->set_batch_size( 100 )->walk();

$file_path = $feed->get_file_path();
```

Integrations register themselves through `ProductFeed::register_integration()`:

```php
use Automattic\WooCommerce\Internal\ProductFeed\ProductFeed;

wc_get_container()->get( ProductFeed::class )->register_integration( new MyIntegration() );
```

## Pull/live-query consumers

A pull consumer keeps its own querying and transport, and reuses only the mapping abstraction:

```php
use Automattic\WooCommerce\Internal\ProductFeed\Mapping\ProductShapeMapperInterface;

class MyCatalogController {
	private ProductShapeMapperInterface $mapper;

	public function __construct( ProductShapeMapperInterface $mapper ) {
		$this->mapper = $mapper;
	}

	public function search( \WP_REST_Request $request ): \WP_REST_Response {
		$products = wc_get_products( array( /* request-derived args */ ) );

		return new \WP_REST_Response(
			array( 'products' => array_map( array( $this->mapper, 'map_product' ), $products ) )
		);
	}
}
```

Because the mapper is delivery-agnostic, the same implementation can also back a push feed for the same catalog shape later, by plugging it into an `IntegrationInterface`.

## Scope and future direction

The framework currently models two consumption patterns: push feeds (file assembly and delivery) and pull/live-query mapping. A third pattern exists in the WooCommerce ecosystem — batched API push with change-trigger hooks, as implemented by Google Listings & Ads (`WCProductAdapter` → `ProductSyncer` → `BatchProductHelper` → `SyncerHooks`). That model is the most mature product-export abstraction in the ecosystem and is a candidate blueprint for a future, richer export framework that push-feed, pull/query, and API-push integrations could all share. Migrating existing API-push integrations onto this framework is intentionally out of scope for now; `ProductShapeMapperInterface` is the shared mapping kernel any such evolution would build on.

## Backwards compatibility notes

- `Feed\ProductMapperInterface` is deprecated, not removed: it extends `Mapping\ProductShapeMapperInterface` without redeclaring methods, so existing implementations keep working unchanged and automatically satisfy the new interface. They should migrate to implementing `ProductShapeMapperInterface` directly before the deprecated interface is removed in a future release.
- `IntegrationInterface::get_product_mapper()` declares `ProductShapeMapperInterface` as its return type. Implementations may keep narrowing it to `ProductMapperInterface` or a concrete mapper class (return type covariance).
- These classes live under `Internal` and are not a public API; interfaces may evolve between minor versions.
