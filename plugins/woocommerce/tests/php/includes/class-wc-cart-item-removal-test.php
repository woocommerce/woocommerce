<?php
/**
 * Unit tests for cart item removal functionality.
 *
 * @package WooCommerce\Tests\Cart
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

/**
 * Class WC_Cart_Item_Removal_Test
 */
class WC_Cart_Item_Removal_Test extends \WC_Unit_Test_Case {

	/**
	 * Set up test data.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		$this->cart = new WC_Cart();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		$this->product->delete( true );
		$this->cart->empty_cart();
		parent::tearDown();
	}

	/**
	 * Test that get_removed_cart_contents returns an array.
	 */
	public function test_get_removed_cart_contents_returns_array() {
		$removed = $this->cart->get_removed_cart_contents();
		$this->assertIsArray( $removed );
		$this->assertEmpty( $removed );
	}

	/**
	 * Test that set_removed_cart_contents works correctly.
	 */
	public function test_set_removed_cart_contents() {
		$test_data = array(
			'test_key' => array(
				'product_id'   => 123,
				'quantity'     => 2,
				'variation_id' => 0,
			),
		);

		$this->cart->set_removed_cart_contents( $test_data );
		$removed = $this->cart->get_removed_cart_contents();

		$this->assertEquals( $test_data, $removed );
	}

	/**
	 * Test that remove_cart_item stores item in removed_cart_contents.
	 */
	public function test_remove_cart_item_stores_in_removed_contents() {
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->assertNotEmpty( $cart_item_key );

		$cart_item = $this->cart->get_cart_item( $cart_item_key );
		$this->assertNotEmpty( $cart_item );

		$result = $this->cart->remove_cart_item( $cart_item_key );
		$this->assertTrue( $result );

		$removed = $this->cart->get_removed_cart_contents();
		$this->assertArrayHasKey( $cart_item_key, $removed );
		$this->assertEquals( $cart_item['product_id'], $removed[ $cart_item_key ]['product_id'] );
		$this->assertEquals( $cart_item['quantity'], $removed[ $cart_item_key ]['quantity'] );
		$this->assertArrayNotHasKey( 'data', $removed[ $cart_item_key ] );
	}

	/**
	 * Test that remove_cart_item fires the correct hooks.
	 */
	public function test_remove_cart_item_fires_hooks() {
		$hooks_fired = array();

		add_action(
			'woocommerce_remove_cart_item',
			function ( $cart_item_key, $cart ) use ( &$hooks_fired ) {
				$hooks_fired['woocommerce_remove_cart_item'] = array( $cart_item_key, $cart );
			},
			10,
			2
		);

		add_action(
			'woocommerce_cart_item_removed',
			function ( $cart_item_key, $cart ) use ( &$hooks_fired ) {
				$hooks_fired['woocommerce_cart_item_removed'] = array( $cart_item_key, $cart );
			},
			10,
			2
		);

		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );

		$this->cart->remove_cart_item( $cart_item_key );

