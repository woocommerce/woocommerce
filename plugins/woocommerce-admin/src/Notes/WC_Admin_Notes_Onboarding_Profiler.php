<?php
/**
 * WooCommerce Admin: Profile reminder note.
 *
 * Adds a notes to complete or skip the profiler.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * WC_Admin_Notes_Onboarding_Profiler.
 */
class WC_Admin_Notes_Onboarding_Profiler {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-onboarding-profiler-reminder';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'possibly_add_note' ) );
		add_action( 'update_option_' . Onboarding::PROFILE_DATA_OPTION, array( $this, 'update_status_on_complete' ), 10, 2 );
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		if ( ! Onboarding::should_show_profiler() ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Welcome to WooCommerce! Set up your store and start selling', 'woocommerce-admin' ) );
		$note->set_content( __( "We're here to help you going through the most important steps to get your store up and running.", 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_UPDATE );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'continue-profiler',
			__( 'Continue Store Setup', 'woocommerce-admin' ),
			wc_admin_url( '&enable_onboarding=1' ),
			'unactioned',
			true
		);
		$note->add_action(
			'skip-profiler',
			__( 'Skip Setup', 'woocommerce-admin' ),
			wc_admin_url( '&reset_profiler=0' ),
			'actioned',
			false
		);
		return $note;
	}

	/**
	 * Updates the note status when the profiler is completed.
	 *
	 * @param mixed $old_value Old value.
	 * @param mixed $new_value New value.
	 */
	public static function update_status_on_complete( $old_value, $new_value ) {
		if (
			( isset( $old_value['completed'] ) && $old_value['completed'] ) ||
			! isset( $new_value['completed'] ) ||
			! $new_value['completed']
		) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( empty( $note_ids ) ) {
			return;
		}

		$note = WC_Admin_Notes::get_note( $note_ids[0] );

		if ( false === $note ) {
			return;
		}

		$note->set_status( 'actioned' );
		$note->save();
	}
}
