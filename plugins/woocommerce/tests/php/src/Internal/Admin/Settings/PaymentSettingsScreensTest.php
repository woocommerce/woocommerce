<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentSettingsScreens;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentSettingsScreens class.
 */
class PaymentSettingsScreensTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PaymentSettingsScreens
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new PaymentSettingsScreens();
	}

	/**
	 * @testdox Should return a valid screen with only string script handles.
	 */
	public function test_returns_valid_screen(): void {
		$this->register_screens(
			array(
				'example' => array(
					'title'     => 'Example settings',
					'rest_path' => '/example/v1/settings',
					'scripts'   => array( 'example-script', 42 ),
				),
			)
		);

		$screen = $this->sut->get_screen( 'example' );

		$this->assertIsArray( $screen, 'The example screen should be registered' );
		$this->assertSame( 'Example settings', $screen['title'] );
		$this->assertSame( '/example/v1/settings', $screen['rest_path'] );
		$this->assertSame( array( 'example-script' ), $screen['scripts'], 'Non-string handles should be dropped' );
	}

	/**
	 * @testdox Should skip screens that can't be used.
	 *
	 * @dataProvider provide_invalid_screens
	 *
	 * @param mixed $id     The screen ID.
	 * @param mixed $screen The screen arguments.
	 */
	public function test_skips_invalid_screens( $id, $screen ): void {
		$this->register_screens( array( $id => $screen ) );

		$this->assertSame( array(), $this->sut->get_screens() );
	}

	/**
	 * Invalid screens.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provide_invalid_screens(): array {
		$valid = array(
			'title'     => 'Example settings',
			'rest_path' => '/example/v1/settings',
		);
		return array(
			'ID with uppercase letters'  => array( 'Example', $valid ),
			'numeric ID'                 => array( 0, $valid ),
			'arguments are not an array' => array( 'example', 'example' ),
			'missing title'              => array( 'example', array( 'rest_path' => '/example/v1/settings' ) ),
			'relative REST path'         => array(
				'example',
				array(
					'title'     => 'Example settings',
					'rest_path' => 'example/v1/settings',
				),
			),
		);
	}

	/**
	 * @testdox Should return no screens when the filter returns something other than an array.
	 */
	public function test_returns_no_screens_for_invalid_filter_result(): void {
		add_filter( 'woocommerce_experimental_payment_settings_screens', fn() => 'not an array' );

		$this->assertSame( array(), $this->sut->get_screens() );
	}

	/**
	 * Register screens through the filter.
	 *
	 * @param array $screens The screens to register.
	 */
	private function register_screens( array $screens ): void {
		add_filter( 'woocommerce_experimental_payment_settings_screens', fn() => $screens );
	}
}
