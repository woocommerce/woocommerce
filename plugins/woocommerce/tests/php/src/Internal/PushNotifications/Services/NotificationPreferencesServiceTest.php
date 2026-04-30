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
	 *
	 * Locks in the contract for forward-compatible sub-fields. When stored preferences contain
	 * multiple sub-fields per pref (e.g. RSM-1550's `min_amount` alongside `enabled`), a partial
	 * update that only sends one sub-field must not clobber the others. With a shallow merge
	 * (`array_merge`), the entire sub-object is replaced; with a deep merge
	 * (`array_replace_recursive`), only the specified sub-fields are overridden.
	 *
	 * Today's schema only has `enabled` per pref, so the bug is invisible. This test extends
	 * the schema via an anonymous subclass to exercise the multi-sub-field case the future
	 * tickets rely on.
	 */
	public function test_save_preferences_deep_merges_partial_updates(): void {
		$service = new class() extends NotificationPreferencesService {
			/**
			 * Extended schema for the test: a second sub-field alongside `enabled`.
			 *
			 * @return array<string, array<string, mixed>>
			 */
			public function get_defaults(): array {
				return array(
					'store_order' => array(
						'enabled'    => true,
						'min_amount' => 0,
					),
				);
			}

			/**
			 * Permissive sanitize for the test: preserve every sub-key in the default shape,
			 * coercing to the type implied by its default value.
			 *
			 * @param string               $key           Preference key.
			 * @param array                $value         Submitted sub-options.
			 * @param array<string, mixed> $default_shape Default sub-options.
			 * @return array<string, mixed>
			 */
			protected function sanitize_value( string $key, array $value, array $default_shape ): array {
				$sanitized = array();
				foreach ( $default_shape as $sub_key => $sub_default ) {
					if ( ! array_key_exists( $sub_key, $value ) ) {
						$sanitized[ $sub_key ] = $sub_default;
						continue;
					}
					if ( is_bool( $sub_default ) ) {
						$sanitized[ $sub_key ] = (bool) $value[ $sub_key ];
					} elseif ( is_int( $sub_default ) ) {
						$sanitized[ $sub_key ] = (int) $value[ $sub_key ];
					} else {
						$sanitized[ $sub_key ] = $value[ $sub_key ];
					}
				}
				return $sanitized;
			}
		};
		$service->init( $this->data_store );

		// Stored state already has a non-default `min_amount`.
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

		// Verify that a partial update of just `enabled` preserves `min_amount`.
		$this->data_store
			->expects( $this->once() )
			->method( 'write' )
			->with(
				$this->anything(),
				$this->callback(
					function ( $envelope ) {
						$prefs = $envelope['preferences']['store_order'];
						return false === $prefs['enabled'] && 500 === $prefs['min_amount'];
					}
				)
			);

		$service->save_preferences(
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

		foreach ( $defaults as $type => $shape ) {
			$this->assertIsArray( $shape, "Default for {$type} should be an object/array." );
			$this->assertArrayHasKey( 'enabled', $shape, "Default for {$type} should have an `enabled` sub-field." );
			$this->assertIsBool( $shape['enabled'] );
		}
	}
}
