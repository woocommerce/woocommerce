<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\Helcim;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks\FakePaymentGateway;
use PHPUnit\Framework\MockObject\MockObject;
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
	 * @testdox Should report account connected when a sandbox API token is set in sandbox mode.
	 */
	public function test_is_account_connected_true_when_sandbox_token_set(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings' => array(
					'environment'       => 'sandbox',
					'sandbox_api_token' => 'bogus_sandbox_token',
				),
			),
		);

		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report account not connected when no sandbox API token is set in sandbox mode.
	 */
	public function test_is_account_connected_false_when_sandbox_token_missing(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings' => array(
					'environment' => 'sandbox',
				),
			),
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should report account connected when a live API token is set in live mode.
	 */
	public function test_is_account_connected_true_when_live_token_set(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings' => array(
					'environment'    => 'live',
					'live_api_token' => 'bogus_live_token',
				),
			),
		);

		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );
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
	 * @testdox Should fall back to the generic account-connected heuristic when the environment option is entirely absent.
	 */
	public function test_is_account_connected_falls_back_when_environment_option_missing(): void {
		// Simulates a pre-v5 Helcim install: same gateway id, but no 'environment'
		// option was ever registered, so it's absent from both settings and form_fields.
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings'          => array(
					'legacy_api_token' => 'real_legacy_live_token',
				),
				'account_connected' => true,
			),
		);

		$this->assertTrue( $this->sut->is_account_connected( $fake_gateway ) );
	}

	/**
	 * @testdox Should fall back to the generic account-connected heuristic for an unrecognized environment value.
	 */
	public function test_is_account_connected_falls_back_for_unrecognized_environment(): void {
		$fake_gateway = new FakePaymentGateway(
			'helcimjs',
			array(
				'settings'          => array(
					'environment' => 'staging',
				),
				'account_connected' => false,
			),
		);

		$this->assertFalse( $this->sut->is_account_connected( $fake_gateway ) );
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
