<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

/**
 * Tests that WC_Unit_Test_Case::tearDown() clears the WC() singleton state that neither the
 * per-test database rollback nor the hook restore covers.
 *
 * The two tests below are a pair: the first dirties every piece of state the teardown is
 * responsible for, the second asserts none of it survived. They rely on running in declaration
 * order, which is PHPUnit's default and is not overridden anywhere in this repo. A dependency
 * annotation is deliberately not used: a failing dependency makes the dependent test skip rather
 * than fail, which would hide the very regression this pair exists to catch.
 */
class UnitTestCaseTearDownTest extends \WC_Unit_Test_Case {

	/**
	 * Synthetic locale value, so the leak assertion cannot be satisfied by a WooCommerce default.
	 */
	private const LEAKED_LOCALE_LABEL = 'Leaked postcode label';

	/**
	 * Dirty every piece of state the teardown is responsible for.
	 *
	 * The assertions here guard the fixture itself, so a failure in this test means the setup
	 * stopped dirtying the state rather than the teardown stopping cleaning it.
	 */
	public function test_singleton_state_is_dirtied(): void {
		add_filter(
			'woocommerce_get_country_locale',
			function ( $locale ) {
				$locale['GB']['postcode']['label'] = self::LEAKED_LOCALE_LABEL;
				return $locale;
			}
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
	}

	/**
	 * Assert none of that state reached this test.
	 */
	public function test_singleton_state_does_not_leak_into_the_next_test(): void {
		$this->assertTrue( WC()->cart->is_empty(), 'The cart should have been emptied.' );
		$this->assertSame( 'shortcode', WC()->cart->cart_context, 'The cart context should be back to shortcode.' );
		$this->assertSame( 0, wc_notice_count(), 'The notice queue should have been cleared.' );

		// The hook restore drops the filter, but it does not drop the value the filter produced,
		// so the cached locale is what this asserts on.
		$this->assertFalse( has_filter( 'woocommerce_get_country_locale' ), 'The hook restore should have dropped the filter.' );
		$locale = WC()->countries->get_country_locale();
		$this->assertNotSame(
			self::LEAKED_LOCALE_LABEL,
			$locale['GB']['postcode']['label'] ?? null,
			'The filtered locale should not still be cached.'
		);
	}
}
