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
				'preferences'    => array( 'store_order' => false ),
			)
		);

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertArrayHasKey( 'store_order', $preferences );
		$this->assertFalse( $preferences['store_order'] );
		$this->assertArrayHasKey( 'store_review', $preferences );
		$this->assertTrue( $preferences['store_review'] );
	}

	/**
	 * @testdox Should fall back to defaults when the stored envelope has empty preferences.
	 *
	 * This is the path the data-store hits after a malformed-input migration: the stored envelope
	 * has `preferences => array()`. The service should fill in defaults rather than return an
	 * empty preferences map.
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
				array(
					'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
					'preferences'    => array(
						'store_order'  => false,
						'store_review' => true,
					),
				)
			);

		$this->sut->save_preferences( $this->user_id, array( 'store_order' => false ) );
	}

	/**
	 * @testdox Should return the merged preferences map after save.
	 */
	public function test_save_preferences_returns_merged_map(): void {
		$this->data_store->method( 'read' )->willReturn( null );

		$result = $this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order'  => false,
				'store_review' => false,
			)
		);

		$this->assertArrayHasKey( 'store_order', $result );
		$this->assertFalse( $result['store_order'] );
		$this->assertArrayHasKey( 'store_review', $result );
		$this->assertFalse( $result['store_review'] );
	}

	/**
	 * @testdox Should merge a partial save with previously stored preferences.
	 */
	public function test_save_preferences_merges_with_existing_preferences(): void {
		$this->data_store->method( 'read' )->willReturn(
			array(
				'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
				'preferences'    => array(
					'store_order'  => false,
					'store_review' => false,
				),
			)
		);

		$result = $this->sut->save_preferences( $this->user_id, array( 'store_review' => true ) );

		$this->assertFalse( $result['store_order'] );
		$this->assertTrue( $result['store_review'] );
	}

	/**
	 * @testdox Should drop unknown preference keys before writing.
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
				'store_order'          => false,
				'store_abandoned_cart' => true,
			)
		);

		$this->assertArrayNotHasKey( 'store_abandoned_cart', $result );
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

		$this->sut->save_preferences( $this->user_id, array( 'store_order' => false ) );
	}

	/**
	 * @testdox Should include every known notification type in the defaults.
	 */
	public function test_get_defaults_includes_all_notification_types(): void {
		$defaults = $this->sut->get_defaults();

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'store_order', $defaults );
		$this->assertArrayHasKey( 'store_review', $defaults );

		foreach ( $defaults as $value ) {
			$this->assertIsBool( $value );
		}
	}
}
