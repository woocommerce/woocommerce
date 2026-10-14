<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsPrivacy;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Data_Store;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the FulfillmentsPrivacy class.
 */
class FulfillmentsPrivacyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FulfillmentsPrivacy
	 */
	private $sut;

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Enable the fulfillments feature and register its data store.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Restore the fulfillments feature flag.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new FulfillmentsPrivacy();
	}

	/**
	 * Create an order with a fulfillment carrying the given tracking meta.
	 *
	 * @param array $meta Fulfillment meta.
	 * @return array{0: WC_Order, 1: Fulfillment}
	 */
	private function create_order_with_fulfillment( array $meta ): array {
		$order       = OrderHelper::create_order();
		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => (string) $order->get_id() ),
			array_merge(
				array(
					'_items' => array(
						array(
							'item_id' => 1,
							'qty'     => 1,
						),
					),
				),
				$meta
			)
		);

		return array( $order, $fulfillment );
	}

	/**
	 * Reload a fulfillment by ID, including soft-deleted records.
	 *
	 * @param int $order_id       The order ID.
	 * @param int $fulfillment_id The fulfillment ID.
	 * @return Fulfillment|null
	 */
	private function reload_fulfillment( int $order_id, int $fulfillment_id ): ?Fulfillment {
		$datastore    = WC_Data_Store::load( 'order-fulfillment' );
		$fulfillments = $datastore->read_fulfillments( WC_Order::class, (string) $order_id, true );

		foreach ( $fulfillments as $fulfillment ) {
			if ( $fulfillment->get_id() === $fulfillment_id ) {
				return $fulfillment;
			}
		}

		return null;
	}

	/**
	 * @testdox register() adds the fulfillment exporter and hooks the order anonymization eraser.
	 */
	public function test_register_registers_exporter_and_eraser(): void {
		$this->sut->register();

		$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );

		$this->assertArrayHasKey( 'woocommerce-customer-fulfillments', $exporters, 'The fulfillment exporter should be registered.' );
		$this->assertNotFalse(
			has_action( 'woocommerce_privacy_remove_order_personal_data', array( $this->sut, 'remove_fulfillment_personal_data' ) ),
			'The eraser should hook order anonymization.'
		);
	}

	/**
	 * @testdox The exporter includes the customer's fulfillment tracking data.
	 */
	public function test_exporter_includes_fulfillment_tracking_data(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number'   => 'TRACK123456',
				'_tracking_url'      => 'https://example.com/track/TRACK123456',
				'_shipment_provider' => 'fedex',
			)
		);

		$result = $this->sut->fulfillment_data_exporter( $order->get_billing_email(), 1 );

		$this->assertTrue( $result['done'], 'A single page of orders should mark the export done.' );
		$this->assertNotEmpty( $result['data'], 'The export should include the fulfillment.' );

		$values = wp_list_pluck( $result['data'][0]['data'], 'value' );
		$this->assertContains( 'TRACK123456', $values, 'The export should include the tracking number.' );
		$this->assertContains( 'https://example.com/track/TRACK123456', $values, 'The export should include the tracking URL.' );
	}

	/**
	 * @testdox The exporter returns no data for an email without fulfillments.
	 */
	public function test_exporter_returns_empty_for_unknown_email(): void {
		$result = $this->sut->fulfillment_data_exporter( 'nobody@example.com', 1 );

		$this->assertSame( array(), $result['data'] );
		$this->assertTrue( $result['done'] );
	}

	/**
	 * @testdox The eraser redacts the tracking number and URL of an active fulfillment.
	 */
	public function test_eraser_redacts_active_fulfillment(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number'   => 'TRACK123456',
				'_tracking_url'      => 'https://example.com/track/TRACK123456',
				'_shipment_provider' => 'fedex',
			)
		);

		$this->sut->remove_fulfillment_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertNull( $reloaded->get_tracking_number(), 'The tracking number should be cleared.' );
		$this->assertNull( $reloaded->get_tracking_url(), 'The tracking URL should be cleared.' );
		$this->assertSame( 'fedex', $reloaded->get_shipment_provider(), 'The provider is not personal data and should be retained.' );
	}

	/**
	 * @testdox The eraser redacts a soft-deleted fulfillment, whose meta the data store keeps immutable.
	 */
	public function test_eraser_redacts_soft_deleted_fulfillment(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number' => 'TRACK123456',
				'_tracking_url'    => 'https://example.com/track/TRACK123456',
			)
		);

		$fulfillment_id = $fulfillment->get_id();
		$datastore      = WC_Data_Store::load( 'order-fulfillment' );
		$datastore->delete( $fulfillment );

		$deleted = $this->reload_fulfillment( $order->get_id(), $fulfillment_id );
		$this->assertNotNull( $deleted->get_date_deleted(), 'The fulfillment should be soft-deleted.' );

		$this->sut->remove_fulfillment_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment_id );
		$this->assertNull( $reloaded->get_tracking_number(), 'A soft-deleted fulfillment should still be redacted.' );
		$this->assertNull( $reloaded->get_tracking_url(), 'A soft-deleted fulfillment URL should still be redacted.' );
	}

	/**
	 * @testdox The eraser honors the meta-key filter.
	 */
	public function test_eraser_respects_meta_keys_filter(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number' => 'TRACK123456',
				'_tracking_url'    => 'https://example.com/track/TRACK123456',
			)
		);

		add_filter(
			'woocommerce_privacy_remove_fulfillment_personal_data_meta',
			function () {
				return array( '_tracking_number' );
			}
		);

		$this->sut->remove_fulfillment_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertNull( $reloaded->get_tracking_number(), 'The filtered meta key should be redacted.' );
		$this->assertSame( 'https://example.com/track/TRACK123456', $reloaded->get_tracking_url(), 'An unfiltered meta key should be retained.' );
	}

	/**
	 * @testdox Order anonymization through WC_Privacy_Erasers redacts the fulfillment tracking.
	 */
	public function test_order_anonymization_redacts_fulfillment(): void {
		$this->sut->register();

		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number' => 'TRACK123456',
				'_tracking_url'    => 'https://example.com/track/TRACK123456',
			)
		);

		\WC_Privacy_Erasers::remove_order_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertNull( $reloaded->get_tracking_number(), 'Anonymizing the order should redact the fulfillment tracking number.' );
	}

	/**
	 * @testdox The eraser only redacts the fulfillments of the given order.
	 */
	public function test_eraser_only_redacts_the_given_order(): void {
		list( $order_a, $fulfillment_a ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'AAAA1111' )
		);
		list( $order_b, $fulfillment_b ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'BBBB2222' )
		);

		$this->sut->remove_fulfillment_personal_data( $order_a );

		$reloaded_a = $this->reload_fulfillment( $order_a->get_id(), $fulfillment_a->get_id() );
		$reloaded_b = $this->reload_fulfillment( $order_b->get_id(), $fulfillment_b->get_id() );
		$this->assertNull( $reloaded_a->get_tracking_number(), 'The erased order should be redacted.' );
		$this->assertSame( 'BBBB2222', $reloaded_b->get_tracking_number(), 'Another order should be left untouched.' );
	}

	/**
	 * @testdox The eraser ignores non-scalar meta keys returned by the filter.
	 */
	public function test_eraser_ignores_non_scalar_filter_keys(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		add_filter(
			'woocommerce_privacy_remove_fulfillment_personal_data_meta',
			function () {
				return array( '_tracking_number', array( 'unexpected' ) );
			}
		);

		$this->sut->remove_fulfillment_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertNull( $reloaded->get_tracking_number(), 'The valid key should be redacted and the non-scalar entry ignored.' );
	}

	/**
	 * @testdox The exporter only returns fulfillments belonging to the requested email.
	 */
	public function test_exporter_excludes_other_customers(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'MINE1111' )
		);

		$other_order = OrderHelper::create_order();
		$other_order->set_customer_id( 0 );
		$other_order->set_billing_email( 'someone-else@example.com' );
		$other_order->save();
		FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => (string) $other_order->get_id() ),
			array(
				'_items'           => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
				'_tracking_number' => 'THEIRS2222',
			)
		);

		$result = $this->sut->fulfillment_data_exporter( $order->get_billing_email(), 1 );

		$values = array();
		foreach ( $result['data'] as $item ) {
			$values = array_merge( $values, wp_list_pluck( $item['data'], 'value' ) );
		}
		$this->assertContains( 'MINE1111', $values, 'The requesting customer tracking number should be exported.' );
		$this->assertNotContains( 'THEIRS2222', $values, 'Another customer tracking number must not be exported.' );
	}
}
