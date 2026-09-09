<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Admin;

use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController as StockNotificationsSettings;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
use WC_Settings_Advanced;
use WC_Settings_Products;

/**
 * SettingsControllerTests data tests.
 */
class SettingsControllerTests extends \WC_Settings_Unit_Test_Case {

	/**
	 * @testdox get_settings('customer_stock_notifications') should return all the settings for the customer stock notifications section.
	 */
	public function test_get_customer_stock_notifications_settings_returns_all_settings() {
		// Get customer stock notification settings.
		// This is required because this class is loaded only in admin context,
		// and this test doesn't run with an admin user.
		wc_get_container()->get( StockNotificationsSettings::class );

		$sut = new WC_Settings_Products();

		$settings              = $sut->get_settings_for_section( 'customer_stock_notifications' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'product_customer_stock_notifications_options' => array( 'title', 'sectionend' ),
			'woocommerce_customer_stock_notifications_allow_signups' => 'checkbox',
			'woocommerce_customer_stock_notifications_require_double_opt_in' => 'checkbox',
			'woocommerce_customer_stock_notifications_require_account' => 'checkbox',
			'woocommerce_customer_stock_notifications_create_account_on_signup' => 'checkbox',
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold' => 'number',
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}

	/**
	 * @testdox The My Account endpoint setting is added to the Advanced tab, inside the account endpoints group, right after Downloads.
	 */
	public function test_my_account_endpoint_setting_is_added_to_the_advanced_tab() {
		// Instantiated directly rather than through the container: the container caches the
		// instance from the previous test, whose hooks the test case has since torn down.
		new StockNotificationsSettings();

		$sut = new WC_Settings_Advanced();

		$settings = $sut->get_settings_for_section( '' );
		$ids      = wp_list_pluck( $settings, 'id' );

		$setting_index = array_search( MyAccountEndpoint::ENDPOINT_OPTION, $ids, true );

		$this->assertNotFalse( $setting_index, 'The endpoint setting should be registered.' );

		// It must land inside the account endpoints group, before its sectionend.
		$group_end = null;
		foreach ( $settings as $index => $setting ) {
			if ( isset( $setting['type'], $setting['id'] ) && 'sectionend' === $setting['type'] && 'account_endpoint_options' === $setting['id'] ) {
				$group_end = $index;
				break;
			}
		}

		$this->assertNotNull( $group_end, 'The account endpoints group should exist.' );
		$this->assertLessThan( $group_end, $setting_index );
		$this->assertSame( 'woocommerce_myaccount_downloads_endpoint', $ids[ $setting_index - 1 ], 'The endpoint setting should sit right after Downloads.' );
		$this->assertSame( 'text', $settings[ $setting_index ]['type'] );
		$this->assertSame( MyAccountEndpoint::ENDPOINT, $settings[ $setting_index ]['default'] );
	}
}
