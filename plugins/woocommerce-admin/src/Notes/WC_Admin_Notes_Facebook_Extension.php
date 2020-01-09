<?php
/**
 * WooCommerce Admin Facebook Extension Note Provider.
 *
 * Adds a note to the merchant's inbox showing the benefits of the Facebook extension.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Facebook_Extension
 */
class WC_Admin_Notes_Facebook_Extension {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-facebook-extension';

	/**
	 * Attach hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_note_action_install-now', array( $this, 'install_facebook_extension' ) );
	}

	/**
	 * Possibly add Facebook extension note.
	 */
	public static function possibly_add_facebook_note() {
		// Only show the Facebook note to stores with products.
		$products = wp_count_posts( 'product' );
		if ( (int) $products->publish < 1 ) {
			return;
		}

		// We want to show the Facebook note after day 3.
		$three_days_in_seconds = 3 * DAY_IN_SECONDS;
		if ( ! self::wc_admin_active_for( $three_days_in_seconds ) ) {
			return;
		}

		$data_store = \WC_Data_Store::load( 'admin-note' );

		// We already have this note? Then exit, we're done.
		$note_ids = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		include_once ABSPATH . '/wp-admin/includes/plugin.php';

		$content = __( 'Grow your business by targeting the right people and driving sales with Facebook. You can install this free extension now.', 'woocommerce-admin' );

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Market on Facebook', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'thumbs-up' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce-admin' ), 'https://woocommerce.com/products/facebook/', WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED );
		$note->add_action( 'install-now', __( 'Install now', 'woocommerce-admin' ), false, WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED, true );

		// Create the note as "actioned" if the Facebook extension is already installed.
		if ( 0 === validate_plugin( 'facebook-for-woocommerce/facebook-for-woocommerce.php' ) ) {
			$note->set_status( WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED );
		}

		$note->save();
	}

	/**
	 * Install Facebook extension when note is actioned.
	 *
	 * @param WC_Admin_Note $note Note being acted upon.
	 */
	public function install_facebook_extension( $note ) {
		if ( self::NOTE_NAME === $note->get_name() ) {
			$install_request = array( 'plugin' => 'facebook-for-woocommerce' );
			$installer       = new \Automattic\WooCommerce\Admin\API\OnboardingPlugins();
			$result          = $installer->install_plugin( $install_request );

			if ( is_wp_error( $result ) ) {
				// @todo Reset note actioned status?
				return;
			}

			$activate_request = array( 'plugins' => 'facebook-for-woocommerce' );
			$installer->activate_plugins( $activate_request );

			$content = __( 'You\'re almost ready to start driving sales with Facebook. Complete the setup steps to control how WooCommerce integrates with your Facebook store.', 'woocommerce-admin' );
			$note->set_title( __( 'Market on Facebook — Installed', 'woocommerce-admin' ) );
			$note->set_content( $content );
			$note->set_icon( 'checkmark-circle' );
			$note->clear_actions();
			$note->add_action(
				'configure-facebook',
				__( 'Setup', 'woocommerce-admin' ),
				add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => 'integration',
						'section' => 'facebookcommerce',
					),
					admin_url( 'admin.php' )
				),
				WC_Admin_Note::E_WC_ADMIN_NOTE_UNACTIONED
			);
		}
	}
}
