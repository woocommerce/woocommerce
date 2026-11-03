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

		// Swap in a data store whose read throws, mimicking a DB failure the real store surfaces
		// when the exporter asks it to. Extends the real store so it passes WC_Data_Store validation.
		add_filter(
			'woocommerce_order-fulfillment_data_store',
			function () {
				return new class() extends FulfillmentsDataStore {
					/**
					 * Throw when the caller opts into error surfacing.
					 *
					 * @param string $entity_type    The entity type.
					 * @param string $entity_id      The entity ID.
					 * @param bool   $with_deleted   Whether to include deleted fulfillments.
					 * @param bool   $throw_on_error Whether to throw when the query fails.
					 * @return array
					 * @throws \Exception When $throw_on_error is true.
					 */
					public function read_fulfillments( string $entity_type, string $entity_id, bool $with_deleted = false, bool $throw_on_error = false ): array {
						if ( $throw_on_error ) {
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

		$values = wp_list_pluck( $this->sut->export_order_fulfillment_data( array(), $order ), 'value' );

		$this->assertContains( 'TRACK123456', $values, 'The export should include the tracking number.' );
		$this->assertContains( 'https://example.com/track/TRACK123456', $values, 'The export should include the tracking URL.' );
	}

	/**
	 * @testdox The order export includes the fulfillment status and the items shipped in it.
	 */
	public function test_export_includes_status_and_items(): void {
		list( $order ) = $this->create_order_with_fulfillment(
			array( '_tracking_number' => 'TRACK123456' )
		);

		$rows = array();
		foreach ( $this->sut->export_order_fulfillment_data( array(), $order ) as $row ) {
			$rows[ $row['name'] ] = $row['value'];
		}

		$this->assertArrayHasKey( 'Fulfillment status', $rows, 'The export should include the fulfillment status.' );
		$this->assertSame( 'Fulfilled', $rows['Fulfillment status'] );
		$this->assertArrayHasKey( 'Items in this shipment', $rows, 'The export should list the items shipped in the fulfillment.' );
		$this->assertStringContainsString( ' x 1', $rows['Items in this shipment'], 'The shipment items should include the quantity.' );
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

		$values = wp_list_pluck( $this->sut->export_order_fulfillment_data( array(), $order ), 'value' );
		$this->assertContains( 'TRACK123456', $values, 'A soft-deleted fulfillment should still appear in the export.' );
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
	 * @testdox Several shipments on one order are numbered so their rows stay distinct.
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

		$names = wp_list_pluck( $this->sut->export_order_fulfillment_data( array(), $order ), 'name' );

		$this->assertContains( 'Shipment 1: Tracking number', $names, 'The first shipment rows should be numbered.' );
		$this->assertContains( 'Shipment 2: Tracking number', $names, 'The second shipment rows should be numbered.' );
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

		$values = wp_list_pluck( $this->sut->export_order_fulfillment_data( array(), $order ), 'value' );
		$this->assertContains( 'DHL Express', $values, 'The export should use the provider display name.' );
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
		$this->assertContains( 'MINE1111', $values, 'The requesting customer tracking number should be exported.' );
		$this->assertNotContains( 'THEIRS2222', $values, 'Another customer tracking number must not be exported.' );
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
}
