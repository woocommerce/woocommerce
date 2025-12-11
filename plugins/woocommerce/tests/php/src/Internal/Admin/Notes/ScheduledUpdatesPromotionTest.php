<?php
/**
 * Tests for ScheduledUpdatesPromotion class.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Notes;

use Automattic\WooCommerce\Internal\Admin\Notes\ScheduledUpdatesPromotion;
use Automattic\WooCommerce\Admin\Notes\Note;
use WC_Unit_Test_Case;

/**
 * Class ScheduledUpdatesPromotionTest
 */
class ScheduledUpdatesPromotionTest extends WC_Unit_Test_Case {

	/**
	 * Enable the analytics-scheduled-import feature.
	 *
	 * @param array $features Existing features.
	 * @return array Modified features.
	 */
	public function enable_feature( $features ) {
		$features[] = 'analytics-scheduled-import';
		return $features;
	}

	/**
	 * Disable the analytics-scheduled-import feature.
	 *
	 * @param array $features Existing features.
	 * @return array Modified features.
	 */
	public function disable_feature( $features ) {
		return array_diff( $features, array( 'analytics-scheduled-import' ) );
	}

	/**
	 * Test is_applicable returns false when feature flag is disabled.
	 */
	public function test_is_applicable_returns_false_when_feature_disabled() {
		// Disable the feature (it's enabled by default in feature-config.php).
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_feature' ) );

		// Delete option to simulate existing installation.
		delete_option( 'woocommerce_analytics_scheduled_import' );

		$this->assertFalse( ScheduledUpdatesPromotion::is_applicable() );

		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_feature' ) );
	}

	/**
	 * Test is_applicable returns true for existing installations.
	 */
	public function test_is_applicable_returns_true_for_existing_installations() {
		// Enable feature flag.
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );

		// Delete option to simulate existing installation (doesn't exist).
		delete_option( 'woocommerce_analytics_scheduled_import' );

		$this->assertTrue( ScheduledUpdatesPromotion::is_applicable() );

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );
	}

	/**
	 * Test is_applicable returns false for new installations.
	 */
	public function test_is_applicable_returns_false_for_new_installations() {
		// Enable feature flag.
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );

		// Set option to 'yes' to simulate new installation (scheduled enabled).
		update_option( 'woocommerce_analytics_scheduled_import', 'yes' );

		$this->assertFalse( ScheduledUpdatesPromotion::is_applicable() );

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );
	}

	/**
	 * Test get_note returns note for existing installations.
	 */
	public function test_get_note_returns_note_for_existing_installations() {
		// Enable feature flag.
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );

		// Delete option to simulate existing installation (doesn't exist).
		delete_option( 'woocommerce_analytics_scheduled_import' );

		$note = ScheduledUpdatesPromotion::get_note();

		$this->assertInstanceOf( Note::class, $note );
		$this->assertEquals( 'Analytics now supports scheduled updates', $note->get_title() );
		$this->assertEquals( Note::E_WC_ADMIN_NOTE_INFORMATIONAL, $note->get_type() );

		// Verify action (only 1 action now: Enable).
		$actions = $note->get_actions();
		$this->assertCount( 1, $actions, 'Note should have 1 action' );
		$this->assertEquals( 'scheduled-updates-enable', $actions[0]->name );

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );
	}

	/**
	 * Test get_note returns null when not applicable.
	 */
	public function test_get_note_returns_null_when_not_applicable() {
		// Disable the feature (it's enabled by default in feature-config.php).
		add_filter( 'woocommerce_admin_features', array( $this, 'disable_feature' ) );

		delete_option( 'woocommerce_analytics_scheduled_import' );

		$note = ScheduledUpdatesPromotion::get_note();

		$this->assertNull( $note );

		remove_filter( 'woocommerce_admin_features', array( $this, 'disable_feature' ) );
	}

	/**
	 * Test that note is added via possibly_add_note for existing installations.
	 */
	public function test_possibly_add_note_adds_note_for_existing_installations() {
		// Enable feature flag.
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );

		// Delete option to simulate existing installation.
		delete_option( 'woocommerce_analytics_scheduled_import' );

		ScheduledUpdatesPromotion::possibly_add_note();

		// Verify note was created.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( ScheduledUpdatesPromotion::NOTE_NAME );

		$this->assertNotEmpty( $note_ids, 'Note should be created for existing installations' );

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );
	}

	/**
	 * Test that possibly_add_note prevents duplicates.
	 */
	public function test_possibly_add_note_prevents_duplicates() {
		// Enable feature flag.
		add_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );

		// Delete option to simulate existing installation.
		delete_option( 'woocommerce_analytics_scheduled_import' );

		// Add note first time.
		ScheduledUpdatesPromotion::possibly_add_note();

		// Try to add again.
		ScheduledUpdatesPromotion::possibly_add_note();

		// Verify only one note exists.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( ScheduledUpdatesPromotion::NOTE_NAME );

		$this->assertCount( 1, $note_ids, 'Only one note should exist' );

		remove_filter( 'woocommerce_admin_features', array( $this, 'enable_feature' ) );
	}

	/**
	 * Test enable action handler updates option.
	 */
	public function test_enable_action_updates_option() {
		// Delete option to simulate existing installation.
		delete_option( 'woocommerce_analytics_scheduled_import' );

		// Get the note.
		$note = ScheduledUpdatesPromotion::get_note();
		$note->save();

		// Create instance and trigger action.
		$promotion = new ScheduledUpdatesPromotion();
		$promotion->enable_scheduled_updates( $note );

		// Verify option was updated to 'yes' (scheduled enabled).
		$this->assertEquals( 'yes', get_option( 'woocommerce_analytics_scheduled_import' ) );
	}

	/**
	 * Test that enable action only responds to correct note.
	 */
	public function test_enable_action_ignores_wrong_note() {
		// Delete option to simulate existing installation.
		delete_option( 'woocommerce_analytics_scheduled_import' );

		// Create a different note.
		$other_note = new Note();
		$other_note->set_name( 'some-other-note' );

		// Create instance and trigger action.
		$promotion = new ScheduledUpdatesPromotion();
		$promotion->enable_scheduled_updates( $other_note );

		// Verify option was NOT updated (still null/doesn't exist).
		$this->assertFalse( get_option( 'woocommerce_analytics_scheduled_import' ), 'Option should not exist' );
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Delete all test notes.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( ScheduledUpdatesPromotion::NOTE_NAME );

		foreach ( $note_ids as $note_id ) {
			$note = \Automattic\WooCommerce\Admin\Notes\Notes::get_note( $note_id );
			if ( $note ) {
				$note->delete();
			}
		}

		// Reset options.
		delete_option( 'woocommerce_analytics_scheduled_import' );
	}
}
