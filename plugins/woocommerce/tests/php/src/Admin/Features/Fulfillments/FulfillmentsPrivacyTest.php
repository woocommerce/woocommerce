<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
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
	 * Create the fulfillments tables.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		FulfillmentsHelper::create_tables();
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		// Only the flag, not enable_fulfillments(): it would hook the container's FulfillmentsPrivacy
		// too, and the end-to-end tests would pass on those hooks instead of the ones $this->sut adds.
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
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
		$order_items = $order->get_items();
		$order_item  = reset( $order_items );
		$item_id     = $order_item ? $order_item->get_id() : 1;

		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id'    => (string) $order->get_id(),
				'status'       => 'fulfilled',
				'is_fulfilled' => true,
			),
			array_merge(
				array(
					'_items' => array(
						array(
							'item_id' => $item_id,
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
	 * @testdox register() hooks the order exporter and the order anonymization eraser.
	 */
	public function test_register_hooks_order_exporter_and_eraser(): void {
		$this->sut->register();

		$this->assertNotFalse(
			has_filter( 'woocommerce_privacy_export_order_personal_data', array( $this->sut, 'export_order_fulfillment_data' ) ),
			'The order exporter filter should be hooked.'
		);
		$this->assertNotFalse(
			has_action( 'woocommerce_privacy_remove_order_personal_data', array( $this->sut, 'remove_fulfillment_personal_data' ) ),
			'The eraser should hook order anonymization.'
		);
	}

	/**
	 * @testdox The order export does not fake a complete export when the fulfillment read fails.
	 */
	public function test_export_skips_order_when_read_fails(): void {
		$order = new WC_Order();
		$order->save();

		// Swap in a data store whose read throws. Extends the real store so it passes
		// WC_Data_Store validation.
		add_filter(
			'woocommerce_order-fulfillment_data_store',
			function () {
				return new class() extends FulfillmentsDataStore {
					/**
					 * Fail the read the exporter performs.
					 *
					 * @param string $entity_type  The entity type.
					 * @param string $entity_id    The entity ID.
					 * @param bool   $with_deleted Whether to include deleted fulfillments.
					 * @return array
					 * @throws \Exception When deleted records are requested, as the exporter does.
					 */
					public function read_fulfillments( string $entity_type, string $entity_id, bool $with_deleted = false ): array {
						if ( $with_deleted ) {
							throw new \Exception( 'Simulated read failure.' );
						}
						return array();
					}
				};
			}
		);

		$existing = array(
			array(
				'name'  => 'Order number',
				'value' => (string) $order->get_id(),
			),
		);

		$result = $this->sut->export_order_fulfillment_data( $existing, $order );

		// The order's own rows survive and no fulfillment rows are appended from the failed read.
		$this->assertSame( $existing, $result );
	}

	/**
	 * @testdox The order export does not fake a complete export when the fulfillment query errors.
	 */
	public function test_export_skips_order_when_the_query_errors(): void {
		$order = new WC_Order();
		$order->save();

		// get_results() returns an empty array on a failed query, so mimic that rather than a
		// throw: the error only shows up in $wpdb->last_error.
		add_filter(
			'woocommerce_order-fulfillment_data_store',
			function () {
				return new class() extends FulfillmentsDataStore {
					/**
					 * Return nothing, leaving a DB error behind as a failed query does.
					 *
					 * @param string $entity_type  The entity type.
					 * @param string $entity_id    The entity ID.
					 * @param bool   $with_deleted Whether to include deleted fulfillments.
					 * @return array
					 */
					public function read_fulfillments( string $entity_type, string $entity_id, bool $with_deleted = false ): array {
						global $wpdb;
						$wpdb->last_error = 'Table \'wp_wc_order_fulfillment_meta\' doesn\'t exist';
						return array();
					}
				};
			}
		);

		$existing = array(
			array(
				'name'  => 'Order number',
				'value' => (string) $order->get_id(),
			),
		);

		global $wpdb;
		try {
			$result = $this->sut->export_order_fulfillment_data( $existing, $order );
		} finally {
			$wpdb->last_error = '';
		}

		$this->assertSame( $existing, $result, 'No fulfillment rows should be appended from an errored read.' );
	}

	/**
	 * @testdox The order export includes the customer's fulfillment tracking data.
	 */
	public function test_export_includes_fulfillment_tracking_data(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number'   => 'TRACK123456',
				'_tracking_url'      => 'https://example.com/track/TRACK123456',
				'_shipment_provider' => 'fedex',
			)
		);

		$rows = $this->sut->export_order_fulfillment_data( array(), $order );

		$this->assertCount( 1, $rows, 'A fulfillment should add one row, not one row per field.' );
		$this->assertSame( 'Shipment', $rows[0]['name'] );
		$this->assertStringContainsString( 'TRACK123456', $rows[0]['value'], 'The export should include the tracking number.' );
		$this->assertStringContainsString( 'https://example.com/track/TRACK123456', $rows[0]['value'], 'The export should include the tracking URL.' );
	}

	/**
	 * @testdox The order export includes the fulfillment status and the items shipped in it.
	 */
	public function test_export_includes_status_and_items(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		$rows = $this->sut->export_order_fulfillment_data( array(), $order );

		$this->assertCount( 1, $rows );
		$this->assertStringContainsString( 'Status: Fulfilled', $rows[0]['value'], 'The export should include the fulfillment status.' );
		$this->assertMatchesRegularExpression( '/Items: .+ x 1/', $rows[0]['value'], 'The export should list the items shipped, with their quantity.' );
	}

	/**
	 * @testdox The order export includes soft-deleted fulfillments, matching what the eraser clears.
	 */
	public function test_export_includes_soft_deleted_fulfillment(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		$datastore = WC_Data_Store::load( 'order-fulfillment' );
		$datastore->delete( $fulfillment );

		$values = implode( ' ', wp_list_pluck( $this->sut->export_order_fulfillment_data( array(), $order ), 'value' ) );
		$this->assertStringContainsString( 'TRACK123456', $values, 'A soft-deleted fulfillment should still appear in the export.' );
	}

	/**
	 * @testdox The order export leaves the existing rows unchanged for an order without fulfillments.
	 */
	public function test_export_leaves_order_without_fulfillments_unchanged(): void {
		$order    = OrderHelper::create_order();
		$existing = array(
			array(
				'name'  => 'Order Number',
				'value' => (string) $order->get_id(),
			),
		);

		$this->assertSame( $existing, $this->sut->export_order_fulfillment_data( $existing, $order ) );
	}

	/**
	 * @testdox Several shipments on one order each add one numbered row.
	 */
	public function test_export_numbers_multiple_shipments(): void {
		$order       = OrderHelper::create_order();
		$order_items = $order->get_items();
		$order_item  = reset( $order_items );
		$item_id     = $order_item ? $order_item->get_id() : 1;

		foreach ( array( 'FIRST111', 'SECOND22' ) as $tracking ) {
			FulfillmentsHelper::create_fulfillment(
				array(
					'entity_id'    => (string) $order->get_id(),
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
				),
				array(
					'_items'           => array(
						array(
							'item_id' => $item_id,
							'qty'     => 1,
						),
					),
					'_tracking_number' => $tracking,
				)
			);
		}

		$rows = $this->sut->export_order_fulfillment_data( array(), $order );

		// One row per shipment keeps the accumulated export blob small, which core rewrites
		// in full on every export page.
		$this->assertCount( 2, $rows, 'Each shipment should add exactly one row.' );
		$this->assertSame( array( 'Shipment 1', 'Shipment 2' ), wp_list_pluck( $rows, 'name' ) );

		// read_fulfillments() does not order its query, so assert on the set of values.
		$values = implode( ' ', wp_list_pluck( $rows, 'value' ) );
		$this->assertStringContainsString( 'FIRST111', $values );
		$this->assertStringContainsString( 'SECOND22', $values );
	}

	/**
	 * @testdox The order export resolves the shipment provider to its display name, not its slug.
	 */
	public function test_export_uses_provider_display_name(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number'   => 'TRACK123456',
				'_shipment_provider' => 'dhl',
			)
		);

		// get_provider_display_name() applies this label filter; resolve_provider_name() does not,
		// so a hit here proves the export resolves the display name rather than the raw slug.
		add_filter(
			'woocommerce_fulfillment_shipment_provider_label',
			function () {
				return 'DHL Express';
			}
		);

		$values = implode( ' ', wp_list_pluck( $this->sut->export_order_fulfillment_data( array(), $order ), 'value' ) );
		$this->assertStringContainsString( 'DHL Express', $values, 'The export should use the provider display name.' );
	}

	/**
	 * @testdox Running the core order exporter attaches the customer's fulfillment rows and excludes other customers.
	 */
	public function test_core_order_exporter_includes_fulfillment_rows(): void {
		$this->sut->register();

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

		$export = \WC_Privacy_Exporters::order_data_exporter( $order->get_billing_email(), 1 );

		$values = array();
		foreach ( $export['data'] as $item ) {
			$values = array_merge( $values, wp_list_pluck( $item['data'], 'value' ) );
		}
		$values = implode( ' ', $values );
		$this->assertStringContainsString( 'MINE1111', $values, 'The requesting customer tracking number should be exported.' );
		$this->assertStringNotContainsString( 'THEIRS2222', $values, 'Another customer tracking number must not be exported.' );
	}

	/**
	 * @testdox The export adds no rows on a store that never created the fulfillment tables.
	 */
	public function test_export_skips_order_when_tables_were_never_created(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		// The exporter is registered for every store with the feature off, so it has to skip
		// the ones that never created the tables rather than ask for an absent data store.
		$original = get_option( 'woocommerce_fulfillments_db_tables_created' );
		delete_option( 'woocommerce_fulfillments_db_tables_created' );
		try {
			$rows = $this->sut->export_order_fulfillment_data( array(), $order );
		} finally {
			if ( false !== $original ) {
				update_option( 'woocommerce_fulfillments_db_tables_created', $original );
			}
		}

		$this->assertSame( array(), $rows, 'The export should add no fulfillment rows.' );
	}

	/**
	 * @testdox The eraser stops before redacting on a store that never created the fulfillment tables.
	 */
	public function test_eraser_skips_redaction_when_tables_were_never_created(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		$filter_ran = false;
		add_filter(
			'woocommerce_privacy_remove_fulfillment_personal_data_meta',
			function ( $meta_keys ) use ( &$filter_ran ) {
				$filter_ran = true;
				return $meta_keys;
			}
		);

		// The eraser is registered for every store with the feature off, so it has to skip the
		// ones that never created the tables.
		$original = get_option( 'woocommerce_fulfillments_db_tables_created' );
		delete_option( 'woocommerce_fulfillments_db_tables_created' );
		try {
			$this->sut->remove_fulfillment_personal_data( $order );
		} finally {
			if ( false !== $original ) {
				update_option( 'woocommerce_fulfillments_db_tables_created', $original );
			}
		}

		$this->assertFalse( $filter_ran, 'Redaction should stop before building the query.' );
		$this->assertSame(
			'TRACK123456',
			$this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() )->get_tracking_number(),
			'The tracking number should be left untouched.'
		);
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
	 * @testdox The scheduled retention cleanup redacts fulfillment tracking on anonymized orders.
	 */
	public function test_retention_cleanup_redacts_fulfillment(): void {
		$this->sut->register();

		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number' => 'TRACK123456',
				'_tracking_url'    => 'https://example.com/track/TRACK123456',
			)
		);

		// Make the order eligible for the completed-order retention sweep.
		$order->set_status( 'completed' );
		$order->set_date_created( time() - 2 * DAY_IN_SECONDS );
		$order->save();

		$original = get_option( 'woocommerce_anonymize_completed_orders' );
		update_option(
			'woocommerce_anonymize_completed_orders',
			array(
				'number' => 1,
				'unit'   => 'days',
			)
		);

		\WC_Privacy::anonymize_completed_orders();

		if ( false === $original ) {
			delete_option( 'woocommerce_anonymize_completed_orders' );
		} else {
			update_option( 'woocommerce_anonymize_completed_orders', $original );
		}

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertNull( $reloaded->get_tracking_number(), 'The retention cleanup should redact the fulfillment tracking number.' );
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
	 * @testdox The eraser falls back to the default meta keys and logs a warning when the filter returns a non-array.
	 *
	 * @dataProvider provider_non_array_filter_values
	 *
	 * @param mixed $filtered_value The invalid value returned by the filter.
	 */
	public function test_eraser_falls_back_to_default_keys_for_non_array_filter_value( $filtered_value ): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array(
				'_tracking_number' => 'TRACK123456',
				'_tracking_url'    => 'https://example.com/track/TRACK123456',
			)
		);

		$logger = $this->createMock( \WC_Logger_Interface::class );
		$logger->expects( $this->once() )
			->method( 'warning' )
			->with( $this->stringContains( 'order ' . $order->get_id() ), $this->arrayHasKey( 'source' ) );
		add_filter(
			'woocommerce_logging_class',
			function () use ( $logger ) {
				return $logger;
			}
		);
		add_filter(
			'woocommerce_privacy_remove_fulfillment_personal_data_meta',
			function () use ( $filtered_value ) {
				return $filtered_value;
			}
		);

		$this->sut->remove_fulfillment_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertNull( $reloaded->get_tracking_number(), 'The default tracking number key should still be cleared.' );
		$this->assertNull( $reloaded->get_tracking_url(), 'The default tracking URL key should still be cleared.' );
	}

	/**
	 * @return array<string, array<mixed>>
	 */
	public function provider_non_array_filter_values(): array {
		return array(
			'string' => array( 'invalid' ),
			'null'   => array( null ),
			'false'  => array( false ),
		);
	}

	/**
	 * @testdox The eraser clears nothing, without a warning, when the filter explicitly returns an empty array.
	 */
	public function test_eraser_clears_nothing_when_filter_returns_empty_array(): void {
		list( $order, $fulfillment ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		$logger = $this->createMock( \WC_Logger_Interface::class );
		$logger->expects( $this->never() )->method( 'warning' );
		add_filter(
			'woocommerce_logging_class',
			function () use ( $logger ) {
				return $logger;
			}
		);
		add_filter( 'woocommerce_privacy_remove_fulfillment_personal_data_meta', '__return_empty_array' );

		$this->sut->remove_fulfillment_personal_data( $order );

		$reloaded = $this->reload_fulfillment( $order->get_id(), $fulfillment->get_id() );
		$this->assertSame( 'TRACK123456', $reloaded->get_tracking_number(), 'An explicit empty array is an opt-out and should clear nothing.' );
	}
}
