<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Admin\PageController;

/**
 * Inbox note for active WooCommerce.com extensions that have no subscription on this store.
 */
class ExtensionsWithoutSubscription {
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-extensions-without-subscription';

	/**
	 * Hook the note refresh.
	 */
	public function __construct() {
		add_action( 'admin_head', array( $this, 'refresh_note' ) );
	}

	/**
	 * Add, update or remove the note on WooCommerce admin pages, so it follows the installed extensions.
	 *
	 * @since 11.3.0
	 */
	public function refresh_note(): void {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		$plugins = self::get_plugins();

		// Leave the note as it is while the subscription list can't be trusted.
		if ( null === $plugins ) {
			return;
		}

		if ( empty( $plugins ) ) {
			self::possibly_delete_note();
		} elseif ( self::note_exists() ) {
			self::possibly_update_note();
		} else {
			self::possibly_add_note();
		}
	}

	/**
	 * Get the note.
	 *
	 * @since 11.3.0
	 *
	 * @return Note|null
	 */
	public static function get_note() {
		$plugins = self::get_plugins();
		if ( empty( $plugins ) ) {
			return null;
		}

		if ( 1 === count( $plugins ) ) {
			$title = sprintf(
				/* translators: %s: extension name */
				__( '%s doesn\'t have an active subscription', 'woocommerce' ),
				wp_strip_all_tags( current( $plugins )['Name'] )
			);
			$content = __( 'Without one, it won\'t receive security updates, product improvements, or support. Subscribe on WooCommerce.com to keep it protected.', 'woocommerce' );
		} else {
			$title   = __( 'Some of your extensions don\'t have an active subscription', 'woocommerce' );
			$content = __( 'Without one, they won\'t receive security updates, product improvements, or support. Subscribe on WooCommerce.com to keep them protected.', 'woocommerce' );
		}

		$note = new Note();
		$note->set_title( $title );
		$note->set_content( $content );
		$note->set_content_data( (object) array( 'plugins' => array_keys( $plugins ) ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_WARNING );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'subscribe',
			__( 'Subscribe', 'woocommerce' ),
			add_query_arg(
				array(
					'page'         => 'wc-admin',
					'tab'          => 'my-subscriptions',
					'path'         => rawurlencode( '/extensions' ),
					'utm_source'   => 'inbox_notification',
					'utm_campaign' => 'pu_inbox_purchase',
				),
				admin_url( 'admin.php' )
			)
		);

		return $note;
	}

	/**
	 * Active WooCommerce.com extensions without a subscription, or null when that can't be known.
	 *
	 * @return array|null
	 */
	private static function get_plugins(): ?array {
		if ( ! class_exists( 'WC_Helper_Updater' ) ) {
			return null;
		}

		return \WC_Helper_Updater::get_plugins_without_subscription();
	}
}
