<?php
/**
 * POS Store API route contract tests.
 *
 * The POS routes subclass concrete Store API route classes and the POS
 * Controller reshapes the parent's `get_args()` output at registration time.
 * That couples POS to a handful of internal Store API details that are NOT part
 * of any public contract: the structure returned by `get_args()`, the
 * `SCHEMA_TYPE` / `SCHEMA_VERSION` constants the Controller instantiates routes
 * with, the billing/shipping address args the Controller relaxes, and the
 * `is_cookie_authenticated()` nonce seam POS opts out through.
 *
 * If a web-side refactor changes any of those, POS would otherwise break
 * silently — the overrides would no-op or target a key that no longer exists.
 * These tests pin the shape POS depends on so such a change fails loudly here
 * instead of in production. They are intentionally about structure, not
 * behaviour; the behavioural coverage lives in the *IntegrationTest classes.
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddItem;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartApplyCoupon;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Checkout;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Automattic\WooCommerce\StoreApi\Routes\V1\CartAddItem as StoreApiCartAddItem;
use Automattic\WooCommerce\StoreApi\Routes\V1\CartApplyCoupon as StoreApiCartApplyCoupon;
use Automattic\WooCommerce\StoreApi\Routes\V1\Checkout as StoreApiCheckout;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\StoreApi;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller
 * @covers \Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute
 */
class PosRouteContractTest extends WC_Unit_Test_Case {

	/**
	 * Each POS route class mapped to the Store API concrete class it subclasses.
	 *
	 * @return array<string, array{0: class-string, 1: class-string}>
	 */
	public function pos_route_classes(): array {
		return array(
			'cart/add-item'     => array( CartAddItem::class, StoreApiCartAddItem::class ),
			'cart/apply-coupon' => array( CartApplyCoupon::class, StoreApiCartApplyCoupon::class ),
			'checkout'          => array( Checkout::class, StoreApiCheckout::class ),
		);
	}

	/**
	 * @testdox Each POS route still subclasses its Store API counterpart.
	 * @dataProvider pos_route_classes
	 *
	 * @param class-string $pos_class       POS route class.
	 * @param class-string $store_api_class Store API concrete route class.
	 */
	public function test_pos_routes_extend_their_store_api_counterparts( string $pos_class, string $store_api_class ): void {
		$this->assertTrue(
			is_subclass_of( $pos_class, $store_api_class ),
			"{$pos_class} must extend {$store_api_class}; the POS route reuses its schema and pipeline."
		);
		$this->assertTrue(
			is_subclass_of( $pos_class, AbstractCartRoute::class ),
			"{$pos_class} must ultimately extend AbstractCartRoute, which provides the cart session and nonce seam POS relies on."
		);
	}

	/**
	 * @testdox Each POS route exposes the SCHEMA_TYPE / SCHEMA_VERSION the Controller instantiates with.
	 * @dataProvider pos_route_classes
	 *
	 * @param class-string $pos_class POS route class.
	 */
	public function test_routes_expose_schema_constants( string $pos_class ): void {
		$this->assertIsString( $pos_class::SCHEMA_TYPE, "{$pos_class}::SCHEMA_TYPE must be a string; the Controller passes it to SchemaController::get()." );
		$this->assertNotSame( '', $pos_class::SCHEMA_TYPE, "{$pos_class}::SCHEMA_TYPE must be non-empty." );
		$this->assertIsInt( $pos_class::SCHEMA_VERSION, "{$pos_class}::SCHEMA_VERSION must be an int; the Controller passes it to SchemaController::get()." );
	}

	/**
	 * @testdox Each Store API parent's get_args() exposes the endpoint shape the POS Controller rewrites.
	 * @dataProvider pos_route_classes
	 *
	 * @param class-string $pos_class       POS route class (unused here).
	 * @param class-string $store_api_class Store API concrete route class.
	 */
	public function test_parent_get_args_has_rewritable_endpoint_shape( string $pos_class, string $store_api_class ): void {
		unset( $pos_class );

		$endpoints = $this->get_endpoint_definitions( $this->instantiate( $store_api_class )->get_args() );

		$this->assertNotEmpty(
			$endpoints,
			"{$store_api_class}::get_args() must contain at least one int-keyed endpoint definition; the Controller iterates these to apply POS overrides."
		);

		foreach ( $endpoints as $endpoint ) {
			$this->assertArrayHasKey(
				'permission_callback',
				$endpoint,
				"Every endpoint in {$store_api_class}::get_args() must expose a permission_callback; the POS Controller swaps it for the capability check."
			);
			if ( isset( $endpoint['args'] ) ) {
				$this->assertIsArray(
					$endpoint['args'],
					"The args entry in {$store_api_class}::get_args() must be an array; the POS Controller merges the cart_token parameter into it."
				);
			}
		}
	}

