<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Helcim;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Payment_Gateway;
use WC_Unit_Test_Case;

/**
 * Helcim payment gateway provider service test.
 *
 * @class Helcim
 */
class HelcimTest extends WC_Unit_Test_Case {

	/**
	 * @var MockableLegacyProxy|MockObject
	 */
	protected $mockable_proxy;

	/**
	 * The System Under Test.
	 *
	 * @var Helcim
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

		$this->sut = new Helcim( $this->mockable_proxy );
	}

	/**
	 * @testdox Should report account connected when the gateway does not need setup.
	 */
	public function test_is_account_connected_true_when_gateway_does_not_need_setup(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings'    => array(
					'environment'       => 'live',
					'renamed_api_token' => 'bogus_live_token',
				),
				'needs_setup' => false,
			),
		);

		$this->assertTrue(
			$this->sut->is_account_connected( $fake_gateway ),
			'The gateway setup requirement should be authoritative when its token option keys change.'
		);
	}

	/**
	 * @testdox Should report account not connected when the gateway needs setup.
	 */
	public function test_is_account_connected_false_when_gateway_needs_setup(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings'    => array(
					'environment'       => 'sandbox',
					'sandbox_api_token' => 'stale_or_irrelevant_token',
				),
				'needs_setup' => true,
			),
		);

		$this->assertFalse(
			$this->sut->is_account_connected( $fake_gateway ),
			'The gateway setup requirement should take precedence over assumed token option keys.'
		);
	}

	/**
	 * @testdox Should be in test mode when the environment is sandbox.
	 */
	public function test_is_in_test_mode_true_in_sandbox(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings' => array(
					'environment' => 'sandbox',
				),
			),
		);

		$this->assertTrue( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * @testdox Should not be in test mode when the environment is live.
	 */
	public function test_is_in_test_mode_false_in_live(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings' => array(
					'environment' => 'live',
				),
			),
		);

		$this->assertFalse( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * @testdox Should report a legacy gateway connected when it inherits the default setup requirement.
	 */
	public function test_is_account_connected_true_for_legacy_gateway_with_default_setup_requirement(): void {
		$legacy_gateway     = new class() extends WC_Payment_Gateway {};
		$legacy_gateway->id = 'helcimjs';

		$this->assertTrue(
			$this->sut->is_account_connected( $legacy_gateway ),
			'Legacy gateways inheriting the default setup requirement should retain the connected state.'
		);
	}

	/**
	 * @testdox Should fall back to the generic account-connected heuristic when the setup check fails.
	 */
	public function test_is_account_connected_falls_back_when_setup_check_fails(): void {
		$fake_gateway = new class(
			'helcimjs',
			array(
				'settings'          => array(
					'environment' => 'live',
				),
				'account_connected' => false,
			),
		) extends FakePaymentGateway {
			/**
			 * Simulate a gateway setup check failure.
			 *
			 * @throws \RuntimeException Always.
			 */
			public function needs_setup() {
				throw new \RuntimeException( 'Failed to check the gateway setup.' );
			}
		};

		$this->assertFalse(
			$this->sut->is_account_connected( $fake_gateway ),
			'Setup check failures should return the generic account-connected heuristic result.'
		);
	}

	/**
	 * @testdox Should fall back to the generic test-mode heuristic for an unrecognized environment value.
	 */
	public function test_is_in_test_mode_falls_back_for_unrecognized_environment(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings'  => array(
					'environment' => 'staging',
				),
				'test_mode' => true,
			),
		);

		$this->assertTrue( $this->sut->is_in_test_mode( $fake_gateway ) );
	}

	/**
	 * @testdox Should report the same test-mode-onboarding value as test mode.
	 */
	public function test_is_in_test_mode_onboarding_matches_is_in_test_mode(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings' => array(
					'environment' => 'sandbox',
				),
			),
		);

		$this->assertSame(
			$this->sut->is_in_test_mode( $fake_gateway ),
			$this->sut->is_in_test_mode_onboarding( $fake_gateway )
		);
	}
}
