<?php

/**
 * Settings API Tests
 * @package WooCommerce\Tests\Settings
 * @since 2.7.0
 */
class WC_Tests_Register_Legacy_Settings extends WC_Unit_Test_Case {

	/**
	 * @var WC_Settings_Page $page
	 */
	protected $page;

	/**
	 * Initialize a WC_Settings_Page for testing
	 */
	public function setUp() {
		parent::setUp();

		$this->page = new WC_Settings_General(); 
	}

	public function test_constructor() {
		
		$legacy_settings = new WC_Register_Legacy_Settings( $this->page );
		
		$this->assertEquals( has_filter( 'woocommerce_settings_groups', array( $legacy_settings, 'register_legacy_group' ) ), 10 );
		$this->assertEquals( has_filter( 'woocommerce_settings-' . $this->page->get_id(), array( $legacy_settings, 'register_legacy_settings' ) ), 10 );
	}

}