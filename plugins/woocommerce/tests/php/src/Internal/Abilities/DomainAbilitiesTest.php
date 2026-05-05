<?php
/**
 * DomainAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\DomainAbilities;

/**
 * Tests for canonical WooCommerce domain abilities.
 */
class DomainAbilitiesTest extends \WC_Unit_Test_Case {

	/**
	 * Ability IDs registered by these tests.
	 *
	 * @var array
	 */
	private $registered_ability_ids = array(
		'woocommerce/products-query',
		'woocommerce/product-create',
		'woocommerce/product-update',
		'woocommerce/product-delete',
		'woocommerce/orders-query',
		'woocommerce/order-update-status',
		'woocommerce/order-add-note',
	);

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
	 * Original value of $wp_actions['wp_abilities_api_init'] to restore in tearDown.
	 *
	 * @var int|null
	 */
	private $original_abilities_init_action_count;

	/**
	 * Original value of $wp_actions['wp_abilities_api_categories_init'] to restore in tearDown.
	 *
	 * @var int|null
	 */
	private $original_categories_init_action_count;

	/**
	 * Set up each test.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		$this->original_abilities_init_action_count  = $wp_actions['wp_abilities_api_init'] ?? null;
		$this->original_categories_init_action_count = $wp_actions['wp_abilities_api_categories_init'] ?? null;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			$abilities_bootstrap = WP_PLUGIN_DIR . '/woocommerce/vendor/wordpress/abilities-api/includes/bootstrap.php';
			if ( file_exists( $abilities_bootstrap ) ) {
				require_once $abilities_bootstrap;
			}
		}

		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator',
				)
			)
		);

		$this->register_woocommerce_category();
		$this->register_domain_abilities();
	}

	/**
	 * Clean up after each test.
	 */
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

