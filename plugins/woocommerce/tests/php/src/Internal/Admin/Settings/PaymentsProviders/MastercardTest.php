<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Mastercard;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;

/**
 * Mastercard Merchant Cloud payment gateway provider service test.
 *
 * @class Mastercard
 */
class MastercardTest extends WC_Unit_Test_Case {

	/**
	 * The gateway ID the extension registers.
	 *
	 * @var string
	 */
	private const GATEWAY_ID = 'mastercard_merchant_cloud';

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * The System Under Test.
	 *
	 * @var Mastercard
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

		$this->sut = new Mastercard( $this->mockable_proxy );
	}

	/**
	 * @testdox Should report account connected when both sandbox credentials are set in sandbox mode.
	 */
	public function test_is_account_connected_true_when_sandbox_credentials_set(): void {
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'          => 'yes',
				'test_merchant_id' => 'bogus_test_merchant',
				'test_password'    => 'bogus_test_password',
			)
		);

		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report account not connected when the sandbox password is missing in sandbox mode.
	 */
	public function test_is_account_connected_false_when_sandbox_password_missing(): void {
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'          => 'yes',
				'test_merchant_id' => 'bogus_test_merchant',
			)
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report account not connected when the sandbox merchant ID is missing in sandbox mode.
	 */
	public function test_is_account_connected_false_when_sandbox_merchant_id_missing(): void {
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'       => 'yes',
				'test_password' => 'bogus_test_password',
			)
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report account connected when both live credentials are set in live mode.
	 */
	public function test_is_account_connected_true_when_live_credentials_set(): void {
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'     => 'no',
				'merchant_id' => 'bogus_merchant',
				'password'    => 'bogus_password',
			)
		);

		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report account not connected when no credentials are set at all.
	 */
	public function test_is_account_connected_false_when_unconfigured(): void {
		$fake_gateway = $this->get_fake_gateway( array( 'sandbox' => 'no' ) );

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should not consider whitespace-only credentials as a connected account.
	 */
	public function test_is_account_connected_false_when_credentials_are_whitespace(): void {
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'     => 'no',
				'merchant_id' => '   ',
				'password'    => '   ',
			)
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should ignore the other environment's credentials when deciding if an account is connected.
	 */
	public function test_is_account_connected_ignores_other_environment_credentials(): void {
		// Live mode, but only the sandbox credentials are filled in.
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'          => 'no',
				'test_merchant_id' => 'bogus_test_merchant',
				'test_password'    => 'bogus_test_password',
			)
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report test mode when the sandbox option is enabled.
	 */
	public function test_is_in_test_mode_true_when_sandbox_enabled(): void {
		$fake_gateway = $this->get_fake_gateway( array( 'sandbox' => 'yes' ) );

		$this->assertTrue( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * @testdox Should not report test mode when the sandbox option is disabled.
	 */
	public function test_is_in_test_mode_false_when_sandbox_disabled(): void {
		$fake_gateway = $this->get_fake_gateway( array( 'sandbox' => 'no' ) );

		$this->assertFalse( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * @testdox Should report test mode onboarding when the sandbox option is enabled.
	 */
	public function test_is_in_test_mode_onboarding_true_when_sandbox_enabled(): void {
		$fake_gateway = $this->get_fake_gateway( array( 'sandbox' => 'yes' ) );

		$this->assertTrue( $this->sut->is_in_test_mode_onboarding( $fake_gateway ) );
	}

	/**
	 * @testdox Should not report test mode onboarding when the sandbox option is disabled.
	 */
	public function test_is_in_test_mode_onboarding_false_when_sandbox_disabled(): void {
		$fake_gateway = $this->get_fake_gateway( array( 'sandbox' => 'no' ) );

		$this->assertFalse( $this->sut->is_in_test_mode_onboarding( $fake_gateway ) );
	}

	/**
	 * @testdox Should treat a non-scalar credential as absent rather than casting it.
	 */
	public function test_is_account_connected_false_when_credential_is_not_scalar(): void {
		// A malformed settings entry must not read as a configured credential.
		$fake_gateway = $this->get_fake_gateway(
			array(
				'sandbox'     => 'no',
				'merchant_id' => array( 'unexpected' => 'array' ),
				'password'    => 'bogus_password',
			)
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should coerce a non-string scalar sandbox value.
	 */
	public function test_sandbox_mode_coerces_non_string_scalars(): void {
		$this->assertTrue( $this->sut->is_in_test_mode( $this->get_fake_gateway( array( 'sandbox' => true ) ) ) );
		$this->assertTrue( $this->sut->is_in_test_mode( $this->get_fake_gateway( array( 'sandbox' => 1 ) ) ) );
		$this->assertFalse( $this->sut->is_in_test_mode( $this->get_fake_gateway( array( 'sandbox' => 0 ) ) ) );
	}

	/**
	 * @testdox Should fall back to the parent behavior when the sandbox option is not usable.
	 */
	public function test_falls_back_to_parent_when_sandbox_option_is_not_scalar(): void {
		$fake_gateway = $this->get_fake_gateway(
			array( 'sandbox' => array( 'unexpected' => 'array' ) ),
			array(
				'test_mode'         => true,
				'account_connected' => false,
			)
		);

		$this->assertTrue( $this->sut->is_in_test_mode( $fake_gateway ) );
		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should fall back to the parent behavior when the sandbox option is absent.
	 */
	public function test_falls_back_to_parent_when_sandbox_option_absent(): void {
		// FakePaymentGateway answers the generic probes, so the parent class decides here.
		$fake_gateway = $this->get_fake_gateway(
			array(),
			array(
				'test_mode'         => true,
				'account_connected' => false,
			)
		);

		$this->assertTrue( $this->sut->is_in_test_mode( $fake_gateway ) );
		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should survive a gateway whose get_option() throws, and fall back to the parent.
	 *
	 * @dataProvider provider_throwing_gateways
	 *
	 * @param \Throwable $to_throw The throwable the gateway's get_option() raises.
	 */
	public function test_survives_a_gateway_that_throws( \Throwable $to_throw ): void {
		$fake_gateway = new class( self::GATEWAY_ID, array( 'account_connected' => false ) ) extends FakePaymentGateway {
			/**
			 * The throwable to raise.
			 *
			 * @var \Throwable
			 */
			public $to_throw;

			/**
			 * Always blow up, the way a hostile or broken extension might.
			 *
			 * @param string $key         The option key.
			 * @param mixed  $empty_value The fallback value.
			 *
			 * @return never
			 */
			public function get_option( $key, $empty_value = null ) {
				throw $this->to_throw;
			}
		};

		$fake_gateway->to_throw = $to_throw;

		// Nothing escapes, and every method degrades to the parent's answer.
		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
		$this->assertFalse( $this->sut->is_in_test_mode( $fake_gateway ) );
		$this->assertFalse( $this->sut->is_in_test_mode_onboarding( $fake_gateway ) );
	}

	/**
	 * Throwables a gateway might raise from get_option().
	 *
	 * @return array<string, array{0: \Throwable}>
	 */
	public function provider_throwing_gateways(): array {
		return array(
			'exception'      => array( new \RuntimeException( 'bogus runtime failure' ) ),
			'error'          => array( new \Error( 'bogus error' ) ),
			'type error'     => array( new \TypeError( 'bogus type error' ) ),
			'argument count' => array( new \ArgumentCountError( 'bogus argument count' ) ),
		);
	}

	/**
	 * @testdox Should treat unusable get_option() return values as absent.
	 *
	 * @dataProvider provider_unusable_option_values
	 *
	 * @param mixed $value The value the gateway's get_option() returns.
	 */
	public function test_survives_unusable_option_values( $value ): void {
		$fake_gateway = new class( self::GATEWAY_ID, array( 'account_connected' => false ) ) extends FakePaymentGateway {
			/**
			 * The value to return.
			 *
			 * @var mixed
			 */
			public $value;

			/**
			 * Return something the provider is not expecting.
			 *
			 * @param string $key         The option key.
			 * @param mixed  $empty_value The fallback value.
			 *
			 * @return mixed
			 */
			public function get_option( $key, $empty_value = null ) {
				return $this->value;
			}
		};

		$fake_gateway->value = $value;

		// No warnings, no fatals, and nothing reads as a configured credential.
		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
		$this->assertFalse( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * Values a gateway might return from get_option() that the provider cannot use.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_unusable_option_values(): array {
		return array(
			'array'        => array( array( 'unexpected' => 'array' ) ),
			'nested array' => array( array( array( 'deep' ) ) ),
			'object'       => array( new \stdClass() ),
			'null'         => array( null ),
			'empty string' => array( '' ),
			'whitespace'   => array( "  \t\n " ),
		);
	}

	/**
	 * Build a fake gateway carrying the given settings.
	 *
	 * @param array $settings The gateway settings to expose through get_option().
	 * @param array $props    Optional. Additional FakePaymentGateway properties.
	 *
	 * @return FakePaymentGateway The fake gateway.
	 */
	private function get_fake_gateway( array $settings, array $props = array() ): FakePaymentGateway {
		return new FakePaymentGateway(
			self::GATEWAY_ID,
			array_merge( array( 'settings' => $settings ), $props )
		);
	}
}
