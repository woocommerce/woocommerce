<?php
/**
 * BlackboxIntegrationTest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\FraudProtection\BlackboxIntegration;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionDispatcher;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\RestApi\UnitTests\LoggerSpyTrait;
use WC_Unit_Test_Case;

/**
 * Tests for the BlackboxIntegration class.
 */
class BlackboxIntegrationTest extends WC_Unit_Test_Case {

	use LoggerSpyTrait;

	/**
	 * The System Under Test.
	 *
	 * @var BlackboxIntegration
	 */
	private $sut;

	/**
	 * Mock session clearance manager.
	 *
	 * @var SessionClearanceManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $session_manager;

	/**
	 * Mock fraud protection dispatcher.
	 *
	 * @var FraudProtectionDispatcher|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $dispatcher;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->session_manager = $this->createMock( SessionClearanceManager::class );
		$this->dispatcher      = $this->createMock( FraudProtectionDispatcher::class );

		$this->sut = new BlackboxIntegration();
		$this->sut->init( $this->session_manager, $this->dispatcher );
	}

	/**
	 * @testdox process_blackbox_session dispatches checkout event with session_id.
	 */
	public function test_process_blackbox_session_dispatches_event(): void {
		$session_id = 'bb_test_session_123';

		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( null );

		$this->session_manager
			->method( 'get_session_id' )
			->willReturn( 'wc_session_456' );

		$this->session_manager
			->expects( $this->once() )
			->method( 'set_blackbox_session_id' )
			->with( $session_id );

		// blackbox_session_id is now included in session data via SessionDataCollector,
		// so dispatch_event is called with an empty array.
		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with( 'checkout' );

		$this->sut->process_blackbox_session( $session_id );

		$this->assertLogged( 'info', 'Processing Blackbox session: bb_test_session_123' );
	}

	/**
	 * @testdox process_blackbox_session skips if same session_id already processed.
	 */
	public function test_process_blackbox_session_skips_duplicate(): void {
		$session_id = 'bb_test_session_123';

		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( $session_id );

		$this->dispatcher
			->expects( $this->never() )
			->method( 'dispatch_event' );

		$this->sut->process_blackbox_session( $session_id );
	}

	/**
	 * @testdox process_blackbox_session dispatches event with empty session_id (fail-open).
	 */
	public function test_process_blackbox_session_dispatches_with_empty_session(): void {
		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( null );

		$this->session_manager
			->method( 'get_session_id' )
			->willReturn( 'wc_session_456' );

		$this->session_manager
			->expects( $this->never() )
			->method( 'set_blackbox_session_id' );

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with( 'checkout' );

		$this->sut->process_blackbox_session( '' );

		$this->assertLogged( 'info', '(empty - fail-open)' );
	}

	/**
	 * @testdox handle_blocks_session_id extracts and processes session_id from data array.
	 */
	public function test_handle_blocks_session_id(): void {
		$session_id = 'bb_blocks_session';

		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( null );

		$this->session_manager
			->method( 'get_session_id' )
			->willReturn( 'wc_session_456' );

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with( 'checkout' );

		$this->sut->handle_blocks_session_id(
			array( 'blackbox_session_id' => $session_id )
		);
	}

	/**
	 * @testdox handle_blocks_session_id handles missing session_id in data.
	 */
	public function test_handle_blocks_session_id_missing_data(): void {
		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( null );

		$this->session_manager
			->method( 'get_session_id' )
			->willReturn( 'wc_session_456' );

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with( 'checkout' );

		$this->sut->handle_blocks_session_id( array() );
	}

	/**
	 * @testdox handle_shortcode_session_id sanitizes and processes session_id.
	 */
	public function test_handle_shortcode_session_id(): void {
		$session_id = 'bb_shortcode_session';

		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( null );

		$this->session_manager
			->method( 'get_session_id' )
			->willReturn( 'wc_session_456' );

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with( 'checkout' );

		$this->sut->handle_shortcode_session_id( $session_id );
	}

	/**
	 * @testdox handle_shortcode_session_id sanitizes potentially malicious input.
	 */
	public function test_handle_shortcode_session_id_sanitizes_input(): void {
		$malicious_input = '<script>alert("xss")</script>bb_session';

		$this->session_manager
			->method( 'get_blackbox_session_id' )
			->willReturn( null );

		$this->session_manager
			->method( 'get_session_id' )
			->willReturn( 'wc_session_456' );

		// Verify that the session_id is sanitized when stored (no HTML tags).
		$this->session_manager
			->expects( $this->once() )
			->method( 'set_blackbox_session_id' )
			->with(
				$this->callback(
					function ( $session_id ) {
						return ! str_contains( $session_id, '<script>' );
					}
				)
			);

		$this->dispatcher
			->expects( $this->once() )
			->method( 'dispatch_event' )
			->with( 'checkout' );

		$this->sut->handle_shortcode_session_id( $malicious_input );
	}
}
