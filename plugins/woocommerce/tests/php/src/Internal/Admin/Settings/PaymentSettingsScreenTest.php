<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreen;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreens;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentSettingsScreen class.
 */
class PaymentSettingsScreenTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PaymentSettingsScreen
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new PaymentSettingsScreen();
		$this->sut->init( new PaymentSettingsScreens() );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			unset( $_GET['p'] );
			wp_deregister_script( 'wc-payment-settings-screen' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should pass the requested screen to the route and load its scripts.
	 *
	 * @testWith ["/settings/example"]
	 *           ["/settings/example?view=support"]
	 *
	 * @param string $route The requested route.
	 */
	public function test_page_init_loads_requested_screen( string $route ): void {
		wp_register_script( 'example-screen-script', 'https://example.org/example.js', array(), '1.0', true );
		$this->register_example_screen();
		$_GET['p'] = $route;

		$this->sut->handle_page_init();

		$this->assertTrue( wp_script_is( 'wc-payment-settings-screen', 'enqueued' ), 'The screen data should be enqueued' );
		$this->assertTrue( wp_script_is( 'example-screen-script', 'enqueued' ), 'The screen\'s own script should be enqueued' );
		$data = implode( '', (array) wp_scripts()->get_data( 'wc-payment-settings-screen', 'before' ) );
		$this->assertStringContainsString( '"baseURL":"\/example\/v1\/settings"', $data );
		$this->assertStringContainsString( '"kind":"woo_settings","name":"example"', $data );
	}

	/**
	 * @testdox Should load nothing for a screen that isn't registered.
	 */
	public function test_page_init_ignores_unknown_screen(): void {
		$this->register_example_screen();
		$_GET['p'] = '/settings/unknown';

		$this->sut->handle_page_init();

		$this->assertFalse( wp_script_is( 'wc-payment-settings-screen', 'registered' ), 'No screen data should be registered' );
	}

	/**
	 * Register an example screen through the filter.
	 */
	private function register_example_screen(): void {
		add_filter(
			'woocommerce_experimental_payment_settings_screens',
			fn( $screens ) => array_merge(
				$screens,
				array(
					'example' => array(
						'title'     => 'Example settings',
						'rest_path' => '/example/v1/settings',
						'scripts'   => array( 'example-screen-script' ),
					),
				)
			)
		);
	}
}
