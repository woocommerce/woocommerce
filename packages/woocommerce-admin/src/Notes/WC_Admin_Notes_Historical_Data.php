<?php
/**
 * WooCommerce Admin: Historical Analytics Data Note.
 *
 * Adds a notes to store alerts area concerning the historial analytics data tool.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Install;

/**
 * WC_Admin_Notes_Historical_Data.
 */
class WC_Admin_Notes_Historical_Data {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-historical-data';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_analytics_regenerate_init', array( $this, 'update_status_to_actioned' ) );
	}

	/**
	 * Update status of note to actioned on data import trigger.
	 */
	public static function update_status_to_actioned() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( empty( $note_ids ) ) {
			return;
		}

		$note = new WC_Admin_Note( $note_ids[0] );
		$note->set_status( 'actioned' );
		$note->save();
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$is_upgrading = get_option( Install::VERSION_OPTION );
		if ( $is_upgrading ) {
			return;
		}

		// Only add this note if we don't have any orders.
		$orders = wc_get_orders(
			array(
				'limit' => 1,
			)
		);
		if ( count( $orders ) < 1 ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'WooCommerce Admin: Historical Analytics Data', 'woocommerce' ) );
		$note->set_content( __( 'To view your historical analytics data, you must process your existing orders and customers.', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_UPDATE );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		// @todo Add remind me later option. See https://github.com/woocommerce/woocommerce-admin/issues/1756.
		$note->add_action(
			'get-started',
			__( 'Get Started', 'woocommerce' ),
			'?page=wc-admin&path=/analytics/settings&import=true',
			'actioned',
			true
		);
		return $note;
	}
}
