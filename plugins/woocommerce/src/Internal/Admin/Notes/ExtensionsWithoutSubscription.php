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
	 * Option holding when the note last fetched the subscription list from WooCommerce.com.
	 */
	const LAST_FETCH_OPTION_KEY = 'woocommerce_admin-extensions-without-subscription-last-fetch';

	/**
	 * Hook the note refresh.
	 *
	 * @since 11.3.0
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

		$plugins = self::get_plugins( self::may_fetch() );

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
	 * Whether this page load may fetch the subscription list from WooCommerce.com.
	 *
	 * The fetch blocks the page, so a connected store without a cached list gets one a day, or one
	 * right after it connects. Any other refresh reads the cache, so it's free to run every time.
	 *
	 * @return bool
	 */
	private static function may_fetch(): bool {
		if ( ! class_exists( 'WC_Helper' ) ) {
			return false;
		}

		if ( ! \WC_Helper::is_site_connected() || \WC_Helper::has_cached_subscriptions() ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read only, the same signal WooSubscriptionsNotes uses.
		if ( isset( $_GET['wc-helper-status'] ) ) {
			return true;
		}

		$now = time();
		if ( (int) get_option( self::LAST_FETCH_OPTION_KEY, 0 ) + DAY_IN_SECONDS > $now ) {
			return false;
		}

		update_option( self::LAST_FETCH_OPTION_KEY, $now, false );

		return true;
	}

	/**
	 * Active WooCommerce.com extensions without a subscription, or null when that can't be known.
	 *
	 * @param bool $fetch Whether the subscription list may be fetched from WooCommerce.com when it isn't cached.
	 * @return array|null
	 */
	private static function get_plugins( bool $fetch = true ): ?array {
		if ( ! class_exists( 'WC_Helper_Updater' ) ) {
			return null;
		}

		return \WC_Helper_Updater::get_plugins_without_subscription( $fetch );
	}
}
