<?php
/**
 * WooCommerce Admin Coupon Page Moved provider.
 *
 * Adds a notice when the store manager access the coupons page via the old WooCommere > Coupons menu.
 */

namespace Automattic\WooCommerce\Admin\Notes;

use Automattic\WooCommerce\Admin\Features\CouponsMovedTrait;
use stdClass;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Coupon_Page_Moved class.
 */
class WC_Admin_Notes_Coupon_Page_Moved {

	use NoteTraits, CouponsMovedTrait;

	const NOTE_NAME = 'wc-admin-coupon-page-moved';

	/**
	 * Initialize our hooks.
	 */
	public function init() {
		if ( ! wc_coupons_enabled() ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'possibly_add_note' ] );
		add_action( 'admin_init', [ $this, 'redirect_to_coupons' ] );
	}

	/**
	 * Checks if a note can and should be added.
	 *
	 * @return bool
	 */
	public static function can_be_added() {
		if ( ! wc_coupons_enabled() ) {
			return false;
		}

		// Don't add the notice if it's been hidden by the user before.
		if ( self::has_dismissed_note() ) {
			return false;
		}

		// If we already have a notice, don't add a new one.
		if ( self::has_unactioned_note() ) {
			return false;
		}

		return isset( $_GET[ self::$query_key ] ) && (bool) $_GET[ self::$query_key ]; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Get the note object for this class.
	 *
	 * @return WC_Admin_Note
	 */
	public static function get_note() {
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Coupon management has moved!', 'woocommerce-admin' ) );
		$note->set_content( __( 'Coupons can now be managed from Marketing > Coupons. Click the button below to remove the legacy WooCommerce > Coupons menu item.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_UPDATE );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( new stdClass() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'remove-legacy-coupon-menu',
			__( 'Remove legacy coupon menu', 'woocommerce-admin' ),
			wc_admin_url( '&action=remove-coupon-menu' ),
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			true
		);

		return $note;
	}

	/**
	 * Find notes that have not been actioned.
	 *
	 * @return bool
	 */
	protected static function has_unactioned_note() {
		$notes = self::get_data_store()->get_notes(
			[
				'name'       => [ self::NOTE_NAME ],
				'status'     => [ 'unactioned' ],
				'is_deleted' => false,
			]
		);

		return ! empty( $notes );
	}

	/**
	 * Whether any notes have been dismissed by the user previously.
	 *
	 * @return bool
	 */
	protected static function has_dismissed_note() {
		$notes = self::get_data_store()->get_notes(
			[
				'name'       => [ self::NOTE_NAME ],
				'is_deleted' => true,
			]
		);

		return ! empty( $notes );
	}

	/**
	 * Get the data store object.
	 *
	 * @return DataStore The data store object.
	 */
	protected static function get_data_store() {
		return WC_Data_Store::load( 'admin-note' );
	}

	/**
	 * Safe redirect to the coupon page to force page refresh.
	 */
	public function redirect_to_coupons() {
		/* phpcs:disable WordPress.Security.NonceVerification */
		if (
			! isset( $_GET['page'] ) ||
			'wc-admin' !== $_GET['page'] ||
			! isset( $_GET['action'] ) ||
			'remove-coupon-menu' !== $_GET['action'] ||
			! defined( 'WC_ADMIN_PLUGIN_FILE' )
		) {
			return;
		}
		/* phpcs:enable */
		$this->display_legacy_menu( false );
		wp_safe_redirect( self::get_management_url( 'coupons' ) );
		exit;
	}
}
