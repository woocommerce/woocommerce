<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests;

use Automattic\Jetpack\Constants;

/**
 * Tests that WC_Unit_Test_Case clears the WC() singleton state that neither the per-test
 * database rollback nor the hook restore covers.
 *
 * This drives clear_wc_singleton_state() directly rather than dirtying state in one test and
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

		$shipping_at_setup = WC()->customer->get_shipping( 'edit' );
		WC()->customer->set_shipping_location( 'GB', '', 'LEAK 1', 'Leaked city' );

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
		$this->assertSame( $shipping_at_setup, WC()->customer->get_shipping( 'edit' ), 'The customer shipping address should be back to what it was at setUp().' );
		$this->assertNotSame(
			self::LEAKED_LOCALE_LABEL,
			WC()->countries->get_country_locale()['GB']['postcode']['label'] ?? null,
			'The filtered locale should not still be cached.'
		);
	}

	/**
	 * @testdox A test that swaps in another customer or changes its props leaves the setUp() customer behind, unchanged.
	 */
	public function test_clear_wc_singleton_state_restores_the_customer(): void {
		$customer_at_setup = WC()->customer;
		$data_at_setup     = $customer_at_setup->get_data();
		$changes_at_setup  = $customer_at_setup->get_changes();
		$vat_at_setup      = $customer_at_setup->get_is_vat_exempt();

		$customer_at_setup->set_billing_country( 'MV' );
		$customer_at_setup->set_shipping_country( 'NZ' );
		$customer_at_setup->set_is_vat_exempt( ! $vat_at_setup );
		WC()->customer = new \WC_Customer( 0, true );

		$this->clear_wc_singleton_state();

		$this->assertSame( $customer_at_setup, WC()->customer, 'The setUp() customer should be back on WC().' );
		$this->assertSame( $data_at_setup, WC()->customer->get_data(), 'The customer data should be back to what it was at setUp().' );
		$this->assertSame( $changes_at_setup, WC()->customer->get_changes(), 'Restoring should not add entries to the change set.' );
		$this->assertSame( $vat_at_setup, WC()->customer->get_is_vat_exempt(), 'The tax exemption should be back to what it was at setUp().' );
	}

	/**
	 * @testdox $_SERVER changes, including the REQUEST_URI that go_to() sets, do not outlive the test.
	 */
	public function test_restore_server_superglobal_undoes_server_changes(): void {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		$server_at_setup = $_SERVER;

		$this->go_to( home_url( '/?product=leaked-product' ) );
		$this->assertSame( '/?product=leaked-product', $_SERVER['REQUEST_URI'], 'go_to() should set REQUEST_URI.' );
		$_SERVER['HTTP_USER_AGENT'] = 'Leaked agent';
		unset( $_SERVER['REMOTE_ADDR'] );

		$this->restore_server_superglobal();

		$this->assertSame( $server_at_setup, $_SERVER, '$_SERVER should be back to what it was at setUp().' );
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput
	}

	/**
	 * @testdox Constants overrides a test sets or clears do not outlive the test.
	 */
	public function test_restore_constant_overrides_undoes_overrides(): void {
		$overrides_at_setup = Constants::$set_constants;

		Constants::set_constant( 'WC_TEARDOWN_LEAKED_CONSTANT', 'leaked' );
		Constants::clear_constants();
		Constants::set_constant( 'WC_TEARDOWN_LEAKED_CONSTANT', 'leaked' );

		$this->restore_constant_overrides();

		$this->assertSame( $overrides_at_setup, Constants::$set_constants, 'The overrides should be back to what they were at setUp().' );
	}

	/**
	 * @testdox Block types a test registers, replaces or removes are put back as they were at setUp().
	 */
	public function test_restore_block_type_registry_undoes_registry_changes(): void {
		$registry        = \WP_Block_Type_Registry::get_instance();
		$types_at_setup  = $registry->get_all_registered();
		$replaced_name   = array_key_first( $types_at_setup );
		$removed_name    = array_keys( $types_at_setup )[1];
		$replaced_type   = $types_at_setup[ $replaced_name ];
		$registered_name = 'woocommerce-tests/leaked-block';

		register_block_type( $registered_name );
		$registry->unregister( $replaced_name );
		register_block_type( $replaced_name );
		$registry->unregister( $removed_name );

		$this->restore_block_type_registry();

		$this->assertFalse( $registry->is_registered( $registered_name ), 'A block type the test registered should be gone.' );
		$this->assertSame( $replaced_type, $registry->get_registered( $replaced_name ), 'A block type the test replaced should be the original again.' );
		$this->assertSame( $types_at_setup[ $removed_name ], $registry->get_registered( $removed_name ), 'A block type the test removed should be back.' );
		$this->assertEqualsCanonicalizing( array_keys( $types_at_setup ), array_keys( $registry->get_all_registered() ), 'The registered names should match setUp().' );
	}
}