	/**
	 * @testdox The Store API Checkout exposes billing_address and shipping_address args for POS to relax.
	 */
	public function test_checkout_get_args_exposes_address_args_for_relaxation(): void {
		$endpoints = $this->get_endpoint_definitions( $this->instantiate( StoreApiCheckout::class )->get_args() );

		$address_args = array();
		foreach ( $endpoints as $endpoint ) {
			$address_args = array_merge( $address_args, array_keys( $endpoint['args'] ?? array() ) );
		}

		$this->assertContains( 'billing_address', $address_args, 'Store API Checkout must expose a billing_address arg; the POS Controller relaxes its required flag.' );
		$this->assertContains( 'shipping_address', $address_args, 'Store API Checkout must expose a shipping_address arg; the POS Controller relaxes its required flag.' );
	}

	/**
	 * @testdox AbstractCartRoute provides the is_cookie_authenticated() nonce seam POS overrides.
	 */
	public function test_cookie_authentication_seam_exists(): void {
		$this->assertTrue(
			method_exists( AbstractCartRoute::class, 'is_cookie_authenticated' ),
			'AbstractCartRoute must provide is_cookie_authenticated(); POS and agentic opt out of the nonce check through it.'
		);
	}

	/**
	 * @testdox requires_nonce() honours the cookie-auth seam: true for the web default, false once POS opts out.
	 */
	public function test_requires_nonce_honours_cookie_authentication_seam(): void {
		$update_request = new WP_REST_Request( 'POST' );

		$this->assertTrue(
			$this->call_requires_nonce( $this->instantiate( StoreApiCartAddItem::class ), $update_request ),
			'A cookie-authenticated update request without a cart token must still require a nonce (web behaviour must not regress).'
		);
		$this->assertFalse(
			$this->call_requires_nonce( $this->instantiate( CartAddItem::class ), $update_request ),
			'The POS route opts out via is_cookie_authenticated(), so requires_nonce() must be false.'
		);
		$this->assertFalse(
			$this->call_requires_nonce( $this->instantiate( Checkout::class ), $update_request ),
			'The POS checkout route opts out via is_cookie_authenticated(), so requires_nonce() must be false.'
		);
	}

	/**
	 * Instantiate a route the same way the Controllers do.
	 *
	 * @param class-string $route_class Route class to instantiate.
	 * @return AbstractCartRoute
	 */
	private function instantiate( string $route_class ): AbstractCartRoute {
		$schema_controller = StoreApi::container()->get( SchemaController::class );
		return new $route_class(
			$schema_controller,
			$schema_controller->get( $route_class::SCHEMA_TYPE, $route_class::SCHEMA_VERSION )
		);
	}

	/**
	 * Extract the int-keyed endpoint definitions from a get_args() result,
	 * ignoring the string-keyed metadata (schema, allow_batch).
	 *
	 * @param array $args Result of a route's get_args().
	 * @return array<int, array>
	 */
	private function get_endpoint_definitions( array $args ): array {
		$endpoints = array();
		foreach ( $args as $key => $endpoint ) {
			if ( is_int( $key ) && is_array( $endpoint ) && isset( $endpoint['methods'] ) ) {
				$endpoints[] = $endpoint;
			}
		}
		return $endpoints;
	}

	/**
	 * Invoke the protected requires_nonce() method on a route instance.
	 *
	 * @param AbstractCartRoute $route   Route instance.
	 * @param WP_REST_Request   $request Request to evaluate.
	 * @return bool
	 */
	private function call_requires_nonce( AbstractCartRoute $route, WP_REST_Request $request ): bool {
		$method = new \ReflectionMethod( $route, 'requires_nonce' );
		$method->setAccessible( true );
		return (bool) $method->invoke( $route, $request );
	}
}
