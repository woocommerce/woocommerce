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
		'woocommerce/products-manage',
		'woocommerce/orders-query',
		'woocommerce/orders-manage',
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
	 * Test canonical abilities register with MCP metadata for the upstream adapter.
	 */
	public function test_canonical_abilities_register_with_mcp_metadata(): void {
		foreach ( $this->registered_ability_ids as $ability_id ) {
			$ability = wp_get_ability( $ability_id );

			$this->assertNotNull( $ability, "{$ability_id} should be registered." );
			$this->assertSame( 'woocommerce', $ability->get_category() );

			$meta = $ability->get_meta();
			$this->assertTrue( $meta['show_in_rest'] );
			$this->assertSame( 'domain-api', $meta['woocommerce_ability_source'] );
			$this->assertTrue( $meta['mcp']['public'] );
			$this->assertSame( 'tool', $meta['mcp']['type'] );
		}
	}

	/**
	 * Test querying products by SKU.
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
	 * Test creating and updating a product.
	 */
	public function test_products_manage_create_and_update(): void {
		$created = wp_get_ability( 'woocommerce/products-manage' )->execute(
			array(
				'action'        => 'create',
				'type'          => 'simple',
				'name'          => 'Domain Managed Product',
				'sku'           => 'domain-managed-product',
				'regular_price' => '19.99',
			)
		);

		$this->assertNotWPError( $created );
		$product_id                  = $created['product']['id'];
		$this->created_product_ids[] = $product_id;

		$this->assertSame( 'create', $created['action'] );
		$this->assertSame( 'Domain Managed Product', $created['product']['name'] );
		$this->assertSame( '19.99', $created['product']['regular_price'] );

		$updated = wp_get_ability( 'woocommerce/products-manage' )->execute(
			array(
				'action'        => 'update',
				'id'            => $product_id,
				'name'          => 'Domain Managed Product Updated',
				'regular_price' => '24.99',
			)
		);

		$this->assertNotWPError( $updated );
		$this->assertSame( 'update', $updated['action'] );
		$this->assertSame( 'Domain Managed Product Updated', $updated['product']['name'] );
		$this->assertSame( '24.99', $updated['product']['regular_price'] );
	}

	/**
	 * Test querying orders by billing email.
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
	 * Test updating order status and adding an order note.
	 */
	public function test_orders_manage_update_status_and_add_note(): void {
		$order                     = \WC_Helper_Order::create_order();
		$this->created_order_ids[] = $order->get_id();

		$updated = wp_get_ability( 'woocommerce/orders-manage' )->execute(
			array(
				'action' => 'update_status',
				'id'     => $order->get_id(),
				'status' => 'processing',
			)
		);

		$this->assertNotWPError( $updated );
		$this->assertSame( 'update_status', $updated['action'] );
		$this->assertSame( 'processing', $updated['order']['status'] );

		$note = wp_get_ability( 'woocommerce/orders-manage' )->execute(
			array(
				'action' => 'add_note',
				'id'     => $order->get_id(),
				'note'   => 'Domain ability order note.',
			)
		);

		$this->assertNotWPError( $note );
		$this->assertSame( 'add_note', $note['action'] );
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
}
