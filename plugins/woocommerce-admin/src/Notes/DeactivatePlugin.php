<?php
/**
 * WooCommerce Admin: Update version reminder note.
 *
 * Creates a note to nudge users to use the newer version when two are installed.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Deactivate_Plugin.
 */
class DeactivatePlugin {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-deactivate-plugin';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'deactivate_feature_plugin' ) );
	}

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	public static function get_note() {
		$note = new Note();
		$note->set_title( __( 'Deactivate old WooCommerce Admin version', 'woocommerce-admin' ) );
		$note->set_content( __( 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce. We recommend deactivating the plugin and using the stable version included with WooCommerce.', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'deactivate-feature-plugin',
			__( 'Deactivate', 'woocommerce-admin' ),
			wc_admin_url( '&action=deactivate-feature-plugin' ),
			Note::E_WC_ADMIN_NOTE_UNACTIONED,
			true
		);
		return $note;
	}

	/**
	 * Delete the note if the version is higher than the included.
	 */
	public static function delete_note() {
		Notes::delete_notes_with_name( self::NOTE_NAME );
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
