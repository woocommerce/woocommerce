<?php
/**
 * Addons Page
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_Addons' ) ) :

/**
 * WC_Admin_Addons Class
 */
class WC_Admin_Addons {

	/**
	 * Handles output of the reports page in admin.
	 */
	public function output() {

		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : '';

		if ( false === ( $addons = get_transient( 'woocommerce_addons_html_' . $view ) ) ) {

			$raw_addons = wp_remote_get( 'http://www.woothemes.com/product-category/woocommerce-extensions/' . $view . '?orderby=popularity', array(
					'user-agent' => 'woocommerce-addons-page',
					'timeout'    => 3
				) );

			if ( ! is_wp_error( $raw_addons ) ) {

				$raw_addons = wp_remote_retrieve_body( $raw_addons );

				// Get Products
				$dom = new DOMDocument();
				libxml_use_internal_errors(true);
				$dom->loadHTML( $raw_addons );

				$addons = '';
				$xpath  = new DOMXPath( $dom );
				$tags   = $xpath->query('//ul[@class="products"]');
				foreach ( $tags as $tag ) {
					$addons = $tag->ownerDocument->saveXML( $tag );
					break;
				}

				if ( $addons )
					set_transient( 'woocommerce_addons_html_' . $view, wp_kses_post( $addons ), 60*60*24*7 ); // Cached for a week
			}
		}

		include_once( 'views/html-admin-page-addons.php' );
	}
}

endif;

return new WC_Admin_Addons();