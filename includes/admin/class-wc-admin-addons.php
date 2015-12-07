<?php
/**
 * Addons Page
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Admin_Addons Class.
 */
class WC_Admin_Addons {

	/**
	 * Handles the outputting of a contextually aware Storefront link (points to child themes if Storefront is already active).
	 */
	public static function output_storefront_button() {
		$url = 'http://www.woothemes.com/storefront/';
		$text = __( 'View more about Storefront', 'woocommerce' );
		$template = get_option( 'template' );
		$stylesheet = get_option( 'stylesheet' );
		$utm_content = 'hasstorefront';

		// If we're using Storefront with a child theme.
		if ( 'storefront' == $template && 'storefront' != $stylesheet ) {
			$url = 'http:///www.woothemes.com/product-category/themes/storefront-child-theme-themes/';
			$text = __( 'View more Storefront child themes', 'woocommerce' );
			$utm_content = 'hasstorefrontchildtheme';
		}

		// If we're using Storefront without a child theme.
		if ( 'storefront' == $template && 'storefront' == $stylesheet ) {
			$url = 'http:///www.woothemes.com/product-category/themes/storefront-child-theme-themes/';
			$text = __( 'Need a fresh look? Try Storefront child themes', 'woocommerce' );
			$utm_content = 'nostorefrontchildtheme';
		}

		// If we're not using Storefront at all.
		if ( 'storefront' != $template && 'storefront' != $stylesheet ) {
			$url = 'http://www.woothemes.com/storefront/';
			$text = __( 'Need a theme? Try Storefront', 'woocommerce' );
			$utm_content = 'nostorefront';
		}

		$url = add_query_arg( array(
			'utm_source'   => 'product',
			'utm_medium'   => 'upsell',
			'utm_campaign' => 'wcaddons',
			'utm_content'  => $utm_content,
		), $url );

		echo '<a href="' . esc_url( $url ) . '" class="add-new-h2">' . esc_html( $text ) . '</a>' . "\n";
	}

	/**
	 * Handles output of the reports page in admin.
	 */
	public static function output() {

		if ( false === ( $addons = get_transient( 'woocommerce_addons_data' ) ) ) {

			$addons_json = wp_safe_remote_get( 'http://d3t0oesq8995hv.cloudfront.net/woocommerce-addons.json', array( 'user-agent' => 'WooCommerce Addons Page' ) );

			if ( ! is_wp_error( $addons_json ) ) {

				$addons = json_decode( wp_remote_retrieve_body( $addons_json ) );

				if ( $addons ) {
					set_transient( 'woocommerce_addons_data', $addons, WEEK_IN_SECONDS );
				}
			}
		}

		include_once( 'views/html-admin-page-addons.php' );
	}
}
