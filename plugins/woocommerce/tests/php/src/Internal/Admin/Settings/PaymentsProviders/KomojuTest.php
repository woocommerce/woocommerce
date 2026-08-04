<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
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
	}

	/**
	 * Get a fake KOMOJU gateway object for testing.
	 *
	 * @param array $extra_props Optional. Additional gateway properties to apply on top of the defaults.
	 *
	 * @return FakePaymentGateway
	 */
	private function get_fake_gateway( array $extra_props = array() ): FakePaymentGateway {
		return new FakePaymentGateway(
			'komoju',
			array_merge(
				array(
					'enabled'            => true,
					'plugin_slug'        => 'komoju-japanese-payments',
					'plugin_file'        => 'komoju-japanese-payments/index',
					'method_title'       => 'KOMOJU',
					'method_description' => 'Deprecated legacy combined gateway.',
				),
				$extra_props
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
			add_query_arg(
				array( 'from' => Payments::FROM_PAYMENTS_SETTINGS ),
				admin_url( 'admin.php?page=wc-settings&tab=komoju_settings' )
			),
			$settings_url
		);
	}

	/**
	 * @testdox get_settings_url() defers to the gateway's own settings section for per-method
	 *          gateways, since they already have a real settings section unlike the legacy gateway.
	 */
	public function test_get_settings_url_defers_to_the_gateways_own_settings_section_for_a_per_method_gateway(): void {
		$gateway = new FakePaymentGateway(
			'komoju_konbini',
			array(
				'method_title' => 'KOMOJU - Konbini',
				'settings_url' => 'https://example.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=komoju_konbini',
			)
		);

		$settings_url = $this->sut->get_settings_url( $gateway );

		$this->assertSame(
			add_query_arg( array( 'from' => Payments::FROM_PAYMENTS_SETTINGS ), $gateway->get_settings_url() ),
			$settings_url
		);
	}

	/**
	 * @testdox is_in_test_mode() returns true when the current global secret key has the `sk_test_` prefix.
	 */
	public function test_is_in_test_mode_returns_true_with_a_test_secret_key(): void {
		update_option( 'komoju_woocommerce_secret_key', 'sk_test_123' );

		$is_test_mode = $this->sut->is_in_test_mode( $this->get_fake_gateway() );

		$this->assertTrue( $is_test_mode );
	}

	/**
	 * @testdox is_in_test_mode() returns false when the current global secret key has the `sk_live_` prefix.
	 */
	public function test_is_in_test_mode_returns_false_with_a_live_secret_key(): void {
		update_option( 'komoju_woocommerce_secret_key', 'sk_live_456' );

		$is_test_mode = $this->sut->is_in_test_mode( $this->get_fake_gateway() );

		$this->assertFalse( $is_test_mode );
	}

	/**
	 * @testdox is_in_test_mode() returns true when only the legacy per-gateway secret key
	 *          is set with the `sk_test_` prefix.
	 */
	public function test_is_in_test_mode_returns_true_with_the_legacy_secret_key(): void {
		update_option( 'woocommerce_komoju_settings', array( 'secretKey' => 'sk_test_789' ) );

		$is_test_mode = $this->sut->is_in_test_mode( $this->get_fake_gateway() );

		$this->assertTrue( $is_test_mode );
	}

	/**
	 * @testdox is_in_test_mode() falls back to the generic detection when no secret key is saved anywhere,
	 *          instead of erroring out.
	 */
	public function test_is_in_test_mode_falls_back_when_no_secret_key_is_saved(): void {
		// The fake gateway's own is_test_mode() returns true, proving this genuinely
		// delegates to the generic provider fallback rather than defaulting to false.
		$is_test_mode = $this->sut->is_in_test_mode( $this->get_fake_gateway( array( 'test_mode' => true ) ) );

		$this->assertTrue( $is_test_mode );
	}

	/**
	 * @testdox is_in_test_mode_onboarding() returns true with a test secret key, since a `sk_test_`
	 *          credential means the account itself is a sandbox account, not just a live account
	 *          processing test payments.
	 */
	public function test_is_in_test_mode_onboarding_returns_true_with_a_test_secret_key(): void {
		update_option( 'komoju_woocommerce_secret_key', 'sk_test_123' );

		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding( $this->get_fake_gateway() );

		$this->assertTrue( $is_test_mode_onboarding );
	}

	/**
	 * @testdox is_in_test_mode_onboarding() returns false when the secret key has the `sk_live_` prefix.
	 */
	public function test_is_in_test_mode_onboarding_returns_false_with_a_live_secret_key(): void {
		update_option( 'komoju_woocommerce_secret_key', 'sk_live_456' );

		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding( $this->get_fake_gateway() );

		$this->assertFalse( $is_test_mode_onboarding );
	}

	/**
	 * @testdox is_in_test_mode_onboarding() returns true when only the legacy per-gateway secret key
	 *          is set with the `sk_test_` prefix.
	 */
	public function test_is_in_test_mode_onboarding_returns_true_with_the_legacy_secret_key(): void {
		update_option( 'woocommerce_komoju_settings', array( 'secretKey' => 'sk_test_789' ) );

		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding( $this->get_fake_gateway() );

		$this->assertTrue( $is_test_mode_onboarding );
	}

	/**
	 * @testdox is_in_test_mode_onboarding() falls back to the generic detection when no secret key
	 *          is saved anywhere, instead of erroring out.
	 */
	public function test_is_in_test_mode_onboarding_falls_back_when_no_secret_key_is_saved(): void {
		// The fake gateway's own is_in_test_mode_onboarding() returns true, proving this genuinely
		// delegates to the generic provider fallback rather than defaulting to false.
		$is_test_mode_onboarding = $this->sut->is_in_test_mode_onboarding(
			$this->get_fake_gateway( array( 'test_mode_onboarding' => true ) )
		);

		$this->assertTrue( $is_test_mode_onboarding );
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
