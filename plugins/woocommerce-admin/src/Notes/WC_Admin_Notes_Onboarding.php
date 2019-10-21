<?php
/**
 * WooCommerce Admin: Store ready note
 *
 * Adds notes for store onboarding and setup.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Onboarding.
 */
class WC_Admin_Notes_Onboarding {
	const NOTE_NAME = 'wc-admin-task-list-complete';

	/**
	 * Creates a note for task list completion.
	 */
	public static function add_task_list_complete_note() {
		$is_task_list_complete = get_option( 'woocommerce_task_list_complete', false );
		if ( ! $is_task_list_complete ) {
			return;
		}

		// See if we've already created this kind of note so we don't do it again.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Congratulations - your store is ready for launch!', 'woocommerce-admin' ) );
		$note->set_content( __( 'You’re ready to take your first order - may the sales roll in!', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'notice' );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'market-store',
			__( 'Market my store', 'woocommerce-admin' ),
			'https://woocommerce.com/product-category/woocommerce-extensions/marketing-extensions/'
		);

		$note->save();
	}
}
