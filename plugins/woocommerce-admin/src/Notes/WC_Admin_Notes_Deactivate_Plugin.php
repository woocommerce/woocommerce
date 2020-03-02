<?php
/**
 * WooCommerce Admin: Update version reminder note.
 *
 * Creates a note to nudge users to use the newer version when two are installed.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Deactivate_Plugin.
 */
class WC_Admin_Notes_Deactivate_Plugin {
	const NOTE_NAME = 'wc-admin-deactivate-plugin';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'deactivate_feature_plugin' ) );
	}

	/**
	 * Creates the note to deactivate the older version.
	 */
	public static function add_note() {
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Deactivate old WooCommerce Admin version', 'woocommerce-admin' ) );
		$note->set_content( __( 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_UPDATE );
		$note->set_icon( 'warning' );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'deactivate-feature-plugin',
			__( 'Deactivate', 'woocommerce-admin' ),
			wc_admin_url( '&action=deactivate-feature-plugin' ),
			'unactioned',
			true
		);
		$note->save();
	}

	/**
	 * Delete the note if the version is higher than the included.
	 */
	public static function delete_note() {
		WC_Admin_Notes::delete_notes_with_name( self::NOTE_NAME );
	}

	/**
	 * Deactivate feature plugin.
	 */
	public function deactivate_feature_plugin() {
		/* phpcs:disable WordPress.Security.NonceVerification */
		if (
			! isset( $_GET['page'] ) ||
			'wc-admin' !== $_GET['page'] ||
			! isset( $_GET['action'] ) ||
			'deactivate-feature-plugin' !== $_GET['action'] ||
			! defined( 'WC_ADMIN_PLUGIN_FILE' )
		) {
			return;
		}
		/* phpcs:enable */

		$deactivate_url = admin_url( 'plugins.php?action=deactivate&plugin=' . rawurlencode( WC_ADMIN_PLUGIN_FILE ) . '&plugin_status=all&paged=1&_wpnonce=' . wp_create_nonce( 'deactivate-plugin_' . WC_ADMIN_PLUGIN_FILE ) );
		wp_safe_redirect( $deactivate_url );
		exit;
	}
}
