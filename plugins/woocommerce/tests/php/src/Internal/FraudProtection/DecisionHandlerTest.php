<?php
/**
 * DecisionHandlerTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\ApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\DecisionHandler;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * Tests for the DecisionHandler class.
 */
class DecisionHandlerTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var DecisionHandler
	 */
	private $sut;

	/**
	 * Mock session clearance manager.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->session_manager = $this->createMock( SessionClearanceManager::class );
		$this->sut             = new DecisionHandler();
		$this->sut->init( $this->session_manager );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_fraud_protection_decision' );
		parent::tearDown();
	}

	/**
	 * @testdox Should apply allow decision and update session to allowed.
	 */
	public function test_apply_allow_decision(): void {
		$this->session_manager
			->expects( $this->once() )
			->method( 'allow_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_ALLOW, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}

	/**
	 * @testdox Should apply block decision and update session to blocked.
	 */
	public function test_apply_block_decision(): void {
		$this->session_manager
			->expects( $this->once() )
			->method( 'block_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_BLOCK, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result );
	}

	/**
	 * @testdox Should default to allow for invalid decision and log warning.
	 */
	public function test_invalid_decision_defaults_to_allow(): void {
		$this->session_manager
			->expects( $this->once() )
			->method( 'allow_session' );

		$result = $this->sut->apply_decision( 'invalid_decision', array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'warning', 'Invalid decision "invalid_decision" received' );
	}

	/**
	 * @testdox Should allow filter to override decision from block to allow.
	 */
	public function test_filter_can_override_block_to_allow(): void {
		add_filter(
			'woocommerce_fraud_protection_decision',
			function () {
				return ApiClient::DECISION_ALLOW;
			}
		);

		$this->session_manager
			->expects( $this->once() )
			->method( 'allow_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_BLOCK, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'info', 'Decision overridden by filter `woocommerce_fraud_protection_decision`' );
	}

	/**
	 * @testdox Should allow filter to override decision from allow to block.
	 */
	public function test_filter_can_override_allow_to_block(): void {
		add_filter(
			'woocommerce_fraud_protection_decision',
			function () {
				return ApiClient::DECISION_BLOCK;
			}
		);

		$this->session_manager
			->expects( $this->once() )
			->method( 'block_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_ALLOW, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result );
		$this->assertLogged( 'info', 'Decision overridden by filter `woocommerce_fraud_protection_decision`' );
	}

	/**
	 * @testdox Should reject invalid filter return value and use original decision.
	 */
	public function test_filter_invalid_return_uses_original_decision(): void {
		add_filter(
			'woocommerce_fraud_protection_decision',
			function () {
				return 'totally_invalid';
			}
		);

		$this->session_manager
			->expects( $this->once() )
			->method( 'block_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_BLOCK, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result );
		$this->assertLogged( 'warning', 'Filter `woocommerce_fraud_protection_decision` returned invalid decision "totally_invalid"' );
	}
}
