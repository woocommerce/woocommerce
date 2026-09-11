<?php
/**
 * WooCommerce Admin Double-Counted Refunds Tool Notice Provider.
 *
 * Adds a note to the merchant's inbox pointing to the double-counted refunds fix
 * tool on the WooCommerce > Status > Tools page.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * RefundDoubleCountToolNotice
 *
 * @internal
 * @since 11.2.0
 */
class RefundDoubleCountToolNotice {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-refund-double-count-tool';

	/**
	 * Should this note exist?
	 *
	 * The note only nudges merchants who have not run the tool yet.
	 *
	 * @return bool
	 */
	public static function is_applicable() {
		if ( ! FeaturesUtil::feature_is_enabled( 'analytics' ) ) {
			return false;
		}

		return Analytics::is_refund_double_count_tool_applicable()
			&& '' === Analytics::get_refund_double_count_state()['status'];
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

		$note->set_title( __( 'Check your refunds in Analytics', 'woocommerce' ) );
		$note->set_content(
			__( 'Orders that received a partial refund followed by a full refund before WooCommerce 11.1 may show higher returns in your Analytics reports than they should. Use the double-counted refunds tool on the Status page to check for affected orders and fix them.', 'woocommerce' )
		);
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_WARNING );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );

		$note->add_action(
			'refund-double-count-tool_view',
			__( 'Check refunds', 'woocommerce' ),
			admin_url( 'admin.php?page=wc-status&tab=tools#tool_' . Analytics::REFUND_DOUBLE_COUNT_TOOL_ID ),
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			true
		);

		return $note;
	}
}
