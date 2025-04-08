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

	const CRON_NAME           = 'woocommerce_marketplace_cron_fetch_promotions';
	const TRANSIENT_NAME      = 'woocommerce_marketplace_promotions_v2';
	const TRANSIENT_LIFE_SPAN = DAY_IN_SECONDS;
	const PROMOTIONS_API_URL  = 'https://woocommerce.com/wp-json/wccom-extensions/3.0/promotions';

	/**
	 * The user's locale, for example en_US.
	 *
	 * @var string
	 */
	public static string $locale;

	/**
	 * On all admin pages, try go get Marketplace promotions every day.
	 * Shows notice and adds menu badge to WooCommerce Extensions item
	 * if the promotions API requests them.
	 *
	 * WC_Admin calls this method when it is instantiated during
	 * is_admin requests.
	 *
	 * @return void
	 */
	public static function init() {
		// A legacy hook that can be triggered by action scheduler.
		add_action( 'woocommerce_marketplace_fetch_promotions', array( __CLASS__, 'clear_deprecated_action' ) );
		add_action(
			'woocommerce_marketplace_fetch_promotions_clear',
			array(
				__CLASS__,
				'clear_deprecated_scheduled_event',
			)
		);

		// Fetch promotions from the API and store them in a transient.
		add_action( self::CRON_NAME, array( __CLASS__, 'update_promotions' ) );

		if (
			defined( 'DOING_AJAX' ) && DOING_AJAX
			|| defined( 'DOING_CRON' ) && DOING_CRON
			|| defined( 'WP_CLI' ) && WP_CLI
		) {
			return;
		}

		if ( ! is_admin() ) {
			return;
		}

		self::schedule_cron_event();

		register_deactivation_hook( WC_PLUGIN_FILE, array( __CLASS__, 'clear_cron_event' ) );

		self::$locale = ( self::$locale ?? get_user_locale() ) ?? 'en_US';
		self::maybe_show_bubble_promotions();
	}

	/**
	 * Schedule a daily cron event to fetch promotions.
	 *
	 * @version 9.5.0
	 *
	 * @return void
	 */
	private static function schedule_cron_event() {
		if ( ! wp_next_scheduled( self::CRON_NAME ) ) {
			wp_schedule_event( time(), 'twicedaily', self::CRON_NAME );
		}
	}

	/**
	 * Fetch promotions from the API and store them in a transient.
	 *
	 * @return void
	 */
	public static function update_promotions() {
		// Fetch promotions from the API.
		$promotions = self::fetch_marketplace_promotions();
		set_transient( self::TRANSIENT_NAME, $promotions, self::TRANSIENT_LIFE_SPAN );
	}

	/**
	 * Get active Marketplace promotions from the transient.
	 * Use `woocommerce_marketplace_suppress_promotions` filter to suppress promotions.
	 *
	 * @since 9.0
	 */
	public static function get_active_promotions() {
		/**
		 * Filter to suppress the requests for and showing of marketplace promotions.
		 *
		 * @since 8.8
		 */
		if ( apply_filters( 'woocommerce_marketplace_suppress_promotions', false ) ) {
			return array();
		}

		$promotions = get_transient( self::TRANSIENT_NAME );
		if ( ! $promotions ) {
			return array();
		}

		$promotions = self::merge_promos( $promotions );

		return self::filter_out_inactive_promotions( $promotions );
	}

	/**
	 * Get promotions to show in the Woo in-app marketplace and load them into a transient
	 * with a 12-hour life. Run as a recurring scheduled action.
	 *
	 * @return array
	 */
	private static function fetch_marketplace_promotions() {
		/**
		 * Filter to suppress the requests for and showing of marketplace promotions.
		 *
		 * @since 8.8
		 */
		if ( apply_filters( 'woocommerce_marketplace_suppress_promotions', false ) ) {
			return array();
		}

		// Fetch promotions from the API.
		$fetch_options  = array(
			'auth'    => true,
			'country' => true,
		);
		$raw_promotions = WC_Admin_Addons::fetch( self::PROMOTIONS_API_URL, $fetch_options );

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

		if ( ! is_array( $promotions ) ) {
			$promotions = array();

			/**
			 * Allows connection error to be handled.
			 *
			 * @since 8.7
			 */
			do_action( 'woocommerce_page_wc-addons_connection_error', 'Malformed response' );
		}
		// phpcs:enable WordPress.NamingConventions.ValidHookName.UseUnderscores

		return $promotions;
	}

	/**
	 * If there's an active promotion of the format `menu_bubble`,
	 * add a filter to show a bubble on the Extensions item in the
	 * WooCommerce menu.
	 *
	 * Use `woocommerce_marketplace_suppress_promotions` filter to suppress the bubble.
	 *
	 * @return void
	 * @throws Exception  If we are unable to create a DateTime from the date_to_gmt.
	 */
	private static function maybe_show_bubble_promotions() {
		/**
		 * Filter to suppress the requests for and showing of marketplace promotions.
		 *
		 * @since 8.8
		 */
		if ( apply_filters( 'woocommerce_marketplace_suppress_promotions', false ) ) {
			return;
		}

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
	 * @param ?array  $promotions  Array of data about promotions of all formats.
	 * @param ?string $format      Format we want to filter for.
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
	private static function filter_out_inactive_promotions( $promotions = array() ) {
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
	 * Promos arrive in the array of promotions as an array of arrays with the key 'promos'.
	 * We merge them into the main array.
	 *
	 * @param ?array $promotions  Promotions data received from WCCOM.
	 *                            May have an element with the key 'promos', which contains an array.
	 *
	 * @return array
	 * */
	private static function merge_promos( ?array $promotions = array() ): array {
		if (
			! empty( $promotions['promos'] )
			&& is_array( $promotions['promos'] )
		) {
			$promotions = array_merge( $promotions, $promotions['promos'] );
			unset( $promotions['promos'] );
		}

		return $promotions;
	}

	/**
	 * Callback for the `woocommerce_marketplace_menu_items` filter
	 * in `Automattic\WooCommerce\Internal\Admin\Marketplace::get_marketplace_pages`.
	 * At the moment, the Extensions page is the only page in `$menu_items`.
	 * Adds a bubble to the menu item.
	 *
	 * @param array  $menu_items  Arrays representing items in nav menu.
	 * @param ?array $promotion   Data about a promotion from the WooCommerce.com API.
	 *
	 * @return array
	 */
	public static function filter_marketplace_menu_items( $menu_items, $promotion = array() ): array {
		if ( ! isset( $promotion['menu_item_id'] ) || ! isset( $promotion['content'] ) ) {
			return $menu_items;
		}
		foreach ( $menu_items as $index => $menu_item ) {
			if (
				'woocommerce' === $menu_item['parent']
				&& $promotion['menu_item_id'] === $menu_item['id']
			) {
				$bubble_text                   = $promotion['content'][ self::$locale ] ?? ( $promotion['content']['en_US'] ?? __( 'Sale', 'woocommerce' ) );
				$menu_items[ $index ]['title'] = self::append_bubble( $menu_item['title'], $bubble_text );

				break;
			}
		}

		return $menu_items;
	}

	/**
	 * Return the markup for a menu item bubble with a given text.
	 *
	 * @param string $menu_item_text Text of menu item we want to change.
	 * @param string $bubble_text    Text of bubble.
	 *
	 * @return string
	 */
	private static function append_bubble( string $menu_item_text, string $bubble_text ): string {
		// Strip out update count bubble added by Marketplace::get_marketplace_update_count_html.
		$menu_item_text = preg_replace( '|<span class="update-plugins count-[\d]+">[A-z0-9 <>="-]+</span>|', '', $menu_item_text );

		return $menu_item_text
			. '<span class="awaiting-mod update-plugins remaining-tasks-badge woocommerce-task-list-remaining-tasks-badge">'
			. esc_html( $bubble_text )
			. '</span>';
	}

	/**
	 * When WooCommerce is disabled, clear the WP Cron event we use to fetch promotions.
	 *
	 * @version 9.5.0
	 *
	 * @return void
	 */
	public static function clear_cron_event() {
		$timestamp = wp_next_scheduled( self::CRON_NAME );
		wp_unschedule_event( $timestamp, self::CRON_NAME );
	}

	/**
	 * Clear deprecated scheduled action that was used to fetch promotions in WooCommerce 8.8.
	 * Replaced with a transient in WooCommerce 9.0.
	 *
	 * @return void
	 */
	public static function clear_deprecated_scheduled_event() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_marketplace_fetch_promotions' );
		}
	}

	/**
	 * We can't clear deprecated action from AS when it's running,
	 * so we schedule a new single action to clear the deprecated
	 * `woocommerce_marketplace_fetch_promotions` action.
	 */
	public static function clear_deprecated_action() {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), 'woocommerce_marketplace_fetch_promotions_clear' );
		}
	}
}

// Fetch list of promotions from WooCommerce.com for WooCommerce admin UI.
if ( ! has_action( 'init', array( 'WC_Admin_Marketplace_Promotions', 'init' ) ) ) {
	add_action( 'init', array( 'WC_Admin_Marketplace_Promotions', 'init' ), 11 );
}
