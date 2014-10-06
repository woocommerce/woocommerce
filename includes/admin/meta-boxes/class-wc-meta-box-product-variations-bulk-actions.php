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

		foreach ( $actions as $action ) {
			add_filter( 'woocommerce_variation_apply_bulk_action_' . $action, array( &$this, $action ), 10, 3 );
		}
	}

	public function toggle_enabled( $response, $variation, $request ){


		return $response;
	}

	public function toggle_downloadable( $response, $variation, $request ){

		return $response;
	}

	public function toggle_virtual( $response, $variation, $request ){

		return $response;
	}

	public function delete_all( $response, $variation, $request ){

		return $response;
	}

	public function variable_regular_price( $response, $variation, $request ){

		return $response;
	}

	public function variable_regular_price_increase( $response, $variation, $request ){

		return $response;
	}

	public function variable_regular_price_decrease( $response, $variation, $request ){

		return $response;
	}

	public function variable_sale_price( $response, $variation, $request ){

		return $response;
	}

	public function variable_sale_price_increase( $response, $variation, $request ){

		return $response;
	}

	public function variable_sale_price_decrease( $response, $variation, $request ){

		return $response;
	}

	public function toggle_manage_stock( $response, $variation, $request ){

		return $response;
	}

	public function variable_stock( $response, $variation, $request ){

		return $response;
	}

	public function variable_length( $response, $variation, $request ){

		return $response;
	}

	public function variable_width( $response, $variation, $request ){

		return $response;
	}

	public function variable_height( $response, $variation, $request ){

		return $response;
	}

	public function variable_weight( $response, $variation, $request ){

		return $response;
	}

	public function variable_download_limit( $response, $variation, $request ){

		return $response;
	}

	public function variable_download_expiry( $response, $variation, $request ){

		return $response;
	}
}
