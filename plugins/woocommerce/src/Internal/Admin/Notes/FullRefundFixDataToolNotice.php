<?php
/**
 * WooCommerce Admin Full Refund Fix Data Tool Notice Provider.
 *
 * Adds a note to the merchant's inbox pointing to the full refund fix tool on
 * the WooCommerce > Status > Tools page.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Internal\Admin\Analytics;

/**
 * FullRefundFixDataToolNotice
 *
 * @since 11.0.0
 */
class FullRefundFixDataToolNotice {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-full-refund-fix-data-tool';

	/**
	 * Should this note exist?
	 *
	 * @return bool
	 */
	public static function is_applicable() {
		if ( ! Features::is_enabled( 'analytics' ) ) {
			return false;
		}

		return Analytics::should_show_refund_fix_tool();
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

		$note->set_title( __( 'Fix your refund data in Analytics', 'woocommerce' ) );
		$note->set_content(
			__( 'We found some refunded orders where the full refund amount was not recorded correctly in your Analytics reports. Use the full refund fix tool on the Status page to re-import the affected data.', 'woocommerce' )
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_WARNING );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );

		$note->add_action(
			'full-refund-fix-data-tool_view',
			__( 'Fix refund data', 'woocommerce' ),
			admin_url( 'admin.php?page=wc-status&tab=tools' ),
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			true
		);

		return $note;
	}
}
