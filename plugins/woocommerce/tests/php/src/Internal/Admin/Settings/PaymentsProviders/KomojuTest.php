<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Komoju;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * KOMOJU payment gateway provider service test.
 *
 * @class Komoju
 */
class KomojuTest extends WC_Unit_Test_Case {

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * @var Komoju
	 */
	protected $sut;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		/**
		 * TestingContainer instance.
		 *
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();

		$this->mockable_proxy = $container->get( LegacyProxy::class );

		$this->sut = new Komoju( $this->mockable_proxy );

		delete_option( 'komoju_woocommerce_secret_key' );
		delete_option( 'woocommerce_komoju_settings' );
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		delete_option( 'komoju_woocommerce_secret_key' );
		delete_option( 'woocommerce_komoju_settings' );

		parent::tearDown();
	}

	/**
	 * Get a fake KOMOJU gateway object for testing.
	 *
	 * @return FakePaymentGateway
	 */
	private function get_fake_gateway(): FakePaymentGateway {
		return new FakePaymentGateway(
			'komoju',
			array(
				'enabled'            => true,
				'plugin_slug'        => 'komoju-japanese-payments',
				'plugin_file'        => 'komoju-japanese-payments/index',
				'method_title'       => 'KOMOJU',
				'method_description' => 'Deprecated legacy combined gateway.',
			),
		);
	}

	/**
	 * Test that the settings URL points to KOMOJU's own dedicated settings tab,
	 * not the generic per-gateway settings section.
	 */
	public function test_get_settings_url_points_to_komoju_settings_tab(): void {
		// Act.
		$settings_url = $this->sut->get_settings_url( $this->get_fake_gateway() );

		// Assert.
		$this->assertSame(
			admin_url( 'admin.php?page=wc-settings&tab=komoju_settings' ),
			$settings_url
		);
	}

	/**
	 * @testdox is_in_test_mode() falls back to the generic detection when the KOMOJU
	 *          extension classes are not loaded, instead of erroring out.
	 */
	public function test_is_in_test_mode_falls_back_when_komoju_class_is_missing(): void {
		// Arrange - the KOMOJU extension is not installed in the test environment,
		// so `WC_Gateway_Komoju` does not exist here. This exercises the defensive
		// class_exists()/is_callable() guard rather than the real KOMOJU logic.
		$this->assertFalse( class_exists( '\WC_Gateway_Komoju' ) );

		// Act.
		$is_test_mode = $this->sut->is_in_test_mode( $this->get_fake_gateway() );

		// Assert - falls back to the generic provider behaviour instead of fataling.
		$this->assertFalse( $is_test_mode );
	}

	/**
	 * @testdox is_account_connected() returns false when no secret key is saved anywhere.
	 */
	public function test_is_account_connected_returns_false_without_a_secret_key(): void {
		// Act.
		$is_connected = $this->sut->is_account_connected( $this->get_fake_gateway() );

		// Assert.
		$this->assertFalse( $is_connected );
	}

	/**
	 * @testdox is_account_connected() returns true when the current global secret key option is set.
	 */
	public function test_is_account_connected_returns_true_with_the_global_secret_key(): void {
		// Arrange.
		update_option( 'komoju_woocommerce_secret_key', 'sk_test_123' );

		// Act.
		$is_connected = $this->sut->is_account_connected( $this->get_fake_gateway() );

		// Assert.
		$this->assertTrue( $is_connected );
	}

	/**
	 * @testdox is_account_connected() falls back to the legacy per-gateway settings array
	 *          when the global secret key option is not set, matching the plugin's own compat logic.
	 */
	public function test_is_account_connected_returns_true_with_the_legacy_secret_key(): void {
		// Arrange.
		update_option( 'woocommerce_komoju_settings', array( 'secretKey' => 'sk_live_456' ) );

		// Act.
		$is_connected = $this->sut->is_account_connected( $this->get_fake_gateway() );

		// Assert.
		$this->assertTrue( $is_connected );
	}
}
