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
	 * Get feed suggestions for a display context, filtered the same way as marketplace-suggestions.js.
	 *
	 * @since 11.3.0
	 *
	 * @param string $context Display context, for example products-list-empty-body.
	 * @return array[] Feed items.
	 */
	private static function get_suggestions_for_context( $context ) {
		$feed           = self::get_suggestions_api_data();
		$dismissed      = self::get_dismissed_suggestions();
		$active_plugins = array_map( 'dirname', (array) get_option( 'active_plugins', array() ) );

		$suggestions = array_filter(
			$feed,
			function ( $item ) use ( $context, $dismissed, $active_plugins ) {
				return isset( $item['slug'], $item['context'] )
					&& in_array( $context, (array) $item['context'], true )
					&& ! in_array( $item['slug'], $dismissed, true )
					&& ! in_array( $item['product'] ?? '', $active_plugins, true )
					&& ( empty( $item['show-if-active'] ) || array() !== array_intersect( (array) $item['show-if-active'], $active_plugins ) );
			}
		);

		$count = (int) ( $feed[0]['recommendations-count'] ?? 5 );

		return array_slice( array_values( $suggestions ), 0, $count > 0 && $count <= 50 ? $count : 5 );
	}

	/**
	 * Add the productsListEmptySuggestions admin setting on the add products task page when it was reached from the empty products list.
	 *
	 * @internal
	 * @since 11.3.0
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

		$settings['productsListEmptySuggestions'] = array_map(
			function ( $item ) {
				return array(
					'slug'     => $item['slug'],
					'product'  => $item['product'] ?? '',
					'title'    => $item['title'] ?? '',
					'copy'     => $item['copy'] ?? '',
					'icon'     => esc_url_raw( $item['icon'] ?? '' ),
					'url'      => esc_url_raw( $item['url'] ?? '' ),
					'promoted' => $item['promoted'] ?? '',
				);
			},
			self::get_suggestions_for_context( 'products-list-empty-body' )
		);

		return $settings;
	}
}

WC_Marketplace_Suggestions::init();

