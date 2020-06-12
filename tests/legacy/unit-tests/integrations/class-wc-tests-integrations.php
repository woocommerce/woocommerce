<?php
/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Integrations
 */

/**
 * Class WC_Tests_Integrations
 */
class WC_Tests_Integrations extends WC_Unit_Test_Case {
	/**
	 * Test instance creation
	 */
	public function test_integrations_instance() {
		$integrations = new WC_Integrations();
		$this->assertTrue( property_exists( $integrations, 'integrations' ) );
	}

	/**
	 * Test action triggering
	 */
	public function test_action() {
		new WC_Integrations();
		$this->assertTrue( ( did_action( 'woocommerce_integrations_init' ) > 0 ) );
	}

	/**
	 * Test filter to add integrations
	 */
	public function test_filter() {
		$integrations = new WC_Integrations();
		$this->assertArrayHasKey( 'maxmind_geolocation', $integrations->integrations );
		$this->assertArrayHasKey( 'maxmind_geolocation', $integrations->get_integrations() );

		require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'class-dummy-integration.php';

		add_filter( 'woocommerce_integrations', array( $this, 'add_dummy_integration' ) );
		$integrations = new WC_Integrations();
		$this->assertArrayHasKey( 'dummy-integration', $integrations->integrations );
		$this->assertArrayHasKey( 'dummy-integration', $integrations->get_integrations() );

		remove_filter( 'woocommerce_integrations', array( $this, 'add_dummy_integration' ) );
	}

	/**
	 * Add dummy integration via filter
	 *
	 * @return array
	 */
	public function add_dummy_integration() {
		return array(
			'Dummy_Integration',
		);
	}
}
