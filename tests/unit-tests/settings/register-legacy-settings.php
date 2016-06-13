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

	/**
	 * @covers WC_Register_Legacy_Settings::__construct
	 */
	public function test_constructor() {
		$legacy_settings = new WC_Register_Legacy_Settings( $this->page );
		
		$this->assertEquals( has_filter( 'woocommerce_settings_groups', array( $legacy_settings, 'register_legacy_group' ) ), 10 );
		$this->assertEquals( has_filter( 'woocommerce_settings-' . $this->page->get_id(), array( $legacy_settings, 'register_legacy_settings' ) ), 10 );
	}

	/**
	 * @covers WC_Register_Legacy_Settings::register_legacy_group
	 */
	public function test_register_legacy_group() {
		$legacy_settings = new WC_Register_Legacy_Settings( $this->page );

		$existing = array(
			'id'    => 'existing-id',
			'label' => 'Existing Group',
		);
		$initial  = array( $existing );
		$expected = array(
			$existing,
			array(
				'id'    => $this->page->get_id(),
				'label' => $this->page->get_label(),
			),
		);
		$actual = $legacy_settings->register_legacy_group( $initial );

		$this->assertEquals( $expected, $actual );
	}

}