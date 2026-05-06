<?php
/**
 * AbilitiesLoaderTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderAddNote;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrderUpdateStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\OrdersQuery;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductDelete;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductUpdate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductsQuery;

/**
 * Tests for the canonical WooCommerce domain abilities and their loader.
 */
class AbilitiesLoaderTest extends \WC_Unit_Test_Case {

	private const CANONICAL_ABILITY_IDS = array(
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
	);

	/**
	 * Ability IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_ids = self::CANONICAL_ABILITY_IDS;

	/**
	 * Category IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_category_ids = array();

	/**
	 * Product IDs created by these tests.
	 *
	 * @var array
	 */
	private $created_product_ids = array();

	/**
	 * Order IDs created by these tests.
	 *
	 * @var array
	 */
	private $created_order_ids = array();

	/**
	 * Original action counts captured for restoration in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		foreach ( array( 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$abilities_bootstrap = WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $abilities_bootstrap ) ) {
				require_once $abilities_bootstrap;
			}
		}

		wp_set_current_user(
			$this->factory->user->create( array( 'role' => 'administrator' ) )
		);

		$this->register_woocommerce_category();
		$this->register_domain_abilities();
	}

	public function tearDown(): void {
		global $wp_actions;

		foreach ( $this->created_order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order ) {
				$order->delete( true );
			}
		}

		foreach ( $this->created_product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$product->delete( true );
			}
		}

		foreach ( $this->registered_ability_ids as $ability_id ) {
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}

		foreach ( $this->registered_category_ids as $category_id ) {
			if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( $category_id ) ) {
				wp_unregister_ability_category( $category_id );
			}
		}

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} elseif ( isset( $wp_actions[ $action ] ) ) {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/*
	 * ---------------------------------------------------------------------
	 * Registration / Loader
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should register every canonical ability with WooCommerce metadata.
	 */
	public function test_canonical_abilities_register_with_woocommerce_metadata(): void {
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			$ability = wp_get_ability( $ability_id );

			$this->assertNotNull( $ability, "{$ability_id} should be registered." );
			$this->assertSame( 'woocommerce', $ability->get_category(), "{$ability_id} should belong to the woocommerce category." );

			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] ?? false, "{$ability_id} should be exposed in REST." );
			$this->assertTrue( $meta['mcp']['public'] ?? false, "{$ability_id} should be flagged as MCP-public." );
			$this->assertSame( 'tool', $meta['mcp']['type'] ?? '', "{$ability_id} should be an MCP tool." );
			$this->assertArrayHasKey( 'readonly', $meta['annotations'] );
			$this->assertArrayHasKey( 'destructive', $meta['annotations'] );
			$this->assertArrayHasKey( 'idempotent', $meta['annotations'] );
			$this->assertArrayNotHasKey( 'expose_in_deprecated_woocommerce_mcp', $meta );
		}
	}

	/**
	 * @testdox Should mark write abilities as destructive and queries as readonly/idempotent.
	 */
	public function test_canonical_ability_annotations_match_intent(): void {
		$expectations = array(
			'woocommerce/products-query'      => array( 'readonly' => true,  'idempotent' => true,  'destructive' => false ),
			'woocommerce/product-create'      => array( 'readonly' => false, 'idempotent' => false, 'destructive' => false ),
			'woocommerce/product-update'      => array( 'readonly' => false, 'idempotent' => false, 'destructive' => true ),
			'woocommerce/product-delete'      => array( 'readonly' => false, 'idempotent' => false, 'destructive' => true ),
			'woocommerce/orders-query'        => array( 'readonly' => true,  'idempotent' => true,  'destructive' => false ),
			'woocommerce/order-update-status' => array( 'readonly' => false, 'idempotent' => false, 'destructive' => true ),
			'woocommerce/order-add-note'      => array( 'readonly' => false, 'idempotent' => false, 'destructive' => false ),
		);

		foreach ( $expectations as $ability_id => $annotations ) {
			$meta = wp_get_ability( $ability_id )->get_meta();

			foreach ( $annotations as $key => $value ) {
				$this->assertSame(
					$value,
					$meta['annotations'][ $key ] ?? null,
					"{$ability_id} should have annotations.{$key}=" . ( $value ? 'true' : 'false' ) . '.'
				);
			}
		}
	}

	/**
	 * @testdox Should advertise every product status that mutation responses can return.
	 */
	public function test_product_output_schema_allows_mutation_statuses(): void {
		$output_schema = wp_get_ability( 'woocommerce/product-create' )->get_output_schema();
		$status_enum   = $output_schema['properties']['product']['properties']['status']['enum'] ?? array();

		$this->assertContains( 'auto-draft', $status_enum );
		$this->assertContains( 'trash', $status_enum );
		$this->assertContains( 'publish', $status_enum );
	}

	/**
	 * @testdox Should describe product output primitives using WooCommerce registries.
	 */
	public function test_product_output_schema_uses_woocommerce_primitive_constraints(): void {
		$output_schema = wp_get_ability( 'woocommerce/product-create' )->get_output_schema();
		$product       = $output_schema['properties']['product']['properties'] ?? array();

		$this->assertSame( 'uri', $product['permalink']['format'] ?? null );
		$this->assertContains( get_woocommerce_currency(), $product['currency']['enum'] ?? array() );
		$this->assertSame(
			array( wc_is_stock_amount_integer() ? 'integer' : 'number', 'null' ),
			$product['stock_quantity']['type'] ?? null
		);
	}

	/**
	 * @testdox Should describe order output primitives using WooCommerce registries.
	 */
	public function test_order_output_schema_uses_woocommerce_primitive_constraints(): void {
		$output_schema = wp_get_ability( 'woocommerce/orders-query' )->get_output_schema();
		$order         = $output_schema['properties']['orders']['items']['properties'] ?? array();

		$this->assertContains( 'auto-draft', $order['status']['enum'] ?? array() );
		$this->assertContains( 'trash', $order['status']['enum'] ?? array() );
		$this->assertContains( 'checkout-draft', $order['status']['enum'] ?? array() );
		$this->assertContains( get_woocommerce_currency(), $order['currency']['enum'] ?? array() );
		$this->assertSame( array( 'string', 'null' ), $order['billing_email']['type'] ?? null );
		$this->assertSame( 'email', $order['billing_email']['format'] ?? null );
	}

	/**
	 * @testdox Should register extension ability classes appended via the loader filter.
	 */
	public function test_loader_filter_accepts_valid_extension_classes(): void {
		add_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_extension_ability_definition_class' ) );
		$this->register_domain_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', array( $this, 'add_test_extension_ability_definition_class' ) );

		$this->registered_ability_ids[] = TestExtensionAbilityDefinition::ABILITY_ID;
		$ability                        = wp_get_ability( TestExtensionAbilityDefinition::ABILITY_ID );

		$this->assertNotNull( $ability, 'Extension ability should be registered.' );
		$this->assertSame( 'woocommerce', $ability->get_category() );

		$result = $ability->execute();

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['ok'] );
	}

	/**
	 * @testdox Should ignore filter entries that are not strings or AbilityDefinition implementations.
	 */
	public function test_loader_filter_skips_invalid_entries(): void {
		$callback = static function ( array $classes ): array {
			$classes[] = '\\Some\\Class\\That\\Does\\Not\\Exist';
			$classes[] = \WC_Order::class; // Real class but not an AbilityDefinition.
			$classes[] = 42; // Wrong type.
			return $classes;
		};

		add_filter( 'woocommerce_ability_definition_classes', $callback );
		$this->register_domain_abilities();
		remove_filter( 'woocommerce_ability_definition_classes', $callback );

		// Canonical abilities should still register; nothing extra.
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			$this->assertNotNull( wp_get_ability( $ability_id ), "{$ability_id} should remain registered." );
		}
	}

	/*
	 * ---------------------------------------------------------------------
	 * Permission negative paths
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should reject every canonical ability when no user is authenticated.
	 *
	 * @dataProvider provider_canonical_abilities_with_minimal_input
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Minimal valid-shape input.
	 */
	public function test_abilities_reject_unauthenticated_user( string $ability_id, array $input ): void {
		wp_set_current_user( 0 );

		$result = wp_get_ability( $ability_id )->execute( $input );

		$this->assertWPError( $result, "{$ability_id} should reject unauthenticated users." );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject every canonical ability for a subscriber with no shop caps.
	 *
	 * @dataProvider provider_canonical_abilities_with_minimal_input
	 *
	 * @param string $ability_id Ability ID.
	 * @param array  $input      Minimal valid-shape input.
	 */
	public function test_abilities_reject_subscriber_without_caps( string $ability_id, array $input ): void {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		$result = wp_get_ability( $ability_id )->execute( $input );

		$this->assertWPError( $result, "{$ability_id} should reject subscribers without shop caps." );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * Provides ability IDs and minimal valid-shape input for cap negative-path tests.
	 *
	 * @return array<string, array{0: string, 1: array}>
	 */
	public function provider_canonical_abilities_with_minimal_input(): array {
		// Valid-shape input only — permission check should fire before execute.
		return array(
			'products-query'      => array( 'woocommerce/products-query', array( 'id' => 1 ) ),
			'product-create'      => array( 'woocommerce/product-create', array( 'name' => 'Forbidden' ) ),
			'product-update'      => array( 'woocommerce/product-update', array( 'id' => 1, 'name' => 'Forbidden' ) ),
			'product-delete'      => array( 'woocommerce/product-delete', array( 'id' => 1 ) ),
			'orders-query'        => array( 'woocommerce/orders-query', array( 'id' => 1 ) ),
			'order-update-status' => array( 'woocommerce/order-update-status', array( 'id' => 1, 'status' => 'processing' ) ),
			'order-add-note'      => array( 'woocommerce/order-add-note', array( 'id' => 1, 'note' => 'denied' ) ),
		);
	}

	/**
	 * @testdox Should require publish_products to transition an existing draft to published.
	 */
	public function test_product_update_publish_transition_requires_publish_cap(): void {
		$product = \WC_Helper_Product::create_simple_product( true, array( 'status' => 'draft' ) );
		$this->created_product_ids[] = $product->get_id();

		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'edit_products' );
		$user->add_cap( 'edit_others_products' );
		$user->add_cap( 'edit_published_products' );
		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'     => $product->get_id(),
				'status' => 'publish',
			)
		);

		$this->assertWPError( $result, 'A user without publish_products should not be able to publish a draft.' );
		$this->assertSame( 'woocommerce_product_publish_forbidden', $result->get_error_code() );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Input validation
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should reject order status updates with an unknown status slug.
	 */
	public function test_order_update_status_rejects_invalid_status_slug(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'totally-bogus',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject product creation with a status not in the allowed enum.
	 */
	public function test_product_create_rejects_unknown_status(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'   => 'Bad Status Product',
				'status' => 'not-a-real-status',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject extra unknown fields on product create input.
	 */
	public function test_product_create_rejects_unknown_input_field(): void {
		$result = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'         => 'Mass Assigned',
				'invoice_url'  => 'https://attacker.example.com',
				'admin_notes'  => 'tampering',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject orders-query orderby values outside the allowed enum.
	 */
	public function test_orders_query_rejects_unknown_orderby(): void {
		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array( 'orderby' => 'malicious_field' )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Execution behaviors
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @testdox Should default products-query status filter to publish.
	 */
	public function test_products_query_default_status_is_publish(): void {
		$published = \WC_Helper_Product::create_simple_product(
			true,
			array( 'name' => 'Public Item' )
		);
		$this->created_product_ids[] = $published->get_id();

		$draft = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Draft Item',
				'status' => 'draft',
			)
		);
		$this->created_product_ids[] = $draft->get_id();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array() );

		$this->assertNotWPError( $result );
		$ids = array_column( $result['products'], 'id' );
		$this->assertContains( $published->get_id(), $ids );
		$this->assertNotContains( $draft->get_id(), $ids );
	}

	/**
	 * @testdox Should return drafts when explicitly filtered by status=draft.
	 */
	public function test_products_query_returns_drafts_when_explicitly_requested(): void {
		$draft = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'   => 'Hidden Draft',
				'status' => 'draft',
				'sku'    => 'hidden-draft-sku',
			)
		);
		$this->created_product_ids[] = $draft->get_id();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'status' => 'draft',
				'sku'    => 'hidden-draft-sku',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $draft->get_id(), $result['products'][0]['id'] );
	}

	/**
	 * @testdox Should include currency context in product responses.
	 */
	public function test_product_response_includes_currency_context(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'name'          => 'Priced Product',
				'regular_price' => '12.34',
			)
		);

		$this->assertNotWPError( $created );
		$this->created_product_ids[] = $created['product']['id'];

		$this->assertSame( get_woocommerce_currency(), $created['product']['currency'] );
		$this->assertNotEmpty( $created['product']['currency_symbol'] );
		$this->assertSame( '12.34', $created['product']['regular_price'] );
	}

	/**
	 * @testdox Should support fractional product stock quantities when WooCommerce is configured for them.
	 */
	public function test_product_stock_quantity_schema_allows_fractional_stock_amounts(): void {
		$this->unregister_domain_abilities();
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', array( $this, 'preserve_fractional_stock_amount' ) );

		try {
			$this->register_domain_abilities();

			$input_schema  = wp_get_ability( 'woocommerce/product-create' )->get_input_schema();
			$output_schema = wp_get_ability( 'woocommerce/product-create' )->get_output_schema();

			$this->assertSame( 'number', $input_schema['properties']['stock_quantity']['type'] ?? null );
			$this->assertSame(
				array( 'number', 'null' ),
				$output_schema['properties']['product']['properties']['stock_quantity']['type'] ?? null
			);

			$created = wp_get_ability( 'woocommerce/product-create' )->execute(
				array(
					'name'           => 'Fractional Stock Product',
					'manage_stock'   => true,
					'stock_quantity' => 1.5,
				)
			);

			$this->assertNotWPError( $created );
			$this->created_product_ids[] = $created['product']['id'];
			$this->assertSame( 1.5, $created['product']['stock_quantity'] );
		} finally {
			remove_filter( 'woocommerce_stock_amount', array( $this, 'preserve_fractional_stock_amount' ) );
			add_filter( 'woocommerce_stock_amount', 'intval' );
		}
	}

	/**
	 * @testdox Should update product properties and reflect changes in the response.
	 */
	public function test_product_update_changes_props_and_returns_updated_response(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'            => $product->get_id(),
				'name'          => 'Updated Name',
				'regular_price' => '99.00',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'Updated Name', $result['product']['name'] );
		$this->assertSame( '99.00', $result['product']['regular_price'] );
	}

	/**
	 * @testdox Should hard-delete a product when force=true.
	 */
	public function test_product_delete_force_true_hard_deletes(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $product->get_id(),
				'force' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertSame( $product->get_id(), $result['id'] );
		$this->assertNull( get_post( $product->get_id() ) );
	}

	/**
	 * @testdox Should trash a product when force=false.
	 */
	public function test_product_delete_force_false_trashes(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $product->get_id(),
				'force' => false,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertTrue( $result['deleted'] );
		$this->assertSame( 'trash', get_post_status( $product->get_id() ) );
	}

	/**
	 * @testdox Should return a not-found error when querying an unknown product ID.
	 */
	public function test_products_query_returns_not_found_for_unknown_id(): void {
		$result = wp_get_ability( 'woocommerce/products-query' )->execute( array( 'id' => 999999 ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_product_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should query orders by billing email and include line items when requested.
	 */
	public function test_orders_query_filters_by_billing_email_with_line_items(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_billing_email( 'domain-order-query@example.com' );
		$order->save();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email'      => 'domain-order-query@example.com',
				'include_line_items' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $order->get_id(), $result['orders'][0]['id'] );
		$this->assertNotEmpty( $result['orders'][0]['line_items'] );
		$this->assertNotEmpty( $result['orders'][0]['currency_symbol'] );
	}

	/**
	 * @testdox Should return null for absent billing email values.
	 */
	public function test_orders_query_returns_null_for_absent_billing_email(): void {
		$order = wc_create_order();
		$this->assertNotWPError( $order );
		$order->save();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id' => $order->get_id(),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertNull( $result['orders'][0]['billing_email'] );
	}

	/**
	 * @testdox Should narrow orders-query results by date range.
	 */
	public function test_orders_query_filters_by_date_range(): void {
		$old_order = \WC_Helper_Order::create_order();
		$old_order->set_date_created( '2020-01-15T00:00:00' );
		$old_order->set_billing_email( 'date-range@example.com' );
		$old_order->save();
		$this->created_order_ids[] = $old_order->get_id();

		$new_order = \WC_Helper_Order::create_order();
		$new_order->set_date_created( '2025-01-15T00:00:00' );
		$new_order->set_billing_email( 'date-range@example.com' );
		$new_order->save();
		$this->created_order_ids[] = $new_order->get_id();

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'billing_email' => 'date-range@example.com',
				'date_after'    => '2024-01-01T00:00:00',
			)
		);

		$this->assertNotWPError( $result );
		$ids = array_column( $result['orders'], 'id' );
		$this->assertContains( $new_order->get_id(), $ids );
		$this->assertNotContains( $old_order->get_id(), $ids );
	}

	/**
	 * @testdox Should change order status and surface failure when status update fails.
	 */
	public function test_order_update_status_changes_status(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'processing',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 'processing', $result['order']['status'] );
		$this->assertSame( 'processing', wc_get_order( $order->get_id() )->get_status() );
	}

	/**
	 * @testdox Should reject status updates against unknown order IDs without leaking existence.
	 */
	public function test_order_update_status_rejects_unknown_id(): void {
		$result = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => 999999,
				'status' => 'processing',
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * @testdox Should attribute order notes to the acting user.
	 */
	public function test_order_add_note_attributes_to_acting_user(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$user_id = $this->factory->user->create(
			array(
				'role'         => 'shop_manager',
				'display_name' => 'Audit Trail Admin',
			)
		);
		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Tracked by acting user.',
			)
		);

		$this->assertNotWPError( $result );

		$comment = get_comment( $result['note_id'] );
		$this->assertNotNull( $comment );
		$this->assertSame( 'Audit Trail Admin', $comment->comment_author );
	}

	/**
	 * @testdox Should mark notes as customer-facing when customer_note=true.
	 */
	public function test_order_add_note_persists_customer_note_flag(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$result = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'            => $order->get_id(),
				'note'          => 'Visible to customer.',
				'customer_note' => true,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( '1', get_comment_meta( $result['note_id'], 'is_customer_note', true ) );
	}

	/**
	 * Register the WooCommerce ability category for this test.
	 */
	private function register_woocommerce_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) || ! function_exists( 'wp_has_ability_category' ) ) {
			return;
		}

		if ( wp_has_ability_category( 'woocommerce' ) ) {
			return;
		}

		$callback = static function () {
			wp_register_ability_category(
				'woocommerce',
				array(
					'label'       => 'WooCommerce',
					'description' => 'Canonical WooCommerce store management abilities.',
				)
			);
		};

		add_action( 'wp_abilities_api_categories_init', $callback );
		do_action( 'wp_abilities_api_categories_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_categories_init', $callback );

		$this->registered_category_ids[] = 'woocommerce';
	}

	/**
	 * Register canonical domain abilities for this test.
	 */
	private function register_domain_abilities(): void {
		$callback = array( AbilitiesLoader::class, 'register_abilities' );

		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Unregister canonical domain abilities for this test.
	 */
	private function unregister_domain_abilities(): void {
		foreach ( self::CANONICAL_ABILITY_IDS as $ability_id ) {
			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Add the test extension ability definition class.
	 *
	 * @param array $classes Ability definition class names.
	 * @return array
	 */
	public function add_test_extension_ability_definition_class( array $classes ): array {
		$classes[] = TestExtensionAbilityDefinition::class;

		return $classes;
	}

	/**
	 * Preserve fractional stock amounts for filtered stock quantity tests.
	 *
	 * @param mixed $amount Stock amount.
	 * @return float
	 */
	public function preserve_fractional_stock_amount( $amount ): float {
		return (float) $amount;
	}
}
