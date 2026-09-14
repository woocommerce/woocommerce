<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\DataStores;

use Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore;
use Automattic\WooCommerce\Internal\Utilities\Users;
use WC_Unit_Test_Case;

/**
 * Tests for the NotificationPreferencesDataStore class.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\DataStores\NotificationPreferencesDataStore
 */
class NotificationPreferencesDataStoreTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NotificationPreferencesDataStore
	 */
	private $sut;

	/**
	 * A test user ID.
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut     = new NotificationPreferencesDataStore();
		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Clean up user meta and the test user between tests.
	 */
	public function tearDown(): void {
		Users::delete_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
		wp_delete_user( $this->user_id );
		parent::tearDown();
	}

	/**
	 * @testdox Should return null when nothing is stored for the user.
	 */
	public function test_read_returns_null_for_unstored_user(): void {
		$this->assertNull( $this->sut->read( $this->user_id ) );
	}

	/**
	 * @testdox Should return the envelope as stored when it is at the current schema version.
	 */
	public function test_read_returns_stored_envelope(): void {
		$envelope = array(
			'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
			'preferences'    => array(
				'store_order'  => array( 'enabled' => false ),
				'store_review' => array( 'enabled' => true ),
			),
		);

		Users::update_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY, $envelope );

		$result = $this->sut->read( $this->user_id );

		$this->assertIsArray( $result );
		$this->assertSame( NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION, $result['schema_version'] );
		$this->assertArrayHasKey( 'preferences', $result );
		$this->assertArrayHasKey( 'store_order', $result['preferences'] );
		$this->assertFalse( $result['preferences']['store_order']['enabled'] );
		$this->assertArrayHasKey( 'store_review', $result['preferences'] );
		$this->assertTrue( $result['preferences']['store_review']['enabled'] );
	}

	/**
	 * @testdox Should migrate an older envelope on read and persist the upgraded version.
	 */
	public function test_read_migrates_old_envelope_and_persists_upgrade(): void {
		Users::update_site_user_meta(
			$this->user_id,
			NotificationPreferencesDataStore::META_KEY,
			array(
				'schema_version' => 0,
				'preferences'    => array(
					'store_order' => array( 'enabled' => false ),
				),
			)
		);

		$result = $this->sut->read( $this->user_id );

		// Returned envelope is at current version.
		$this->assertSame( NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION, $result['schema_version'] );
		$this->assertArrayHasKey( 'store_order', $result['preferences'] );
		$this->assertFalse( $result['preferences']['store_order']['enabled'] );

		// And the upgrade was persisted back to user meta.
		$stored = Users::get_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
		$this->assertSame( NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION, $stored['schema_version'] );
	}

	/**
	 * @testdox Should fall back to an empty preferences array when migrating an envelope with malformed preferences.
	 */
	public function test_migrate_falls_back_to_empty_preferences_for_malformed_input(): void {
		$migrated = $this->sut->migrate(
			array(
				'schema_version' => 0,
				'preferences'    => 'corrupted',
			),
			0
		);

		$this->assertSame( NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION, $migrated['schema_version'] );
		$this->assertArrayHasKey( 'preferences', $migrated );
		$this->assertSame( array(), $migrated['preferences'] );
	}

	/**
	 * @testdox Should persist the supplied envelope to user meta.
	 */
	public function test_write_persists_envelope_to_user_meta(): void {
		$envelope = array(
			'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
			'preferences'    => array(
				'store_order'  => array( 'enabled' => false ),
				'store_review' => array( 'enabled' => false ),
			),
		);

		$this->sut->write( $this->user_id, $envelope );

		$stored = Users::get_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
		$this->assertSame( $envelope, $stored );
	}

	/**
	 * @testdox Should be a no-op when the supplied envelope already matches what is stored.
	 *
	 * Verified indirectly: a second write of an identical envelope does not throw, and the stored
	 * value remains unchanged. The pre-check inside write() short-circuits before update_user_meta(),
	 * which would otherwise return false for the unchanged value and trigger a spurious exception.
	 */
	public function test_write_is_a_no_op_when_envelope_unchanged(): void {
		$envelope = array(
			'schema_version' => NotificationPreferencesDataStore::CURRENT_SCHEMA_VERSION,
			'preferences'    => array(
				'store_order'  => array( 'enabled' => true ),
				'store_review' => array( 'enabled' => true ),
			),
		);

		$this->sut->write( $this->user_id, $envelope );
		$this->sut->write( $this->user_id, $envelope );

		$stored = Users::get_site_user_meta( $this->user_id, NotificationPreferencesDataStore::META_KEY );
		$this->assertSame( $envelope, $stored );
	}
}
