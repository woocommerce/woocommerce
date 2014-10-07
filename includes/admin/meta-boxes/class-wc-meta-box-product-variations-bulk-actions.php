<?php
/**
 * Variations Bulk Actions
 *
 * All of the core Bulk Actions methods are stored here
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Meta Boxes
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Product_Variations_Bulk_Actions Class
 */
class WC_Meta_Box_Product_Variations_Bulk_Actions {

	/**
	 * Hook all the Actions to it's filters
	 */
	public static function init() {
		$actions =  array(
			'toggle_enabled',
			'toggle_downloadable',
			'toggle_virtual',
			'delete_all',

			'variable_regular_price',
			'variable_regular_price_increase',
			'variable_regular_price_decrease',
			'variable_sale_price',
			'variable_sale_price_increase',
			'variable_sale_price_decrease',

			'toggle_manage_stock',
			'variable_stock',

			'variable_length',
			'variable_width',
			'variable_height',
			'variable_weight',

			'variable_download_limit',
			'variable_download_expiry',
		);
		$myself = new self;

		foreach ( $actions as $action ) {
			add_filter( 'woocommerce_variations_apply_bulk_action_' . $action, array( $myself, $action ), 10, 2 );
		}
	}

	public function toggle_enabled( $response, $variations, $request ){
		global $wpdb;

		$ids = array();

		$toggle = 'publish';
		foreach ( $variations as $variation ) {
			$ids[] = $variation->ID;
			if ( $variation->post_status === 'publish' ){
				$toggle = 'private';
			}
		}

		// Ensure that our variables are safe
		$ids = array_filter( array_map( 'absint', $ids ) );

		$response->status = $wpdb->query( "UPDATE {$wpdb->posts} SET `post_status` = 'publish' WHERE `ID` IN ( " . implode( ',' , $ids ) . " );" ) !== false;

		return $response;
	}

	public function toggle_downloadable( $response, $variations, $request ){

		return $response;
	}

	public function toggle_virtual( $response, $variations, $request ){

		return $response;
	}

	public function delete_all( $response, $variations, $request ){
		return (bool) wp_delete_post( $variation->ID, true );
	}

	public function variable_regular_price( $response, $variations, $request ){

		return $response;
	}

	public function variable_regular_price_increase( $response, $variations, $request ){

		return $response;
	}

	public function variable_regular_price_decrease( $response, $variations, $request ){

		return $response;
	}

	public function variable_sale_price( $response, $variations, $request ){

		return $response;
	}

	public function variable_sale_price_increase( $response, $variations, $request ){

		return $response;
	}

	public function variable_sale_price_decrease( $response, $variations, $request ){

		return $response;
	}

	public function toggle_manage_stock( $response, $variations, $request ){

		return $response;
	}

	public function variable_stock( $response, $variations, $request ){

		return $response;
	}

	public function variable_length( $response, $variations, $request ){

		return $response;
	}

	public function variable_width( $response, $variations, $request ){

		return $response;
	}

	public function variable_height( $response, $variations, $request ){

		return $response;
	}

	public function variable_weight( $response, $variations, $request ){

		return $response;
	}

	public function variable_download_limit( $response, $variations, $request ){

		return $response;
	}

	public function variable_download_expiry( $response, $variations, $request ){

		return $response;
	}
}
