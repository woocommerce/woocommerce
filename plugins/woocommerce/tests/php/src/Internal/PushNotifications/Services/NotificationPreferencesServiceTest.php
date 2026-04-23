<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Services;

use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationPreferencesService;
use WC_Unit_Test_Case;

/**
 * Tests for the NotificationPreferencesService class.
 */
class NotificationPreferencesServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NotificationPreferencesService
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

		$this->sut     = new NotificationPreferencesService();
		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Clean up user meta between tests.
	 */
	public function tearDown(): void {
		delete_user_meta( $this->user_id, NotificationPreferencesService::META_KEY );
		wp_delete_user( $this->user_id );
		parent::tearDown();
	}

	/**
	 * @testdox Should return defaults when the user has no stored preferences.
	 */
	public function test_get_preferences_returns_defaults_for_new_user(): void {
		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertSame( $this->sut->get_defaults(), $preferences );
	}

	/**
	 * @testdox Should return previously saved preferences, overlaid on defaults.
	 */
	public function test_get_preferences_returns_saved_preferences(): void {
		$this->sut->save_preferences( $this->user_id, array( 'store_order' => false ) );

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertArrayHasKey( 'store_order', $preferences );
		$this->assertFalse( $preferences['store_order'] );
		$this->assertArrayHasKey( 'store_review', $preferences );
		$this->assertTrue( $preferences['store_review'] );
	}

	/**
	 * @testdox Should write the versioned envelope to user meta on save.
	 */
	public function test_save_preferences_updates_user_meta(): void {
		$this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order'  => false,
				'store_review' => false,
			)
		);

		$stored = get_user_meta( $this->user_id, NotificationPreferencesService::META_KEY, true );

		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'schema_version', $stored );
		$this->assertSame( NotificationPreferencesService::CURRENT_SCHEMA_VERSION, $stored['schema_version'] );
		$this->assertArrayHasKey( 'preferences', $stored );
		$this->assertFalse( $stored['preferences']['store_order'] );
		$this->assertFalse( $stored['preferences']['store_review'] );
	}

	/**
	 * @testdox Should merge partial saves with previously stored preferences.
	 */
	public function test_save_preferences_merges_with_existing(): void {
		$this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order'  => false,
				'store_review' => false,
			)
		);

		$this->sut->save_preferences( $this->user_id, array( 'store_review' => true ) );

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertFalse( $preferences['store_order'] );
		$this->assertTrue( $preferences['store_review'] );
	}

	/**
	 * @testdox Should upgrade an older envelope to the current schema version on read.
	 */
	public function test_migration_upgrades_schema_version(): void {
		update_user_meta(
			$this->user_id,
			NotificationPreferencesService::META_KEY,
			array(
				'schema_version' => 0,
				'preferences'    => array( 'store_order' => false ),
			)
		);

		$preferences = $this->sut->get_preferences( $this->user_id );

		$stored = get_user_meta( $this->user_id, NotificationPreferencesService::META_KEY, true );
		$this->assertSame( NotificationPreferencesService::CURRENT_SCHEMA_VERSION, $stored['schema_version'] );
		$this->assertFalse( $preferences['store_order'] );
		$this->assertArrayHasKey( 'store_review', $preferences );
		$this->assertTrue( $preferences['store_review'] );
	}

	/**
	 * @testdox Should drop unknown preference keys on save.
	 */
	public function test_save_preferences_drops_unknown_keys(): void {
		$this->sut->save_preferences(
			$this->user_id,
			array(
				'store_order'          => false,
				'store_abandoned_cart' => true,
			)
		);

		$preferences = $this->sut->get_preferences( $this->user_id );
		$stored      = get_user_meta( $this->user_id, NotificationPreferencesService::META_KEY, true );

		$this->assertArrayNotHasKey( 'store_abandoned_cart', $preferences );
		$this->assertArrayNotHasKey( 'store_abandoned_cart', $stored['preferences'] );
		$this->assertFalse( $preferences['store_order'] );
	}

	/**
	 * @testdox Should fall back to defaults when a migrated envelope has a malformed preferences value.
	 */
	public function test_migrate_falls_back_to_defaults_for_malformed_preferences(): void {
		update_user_meta(
			$this->user_id,
			NotificationPreferencesService::META_KEY,
			array(
				'schema_version' => 0,
				'preferences'    => 'corrupted',
			)
		);

		$preferences = $this->sut->get_preferences( $this->user_id );

		$this->assertSame( $this->sut->get_defaults(), $preferences );
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
