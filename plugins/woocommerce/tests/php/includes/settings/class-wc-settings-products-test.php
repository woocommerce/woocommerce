<?php
/**
 * Class WC_Settings_Products_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

// phpcs:ignore Squiz.Commenting.FileComment.Missing

use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Products class.
 */
class WC_Settings_Products_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testdox get_sections should get all the existing sections.
	 */
	public function test_get_sections() {
		$sut = new WC_Settings_Products();

		$section_names = array_keys( $sut->get_sections() );

		$expected = array(
			'',
			'inventory',
			'downloadable',
		);

		// TODO: Once the lookup table is created in a migration, remove the check and just include 'advanced' in $expected.
		if ( wc_get_container()->get( LookupDataStore::class )->check_lookup_table_exists() ) {
			array_push( $expected, 'advanced' );
		}

		$this->assertEquals( $expected, $section_names );
	}

	/**
	 * get_settings should trigger the appropriate filter(s) depending on the requested section name.
	 *
	 * @testWith ["", ["woocommerce_products_general_settings", "woocommerce_product_settings"]]
	 *           ["inventory", ["woocommerce_inventory_settings"]]
	 *           ["downloadable", ["woocommerce_downloadable_products_settings"]]
	 *
	 * @param string $section_name The section name to test getting the settings for.
	 * @param string $filter_names The name of the filter that is expected to be triggered.
	 */
	public function test_get_settings_triggers_filter( $section_name, $filter_names ) {
		$actual_settings_via_filter = array();

		foreach ( $filter_names as $filter_name ) {
			add_filter(
				$filter_name,
				function ( $settings ) use ( $filter_name, &$actual_settings_via_filter ) {
					$actual_settings_via_filter[ $filter_name ] = $settings;

					return $settings;
				},
				10,
				1
			);
		}

		$sut = new WC_Settings_Products();

		$actual_settings_returned = $sut->get_settings_for_section( $section_name );

		foreach ( $filter_names as $filter_name ) {
			remove_all_filters( $filter_name );
		}

		foreach ( $filter_names as $filter_name ) {
			$this->assertSame( $actual_settings_returned, $actual_settings_via_filter[ $filter_name ] );
		}
	}

	/**
	 * @testdox get_settings('') should return all the settings for the default section.
	 */
	public function test_get_default_settings_returns_all_settings() {
		$sut = new WC_Settings_Products();

		$settings               = $sut->get_settings_for_section( '' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'catalog_options'                              => array( 'title', 'sectionend' ),
			'woocommerce_shop_page_id'                     => 'single_select_page',
			'woocommerce_cart_redirect_after_add'          => 'checkbox',
			'woocommerce_enable_ajax_add_to_cart'          => 'checkbox',
			'woocommerce_placeholder_image'                => 'text',
			'product_measurement_options'                  => array( 'title', 'sectionend' ),
			'woocommerce_weight_unit'                      => 'select',
			'woocommerce_dimension_unit'                   => 'select',
			'product_rating_options'                       => array( 'title', 'sectionend' ),
			'woocommerce_enable_reviews'                   => 'checkbox',
			'woocommerce_review_rating_verification_label' => 'checkbox',
			'woocommerce_review_rating_verification_required' => 'checkbox',
			'woocommerce_enable_review_rating'             => 'checkbox',
			'woocommerce_review_rating_required'           => 'checkbox',
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testdox get_settings('inventory') should return all the settings for the inventory section.
	 */
	public function test_get_inventory_settings_returns_all_settings() {
		$sut = new WC_Settings_Products();

		$settings               = $sut->get_settings_for_section( 'inventory' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'product_inventory_options'           => array( 'title', 'sectionend' ),
			'woocommerce_manage_stock'            => 'checkbox',
			'woocommerce_hold_stock_minutes'      => 'number',
			'woocommerce_notify_low_stock'        => 'checkbox',
			'woocommerce_notify_no_stock'         => 'checkbox',
			'woocommerce_stock_email_recipient'   => 'text',
			'woocommerce_notify_low_stock_amount' => 'number',
			'woocommerce_notify_no_stock_amount'  => 'number',
			'woocommerce_hide_out_of_stock_items' => 'checkbox',
			'woocommerce_stock_format'            => 'select',
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testdox get_settings('downloadable') should return all the settings for the inventory section.
	 */
	public function test_get_downloadable_settings_returns_all_settings() {
		$sut = new WC_Settings_Products();

		$settings               = $sut->get_settings_for_section( 'downloadable' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'digital_download_options'                         => array( 'title', 'sectionend' ),
			'woocommerce_file_download_method'                 => 'select',
			'woocommerce_downloads_redirect_fallback_allowed'  => 'checkbox',
			'woocommerce_downloads_require_login'              => 'checkbox',
			'woocommerce_downloads_grant_access_after_payment' => 'checkbox',
			'woocommerce_downloads_add_hash_to_filename'       => 'checkbox',
			'woocommerce_downloads_deliver_inline'             => 'checkbox',
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * @testdox 'save' flushes the term count cache.
	 */
	public function test_save_does_recount_terms() {
		$wc_recount_all_terms_called = false;

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_recount_all_terms' => function() use ( &$wc_recount_all_terms_called ) {
					$wc_recount_all_terms_called = true;
				},
			)
		);

		$sut = new WC_Settings_Products();
		$sut->save();

		$this->assertTrue( $wc_recount_all_terms_called );
	}
}
