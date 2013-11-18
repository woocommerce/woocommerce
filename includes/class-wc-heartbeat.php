<?php
/**
 * WooCommerce Heartbeat API
 *
 * This class handles integration between WooCommerce and the Heartbeat API
 *
 * @class WC_Heartbeat
 * @version 2.1
 * @package WooCommerce/Includes
 * @category class
 * @author Gerhard Potgieter <potgieterg@gmail.com>
 */
class WC_Heartbeat {
	/**
	 * Constructor
	 *
	 * @return  void
	 */
	public function __construct() {
		add_filter( 'heartbeat_received', array( $this, 'heartbeat_received' ), 10, 3 );
		add_filter( 'heartbeat_nopriv_received', array( $this, 'nopriv_heartbeat_received' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

	}

	/**
	 * Hook into the heartbeat API for authenticated users
	 *
	 * @param  array|object $response
	 * @param  array|object $data
	 * @param  string $screen_id
	 * @return array|object
	 */
	public static function heartbeat_received( $response, $data, $screen_id ) {
		if ( isset( $data['wc_heartbeat'] ) ) {
			switch ( $data['wc_heartbeat'] ) {
				case 'dashboard_stats' :
				global $wpdb;
					// Get sales
					$sales = $wpdb->get_var( "SELECT SUM( postmeta.meta_value ) FROM {$wpdb->posts} as posts
						LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
						LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
						LEFT JOIN {$wpdb->terms} AS term USING( term_id )
						LEFT JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
						WHERE 	posts.post_type 	= 'shop_order'
						AND 	posts.post_status 	= 'publish'
						AND 	tax.taxonomy		= 'shop_order_status'
						AND		term.slug			IN ( 'completed', 'processing', 'on-hold' )
						AND 	postmeta.meta_key   = '_order_total'
						AND 	posts.post_date >= '" . date( 'Y-m-01', current_time('timestamp') ) . "'
						AND 	posts.post_date <= '" . date( 'Y-m-d H:i:s', current_time('timestamp') ) . "'
					" );
					$response['wc_stats']['total_sales'] = woocommerce_price( $sales );

					// Counts
					$processing_count = get_term_by( 'slug', 'processing', 'shop_order_status' )->count;
					$response['wc_stats']['processing'] = sprintf( _n( "<strong>%s order</strong> awaiting processing", "<strong>%s orders</strong> are awaiting processing", $processing_count, 'woocommerce' ), $processing_count );
					$on_hold_count = get_term_by( 'slug', 'on-hold', 'shop_order_status' )->count;
					$response['wc_stats']['on_hold'] = sprintf( _n( "<strong>%s order</strong> are on-hold", "<strong>%s orders</strong> are currently on-hold", $on_hold_count, 'woocommerce' ), $on_hold_count );

					// Get products using a query - this is too advanced for get_posts :(
					$stock   = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
					$nostock = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );

					$query_from = "FROM {$wpdb->posts} as posts
						INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
						INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
						WHERE 1=1
							AND posts.post_type IN ('product', 'product_variation')
							AND posts.post_status = 'publish'
							AND (
								postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$stock}' AND CAST(postmeta.meta_value AS SIGNED) > '{$nostock}' AND postmeta.meta_value != ''
							)
							AND (
								( postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes' ) OR ( posts.post_type = 'product_variation' )
							)
						";

					$lowinstock_count = absint( $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" ) );
					$response['wc_stats']['low_stock'] = sprintf( _n( "<strong>%s product</strong> low in stock", "<strong>%s products</strong> are low in stock", $lowinstock_count, 'woocommerce' ), $lowinstock_count );

					$query_from = "FROM {$wpdb->posts} as posts
						INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
						INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
						WHERE 1=1
							AND posts.post_type IN ('product', 'product_variation')
			                AND posts.post_status = 'publish'
			                AND (
			                    postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$stock}' AND postmeta.meta_value != ''
			                )
			                AND (
			                    ( postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes' ) OR ( posts.post_type = 'product_variation' )
			                )
						";

					$outofstock_count = absint( $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" ) );
					$response['wc_stats']['no_stock'] = sprintf( _n( "<strong>%s product</strong> out of stock", "<strong>%s products</strong> are out of stock", $outofstock_count, 'woocommerce' ), $outofstock_count );
				break;
				default:
					do_action( 'woocommerce_heartbeat_' . $data['wc_heartbeat'] . '_received' );
				break;
			}
		}
		return apply_filters( 'woocommerce_heartbeat_received', $response, $data, $screen_id );
	}

	/**
	 * Hook into the heartbeat API for no privilidged users
	 *
	 * @param  array|object $response
	 * @param  array|object $data
	 * @param  string $screen_id
	 * @return array|object
	 */
	public static function nopriv_heartbeat_received( $response, $data, $screen_id ) {
		if ( isset( $data['wc_heartbeat'] ) ) {
			switch ( $data['wc_heartbeat'] ) {
				default:
					do_action( 'woocommerce_nopriv_heartbeat_' . $data['wc_heartbeat'] . '_received' );
				break;
			}
		}
		return apply_filters( 'woocommerce_nopriv_heartbeat_received', $response, $data, $screen_id );
	}

	/**
	 * Enqueue heartbeat JS in admin area
	 *
	 * @return void
	 */
	public static function admin_scripts() {
		global $pagenow;
		// Make sure the heartbeat is loaded
		wp_enqueue_script( 'heartbeat' );

		// We only want to load the stats on the dashboard where the widget is
		if ( 'index.php' == $pagenow ) {
			wc_enqueue_js("
				$(document).on('heartbeat-send', function(e, data) {
					data['wc_heartbeat'] = 'dashboard_stats';
				});
			");
			wc_enqueue_js("
				$(document).on( 'heartbeat-tick', function(e, data) {
					if ( ! data['wc_stats'] )
						return;

					$('.wc_status_list .sales-this-month a strong').html( data['wc_stats']['total_sales'] );
					$('.wc_status_list .processing-orders a').html( data['wc_stats']['processing'] );
					$('.wc_status_list .on-hold-orders a').html( data['wc_stats']['on_hold'] );
					$('.wc_status_list .low-in-stock a').html( data['wc_stats']['low_stock'] );
					$('.wc_status_list .out-of-stock a').html( data['wc_stats']['no_stock'] );
				});
			");
		}

		do_action( 'woocommerce_heartbeat_admin_scripts' );
	}

	/**
	 * Enqueue heartbeat JS on frontend
	 * @return void
	 */
	public static function frontend_scripts() {
		// Make sure the heartbeat is loaded
		wp_enqueue_script( 'heartbeat' );

		do_action( 'woocommerce_heartbeat_frontend_scripts' );
	}
}

return new WC_Heartbeat;