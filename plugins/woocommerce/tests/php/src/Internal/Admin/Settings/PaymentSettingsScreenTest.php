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
	 * @testdox Should redirect a classic section GET request to its screen, passing other query arguments.
	 */
	public function test_redirects_classic_section_to_screen(): void {
		$this->register_example_screen();

		$url = $this->sut->get_classic_redirect_url(
			array(
				'page'    => 'wc-settings',
				'tab'     => 'checkout',
				'section' => 'Example_Gateway',
				'method'  => 'apple_pay',
			),
			'GET'
		);

		$this->assertIsString( $url, 'The classic section should redirect' );
		$this->assertStringContainsString( 'page=wc-payment-settings-wp-admin', $url );
		$this->assertStringContainsString( 'p=' . rawurlencode( '/settings/example?method=apple_pay' ), $url );
	}

	/**
	 * @testdox Should not redirect saves, marked classic requests or other pages.
	 *
	 * @dataProvider provide_requests_that_do_not_redirect
	 *
	 * @param array  $query  The query arguments.
	 * @param string $method The request method.
	 */
	public function test_does_not_redirect( array $query, string $method ): void {
		$this->register_example_screen();

		$this->assertNull( $this->sut->get_classic_redirect_url( $query, $method ) );
	}

	/**
	 * Requests that should keep the classic page.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provide_requests_that_do_not_redirect(): array {
		$classic = array(
			'page'    => 'wc-settings',
			'tab'     => 'checkout',
			'section' => 'example_gateway',
		);
		return array(
			'save'              => array( $classic, 'POST' ),
			'classic marker'    => array( array_merge( $classic, array( 'wc_classic_settings' => '1' ) ), 'GET' ),
			'unknown section'   => array( array_merge( $classic, array( 'section' => 'other_gateway' ) ), 'GET' ),
			'other tab'         => array( array_merge( $classic, array( 'tab' => 'shipping' ) ), 'GET' ),
			'payments list tab' => array( array_diff_key( $classic, array( 'section' => '' ) ), 'GET' ),
		);
	}

	/**
	 * @testdox Should pass the classic settings URL, with the classic marker, to the screen.
	 */
	public function test_page_init_passes_classic_url(): void {
		$this->register_example_screen();
		$_GET['p'] = '/settings/example';

		$this->sut->handle_page_init();

		$data = implode( '', (array) wp_scripts()->get_data( 'wc-payment-settings-screen', 'before' ) );
		$this->assertStringContainsString( 'section=example_gateway', $data );
		$this->assertStringContainsString( 'wc_classic_settings=1', $data );
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
						'title'           => 'Example settings',
						'rest_path'       => '/example/v1/settings',
						'scripts'         => array( 'example-screen-script' ),
						'classic_section' => 'example_gateway',
					),
				)
			)
		);
	}
}