		$this->assertArrayHasKey( 'woocommerce_remove_cart_item', $hooks_fired );
		$this->assertArrayHasKey( 'woocommerce_cart_item_removed', $hooks_fired );
		$this->assertEquals( $cart_item_key, $hooks_fired['woocommerce_remove_cart_item'][0] );
		$this->assertEquals( $cart_item_key, $hooks_fired['woocommerce_cart_item_removed'][0] );
	}

	/**
	 * Test restore_cart_item with removed_cart_contents (backward compatibility).
	 */
	public function test_restore_cart_item_with_removed_contents() {
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$this->assertEmpty( $this->cart->get_cart() );

		$result = $this->cart->restore_cart_item( $cart_item_key );

		$this->assertTrue( $result );

		$cart_item = $this->cart->get_cart_item( $cart_item_key );
		$this->assertNotEmpty( $cart_item );
		$this->assertEquals( $this->product->get_id(), $cart_item['product_id'] );
		$this->assertEquals( 2, $cart_item['quantity'] );

		$removed = $this->cart->get_removed_cart_contents();
		$this->assertArrayNotHasKey( $cart_item_key, $removed );
	}

	/**
	 * Test restore_cart_item fires the correct hooks.
	 */
	public function test_restore_cart_item_fires_hooks() {
		$hooks_fired = array();

		add_action(
			'woocommerce_restore_cart_item',
			function ( $cart_item_key, $cart ) use ( &$hooks_fired ) {
				$hooks_fired['woocommerce_restore_cart_item'] = array( $cart_item_key, $cart );
			},
			10,
			2
		);

		add_action(
			'woocommerce_cart_item_restored',
			function ( $cart_item_key, $cart ) use ( &$hooks_fired ) {
				$hooks_fired['woocommerce_cart_item_restored'] = array( $cart_item_key, $cart );
			},
			10,
			2
		);

		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$this->cart->restore_cart_item( $cart_item_key );

		$this->assertArrayHasKey( 'woocommerce_restore_cart_item', $hooks_fired );
		$this->assertArrayHasKey( 'woocommerce_cart_item_restored', $hooks_fired );
		$this->assertEquals( $cart_item_key, $hooks_fired['woocommerce_restore_cart_item'][0] );
		$this->assertEquals( $cart_item_key, $hooks_fired['woocommerce_cart_item_restored'][0] );
	}

	/**
	 * Test restore_cart_item returns false for non-existent items.
	 */
	public function test_restore_cart_item_returns_false_for_non_existent() {
		$result = $this->cart->restore_cart_item( 'non_existent_key' );
		$this->assertFalse( $result );
	}

	/**
	 * Test that empty_cart clears removed_cart_contents.
	 */
	public function test_empty_cart_clears_removed_contents() {
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$removed = $this->cart->get_removed_cart_contents();
		$this->assertNotEmpty( $removed );

		$this->cart->empty_cart();

		$removed = $this->cart->get_removed_cart_contents();
		$this->assertEmpty( $removed );
	}

	/**
	 * Test that plugins can access get_removed_cart_contents method.
	 */
	public function test_plugins_can_access_get_removed_cart_contents() {
		$removed_contents = $this->cart->get_removed_cart_contents();

		$this->assertIsArray( $removed_contents );
		$this->assertEmpty( $removed_contents );
	}

	/**
	 * Test that plugins can still modify removed_cart_contents.
	 */
	public function test_plugins_can_modify_removed_contents() {
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$removed_contents                                   = $this->cart->get_removed_cart_contents();
		$removed_contents[ $cart_item_key ]['custom_field'] = 'plugin_added_value';
		$this->cart->set_removed_cart_contents( $removed_contents );

		$modified_contents = $this->cart->get_removed_cart_contents();
		$this->assertEquals( 'plugin_added_value', $modified_contents[ $cart_item_key ]['custom_field'] );
	}

	/**
	 * Test that plugins can still access removed_cart_contents after restore.
	 */
	public function test_plugins_can_access_removed_contents_after_restore() {
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$removed_contents = $this->cart->get_removed_cart_contents();
		$this->assertArrayHasKey( $cart_item_key, $removed_contents );

		$this->cart->restore_cart_item( $cart_item_key );

		$removed_contents_after = $this->cart->get_removed_cart_contents();
		$this->assertArrayNotHasKey( $cart_item_key, $removed_contents_after );
	}

	/**
	 * Data provider for clean_up_removed_cart_contents returns early tests.
	 *
	 * @return array
	 */
	public function data_provider_clean_up_returns_early() {
		return array(
			'404 page'               => array(
				'is_404'       => true,
				'is_singular'  => false,
				'is_archive'   => false,
				'is_search'    => false,
				'undo_item'    => null,
				'removed_item' => null,
			),
			'non-page request'       => array(
				'is_404'       => false,
				'is_singular'  => false,
				'is_archive'   => false,
				'is_search'    => false,
				'undo_item'    => null,
				'removed_item' => null,
			),
			'undo_item parameter'    => array(
				'is_404'       => false,
				'is_singular'  => true,
				'is_archive'   => false,
				'is_search'    => false,
				'undo_item'    => 'test_key',
				'removed_item' => null,
			),
			'removed_item parameter' => array(
				'is_404'       => false,
				'is_singular'  => true,
				'is_archive'   => false,
				'is_search'    => false,
				'undo_item'    => null,
				'removed_item' => '1',
			),
		);
	}

	/**
	 * Test clean_up_removed_cart_contents returns early for various conditions.
	 *
	 * @dataProvider data_provider_clean_up_returns_early
	 * @param bool        $is_404       Whether this is a 404 page.
	 * @param bool        $is_singular  Whether this is a singular page.
	 * @param bool        $is_archive   Whether this is an archive page.
	 * @param bool        $is_search    Whether this is a search page.
	 * @param string|null $undo_item    The undo_item GET parameter value.
	 * @param string|null $removed_item The removed_item GET parameter value.
	 */
	public function test_clean_up_removed_cart_contents_returns_early( $is_404, $is_singular, $is_archive, $is_search, $undo_item, $removed_item ) {
		// Set up removed cart contents.
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$removed_contents = $this->cart->get_removed_cart_contents();
		$this->assertNotEmpty( $removed_contents );

		if ( $undo_item ) {
			$_GET['undo_item'] = $undo_item;
		}

		if ( $removed_item ) {
			$_GET['removed_item'] = $removed_item;
		}

		global $wp_query;
		$original_wp_query     = $wp_query;
		$wp_query->is_404      = $is_404;
		$wp_query->is_singular = $is_singular;
		$wp_query->is_archive  = $is_archive;
		$wp_query->is_search   = $is_search;

		$cart_session = new WC_Cart_Session( $this->cart );
		$cart_session->clean_up_removed_cart_contents();

		$removed_contents_after = $this->cart->get_removed_cart_contents();
		$this->assertNotEmpty( $removed_contents_after );
		$this->assertEquals( $removed_contents, $removed_contents_after );

		if ( $undo_item ) {
			unset( $_GET['undo_item'] );
		}

		if ( $removed_item ) {
			unset( $_GET['removed_item'] );
		}

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query = $original_wp_query;
	}

	/**
	 * Data provider for clean_up_removed_cart_contents clears contents tests.
	 *
	 * @return array
	 */
	public function data_provider_clean_up_clears_contents() {
		return array(
			'singular page' => array(
				'is_404'      => false,
				'is_singular' => true,
				'is_archive'  => false,
				'is_search'   => false,
			),
			'archive page'  => array(
				'is_404'      => false,
				'is_singular' => false,
				'is_archive'  => true,
				'is_search'   => false,
			),
			'search page'   => array(
				'is_404'      => false,
				'is_singular' => false,
				'is_archive'  => false,
				'is_search'   => true,
			),
		);
	}

	/**
	 * Test clean_up_removed_cart_contents clears removed_cart_contents on various page types.
	 *
	 * @dataProvider data_provider_clean_up_clears_contents
	 * @param bool $is_404      Whether this is a 404 page.
	 * @param bool $is_singular Whether this is a singular page.
	 * @param bool $is_archive  Whether this is an archive page.
	 * @param bool $is_search   Whether this is a search page.
	 */
	public function test_clean_up_removed_cart_contents_clears_contents( $is_404, $is_singular, $is_archive, $is_search ) {
		$cart_item_key = $this->cart->add_to_cart( $this->product->get_id(), 2 );
		$this->cart->remove_cart_item( $cart_item_key );

		$removed_contents = $this->cart->get_removed_cart_contents();
		$this->assertNotEmpty( $removed_contents );

		global $wp_query;
		$original_wp_query     = $wp_query;
		$wp_query->is_404      = $is_404;
		$wp_query->is_singular = $is_singular;
		$wp_query->is_archive  = $is_archive;
		$wp_query->is_search   = $is_search;

		$cart_session = new WC_Cart_Session( $this->cart );
		$cart_session->clean_up_removed_cart_contents();

		$removed_contents_after = $this->cart->get_removed_cart_contents();
		$this->assertEmpty( $removed_contents_after );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_query = $original_wp_query;
	}
}
