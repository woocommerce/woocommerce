<?php
/**
 * Marketplace suggestions
 *
 * Behaviour for displaying in-context suggestions for marketplace extensions.
 *
 * @package WooCommerce\Classes
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Marketplace suggestions core behaviour.
 */
class WC_Marketplace_Suggestions {

	/**
	 * Initialise.
	 */
	public static function init() {
		if ( ! self::allow_suggestions() ) {
			return;
		}

		// Add suggestions to the product tabs.
		add_action( 'woocommerce_product_data_tabs', array( __CLASS__, 'product_data_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'product_data_panels' ) );

		// Register ajax api handlers.
		add_action( 'wp_ajax_woocommerce_add_dismissed_marketplace_suggestion', array( __CLASS__, 'post_add_dismissed_suggestion_handler' ) );

		// Register hooks for rendering suggestions container markup.
		add_action( 'wc_marketplace_suggestions_orders_empty_state', array( __CLASS__, 'render_orders_list_empty_state' ) );

		// Trigger suggestions fetch on wc-admin dashboard.
		add_action( 'current_screen', array( __CLASS__, 'maybe_trigger_suggestions_fetch' ) );

		// Suggestions for the add products task page, when reached from the empty products list.
		add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'add_products_list_empty_suggestions_setting' ) );
	}

	/**
	 * Product data tabs filter
	 *
	 * Adds a new Extensions tab to the product data meta box.
	 *
	 * @param array $tabs Existing tabs.
	 *
	 * @return array
	 */
	public static function product_data_tabs( $tabs ) {
		$tabs['marketplace-suggestions'] = array(
			'label'    => _x( 'Get more options', 'Marketplace suggestions', 'woocommerce' ),
			'target'   => 'marketplace_suggestions',
			'class'    => array(),
			'priority' => 1000,
		);

		return $tabs;
	}

	/**
	 * Render additional panels in the product data metabox.
	 */
	public static function product_data_panels() {
		include dirname( __FILE__ ) . '/templates/html-product-data-extensions.php';
	}

	/**
	 * Return an array of suggestions the user has dismissed.
	 */
	public static function get_dismissed_suggestions() {
		$dismissed_suggestions = array();

		$dismissed_suggestions_data = get_user_meta( get_current_user_id(), 'wc_marketplace_suggestions_dismissed_suggestions', true );
		if ( $dismissed_suggestions_data ) {
			$dismissed_suggestions = $dismissed_suggestions_data;
			if ( ! is_array( $dismissed_suggestions ) ) {
				$dismissed_suggestions = array();
			}
		}

		return $dismissed_suggestions;
	}

	/**
	 * POST handler for adding a dismissed suggestion.
	 */
	public static function post_add_dismissed_suggestion_handler() {
		if ( ! check_ajax_referer( 'add_dismissed_marketplace_suggestion' ) ) {
			wp_die();
		}

		$post_data       = wp_unslash( $_POST );
		$suggestion_slug = sanitize_text_field( $post_data['slug'] );
		if ( ! $suggestion_slug ) {
			wp_die();
		}

		$dismissed_suggestions = self::get_dismissed_suggestions();

		if ( in_array( $suggestion_slug, $dismissed_suggestions, true ) ) {
			wp_die();
		}

		$dismissed_suggestions[] = $suggestion_slug;
		update_user_meta(
			get_current_user_id(),
			'wc_marketplace_suggestions_dismissed_suggestions',
			$dismissed_suggestions
		);

		wp_die();
	}

	/**
	 * Render suggestions containers in orders list empty state.
	 */
	public static function render_orders_list_empty_state() {
		self::render_suggestions_container( 'orders-list-empty-header' );
		self::render_suggestions_container( 'orders-list-empty-body' );
		self::render_suggestions_container( 'orders-list-empty-footer' );
	}

	/**
	 * Render a suggestions container element, with the specified context.
	 *
	 * @param string $context Suggestion context name (rendered as a css class).
	 */
	public static function render_suggestions_container( $context ) {
		include dirname( __FILE__ ) . '/views/container.php';
	}

	/**
	 * Should suggestions be displayed?
	 *
	 * @param string $screen_id The current admin screen.
	 *
	 * @return bool
	 */
	public static function show_suggestions_for_screen( $screen_id ) {
		// We only show suggestions on certain admin screens.
		if ( ! in_array( $screen_id, array( 'edit-product', 'edit-shop_order', 'product', wc_get_page_screen_id( 'shop-order' ) ), true ) ) {
			return false;
		}

		return self::allow_suggestions();
	}

