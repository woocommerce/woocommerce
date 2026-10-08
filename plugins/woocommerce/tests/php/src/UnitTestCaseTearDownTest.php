<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

/**
 * Tests the state cleanup in WC_Unit_Test_Case: the WC() singleton state that survives the
 * parent teardown, and the attribute taxonomies the rollback leaves registered.
 *
 * This drives the cleanup methods directly rather than dirtying state in one test and
 * asserting it is gone in the next. A pair like that only holds under declaration order: run
 * the suite with --order-by=random or reverse and the assertion half runs first, passes
 * against state nothing has dirtied yet, and stops covering anything without ever going red.
 */
class UnitTestCaseTearDownTest extends \WC_Unit_Test_Case {

	/**
	 * Synthetic locale value, so the leak assertion cannot be satisfied by a WooCommerce default.
	 */
	private const LEAKED_LOCALE_LABEL = 'Leaked postcode label';

	/**
	 * Every piece of singleton state the teardown is responsible for is cleared.
	 */
	public function test_clear_wc_singleton_state_clears_what_survives_the_parent_teardown(): void {
		$locale_filter = function ( $locale ) {
			$locale['GB']['postcode']['label'] = self::LEAKED_LOCALE_LABEL;
			return $locale;
		};

		add_filter(
			'woocommerce_get_country_locale',
			$locale_filter
		);

		// Reading the locale under the filter is what caches the filtered value.
		$locale = WC()->countries->get_country_locale();
		$this->assertSame( self::LEAKED_LOCALE_LABEL, $locale['GB']['postcode']['label'], 'The filter should apply while it is attached.' );

		WC()->cart->cart_context = 'store-api';

		$product = \WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		$this->assertFalse( WC()->cart->is_empty(), 'The cart should hold an item.' );

		wc_add_notice( 'Teardown coverage notice.' );
		$this->assertSame( 1, wc_notice_count(), 'The notice should be queued.' );

		$clear_persistent_cart = null;
		$cart_emptied_callback = function ( $should_clear_persistent_cart ) use ( &$clear_persistent_cart ) {
			$clear_persistent_cart = $should_clear_persistent_cart;
		};
		add_action( 'woocommerce_before_cart_emptied', $cart_emptied_callback );

		// What tearDown() runs before handing off to the parent.
		$this->clear_wc_singleton_state();
		remove_action( 'woocommerce_before_cart_emptied', $cart_emptied_callback );

		// The parent teardown restores the hooks straight afterwards, which is what leaves a
		// filtered locale stranded in the cache. Drop the filter here to reproduce that order.
		remove_filter( 'woocommerce_get_country_locale', $locale_filter );

		$this->assertTrue( WC()->cart->is_empty(), 'The cart should have been emptied.' );
		$this->assertSame( 'shortcode', WC()->cart->cart_context, 'The cart context should be back to shortcode.' );
		$this->assertSame( 0, wc_notice_count(), 'The notice queue should have been cleared.' );
		$this->assertFalse( $clear_persistent_cart, 'Teardown should leave persistent cart cleanup to the database rollback.' );
		$this->assertNotSame(
			self::LEAKED_LOCALE_LABEL,
			WC()->countries->get_country_locale()['GB']['postcode']['label'] ?? null,
			'The filtered locale should not still be cached.'
		);
	}

	/**
	 * @testdox Setup unregisters pa_* taxonomies that have no attribute row and keeps the ones that do.
	 */
	public function test_setup_drops_attribute_taxonomies_without_an_attribute_row(): void {
		global $wc_product_attributes;

		$kept   = \WC_Helper_Product::create_attribute( 'kept_with_a_row', array() )['attribute_taxonomy'];
		$leaked = 'pa_leaked_without_a_row';
		register_taxonomy( $leaked, 'product' );
		$wc_product_attributes[ $leaked ] = (object) array( 'attribute_name' => 'leaked_without_a_row' );

		$method = new \ReflectionMethod( \WC_Unit_Test_Case::class, 'unregister_stale_attribute_taxonomies' );
		$method->setAccessible( true );
		$method->invoke( $this );

		$this->assertFalse( taxonomy_exists( $leaked ), 'A pa_* taxonomy with no attribute row should be unregistered.' );
		$this->assertArrayNotHasKey( $leaked, $wc_product_attributes, 'An attribute with no row should leave $wc_product_attributes.' );
		$this->assertTrue( taxonomy_exists( $kept ), 'A pa_* taxonomy with an attribute row should stay registered.' );
		$this->assertArrayHasKey( $kept, $wc_product_attributes, 'An attribute with a row should stay in $wc_product_attributes.' );
	}
}
