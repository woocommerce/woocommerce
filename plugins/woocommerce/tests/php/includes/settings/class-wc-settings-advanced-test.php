<?php
/**
 * Class WC_Settings_Advanced_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Advanced class.
 */
class WC_Settings_Advanced_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testdox get_sections should get all the existing sections.
	 */
	public function test_get_sections() {
		$sut = new WC_Settings_Advanced();

		$section_names = array_keys( $sut->get_sections() );

		$expected = array(
			'',
			'keys',
			'webhooks',
			'legacy_api',
			'woocommerce_com',
			'features',
		);

		$this->assertEquals( $expected, $section_names );
	}

	/**
	 * get_settings should trigger the appropriate filter depending on the requested section name.
	 *
	 * @testWith ["", "woocommerce_settings_pages"]
	 *           ["woocommerce_com", "woocommerce_com_integration_settings"]
	 *           ["legacy_api", "woocommerce_settings_rest_api"]
	 *
	 * @param string $section_name The section name to test getting the settings for.
	 * @param string $filter_name The name of the filter that is expected to be triggered.
	 */
	public function test_get_settings_triggers_filter( $section_name, $filter_name ) {
		$actual_settings_via_filter = null;

		add_filter(
			$filter_name,
			function ( $settings ) use ( &$actual_settings_via_filter ) {
				$actual_settings_via_filter = $settings;

				return $settings;
			},
			10,
			1
		);

		$sut = new WC_Settings_Advanced();

		$actual_settings_returned = $sut->get_settings_for_section( $section_name );
		remove_all_filters( $filter_name );

		$this->assertSame( $actual_settings_returned, $actual_settings_via_filter );
	}

	/**
	 * @testdox get_settings('') should return all the settings for the default section.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $site_is_https Return value for wc_site_is_https.
	 */
	public function test_get_default_settings_returns_all_settings( $site_is_https ) {
		$sut = new WC_Settings_Advanced();

		$settings               = $sut->get_settings_for_section( '' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		update_option( 'home', $site_is_https ? 'https://foo.bar' : 'http://foo.bar' );

		$expected = array(
			'advanced_page_options'                        => array( 'title', 'sectionend' ),
			'woocommerce_cart_page_id'                     => 'single_select_page_with_search',
			'woocommerce_checkout_page_id'                 => 'single_select_page_with_search',
			'woocommerce_myaccount_page_id'                => 'single_select_page_with_search',
			'woocommerce_terms_page_id'                    => 'single_select_page_with_search',
			'checkout_process_options'                     => array( 'title', 'sectionend' ),
			'woocommerce_force_ssl_checkout'               => 'checkbox',
			'woocommerce_unforce_ssl_checkout'             => 'checkbox',
			'checkout_endpoint_options'                    => array( 'title', 'sectionend' ),
			'woocommerce_checkout_pay_endpoint'            => 'text',
			'woocommerce_checkout_order_received_endpoint' => 'text',
			'woocommerce_myaccount_add_payment_method_endpoint' => 'text',
			'woocommerce_myaccount_delete_payment_method_endpoint' => 'text',
			'woocommerce_myaccount_set_default_payment_method_endpoint' => 'text',
			'account_endpoint_options'                     => array( 'title', 'sectionend' ),
			'woocommerce_myaccount_orders_endpoint'        => 'text',
			'woocommerce_myaccount_view_order_endpoint'    => 'text',
			'woocommerce_myaccount_downloads_endpoint'     => 'text',
			'woocommerce_myaccount_edit_account_endpoint'  => 'text',
			'woocommerce_myaccount_edit_address_endpoint'  => 'text',
			'woocommerce_myaccount_payment_methods_endpoint' => 'text',
			'woocommerce_myaccount_lost_password_endpoint' => 'text',
			'woocommerce_logout_endpoint'                  => 'text',
			'woocommerce_coming_soon_page_id'              => 'single_select_page_with_search'
		);

		if ( $site_is_https ) {
			unset( $expected['unforce_ssl_checkout'], $expected['force_ssl_checkout'] );
		}

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testdox get_settings('woocommerce_com') should return all the settings for the woocommerce_com section.
	 */
	public function test_get_woocommerce_com_settings_returns_all_settings() {
		$sut = new WC_Settings_Advanced();

		$expected = array(
			'tracking_options'                         => array( 'title', 'sectionend' ),
			'woocommerce_allow_tracking'               => 'checkbox',
			'marketplace_suggestions'                  => array( 'title', 'sectionend' ),
			'woocommerce_show_marketplace_suggestions' => 'checkbox',
		);

		$settings               = $sut->get_settings_for_section( 'woocommerce_com' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testdox get_settings('legacy_api') should return all the settings for the legacy_api section.
	 */
	public function test_get_legacy_api_settings_returns_all_settings() {
		$sut = new WC_Settings_Advanced();

		$expected = array(
			'legacy_api_options'      => array( 'title', 'sectionend' ),
			'woocommerce_api_enabled' => 'checkbox',
		);

		$settings               = $sut->get_settings_for_section( 'legacy_api' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testdox output method should invoke the output method of the appropriate class depending on the section.
	 *
	 * @testWith ["webhooks", "WC_Admin_Webhooks"]
	 *           ["keys", "WC_Admin_API_Keys"]
	 *           ["foobar", "WC_Admin_Settings"]
	 *
	 * @param string $current_section_to_use The section to set as the current one for the test.
	 * @param string $expected_invoked_class The name of the class whose output method is expected to be invoked.
	 */
	public function test_output_invokes_the_appropriate_class( $current_section_to_use, $expected_invoked_class ) {
		global $current_section;

		$actual_invoked_class = null;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Webhooks' => array(
					'page_output' => function() use ( &$actual_invoked_class ) {
						$actual_invoked_class = 'WC_Admin_Webhooks';
					},
				),
				'WC_Admin_API_Keys' => array(
					'page_output' => function() use ( &$actual_invoked_class ) {
						$actual_invoked_class = 'WC_Admin_API_Keys';
					},
				),
				'WC_Admin_Settings' => array(
					'output_fields' => function( $settings ) use ( &$actual_invoked_class ) {
						$actual_invoked_class = 'WC_Admin_Settings';
					},
				),
			)
		);

		$current_section = $current_section_to_use;

		$sut = new WC_Settings_Advanced();

		$sut->output();

		$this->assertEquals( $expected_invoked_class, $actual_invoked_class );
	}

	/**
	 * @testdox The notices method of the appropriate class should be invoked on instantiation.
	 *
	 * @testWith ["webhooks", "WC_Admin_Webhooks"]
	 *           ["keys", "WC_Admin_API_Keys"]
	 *           ["foobar", null]
	 *
	 * @param string $section_in_query_string Section name to be simulated in the query string.
	 * @param string $expected_notices_class_invoked Class whose notices method is expected to be invoked, null for none.
	 */
	public function test_notices_are_invoked_on_class_instantiation( $section_in_query_string, $expected_notices_class_invoked ) {
		$actual_invoked_class = null;

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Webhooks' => array(
					'notices' => function() use ( &$actual_invoked_class ) {
						$actual_invoked_class = 'WC_Admin_Webhooks';
					},
				),
				'WC_Admin_API_Keys' => array(
					'notices' => function() use ( &$actual_invoked_class ) {
						$actual_invoked_class = 'WC_Admin_API_Keys';
					},
				),
			)
		);

		$_GET['section'] = $section_in_query_string;

		new WC_Settings_Advanced();

		$this->assertEquals( $expected_notices_class_invoked, $actual_invoked_class );
	}

	/**
	 * @testdox save method should use the woocommerce_rest_api_valid_to_save filter to check if it's ok to save.
	 *
	 * @testWith ["keys", false]
	 *           ["webhooks", false]
	 *           ["foobar", true]
	 *
	 * @param string $current_section_to_use Section to be set as current for the test.
	 * @param bool   $expected_filter_supplied_value Expected default value to be passed to the filter.
	 */
	public function test_save_uses_filter_to_check_if_valid_to_save( $current_section_to_use, $expected_filter_supplied_value ) {
		global $current_section;

		$actual_filter_supplied_value = null;

		add_filter(
			'woocommerce_rest_api_valid_to_save',
			function ( $value ) use ( &$actual_filter_supplied_value ) {
				$actual_filter_supplied_value = $value;

				return false;
			},
			10,
			1
		);

		$current_section = $current_section_to_use;

		$sut = new WC_Settings_Advanced();
		$sut->save();

		remove_all_filters( 'woocommerce_rest_api_valid_to_save' );

		$this->assertEquals( $expected_filter_supplied_value, $actual_filter_supplied_value );
	}

	/**
	 * @testdox save method should save data only if the woocommerce_rest_api_valid_to_save filter returns true.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $is_valid_to_save Return value of the woocommerce_rest_api_valid_to_save filter.
	 */
	public function test_save_saves_data_only_if_valid_to_save( $is_valid_to_save ) {
		$settings_were_saved = false;

		add_filter(
			'woocommerce_rest_api_valid_to_save',
			function ( $value ) use ( &$is_valid_to_save ) {
				return $is_valid_to_save;
			},
			10,
			1
		);

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Admin_Settings' => array(
					'save_fields' => function( $settings ) use ( &$settings_were_saved ) {
						$settings_were_saved = true;
					},
				),
			)
		);

		$sut = new WC_Settings_Advanced();
		$sut->save();

		remove_all_filters( 'woocommerce_rest_api_valid_to_save' );

		$this->assertEquals( $is_valid_to_save, $settings_were_saved );
	}

	/**
	 * @testdox save method should trigger the appropriate woocommerce_update_options action only if it's ok to save.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $is_valid_to_save Return value of the woocommerce_rest_api_valid_to_save filter.
	 */
	public function test_save_does_updated_options_action_if_valid_to_save( $is_valid_to_save ) {
		global $current_section;

		$current_section = 'foobar';
		remove_all_filters( 'woocommerce_update_options_advanced_foobar' );

		add_filter(
			'woocommerce_rest_api_valid_to_save',
			function ( $value ) use ( &$is_valid_to_save ) {
				return $is_valid_to_save;
			},
			10,
			1
		);

		$sut = new WC_Settings_Advanced();
		$sut->save();

		remove_all_filters( 'woocommerce_rest_api_valid_to_save' );

		$did_action = $is_valid_to_save ? 1 : 0;
		$this->assertEquals( $did_action, did_action( 'woocommerce_update_options_advanced_foobar' ) );
	}

	/**
	 * @testdox save method should prevent the terms and the checkout page ids from being the same.
	 *
	 * @testWith ["foo", "bar", "foo"]
	 *           ["foo", "foo", ""]
	 *
	 * @param string $terms_page_id Terms page id in the request.
	 * @param string $checkout_page_id Checkout page id in the request.
	 * @param string $expected_new_terms_page_id Terms page id in the request (possibly modified) after save.
	 */
	public function test_save_prevents_the_terms_and_checkout_pages_from_being_the_same( $terms_page_id, $checkout_page_id, $expected_new_terms_page_id ) {
		$_POST['woocommerce_terms_page_id']    = $terms_page_id;
		$_POST['woocommerce_checkout_page_id'] = $checkout_page_id;

		$sut = new WC_Settings_Advanced();
		$sut->save();

		// phpcs:ignore WordPress.Security
		$this->assertEquals( $expected_new_terms_page_id, $_POST['woocommerce_terms_page_id'] );
	}

	/**
	 * * @testdox save method should prevent the cart, checkout and my account page ids from being the same.
	 *
	 * @testWith ["cart", "checkout", "myaccount", "checkout", "myaccount"]
	 *           ["cartcheckout", "cartcheckout", "myaccount", "", "myaccount"]
	 *           ["cartmyaccount", "checkout", "cartmyaccount", "checkout", ""]
	 *           ["cart", "checkoutmyaccount", "checkoutmyaccount", "checkoutmyaccount", ""]
	 *
	 * @param string $cart_page_id Cart page id in the request.
	 * @param string $checkout_page_id Checkout page id in the request.
	 * @param string $my_account_page_id My account page id in the request.
	 * @param string $expected_new_checkout_page_id Checkout page id in the request (possibly modified) after save.
	 * @param string $expected_new_my_account_page_id My account page id in the request (possibly modified) after save.
	 */
	public function test_save_prevents_the_cart_checkout_and_my_account_pages_from_being_the_same( $cart_page_id, $checkout_page_id, $my_account_page_id, $expected_new_checkout_page_id, $expected_new_my_account_page_id ) {
		$_POST['woocommerce_cart_page_id']      = $cart_page_id;
		$_POST['woocommerce_checkout_page_id']  = $checkout_page_id;
		$_POST['woocommerce_myaccount_page_id'] = $my_account_page_id;

		$sut = new WC_Settings_Advanced();
		$sut->save();

		// phpcs:disable WordPress.Security
		$this->assertEquals( $expected_new_checkout_page_id, $_POST['woocommerce_checkout_page_id'] );
		$this->assertEquals( $expected_new_my_account_page_id, $_POST['woocommerce_myaccount_page_id'] );
		// phpcs:enable WordPress.Security
	}
}
