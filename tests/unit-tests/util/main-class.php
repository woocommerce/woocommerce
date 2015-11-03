<?php

namespace WooCommerce\Tests\Util;

/**
 * Class Main_Class.
 * @package WooCommerce\Tests\Util
 */
class Main_Class extends \WC_Unit_Test_Case {

	/** @var \WooCommerce instance */
	protected $wc;

	/**
	 * Setup test.
	 *
	 * @since 2.2
	 */
	public function setUp() {

		parent::setUp();

		$this->wc = WC();
	}

	/**
	 * Test WC has static instance.
	 *
	 * @since 2.2
	 */
	public function test_wc_instance() {

		$this->assertClassHasStaticAttribute( '_instance', 'WooCommerce' );
	}

	public function test_constructor() {

	}

	/**
	 * Test that all WC constants are set.
	 *
	 * @since 2.2
	 */
	public function test_constants() {

		$this->assertEquals( str_replace( 'tests/unit-tests/util/', '', plugin_dir_path( __FILE__ ) ) . 'woocommerce.php', WC_PLUGIN_FILE );

		$this->assertEquals( $this->wc->version, WC_VERSION );
		$this->assertEquals( WC_VERSION, WOOCOMMERCE_VERSION );
		$this->assertEquals( 4, WC_ROUNDING_PRECISION );
		$this->assertContains( WC_TAX_ROUNDING_MODE, array( 2, 1 ) );
		$this->assertEquals( '|', WC_DELIMITER );
		$this->assertNotEquals( WC_LOG_DIR, '' );
	}

	/**
	 * Test class instance.
	 *
	 * @since 2.2
	 */
	public function test_wc_class_instances() {

		$this->wc->init();

		$this->assertInstanceOf( 'WC_Product_Factory', $this->wc->product_factory );
		$this->assertInstanceOf( 'WC_Order_Factory', $this->wc->order_factory );
		$this->assertInstanceOf( 'WC_Countries', $this->wc->countries );
		$this->assertInstanceOf( 'WC_Integrations', $this->wc->integrations );
		$this->assertInstanceOf( 'WC_Mock_Session_Handler', $this->wc->session );
		$this->assertInstanceOf( 'WC_Cart', $this->wc->cart );
		$this->assertInstanceOf( 'WC_Customer', $this->wc->customer );
	}
}

