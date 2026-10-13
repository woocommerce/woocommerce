<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Admin\Notes\Notes;
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
	 * Option holding the plugin files the note was last refreshed for, so unchanged page loads skip the notes table.
	 */
	const LAST_PLUGINS_OPTION_KEY = 'woocommerce_admin-extensions-without-subscription-last-plugins';

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
	 * Runs on every WooCommerce admin page, so it only touches the notes table when the set of
	 * extensions without a subscription differs from the last refresh.
	 *
	 * @since 11.3.0
	 */
	public function refresh_note(): void {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		// Try the cached subscription list first, so a store with nothing to check never reads the fetch budget.
		$plugins = self::get_plugins( false );
		if ( null === $plugins && self::may_fetch() ) {
			$plugins = self::get_plugins( true );
		}

		// Leave the note as it is while the subscription list can't be trusted.
		if ( null === $plugins ) {
			return;
		}

		$plugin_files = array_keys( $plugins );
		sort( $plugin_files );
		if ( get_option( self::LAST_PLUGINS_OPTION_KEY ) === $plugin_files ) {
			return;
		}

		if ( empty( $plugins ) ) {
			self::delete_note_unless_dismissed_or_actioned();
		} elseif ( self::note_exists() ) {
			self::possibly_update_note();
		} else {
			self::possibly_add_note();
		}

		update_option( self::LAST_PLUGINS_OPTION_KEY, $plugin_files, true );
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
	 * Delete the note now that nothing is missing a subscription, unless the merchant already dealt with it.
	 *
	 * A dismissed or actioned note is hidden anyway, and keeping it means it stays that way if the
	 * extensions come back, for example after deactivating every plugin to find a conflict.
	 *
	 * @return void
	 */
	private static function delete_note_unless_dismissed_or_actioned(): void {
		$note = Notes::get_note_by_name( self::NOTE_NAME );
		if ( ! $note instanceof Note ) {
			return;
		}

		if ( $note->get_is_deleted() || Note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
			return;
		}

		self::possibly_delete_note();
	}

	/**
	 * Whether the note should exist.
	 *
	 * The note follows the installed extensions through refresh_note(), so it's always
	 * applicable once added.
	 *
	 * @return bool
	 */
	public static function is_applicable(): bool {
		return true;
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
