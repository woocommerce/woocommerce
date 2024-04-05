<?php

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Admin\WCAdminHelper;

/**
 * Takes care of Launch Your Store related actions.
 */
class LaunchYourStore {
	const BANNER_DISMISS_USER_META_KEY = 'woocommerce_coming_soon_banner_dismissed';
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_update_options_site-visibility', array( $this, 'save_site_visibility_options' ) );
		if ( is_admin() ) {
			add_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_settings' ) );
		}
		add_action( 'wp_footer', array( $this, 'maybe_add_coming_soon_banner_on_frontend' ) );
		add_action( 'init', array( $this, 'register_launch_your_store_user_meta_fields' ) );
	}

	/**
	 * Save values submitted from WooCommerce -> Settings -> General.
	 *
	 * @return void
	 */
	public function save_site_visibility_options() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'woocommerce-settings' ) ) {
			return;
		}

		$options = array(
			'woocommerce_coming_soon'      => array( 'yes', 'no' ),
			'woocommerce_store_pages_only' => array( 'yes', 'no' ),
			'woocommerce_private_link'     => array( 'yes', 'no' ),
		);

		$at_least_one_saved = false;
		foreach ( $options as $name => $option ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_POST[ $name ] ) && in_array( $_POST[ $name ], $option, true ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				update_option( $name, wp_unslash( $_POST[ $name ] ) );
				$at_least_one_saved = true;
			}
		}

		if ( $at_least_one_saved ) {
			wc_admin_record_tracks_event( 'site_visibility_saved' );
		}
	}

	/**
	 * Preload settings for Site Visibility.
	 *
	 * @param array $settings settings array.
	 *
	 * @return mixed
	 */
	public function preload_settings( $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		$current_screen  = get_current_screen();
		$is_setting_page = $current_screen && 'woocommerce_page_wc-settings' === $current_screen->id;

		if ( $is_setting_page ) {
			$settings['siteVisibilitySettings'] = array(
				'shop_permalink'               => get_permalink( wc_get_page_id( 'shop' ) ),
				'woocommerce_coming_soon'      => get_option( 'woocommerce_coming_soon' ),
				'woocommerce_store_pages_only' => get_option( 'woocommerce_store_pages_only' ),
				'woocommerce_private_link'     => get_option( 'woocommerce_private_link' ),
				'woocommerce_share_key'        => get_option( 'woocommerce_share_key' ),
			);
		}

		return $settings;
	}

	/**
	 * Add 'coming soon' banner on the frontend when the following conditions met.
	 *
	 * - User must be either an admin or store editor (must be logged in).
	 * - 'woocommerce_coming_soon' option value must be 'yes'
	 * - The page must not be the Coming soon page itself.
	 */
	public function maybe_add_coming_soon_banner_on_frontend() {
		// Do not show the banner if the site is being previewed.
		if ( isset( $_GET['site-preview'] ) ) { // @phpcs:ignore
			return false;
		}

		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return false;
		}

		if ( get_user_meta( $current_user_id, self::BANNER_DISMISS_USER_META_KEY, true ) === 'yes' ) {
			return false;
		}
		// User must be an admin or editor.
		// phpcs:ignore
		if ( ! current_user_can( 'shop_manager' ) && ! current_user_can( 'administrator' ) ) {
			return false;
		}

		// 'woocommerce_coming_soon' must be 'yes'
		if ( get_option( 'woocommerce_coming_soon', 'no' ) !== 'yes' ) {
			return false;
		}

		$store_pages_only = get_option( 'woocommerce_store_pages_only' ) === 'yes';
		if ( $store_pages_only && ! WCAdminHelper::is_store_page() ) {
			return false;
		}

		$link       = admin_url( 'admin.php?page=wc-settings#wc_settings_general_site_visibility_slotfill' );
		$rest_url   = rest_url( 'wp/v2/users/' . $current_user_id );
		$rest_nonce = wp_create_nonce( 'wp_rest' );

		$text = sprintf(
			// translators: no need to translate it. It's a link.
			__(
				"
			This page is in \"Coming soon\" mode and is only visible to you and those who have permission. To make it public to everyone,&nbsp;<a href='%s'>change visibility settings</a>
		",
				'woocommerce'
			),
			$link
		);
		// phpcs:ignore
		echo "<div id='coming-soon-footer-banner'>$text<a class='coming-soon-footer-banner-dismiss' data-rest-url='$rest_url' data-rest-nonce='$rest_nonce'></a></div>";
	}

	/**
	 * Register user meta fields for Launch Your Store.
	 */
	public function register_launch_your_store_user_meta_fields() {
		register_meta(
			'user',
			'woocommerce_launch_your_store_tour_hidden',
			array(
				'type'         => 'string',
				'description'  => 'Indicate whether the user has dismissed the site visibility tour on the home screen.',
				'single'       => true,
				'show_in_rest' => true,
			)
		);

		register_meta( 'user', 'woocommerce_coming_soon_banner_dismissed', array(
			'type'         => 'string',
			'description'  => 'Indicate wheter user has dismissed coming soon notice or not',
			'single'       => true,
			'show_in_rest' => true,
		) );
	
	}
	
	/**
	 * Reset 'woocommerce_coming_soon_banner_dismissed' user meta to 'no'.
	 *
	 * Runs when a user logs-in successfully.
	 */
	public function reset_woocommerce_coming_soon_banner_dismissed( $user_login, $user ) {
		update_user_meta( $user->id, self::BANNER_DISMISS_USER_META_KEY, 'no' );
	}
}

