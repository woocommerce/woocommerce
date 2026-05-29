<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Data_Exception;
use WC_Unit_Test_Case;
use WP_Http;

/**
 * Tests for the NotificationPreferencesService class.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService
 */
class NotificationPreferencesServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NotificationPreferencesService
	 */
	private $sut;

	/**
	 * Mocked data store.
	 *
	 * @var NotificationPreferencesDataStore|MockObject
	 */
	private $data_store;

	/**
	 * An arbitrary test user ID.
	 *
	 * @var int
	 */
	private int $user_id = 42;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->data_store = $this->createMock( NotificationPreferencesDataStore::class );
		$this->sut        = new NotificationPreferencesService();
		$this->sut->init( $this->data_store );
	}

	/**
	 * @testdox Should return defaults when the data store has no envelope for the user.
	 */
	public function test_get_preferences_returns_defaults_when_data_store_returns_null(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertSame( $this->sut->get_defaults(), $preferences );
	}

	/**
	 * @testdox Should overlay stored preferences on top of defaults.
	 */
	public function test_get_preferences_returns_saved_preferences_overlaid_on_defaults(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(
					'store_order' => array( 'enabled' => false ),
				),
			)
		);

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertArrayHasKey( 'store_order', $preferences );
		$this->assertArrayHasKey( 'enabled', $preferences['store_order'] );
		$this->assertFalse( $preferences['store_order']['enabled'] );

		$this->assertArrayHasKey( 'store_review', $preferences );
		$this->assertArrayHasKey( 'enabled', $preferences['store_review'] );
		$this->assertTrue( $preferences['store_review']['enabled'] );
	}

	/**
	 * @testdox Should fall back to defaults when the stored envelope has empty preferences.
	 */
	public function test_get_preferences_overlays_defaults_when_stored_preferences_is_empty(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(),
			)
		);

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertSame( $this->sut->get_defaults(), $preferences );
	}

	/**
	 * @testdox Should write the merged envelope to the data store on save.
	 */
	public function test_save_preferences_calls_data_store_with_correctly_built_envelope(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$this->data_store
			->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->user_id,
				$this->callback(
					function ( $envelope ) {
						return NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION === $envelope['schema_version']
							&& false === $envelope['preferences']['store_order']['enabled']
							&& true === $envelope['preferences']['store_review']['enabled'];
					}
				)
			);

		$this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'enabled' => false ) )
		);
	}

	/**
	 * @testdox Should return the merged preferences map after save.
	 */
	public function test_save_preferences_returns_merged_map(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order'  => array( 'enabled' => false ),
				'store_review' => array( 'enabled' => false ),
			)
		);

		$this->assertArrayHasKey( 'store_order', $result );
		$this->assertFalse( $result['store_order']['enabled'] );
		$this->assertArrayHasKey( 'store_review', $result );
		$this->assertFalse( $result['store_review']['enabled'] );
	}

	/**
	 * @testdox Should merge a partial save with previously stored preferences.
	 */
	public function test_save_preferences_merges_with_existing_preferences(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(
					'store_order'  => array( 'enabled' => false ),
					'store_review' => array( 'enabled' => false ),
				),
			)
		);

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_review' => array( 'enabled' => true ) )
		);

		$this->assertFalse( $result['store_order']['enabled'] );
		$this->assertTrue( $result['store_review']['enabled'] );
	}

	/**
	 * @testdox Should drop unknown top-level preference keys before writing.
	 */
	public function test_save_preferences_drops_unknown_keys(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$this->data_store
			->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->user_id,
				$this->callback(
					function ( $envelope ) {
						return ! array_key_exists( 'store_abandoned_cart', $envelope['preferences'] );
					}
				)
			);

		$result = $this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order'          => array( 'enabled' => false ),
				'store_abandoned_cart' => array( 'enabled' => true ),
			)
		);

		$this->assertArrayNotHasKey( 'store_abandoned_cart', $result );
	}

	/**
	 * @testdox Should drop unknown sub-fields within a known preference before writing.
	 */
	public function test_save_preferences_drops_unknown_sub_fields(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order' => array(
					'enabled'        => true,
					'future_unknown' => 'should be dropped',
				),
			)
		);

		$this->assertArrayHasKey( 'store_order', $result );
		$this->assertArrayHasKey( 'enabled', $result['store_order'] );
		$this->assertArrayNotHasKey( 'future_unknown', $result['store_order'] );
	}

	/**
	 * @testdox Should propagate WC_Data_Exception thrown by the data store.
	 */
	public function test_save_preferences_propagates_data_store_exception(): void {
		$this->data_store->method( 'read' )->willReturn( null );
		$this->data_store->method( 'write' )->willThrowException(
			new WC_Data_Exception(
				'woocommerce_push_notification_preferences_save_failed',
				'Failed to save push notification preferences.',
				WP_Http::INTERNAL_SERVER_ERROR
			)
		);

		$this->expectException( WC_Data_Exception::class );

		$this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'enabled' => false ) )
		);
	}

	/**
	 * @testdox Should perform a deep merge so partial updates preserve unrelated sub-fields.
	 */
	public function test_save_preferences_deep_merges_partial_updates(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(
					'store_order' => array(
						'enabled'    => true,
						'min_amount' => 500,
					),
				),
			)
		);

		$this->data_store
			->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->anything(),
				$this->callback(
					function ( $envelope ) {
						$prefs = $envelope['preferences']['store_order'];
						return false === $prefs['enabled'] && 500.0 === $prefs['min_amount'];
					}
				)
			);

		$this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'enabled' => false ) )
		);
	}

	/**
	 * @testdox Should return a nested-object default for every known notification type.
	 */
	public function test_get_defaults_includes_all_notification_types(): void {
		$defaults = $this->sut->get_defaults();

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'store_order', $defaults );
		$this->assertArrayHasKey( 'store_review', $defaults );
		$this->assertArrayHasKey( 'store_stock', $defaults );

		foreach ( $defaults as $type => $shape ) {
			$this->assertIsArray( $shape, "Default for {$type} should be an object/array." );
			$this->assertArrayHasKey( 'enabled', $shape, "Default for {$type} should have an `enabled` sub-field." );
			$this->assertIsBool( $shape['enabled'] );
		}
	}

	/**
	 * @testdox Should default min_amount to null in store_order defaults.
	 */
	public function test_get_defaults_includes_min_amount_for_store_order(): void {
		$defaults = $this->sut->get_defaults();

		$this->assertArrayHasKey( 'min_amount', $defaults['store_order'] );
		$this->assertNull( $defaults['store_order']['min_amount'] );
	}

	/**
	 * @testdox Should preserve explicit null min_amount.
	 */
	public function test_sanitize_preserves_null_min_amount(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'min_amount' => null ) )
		);

		$this->assertNull( $result['store_order']['min_amount'] );
	}

	/**
	 * @testdox Should fall back to null when min_amount is non-positive.
	 */
	public function test_sanitize_falls_back_to_null_for_non_positive_min_amount(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'min_amount' => -50 ) )
		);

		$this->assertNull( $result['store_order']['min_amount'] );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'min_amount' => 0 ) )
		);

		$this->assertNull( $result['store_order']['min_amount'] );
	}

	/**
	 * @testdox Should coerce string min_amount to float.
	 */
	public function test_sanitize_coerces_min_amount_to_float(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_order' => array( 'min_amount' => '50' ) )
		);

		$this->assertSame( 50.0, $result['store_order']['min_amount'] );
	}

	/**
	 * @testdox Should default max_rating to null in store_review defaults.
	 */
	public function test_get_defaults_includes_max_rating_for_store_review(): void {
		$defaults = $this->sut->get_defaults();

		$this->assertArrayHasKey( 'max_rating', $defaults['store_review'] );
		$this->assertNull( $defaults['store_review']['max_rating'] );
	}

	/**
	 * @testdox Should preserve explicit null max_rating.
	 */
	public function test_sanitize_preserves_null_max_rating(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_review' => array( 'max_rating' => null ) )
		);

		$this->assertNull( $result['store_review']['max_rating'] );
	}

	/**
	 * @testdox Should fall back to null when max_rating is out of range.
	 *
	 * @testWith [0]
	 *           [-3]
	 *           [6]
	 *           [10]
	 *
	 * @param int $value The invalid value.
	 */
	public function test_sanitize_falls_back_to_null_for_out_of_range_max_rating( int $value ): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_review' => array( 'max_rating' => $value ) )
		);

		$this->assertNull( $result['store_review']['max_rating'] );
	}

	/**
	 * @testdox Should coerce string max_rating to int.
	 */
	public function test_sanitize_coerces_max_rating_to_int(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array( 'store_review' => array( 'max_rating' => '3' ) )
		);

		$this->assertSame( 3, $result['store_review']['max_rating'] );
	}

	/**
	 * @testdox Should perform a deep merge so a partial update of enabled preserves existing max_rating.
	 */
	public function test_save_preferences_deep_merges_max_rating(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(
					'store_review' => array(
						'enabled'    => true,
						'max_rating' => 3,
					),
				),
			)
		);

		$this->data_store
			->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->anything(),
				$this->callback(
					function ( $envelope ) {
						$prefs = $envelope['preferences']['store_review'];
						return false === $prefs['enabled'] && 3 === $prefs['max_rating'];
					}
				)
			);

		$this->sut->save_preferences(
			$this->user_id,
			array( 'store_review' => array( 'enabled' => false ) )
		);
	}

	/**
	 * @testdox Should include stock sub-flag defaults for store_stock.
	 */
	public function test_get_defaults_includes_stock_sub_flags(): void {
		$defaults = $this->sut->get_defaults();

		$this->assertArrayHasKey( 'low_stock', $defaults['store_stock'] );
		$this->assertTrue( $defaults['store_stock']['low_stock'] );
		$this->assertArrayHasKey( 'out_of_stock', $defaults['store_stock'] );
		$this->assertTrue( $defaults['store_stock']['out_of_stock'] );
		$this->assertArrayHasKey( 'on_backorder', $defaults['store_stock'] );
		$this->assertTrue( $defaults['store_stock']['on_backorder'] );
	}

	/**
	 * @testdox Should coerce stock sub-flags to booleans.
	 */
	public function test_sanitize_coerces_stock_sub_flags_to_bool(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array(
				'store_stock' => array(
					'low_stock'    => 1,
					'out_of_stock' => 0,
				),
			)
		);

		$this->assertTrue( $result['store_stock']['low_stock'] );
		$this->assertFalse( $result['store_stock']['out_of_stock'] );
	}

	/**
	 * @testdox Should drop unknown sub-fields within store_stock.
	 */
	public function test_save_preferences_drops_unknown_stock_sub_fields(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array(
				'store_stock' => array(
					'enabled'       => true,
					'low_stock'     => true,
					'unknown_event' => true,
				),
			)
		);

		$this->assertArrayHasKey( 'store_stock', $result );
		$this->assertArrayNotHasKey( 'unknown_event', $result['store_stock'] );
	}

	/**
	 * @testdox Should deep-merge partial store_stock updates preserving unrelated sub-fields.
	 */
	public function test_save_preferences_deep_merges_stock_sub_flags(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(
					'store_stock' => array(
						'enabled'      => true,
						'low_stock'    => true,
						'out_of_stock' => true,
						'on_backorder' => false,
					),
				),
			)
		);

		$this->data_store
			->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->anything(),
				$this->callback(
					function ( $envelope ) {
						$prefs = $envelope['preferences']['store_stock'];
						return false === $prefs['low_stock']
							&& true === $prefs['out_of_stock']
							&& false === $prefs['on_backorder'];
					}
				)
			);

		$this->sut->save_preferences(
			$this->user_id,
			array( 'store_stock' => array( 'low_stock' => false ) )
		);
	}
}
