<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFailedEventStore;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsFailedEventStore class.
 */
class WooPaymentsFailedEventStoreTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsFailedEventStore
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( WooPaymentsFailedEventStore::class );
		delete_transient( 'wcpay_failed_event_' . md5( 'evt_123' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_transient( 'wcpay_failed_event_' . md5( 'evt_123' ) );
		parent::tearDown();
	}

	/**
	 * @testdox Transient names preserve the WooPayments failed-event shape.
	 */
	public function test_get_transient_name_preserves_woopayments_shape(): void {
		$this->assertSame( 'wcpay_failed_event_' . md5( 'evt_123' ), $this->sut->get_transient_name( 'evt_123' ) );
	}

	/**
	 * @testdox Failed event payloads can be persisted, read, and deleted.
	 */
	public function test_failed_event_payloads_can_be_persisted_read_and_deleted(): void {
		$event = array(
			'id'   => 'evt_123',
			'type' => 'payment_intent.succeeded',
		);

		$this->sut->set_event( 'evt_123', $event );

		$this->assertSame( $event, $this->sut->get_event( 'evt_123' ) );

		$this->sut->delete_event( 'evt_123' );

		$this->assertNull( $this->sut->get_event( 'evt_123' ) );
	}
}
