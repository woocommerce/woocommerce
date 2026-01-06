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
		$this->assertLogged( 'info', 'Decision overridden by filter' );
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
		$this->assertLogged( 'info', 'Decision overridden by filter' );
	}

	/**
	 * @testdox Should pass session data to filter.
	 */
	public function test_filter_receives_session_data(): void {
		$received_session_data = null;

		add_filter(
			'woocommerce_fraud_protection_decision',
			function ( $decision, $session_data ) use ( &$received_session_data ) {
				$received_session_data = $session_data;
				return $decision;
			},
			10,
			2
		);

		$session_data = array(
			'session_id' => 'test-session',
			'ip_address' => '192.168.1.1',
		);

		$this->session_manager->expects( $this->once() )->method( 'allow_session' );

		$this->sut->apply_decision( ApiClient::DECISION_ALLOW, $session_data );

		$this->assertSame( $session_data, $received_session_data );
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
		$this->assertLogged( 'warning', 'Filter returned invalid decision "totally_invalid"' );
	}

	/**
	 * @testdox Should handle filter returning non-string value.
	 */
	public function test_filter_non_string_return_uses_original_decision(): void {
		add_filter(
			'woocommerce_fraud_protection_decision',
			function () {
				return null;
			}
		);

		$this->session_manager
			->expects( $this->once() )
			->method( 'allow_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_ALLOW, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
		$this->assertLogged( 'warning', 'Filter returned invalid decision' );
	}

	/**
	 * @testdox Should apply correct decision when filter returns same decision.
	 */
	public function test_filter_returning_same_decision_applies_correctly(): void {
		add_filter(
			'woocommerce_fraud_protection_decision',
			function ( $decision ) {
				return $decision;
			}
		);

		$this->session_manager->expects( $this->once() )->method( 'block_session' );

		$result = $this->sut->apply_decision( ApiClient::DECISION_BLOCK, array( 'session_id' => 'test' ) );

		$this->assertSame( ApiClient::DECISION_BLOCK, $result );
		$this->assertNoErrorLogged();
	}

	/**
	 * @testdox Should whitelist logged-in users via filter example.
	 */
	public function test_whitelist_logged_in_users_example(): void {
		add_filter(
			'woocommerce_fraud_protection_decision',
			function ( $decision, $session_data ) {
				if ( ! empty( $session_data['user_id'] ) ) {
					return ApiClient::DECISION_ALLOW;
				}
				return $decision;
			},
			10,
			2
		);

		$this->session_manager
			->expects( $this->once() )
			->method( 'allow_session' );

		$result = $this->sut->apply_decision(
			ApiClient::DECISION_BLOCK,
			array(
				'session_id' => 'test',
				'user_id'    => 123,
			)
		);

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}

	/**
	 * @testdox Should whitelist specific IP addresses via filter example.
	 */
	public function test_whitelist_specific_ip_example(): void {
		$trusted_ips = array( '10.0.0.1', '192.168.1.100' );

		add_filter(
			'woocommerce_fraud_protection_decision',
			function ( $decision, $session_data ) use ( $trusted_ips ) {
				$ip = $session_data['ip_address'] ?? '';
				if ( in_array( $ip, $trusted_ips, true ) ) {
					return ApiClient::DECISION_ALLOW;
				}
				return $decision;
			},
			10,
			2
		);

		$this->session_manager
			->expects( $this->once() )
			->method( 'allow_session' );

		$result = $this->sut->apply_decision(
			ApiClient::DECISION_BLOCK,
			array(
				'session_id' => 'test',
				'ip_address' => '10.0.0.1',
			)
		);

		$this->assertSame( ApiClient::DECISION_ALLOW, $result );
	}
}
