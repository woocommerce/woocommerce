<?php
/**
 * WooCommerce Product Usage Notice.
 *
 * @package WooCommerce\Admin\Helper
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product usage notice class.
 */
class WC_Product_Usage_Notice {
	/**
	 * User meta key prefix to store dismiss counts per product. Product ID is
	 * the suffix part.
	 *
	 * @var string
	 */
	const DISMISSED_COUNT_META_PREFIX = '_woocommerce_product_usage_notice_dismissed_count_';

	/**
	 * User meta key prefix to store timestamp of last dismissed product usage notice.
	 * Product ID is the suffix part.
	 *
	 * @var string
	 */
	const DISMISSED_TIMESTAMP_META_PREFIX = '_woocommerce_product_usage_notice_dismissed_timestamp_';

	/**
	 * User meta key prefix to store timestamp of last clicked remind later from
	 * product usage notice. Product ID is the suffix part.
	 *
	 * @var string
	 */
	const REMIND_LATER_TIMESTAMP_META_PREFIX = '_woocommerce_product_usage_notice_remind_later_timestamp_';

	/**
	 * User meta key to store timestamp of last dismissed of any product usage
	 * notices. There's no product ID in the meta key.
	 *
	 * @var string
	 */
	const LAST_DISMISSED_TIMESTAMP_META = '_woocommerce_product_usage_notice_last_dismissed_timestamp';

	/**
	 * Array of product usage notice rules from helper API.
	 *
	 * @var array
	 */
	private static $product_usage_notice_rules = array();

	/**
	 * Current product usage notice rule applied to the current admin screen.
	 *
	 * @var array
	 */
	private static $current_notice_rule = array();

	/**
	 * Loads the class, runs on init.
	 *
	 * @return void
	 */
	public static function load() {
		add_action( 'current_screen', array( __CLASS__, 'maybe_show_product_usage_notice' ) );

		add_action( 'wp_ajax_woocommerce_dismiss_product_usage_notice', array( __CLASS__, 'ajax_dismiss' ) );
		add_action( 'wp_ajax_woocommerce_remind_later_product_usage_notice', array( __CLASS__, 'ajax_remind_later' ) );
	}

	/**
	 * Maybe show product usage notice in a given screen object.
	 *
	 * @param \WP_Screen $screen Current \WP_Screen object.
	 */
	public static function maybe_show_product_usage_notice( $screen ) {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return;
		}

		if ( ! WC_Helper::is_site_connected() ) {
			return;
		}

		self::$product_usage_notice_rules = WC_Helper::get_product_usage_notice_rules();
		if ( empty( self::$product_usage_notice_rules ) ) {
			return;
		}

		self::$current_notice_rule = self::get_current_notice_rule( $screen );
		if ( empty( self::$current_notice_rule ) ) {
			return;
		}

		$product_id = self::$current_notice_rule['id'];