	/**
	 * Trigger suggestion fetch for wc-admin dashboard.
	 */
	public static function maybe_trigger_suggestions_fetch() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'woocommerce_page_wc-admin' === $screen_id && self::allow_suggestions() ) {
			self::get_suggestions_api_data();
		}
	}

	/**
	 * Should suggestions be displayed?
	 *
	 * @return bool
	 */
	public static function allow_suggestions() {
		// We currently only support English suggestions.
		$locale             = get_locale();
		$suggestion_locales = array(
			'en_AU',
			'en_CA',
			'en_GB',
			'en_NZ',
			'en_US',
			'en_ZA',
		);
		if ( ! in_array( $locale, $suggestion_locales, true ) ) {
			return false;
		}

		// Suggestions are only displayed if user can install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Suggestions may be disabled via a setting under Accounts & Privacy.
		if ( 'no' === get_option( 'woocommerce_show_marketplace_suggestions', 'yes' ) ) {
			return false;
		}

		// User can disabled all suggestions via filter.
		return apply_filters( 'woocommerce_allow_marketplace_suggestions', true );
	}

	/**
	 * Pull suggestion data from options. This is retrieved from a remote endpoint.
	 *
	 * @return array of json API data
	 */
	public static function get_suggestions_api_data() {
		$data = get_option( 'woocommerce_marketplace_suggestions', array() );

		// If the options have never been updated, or were updated over a week ago, queue update.
		if ( empty( $data['updated'] ) || ( time() - WEEK_IN_SECONDS ) > $data['updated'] ) {
			$next = WC()->queue()->get_next( 'woocommerce_update_marketplace_suggestions' );
			if ( ! $next ) {
				WC()->queue()->cancel_all( 'woocommerce_update_marketplace_suggestions' );
				WC()->queue()->schedule_single( time(), 'woocommerce_update_marketplace_suggestions' );
			}
		}

		return ! empty( $data['suggestions'] ) ? $data['suggestions'] : array();
	}

	/**
	 * Get feed suggestions for a display context, in feed order, filtered like the legacy marketplace-suggestions.js.
	 * Drops dismissed suggestions and products that are already active, honours show-if-active, and caps the list at the feed's recommendations-count.
	 *
	 * @internal
	 * @since 11.3.0
	 *
	 * @param string $context Display context, for example products-list-empty-body.
	 * @return array[] Raw feed items.
	 */
	public static function get_suggestions_for_context( $context ) {
		$feed = self::get_suggestions_api_data();
		if ( ! is_array( $feed ) || empty( $feed ) ) {
			return array();
		}

		$first = reset( $feed );
		$count = is_array( $first ) && is_numeric( $first['recommendations-count'] ?? null ) ? (int) $first['recommendations-count'] : 0;
		$limit = $count > 0 && $count <= 50 ? $count : 5;

		$dismissed      = self::get_dismissed_suggestions();
		$active_plugins = array_map( 'dirname', (array) get_option( 'active_plugins', array() ) );
		$suggestions    = array();

		foreach ( $feed as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$contexts = isset( $item['context'] ) ? (array) $item['context'] : array();
			if ( ! in_array( $context, $contexts, true ) ) {
				continue;
			}

			$slug = isset( $item['slug'] ) && is_scalar( $item['slug'] ) ? (string) $item['slug'] : '';
			if ( '' === $slug || in_array( $slug, $dismissed, true ) ) {
				continue;
			}

			$product = isset( $item['product'] ) && is_scalar( $item['product'] ) ? (string) $item['product'] : '';
			if ( '' !== $product && in_array( $product, $active_plugins, true ) ) {
				continue;
			}

			$show_if_active = isset( $item['show-if-active'] ) ? array_filter( (array) $item['show-if-active'], 'is_string' ) : array();
			if ( $show_if_active && ! array_intersect( $active_plugins, $show_if_active ) ) {
				continue;
			}

			$suggestions[] = $item;
			if ( count( $suggestions ) >= $limit ) {
				break;
			}
		}

		return $suggestions;
	}

	/**
	 * Add the productsListEmptySuggestions admin setting on the add products task page when it was reached from the empty products list.
	 *
	 * @internal
	 *
	 * @param mixed $settings Admin shared settings.
	 * @return mixed
	 */
	public static function add_products_list_empty_suggestions_setting( $settings ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only check of the current admin page.
		$is_target_page = isset( $_GET['page'], $_GET['path'], $_GET['from'] )
			&& 'wc-admin' === $_GET['page']
			&& '/add-product' === $_GET['path']
			&& 'products-list' === $_GET['from'];
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $is_target_page || ! is_array( $settings ) ) {
			return $settings;
		}

		$suggestions = array();
		foreach ( self::get_suggestions_for_context( 'products-list-empty-body' ) as $item ) {
			$url = isset( $item['url'] ) && is_string( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
			if ( '' === $url ) {
				continue;
			}

			$suggestions[] = array(
				'slug'     => (string) $item['slug'],
				'product'  => self::get_string_field( $item, 'product' ),
				'title'    => self::get_string_field( $item, 'title' ),
				'copy'     => self::get_string_field( $item, 'copy' ),
				'icon'     => esc_url_raw( self::get_string_field( $item, 'icon' ) ),
				'url'      => $url,
				'promoted' => self::get_string_field( $item, 'promoted' ),
			);
		}

		$settings['productsListEmptySuggestions'] = $suggestions;

		return $settings;
	}

	/**
	 * Read a scalar field from a feed item as a string, or an empty string when missing or not scalar.
	 *
	 * @param array  $item Feed item.
	 * @param string $key  Field name.
	 * @return string
	 */
	private static function get_string_field( array $item, string $key ): string {
		return isset( $item[ $key ] ) && is_scalar( $item[ $key ] ) ? (string) $item[ $key ] : '';
	}
}

WC_Marketplace_Suggestions::init();

