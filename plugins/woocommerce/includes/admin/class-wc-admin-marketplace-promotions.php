<?php
/**
 * Addons Page
 *
 * @package  WooCommerce\Admin
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Marketplace_Promotions class.
 */
class WC_Admin_Marketplace_Promotions {

	const TRANSIENT_NAME        = 'woocommerce_marketplace_promotions';
	const SCHEDULED_ACTION_HOOK = 'woocommerce_marketplace_fetch_promotions';
	/**
	 * The user's locale, for example en_US.
	 *
	 * @var string
	 */
	public static string $locale;

	/**
	 * On all admin pages, schedule an action to fetch promotions data.
	 * Add menu badge to WooCommerce Extensions item if the promotions
	 * API requests one.
	 *
	 * @return void
	 */
	public static function init_marketplace_promotions() {
		// Add the callback for our scheduled action.
		if ( ! has_action( self::SCHEDULED_ACTION_HOOK, array( __CLASS__, 'fetch_marketplace_promotions' ) ) ) {
			add_action( self::SCHEDULED_ACTION_HOOK, array( __CLASS__, 'fetch_marketplace_promotions' ) );
		}

		if ( self::is_admin_page() ) {
			// Schedule the action twice a day, starting now.
			if ( false === wp_next_scheduled( self::SCHEDULED_ACTION_HOOK ) ) {
				wp_schedule_event( time(), 'twicedaily', self::SCHEDULED_ACTION_HOOK );
			}

			self::$locale = ( self::$locale ?? get_user_locale() ) ?? 'en_US';
			self::maybe_show_bubble_promotions();
		}

		register_deactivation_hook( WC_PLUGIN_FILE, array( __CLASS__, 'clear_scheduled_event' ) );
	}

