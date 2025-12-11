<?php
/**
 * WooCommerce Admin Scheduled Updates Promotion Note Provider.
 *
 * Adds a note to the merchant's inbox promoting scheduled updates for analytics.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;

/**
 * ScheduledUpdatesPromotion
 *
 * @since 10.5.0
 */
class ScheduledUpdatesPromotion {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-scheduled-updates-promotion';

	/**
	 * Name of the option to check.
	 */
	const OPTION_NAME = 'woocommerce_analytics_scheduled_import';

	/**
	 * Constructor - attach action hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_note_action_scheduled-updates-enable', array( $this, 'enable_scheduled_updates' ) );
	}

	/**
	 * Should this note exist?
	 *
	 * @return bool
	 */
	public static function is_applicable() {
		if ( ! Features::is_enabled( 'analytics-scheduled-import' ) ) {
			return false;
		}

		// Get the current option value.
		// Note: get_option() returns false when option doesn't exist.
		$immediate_import = get_option( self::OPTION_NAME, false );

		// Only show to existing sites (false/not set) that haven't migrated yet.
		// New sites have the option set during onboarding, so they won't see this.
		if ( false !== $immediate_import ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the note.
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		if ( ! self::is_applicable() ) {
			return null;
		}

		$note = new Note();

		$note->set_title( __( 'Analytics now supports scheduled updates', 'woocommerce' ) );
		$note->set_content( __( 'This provides improved performance to your store, enable it in Analytics > Settings.', 'woocommerce' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );

		// Add "Enable" action with custom handler.
		$note->add_action(
			'scheduled-updates-enable',
			__( 'Enable', 'woocommerce' ),
			wc_admin_url(),
			Note::E_WC_ADMIN_NOTE_ACTIONED,
			true,
			__( 'Scheduled updates enabled', 'woocommerce' )
		);

		return $note;
	}

	/**
	 * Enable scheduled updates when the action is triggered.
	 *
	 * @param Note $note The note being actioned.
	 * @return void
	 */
	public function enable_scheduled_updates( $note ): void {
		// Verify this is our note.
		if ( self::NOTE_NAME !== $note->get_name() ) {
			return;
		}

		// Update the option to enable scheduled mode.
		update_option( self::OPTION_NAME, 'yes' );
	}
}
