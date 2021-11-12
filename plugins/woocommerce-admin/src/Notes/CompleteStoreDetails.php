<?php
/**
 * CompleteStoreDetails class
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a note when the profiler was skipped.
 */
class CompleteStoreDetails {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-complete-store-details';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );

		// Bail when profile was set up by client.
		if ( isset( $onboarding_profile['setup_client'] ) && $onboarding_profile['setup_client'] ) {
			return;
		}

		// Bail when profile was not skipped.
		if ( isset( $onboarding_profile['skipped'] ) && ! $onboarding_profile['skipped'] ) {
			return;
		}

		// Bail when profile is already completed.
		if ( isset( $onboarding_profile['completed'] ) && $onboarding_profile['completed'] ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Add your store details to complete store setup', 'woocommerce-admin' ) );
		$note->set_content( __( 'Complete your store details with important information for setup such as your store’s base address', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'add-store-details',
			__( 'Add store details', 'woocommerce-admin' ),
			wc_admin_url( '&path=/setup-wizard' )
		);

		return $note;
	}
}
