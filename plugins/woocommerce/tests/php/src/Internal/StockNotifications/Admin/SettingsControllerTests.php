<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use WC_Settings_Products;

/**
 * SettingsControllerTests data tests.
 */
class SettingsControllerTests extends \WC_Settings_Unit_Test_Case {

	/**
	 * @testdox get_settings('customer_stock_notifications') should return all the settings for the customer stock notifications section.
	 */
	public function test_get_customer_stock_notifications_settings_returns_all_settings() {
		$sut = new WC_Settings_Products();

		$settings              = $sut->get_settings_for_section( 'customer_stock_notifications' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'product_back_in_stock_options'           => array( 'title', 'sectionend' ),
			'woocommerce_back_in_stock_allow_signups' => 'checkbox',
			'woocommerce_back_in_stock_require_double_opt_in' => 'checkbox',
			'woocommerce_back_in_stock_require_account' => 'checkbox',
			'woocommerce_back_in_stock_create_account_on_signup' => 'checkbox',
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}
}
