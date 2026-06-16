<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\CouponAttribution;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use Automattic\WooCommerce\Internal\POS\POSPreset;
use WC_Coupon;
use WC_Logger_Interface;
use WC_Unit_Test_Case;

/**
 * Tests for CouponAttribution — initiator recording (log-only) on POS coupon writes.
 */
class CouponAttributionTest extends WC_Unit_Test_Case {

	/**
	 * Acting staff member (current user) id.
	 *
	 * @var int
	 */
	private int $actor_id;

	/**
	 * Initiator staff member id.
	 *
	 * @var int
	 */
	private int $initiator_id;

	/**
	 * Fake logger capturing info/warning calls.
	 *
	 * @var object
	 */
	private $fake_logger;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->actor_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->actor_id, POSPreset::MANAGER );

		$this->initiator_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->initiator_id, POSPreset::CASHIER );

		wp_set_current_user( $this->actor_id );

		$this->fake_logger = $this->create_fake_logger();
		add_filter( 'woocommerce_logging_class', fn() => $this->fake_logger );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_logging_class' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Build the SUT with a POS-request flag.
	 *
	 * @param bool $is_pos Whether the request is POS-originated.
	 * @return CouponAttribution
	 */
	private function make_sut( bool $is_pos = true ): CouponAttribution {
		$ctx = $this->createMock( POSRequestContext::class );
		$ctx->method( 'is_pos_request' )->willReturn( $is_pos );

		$sut = new CouponAttribution();
		$sut->init( $ctx );
		return $sut;
	}

	/**
	 * Create a coupon carrying the given initiator meta.
	 *
	 * @param int|null $initiator_id Initiator id, or null for none.
	 * @return WC_Coupon
	 */
	private function make_coupon( ?int $initiator_id ): WC_Coupon {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'pos-' . wp_rand( 1000, 9999 ) );
		if ( null !== $initiator_id ) {
			$coupon->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, $initiator_id );
		}
		$coupon->save();
		return $coupon;
	}

	/**
	 * @testdox Logs an info line when a valid initiator is present.
	 */
	public function test_logs_info_for_valid_initiator(): void {
		$this->make_sut()->handle_post_insert( $this->make_coupon( $this->initiator_id ), null, true );

		$this->assertCount( 1, $this->fake_logger->info_calls, 'A valid initiator should produce one info log line' );
	}

	/**
	 * @testdox Logs nothing when there is no initiator meta.
	 */
	public function test_no_log_without_initiator(): void {
		$this->make_sut()->handle_post_insert( $this->make_coupon( null ), null, true );

		$this->assertCount( 0, $this->fake_logger->info_calls );
		$this->assertCount( 0, $this->fake_logger->warning_calls );
	}

	/**
	 * @testdox Logs nothing when the request is not POS-originated.
	 */
	public function test_no_log_when_not_pos_request(): void {
		$this->make_sut( false )->handle_post_insert( $this->make_coupon( $this->initiator_id ), null, true );

		$this->assertCount( 0, $this->fake_logger->info_calls );
	}

	/**
	 * @testdox Logs a warning when the initiator lacks POS access.
	 */
	public function test_warns_for_initiator_without_pos_access(): void {
		$stranger = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->make_sut()->handle_post_insert( $this->make_coupon( $stranger ), null, true );

		$this->assertCount( 1, $this->fake_logger->warning_calls, 'A non-POS initiator should produce one warning' );
		$this->assertCount( 0, $this->fake_logger->info_calls );
	}

	/**
	 * Build a fake logger that records info/warning calls.
	 *
	 * @return object
	 */
	private function create_fake_logger() {
		return new class() implements WC_Logger_Interface {
			/**
			 * Captured info() calls.
			 *
			 * @var array<int, array{0:string,1:array}>
			 */
			public array $info_calls = array();

			/**
			 * Captured warning() calls.
			 *
			 * @var array<int, array{0:string,1:array}>
			 */
			public array $warning_calls = array();

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function info( $message, $context = array() ) {
				$this->info_calls[] = array( $message, $context );
			}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function warning( $message, $context = array() ) {
				$this->warning_calls[] = array( $message, $context );
			}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $level   Level.
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function log( $level, $message, $context = array() ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $handle  Handle.
			 * @param string $message Message.
			 * @param string $level   Level.
			 */
			public function add( $handle, $message, $level = 'notice' ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function debug( $message, $context = array() ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function notice( $message, $context = array() ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function error( $message, $context = array() ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function critical( $message, $context = array() ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function alert( $message, $context = array() ) {}

			/**
			 * {@inheritDoc}
			 *
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function emergency( $message, $context = array() ) {}
		};
	}
}
