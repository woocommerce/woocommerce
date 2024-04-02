<?php

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\PageController;

/**
 * Takes care of Launch Your Store related actions.
 */
class LaunchYourStore {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_update_options_site_visibility', array( $this, 'save_site_visibility_options' ) );
		add_action( 'current_screen', array( $this, 'maybe_create_coming_soon_page' ) );
		if ( is_admin() ) {
			add_filter( 'woocommerce_admin_shared_settings', array( $this, 'preload_settings' ) );
		}
		add_action( 'wp_footer', array( $this, 'maybe_add_coming_soon_banner_on_frontend' ) );
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

		foreach ( $options as $name => $option ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_POST[ $name ] ) && in_array( $_POST[ $name ], $option, true ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				update_option( $name, wp_unslash( $_POST[ $name ] ) );
			}
		}
	}

	/**
	 * Add `coming soon` page when it hasn't been created yet.
	 *
	 * @param WP_Screen $current_screen Current screen object.
	 *
	 * @return void
	 */
	public function maybe_create_coming_soon_page( $current_screen ) {
		$option_name    = 'woocommerce_coming_soon_page_id';
		$current_page   = PageController::get_instance()->get_current_page();
		$is_home        = isset( $current_page['id'] ) && 'woocommerce-home' === $current_page['id'];
		$page_id_option = get_option( $option_name, false );
		if ( $current_screen && 'woocommerce_page_wc-admin' === $current_screen->id && $is_home && ! $page_id_option ) {
			wc_create_page(
				esc_sql( _x( 'Coming Soon', 'Page slug', 'woocommerce' ) ),
				$option_name,
				_x( 'Coming Soon', 'Page title', 'woocommerce' ),
				'tbd',
			);
			// wc_create_page doesn't create options with autoload = yes.
			// Since we'll querying the option on WooCommerce home,
			// we should update the option to set autoload to yes.
			$page_id_option = get_option( $option_name );
			update_option( $option_name, $page_id_option, true );
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
	 */
	public function maybe_add_coming_soon_banner_on_frontend() {
		// User must be an admin or editor.
		// phpcs:ignore
		if ( ! current_user_can( 'shop_manager' ) && ! current_user_can( 'administrator' ) ) {
			return false;
		}

		// 'woocommerce_coming_soon' must be 'yes'
		if ( get_option( 'woocommerce_coming_soon', 'no' ) !== 'yes' ) {
			return false;
		}

		$link = admin_url( 'admin.php?page=wc-settings#wc_settings_general_site_visibility_slotfill' );

		$text = sprintf(
			// translators: no need to translate it. It's a link.
			__(
				"
			This page is in \"Coming soon\" mode and is only visible to you and those who have permission. To make it public to everyone,&nbsp;<a href='%s'>change visibility settings</a>.
		",
				'woocommerce'
			),
			$link
		);
		// phpcs:ignore
		echo "<div id='coming-soon-footer-banner'>$text</div>";
	}
}
