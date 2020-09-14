<?php
/**
 * WooCommerce Admin Facebook Marketing Expert Note Provider.
 *
 * Adds notes to the merchant's inbox concerning Facebook Marketing Expert.
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * WC_Admin_Notes_Facebook_Marketing_Expert
 */
class WC_Admin_Notes_Facebook_Marketing_Expert {

	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-facebook-marketing-expert';

	/**
	 * Name of plugin file.
	 */
	const PLUGIN_FILE = 'facebook-for-woocommerce/facebook-for-woocommerce.php';

	/**
	 * Possibly add note.
	 */
	public static function possibly_add_note() {

		// Check if the note can and should be added.
		if ( ! self::can_be_added() ) {
			return;
		}

		// Only add the note to stores with Facebook for WooCommerce installed.
		if ( ! self::is_facebook_for_woocommerce_installed() ) {
			return;
		}

		// Only add the note to stores with at least 30 orders in the last month.
		if ( self::orders_last_month() < 30 ) {
			return;
		}

		$note = self::get_note();
		$note->save();
	}

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Create and optimise your Facebook ads with the help of a Facebook Marketing Expert', 'woocommerce' ) );
		$note->set_content( __( 'Get 1:1 business support from a Facebook Marketing Expert who will work with you and your business to create and optimize your Facebook ads. Discover new strategies used by similar businesses in your market and industry. Check whether you are eligible to make use of this offer.', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce' ),
			'https://www.facebook.com/business/m/facebook-marketing-experts-program?content_id=UxWvEceBNtpQLhU',
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);
		return $note;
	}

	/**
	 * Determine if Facebook for WooCommerce is already active or installed
	 *
	 * @return bool
	 */
	protected static function is_facebook_for_woocommerce_installed() {
		if ( class_exists( 'WC_Facebookcommerce_Integration' ) ) {
			return true;
		}
		return PluginsHelper::is_plugin_installed( self::PLUGIN_FILE );
	}

	/**
	 * Determine the number of orders in the last month
	 *
	 * @return int
	 */
	protected static function orders_last_month() {

		$date = new \DateTime();

		$args = array(
			'date_created' => '>' . $date->modify( '-1 month' )->format( 'Y-m-d' ),
			'return'       => 'ids',
		);

		return count( wc_get_orders( $args ) );
	}
}