	/**
	 * Check if the request is for an admin page, and not ajax.
	 * We may want to add a menu bubble to WooCommerce Extensions
	 * on any admin page, as the user may view the WooCommerce flyout
	 * menu.
	 *
	 * @return bool
	 */
	private static function is_admin_page(): bool {
		if (
			( defined( 'DOING_AJAX' ) && DOING_AJAX )
			|| ! is_admin()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Get promotions to show in the Woo in-app marketplace.
	 * Only run on selected pages in the main WooCommerce menu in wp-admin.
	 * Loads promotions in transient with one day life.
	 *
	 * @return void
	 */
	public static function fetch_marketplace_promotions() {
		$url        = 'https://woo.com/wp-json/wccom-extensions/3.0/promotions';
		$promotions = get_transient( self::TRANSIENT_NAME );

		if ( false !== $promotions ) {
			return;
		}

		$fetch_options  = array(
			'auth'    => true,
			'country' => true,
		);
		$raw_promotions = WC_Admin_Addons::fetch( $url, $fetch_options );

		// phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
		if ( is_wp_error( $raw_promotions ) ) {
			/**
			 * Allows connection error to be handled.
			 *
			 * @since 8.7
			 */
			do_action( 'woocommerce_page_wc-addons_connection_error', $raw_promotions->get_error_message() );
		}

		$response_code = (int) wp_remote_retrieve_response_code( $raw_promotions );
		if ( 200 !== $response_code ) {
			/**
			 * Allows connection error to be handled.
			 *
			 * @since 8.7
			 */
			do_action( 'woocommerce_page_wc-addons_connection_error', $response_code );
		}

		$promotions = json_decode( wp_remote_retrieve_body( $raw_promotions ), true );
		if ( empty( $promotions ) || ! is_array( $promotions ) ) {
			/**
			 * Allows connection error to be handled.
			 *
			 * @since 8.7
			 */
			do_action( 'woocommerce_page_wc-addons_connection_error', 'Empty or malformed response' );
		}
		// phpcs:enable WordPress.NamingConventions.ValidHookName.UseUnderscores

		if ( $promotions ) {
			// Filter out any expired promotions.
			$promotions = self::get_active_promotions( $promotions );
			set_transient( self::TRANSIENT_NAME, $promotions, DAY_IN_SECONDS );
		}
	}

	/**
	 * If there's an active promotion of the format `menu_bubble`,
	 * add a filter to show a bubble on the Extensions item in the
	 * WooCommerce menu.
	 *
	 * @return void
	 * @throws Exception  If we are unable to create a DateTime from the date_to_gmt.
	 */
	private static function maybe_show_bubble_promotions() {
		$promotions = get_transient( self::TRANSIENT_NAME );
		if ( ! $promotions ) {
			return;
		}

		$bubble_promotions = self::get_promotions_of_format( $promotions, 'menu_bubble' );
		if ( empty( $bubble_promotions ) ) {
			return;
		}

		$now_date_time = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		// Let's make absolutely sure the promotion is still active.
		foreach ( $bubble_promotions as $promotion ) {
			if ( ! isset( $promotion['date_to_gmt'] ) ) {
				continue;
			}

			try {
				$date_to_gmt = new DateTime( $promotion['date_to_gmt'], new DateTimeZone( 'UTC' ) );
			} catch ( \Exception $ex ) {
				continue;
			}

			if ( $now_date_time < $date_to_gmt ) {
				add_filter(
					'woocommerce_marketplace_menu_items',
					function ( $marketplace_pages ) use ( $promotion ) {
						return self::filter_marketplace_menu_items( $marketplace_pages, $promotion );
					}
				);

				break;
			}
		}
	}

	/**
	 * From the array of promotions, select those of a given format.
	 *
	 * @param ? array  $promotions  Array of data about promotions of all formats.
	 * @param ? string $format      Format we want to filter for.
	 *
	 * @return array
	 */
	private static function get_promotions_of_format( $promotions = array(), $format = '' ): array {
		if ( empty( $promotions ) || empty( $format ) ) {
			return array();
		}

		return array_filter(
			$promotions,
			function( $promotion ) use ( $format ) {
				return isset( $promotion['format'] ) && $format === $promotion['format'];
			}
		);
	}

	/**
	 * Find promotions that are still active – they have a date range that
	 * includes the current date.
	 *
	 * @param ?array $promotions  Data about current promotions.
	 *
	 * @return array
	 */
	private static function get_active_promotions( $promotions = array() ) {
		$now_date_time     = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$active_promotions = array();

		foreach ( $promotions as $promotion ) {
			if ( ! isset( $promotion['date_from_gmt'] ) || ! isset( $promotion['date_to_gmt'] ) ) {
				continue;
			}

			try {
				$date_from_gmt = new DateTime( $promotion['date_from_gmt'], new DateTimeZone( 'UTC' ) );
				$date_to_gmt   = new DateTime( $promotion['date_to_gmt'], new DateTimeZone( 'UTC' ) );
			} catch ( \Exception $ex ) {
				continue;
			}

			if ( $now_date_time >= $date_from_gmt && $now_date_time <= $date_to_gmt ) {
				$active_promotions[] = $promotion;
			}
		}

		// Sort promotions so the ones starting more recently are at the top.
		usort(
			$active_promotions,
			function ( $a, $b ) {
				return $b['date_from_gmt'] <=> $a['date_from_gmt'];
			}
		);

		return $active_promotions;
	}

	/**
	 * Callback for the `woocommerce_marketplace_menu_items` filter
	 * in `Automattic\WooCommerce\Internal\Admin\Marketplace::get_marketplace_pages`.
	 * At the moment, the Extensions page is the only page in `$menu_items`.
	 * Adds a bubble to the menu item.
	 *
	 * @param array  $menu_items  Arrays representing items in nav menu.
	 * @param ?array $promotion   Data about a promotion from the Woo.com API.
	 *
	 * @return array
	 */
	public static function filter_marketplace_menu_items( $menu_items, $promotion = array() ) {
		if ( ! isset( $promotion['menu_item_id'] ) || ! isset( $promotion['content'] ) ) {
			return $menu_items;
		}
		foreach ( $menu_items as $index => $menu_item ) {
			if (
				'woocommerce' === $menu_item['parent']
				&& $promotion['menu_item_id'] === $menu_item['id']
			) {
				$bubble_text                   = $promotion['content'][ self::$locale ] ?? ( $promotion['content']['en_US'] ?? __( 'Sale', 'woocommerce' ) );
				$menu_items[ $index ]['title'] = $menu_item['title'] . self::append_bubble( $bubble_text );

				break;
			}
		}

		return $menu_items;
	}

	/**
	 * Return the markup for a menu item bubble with a given text.
	 *
	 * @param string $bubble_text Text of bubble.
	 *
	 * @return string
	 */
	private static function append_bubble( $bubble_text ) {
		return ' <span class="awaiting-mod update-plugins remaining-tasks-badge woocommerce-task-list-remaining-tasks-badge">' . esc_html( $bubble_text ) . '</span>';
	}

	/**
	 * When WooCommerce is deactivated, clear the scheduled action.
	 *
	 * @return void
	 */
	public static function clear_scheduled_event() {
		$timestamp = wp_next_scheduled( self::SCHEDULED_ACTION_HOOK );
		wp_unschedule_event( $timestamp, self::SCHEDULED_ACTION_HOOK );
	}
}