		if ( self::is_notice_throttled( $user_id, $product_id ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_product_usage_notice_scripts' ) );
	}

	/**
	 * Check whether the user clicked "remind later" recently.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	private static function is_remind_later_clicked_recently( int $user_id, int $product_id ): bool {
		$last_remind_later_ts = absint(
			get_user_meta(
				$user_id,
				self::REMIND_LATER_TIMESTAMP_META_PREFIX . $product_id,
				true
			)
		);
		if ( 0 === $last_remind_later_ts ) {
			return false;
		}

		$seconds_since_clicked_remind_later = time() - $last_remind_later_ts;

		$wait_after_remind_later = self::$current_notice_rule['wait_in_seconds_after_remind_later'];

		return $seconds_since_clicked_remind_later < $wait_after_remind_later;
	}

	/**
	 * Check whether the user has reached max dismissals of product usage notice.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	private static function has_reached_max_dismissals( int $user_id, int $product_id ): bool {
		$dismiss_count = absint(
			get_user_meta(
				$user_id,
				self::DISMISSED_COUNT_META_PREFIX . $product_id,
				true
			)
		);

		$max_dismissals = self::$current_notice_rule['max_dismissals'];

		return $dismiss_count >= $max_dismissals;
	}

	/**
	 * Check whether the user dismissed any product usage notices recently.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private static function is_any_notices_dismissed_recently( int $user_id ): bool {
		$global_last_dismissed_ts = absint(
			get_user_meta(
				$user_id,
				self::LAST_DISMISSED_TIMESTAMP_META,
				true
			)
		);
		if ( 0 === $global_last_dismissed_ts ) {
			return false;
		}

		$seconds_since_dismissed = time() - $global_last_dismissed_ts;

		$wait_after_any_dismisses = self::$product_usage_notice_rules['wait_in_seconds_after_any_dismisses'];

		return $seconds_since_dismissed < $wait_after_any_dismisses;
	}

	/**
	 * Check whether the user dismissed given product usage notice recently.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	private static function is_product_notice_dismissed_recently( int $user_id, int $product_id ): bool {
		$last_dismissed_ts = absint(
			get_user_meta(
				$user_id,
				self::DISMISSED_TIMESTAMP_META_PREFIX . $product_id,
				true
			)
		);
		if ( 0 === $last_dismissed_ts ) {
			return false;
		}

		$seconds_since_dismissed = time() - $last_dismissed_ts;

		$wait_after_dismiss = self::$current_notice_rule['wait_in_seconds_after_dismiss'];

		return $seconds_since_dismissed < $wait_after_dismiss;
	}

	/**
	 * Check whether current notice is throttled for the user and product.
	 *
	 * @param int $user_id    User ID.
	 * @param int $product_id Product ID.
	 *
	 * @return bool
	 */
	private static function is_notice_throttled( int $user_id, int $product_id ): bool {
		return self::is_remind_later_clicked_recently( $user_id, $product_id ) ||
			self::has_reached_max_dismissals( $user_id, $product_id ) ||
			self::is_any_notices_dismissed_recently( $user_id ) ||
			self::is_product_notice_dismissed_recently( $user_id, $product_id );
	}

	/**
	 * Enqueue scripts needed to display product usage notice (or modal).
	 */
	public static function enqueue_product_usage_notice_scripts() {
		WCAdminAssets::register_style( 'woo-product-usage-notice', 'style', array( 'wp-components' ) );
		WCAdminAssets::register_script( 'wp-admin-scripts', 'woo-product-usage-notice', true );

		$subscribe_url = add_query_arg(
			array(
				'add-to-cart'  => self::$current_notice_rule['id'],
				'utm_source'   => 'pu',
				'utm_medium'   => 'product',
				'utm_campaign' => 'pu_modal_subscribe',
			),
			'https://woocommerce.com/cart/'
		);

		$renew_url = add_query_arg(
			array(
				'renew_product' => self::$current_notice_rule['id'],
				'product_key'   => self::$current_notice_rule['state']['key'],
				'order_id'      => self::$current_notice_rule['state']['order_id'],
				'utm_source'    => 'pu',
				'utm_medium'    => 'product',
				'utm_campaign'  => 'pu_modal_renew',
			),
			'https://woocommerce.com/cart/'
		);

		wp_localize_script(
			'wc-admin-woo-product-usage-notice',
			'wooProductUsageNotice',
			array(
				'subscribeUrl'        => $subscribe_url,
				'renewUrl'            => $renew_url,
				'dismissAction'       => 'woocommerce_dismiss_product_usage_notice',
				'remindLaterAction'   => 'woocommerce_remind_later_product_usage_notice',
				'productId'           => self::$current_notice_rule['id'],
				'productName'         => self::$current_notice_rule['name'],
				'productRegularPrice' => self::$current_notice_rule['regular_price'],
				'dismissNonce'        => wp_create_nonce( 'dismiss_product_usage_notice' ),
				'remindLaterNonce'    => wp_create_nonce( 'remind_later_product_usage_notice' ),
				'showAs'              => self::$current_notice_rule['show_as'],
				'colorScheme'         => self::$current_notice_rule['color_scheme'],
				'subscriptionState'   => self::$current_notice_rule['state'],
				'screenId'            => get_current_screen()->id,
			)
		);
	}

	/**
	 * Get product usage notice rule from a given WP_Screen object.
	 *
	 * @param \WP_Screen $screen Current \WP_Screen object.
	 *
	 * @return array
	 */
	private static function get_current_notice_rule( $screen ) {
		foreach ( self::$product_usage_notice_rules['products'] as $product_id => $rule ) {
			if ( ! isset( $rule['screens'][ $screen->id ] ) ) {
				continue;
			}

			// Check query strings.
			if ( ! self::query_string_matches( $screen, $rule ) ) {
				continue;
			}

			$product_id = absint( $product_id );
			$state      = WC_Helper::get_product_subscription_state( $product_id );
			if ( $state['expired'] || $state['unregistered'] ) {
				$rule['id']    = $product_id;
				$rule['state'] = $state;
				return $rule;
			}
		}

		return array();
	}

	/**
	 * Check whether the screen and GET parameter matches a given rule.
	 *
	 * @param \WP_Screen $screen Current \WP_Screen object.
	 * @param array      $rule   Product usage notice rule.
	 *
	 * @return bool
	 */
	private static function query_string_matches( $screen, $rule ) {
		if ( empty( $rule['screens'][ $screen->id ]['qs'] ) ) {
			return true;
		}

		$qs = $rule['screens'][ $screen->id ]['qs'];
		foreach ( $qs as $key => $val ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( empty( $_GET[ $key ] ) || $_GET[ $key ] !== $val ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
		return true;
	}

	/**
	 * AJAX handler for dismiss action of product usage notice.
	 */
	public static function ajax_dismiss() {
		if ( ! check_ajax_referer( 'dismiss_product_usage_notice' ) ) {
			wp_die( -1 );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_die( -1 );
		}

		$product_id = absint( $_GET['product_id'] ?? 0 );
		if ( ! $product_id ) {
			wp_die( -1 );
		}

		$dismiss_count = absint( get_user_meta( $user_id, self::DISMISSED_COUNT_META_PREFIX . $product_id, true ) );
		update_user_meta( $user_id, self::DISMISSED_COUNT_META_PREFIX . $product_id, $dismiss_count + 1 );

		update_user_meta( $user_id, self::DISMISSED_TIMESTAMP_META_PREFIX . $product_id, time() );
		update_user_meta( $user_id, self::LAST_DISMISSED_TIMESTAMP_META, time() );

		wp_die( 1 );
	}

	/**
	 * AJAX handler for "remind later" action of product usage notice.
	 */
	public static function ajax_remind_later() {
		if ( ! check_ajax_referer( 'remind_later_product_usage_notice' ) ) {
			wp_die( -1 );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_die( -1 );
		}

		$product_id = absint( $_GET['product_id'] ?? 0 );
		if ( ! $product_id ) {
			wp_die( -1 );
		}

		update_user_meta( $user_id, self::REMIND_LATER_TIMESTAMP_META_PREFIX . $product_id, time() );

		wp_die( 1 );
	}
}

WC_Product_Usage_Notice::load();