		if ( null !== $this->original_abilities_init_action_count ) {
			$wp_actions['wp_abilities_api_init'] = $this->original_abilities_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['wp_abilities_api_init'] ) ) {
			unset( $wp_actions['wp_abilities_api_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		if ( null !== $this->original_categories_init_action_count ) {
			$wp_actions['wp_abilities_api_categories_init'] = $this->original_categories_init_action_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( isset( $wp_actions['wp_abilities_api_categories_init'] ) ) {
			unset( $wp_actions['wp_abilities_api_categories_init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * @testdox Should register canonical abilities with MCP metadata for the upstream adapter.
	 */
	public function test_canonical_abilities_register_with_mcp_metadata(): void {
		$expected_operations = array(
			'woocommerce/products-query'      => 'query',
			'woocommerce/product-create'      => 'create',
			'woocommerce/product-update'      => 'update',
			'woocommerce/product-delete'      => 'delete',
			'woocommerce/orders-query'        => 'query',
			'woocommerce/order-update-status' => 'update-status',
			'woocommerce/order-add-note'      => 'add-note',
		);

		foreach ( $this->registered_ability_ids as $ability_id ) {
			$ability = wp_get_ability( $ability_id );

			$this->assertNotNull( $ability, "{$ability_id} should be registered." );
			$this->assertSame( 'woocommerce', $ability->get_category() );

			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] );
			$this->assertSame( 'domain-api', $meta['woocommerce_ability_source'] );
			$this->assertSame( $expected_operations[ $ability_id ], $meta['woocommerce_ability_operation'] );
			$this->assertTrue( $meta['mcp']['public'] );
			$this->assertSame( 'tool', $meta['mcp']['type'] );
			$this->assertArrayHasKey( 'readonly', $meta['annotations'] );
			$this->assertArrayHasKey( 'destructive', $meta['annotations'] );
			$this->assertArrayHasKey( 'idempotent', $meta['annotations'] );
			$this->assertArrayNotHasKey( 'expose_in_deprecated_woocommerce_mcp', $meta );
		}
	}

	/**
	 * @testdox Should query products by SKU.
	 */
	public function test_products_query_by_sku(): void {
		$product                     = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name' => 'Domain Query Product',
				'sku'  => 'domain-query-product',
			)
		);
		$this->created_product_ids[] = $product->get_id();

		$result = wp_get_ability( 'woocommerce/products-query' )->execute(
			array(
				'sku' => 'domain-query-product',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $product->get_id(), $result['products'][0]['id'] );
		$this->assertSame( 'Domain Query Product', $result['products'][0]['name'] );
	}

	/**
	 * @testdox Should create and update a product.
	 */
	public function test_product_create_and_update(): void {
		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'type'          => 'simple',
				'name'          => 'Domain Managed Product',
				'sku'           => 'domain-managed-product',
				'regular_price' => '19.99',
			)
		);

		$this->assertNotWPError( $created );
		$product_id                  = $created['product']['id'];
		$this->created_product_ids[] = $product_id;

		$this->assertSame( 'Domain Managed Product', $created['product']['name'] );
		$this->assertSame( '19.99', $created['product']['regular_price'] );

		$updated = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'            => $product_id,
				'name'          => 'Domain Managed Product Updated',
				'regular_price' => '24.99',
			)
		);

		$this->assertNotWPError( $updated );
		$this->assertSame( 'Domain Managed Product Updated', $updated['product']['name'] );
		$this->assertSame( '24.99', $updated['product']['regular_price'] );
	}

	/**
	 * @testdox Should delete a product.
	 */
	public function test_product_delete(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		$deleted = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $product->get_id(),
				'force' => true,
			)
		);

		$this->assertNotWPError( $deleted );
		$this->assertTrue( $deleted['deleted'] );
		$this->assertSame( $product->get_id(), $deleted['id'] );

		$this->created_product_ids = array_diff( $this->created_product_ids, array( $product->get_id() ) );
	}

	/**
	 * @testdox Should reject product mutations without operation-specific product caps.
	 */
	public function test_product_mutations_require_operation_specific_product_caps(): void {
		$product                     = \WC_Helper_Product::create_simple_product();
		$this->created_product_ids[] = $product->get_id();

		wp_set_current_user( $this->create_user_with_caps( array( 'read', 'edit_products' ) ) );

		$created = wp_get_ability( 'woocommerce/product-create' )->execute(
			array(
				'type' => 'simple',
				'name' => 'Disallowed Product',
			)
		);

		$this->assertWPError( $created );
		$this->assertSame( 'ability_invalid_permissions', $created->get_error_code() );

		$updated = wp_get_ability( 'woocommerce/product-update' )->execute(
			array(
				'id'   => $product->get_id(),
				'name' => 'Disallowed Product Update',
			)
		);

		$this->assertWPError( $updated );
		$this->assertSame( 'ability_invalid_permissions', $updated->get_error_code() );

		$deleted = wp_get_ability( 'woocommerce/product-delete' )->execute(
			array(
				'id'    => $product->get_id(),
				'force' => true,
			)
		);

		$this->assertWPError( $deleted );
		$this->assertSame( 'ability_invalid_permissions', $deleted->get_error_code() );
	}

	/**
	 * @testdox Should query orders by billing email.
	 */
	public function test_orders_query_by_billing_email(): void {
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
		$this->assertSame( 'domain-order-query@example.com', $result['orders'][0]['billing_email'] );
		$this->assertNotEmpty( $result['orders'][0]['line_items'] );
	}

	/**
	 * @testdox Should reject order queries without order read caps.
	 */
	public function test_orders_query_requires_order_read_caps(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		wp_set_current_user( $this->create_user_with_caps( array( 'read', 'view_woocommerce_reports' ) ) );

		$result = wp_get_ability( 'woocommerce/orders-query' )->execute(
			array(
				'id' => $order->get_id(),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
	}

	/**
	 * @testdox Should update order status and add an order note.
	 */
	public function test_order_update_status_and_add_note(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$updated = wp_get_ability( 'woocommerce/order-update-status' )->execute(
			array(
				'id'     => $order->get_id(),
				'status' => 'processing',
			)
		);

		$this->assertNotWPError( $updated );
		$this->assertSame( 'processing', $updated['order']['status'] );

		$note = wp_get_ability( 'woocommerce/order-add-note' )->execute(
			array(
				'id'   => $order->get_id(),
				'note' => 'Domain ability order note.',
			)
		);

		$this->assertNotWPError( $note );
		$this->assertGreaterThan( 0, $note['note_id'] );
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

		$category = null;
		$callback = function () use ( &$category ) {
			$category = wp_register_ability_category(
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

		$this->assertNotNull( $category, 'WooCommerce ability category should register.' );
		$this->registered_category_ids[] = 'woocommerce';
	}

	/**
	 * Register canonical domain abilities for this test.
	 */
	private function register_domain_abilities(): void {
		$callback = array( DomainAbilities::class, 'register_abilities' );

		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Test bootstrap for Abilities API registration.
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Create a user with the given primitive capabilities.
	 *
	 * @param array $caps Capabilities to grant.
	 * @return int User ID.
	 */
	private function create_user_with_caps( array $caps ): int {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );

		$this->assertNotFalse( $user );

		foreach ( $caps as $cap ) {
			$user->add_cap( $cap );
		}

		return $user_id;
	}
}
