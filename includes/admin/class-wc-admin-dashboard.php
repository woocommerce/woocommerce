<?php
/**
 * Admin Dashboard
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Admin_Dashboard' ) ) :

/**
 * WC_Admin_Dashboard Class.
 */
class WC_Admin_Dashboard {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Only hook in admin parts if the user has admin access
		if ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) ) {
			add_action( 'wp_dashboard_setup', array( $this, 'init' ) );
		}
	}

	/**
	 * Init dashboard widgets.
	 */
	public function init() {
		if ( current_user_can( 'publish_shop_orders' ) ) {
			wp_add_dashboard_widget( 'woocommerce_dashboard_recent_reviews', __( 'WooCommerce Recent Reviews', 'woocommerce' ), array( $this, 'recent_reviews' ) );
		}

		wp_add_dashboard_widget( 'woocommerce_dashboard_status', __( 'WooCommerce Status', 'woocommerce' ), array( $this, 'status_widget' ) );
	}

	/**
	 * Show status widget.
	 */
	public function status_widget() {
		global $wpdb;

		include_once( 'reports/class-wc-admin-report.php' );

		$reports = new WC_Admin_Report();

		// Sales
		$query            = array();
		$query['fields']  = "SELECT SUM( postmeta.meta_value ) FROM {$wpdb->posts} as posts";
		$query['join']    = "INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id LEFT JOIN {$wpdb->posts} AS parent ON posts.post_parent = parent.ID";
		$query['where']   = "WHERE posts.post_type IN ( '" . implode( "','", array_merge( wc_get_order_types( 'sales-reports' ), array( 'shop_order_refund' ) ) ) . "' ) ";
		$query['where']  .= "AND posts.post_status IN ( 'wc-" . implode( "','wc-", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "' ) ";
		$query['where']  .= "AND ( parent.post_status IN ( 'wc-" . implode( "','wc-", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "' ) OR parent.ID IS NULL ) ";
		$query['where']  .= "AND postmeta.meta_key   = '_order_total' ";
		$query['where']  .= "AND posts.post_date >= '" . date( 'Y-m-01', current_time( 'timestamp' ) ) . "' ";
		$query['where']  .= "AND posts.post_date <= '" . date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) . "' ";

		$sales = $wpdb->get_var( implode( ' ', apply_filters( 'woocommerce_dashboard_status_widget_sales_query', $query ) ) );

		// Get top seller
		$query            = array();
		$query['fields']  = "SELECT SUM( order_item_meta.meta_value ) as qty, order_item_meta_2.meta_value as product_id
			FROM {$wpdb->posts} as posts";
		$query['join']    = "INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_id ";
		$query['join']   .= "INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id ";
		$query['join']   .= "INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id ";
		$query['where']   = "WHERE posts.post_type IN ( '" . implode( "','", wc_get_order_types( 'order-count' ) ) . "' ) ";
		$query['where']  .= "AND posts.post_status IN ( 'wc-" . implode( "','wc-", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "' ) ";
		$query['where']  .= "AND order_item_meta.meta_key = '_qty' ";
		$query['where']  .= "AND order_item_meta_2.meta_key = '_product_id' ";
		$query['where']  .= "AND posts.post_date >= '" . date( 'Y-m-01', current_time( 'timestamp' ) ) . "' ";
		$query['where']  .= "AND posts.post_date <= '" . date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) . "' ";
		$query['groupby'] = "GROUP BY product_id";
		$query['orderby'] = "ORDER BY qty DESC";
		$query['limits']  = "LIMIT 1";

		$top_seller = $wpdb->get_row( implode( ' ', apply_filters( 'woocommerce_dashboard_status_widget_top_seller_query', $query ) ) );

		// Counts
		$on_hold_count    = 0;
		$processing_count = 0;

		foreach ( wc_get_order_types( 'order-count' ) as $type ) {
			$counts           = (array) wp_count_posts( $type );
			$on_hold_count    += isset( $counts['wc-on-hold'] ) ? $counts['wc-on-hold'] : 0;
			$processing_count += isset( $counts['wc-processing'] ) ? $counts['wc-processing'] : 0;
		}

		// Get products using a query - this is too advanced for get_posts :(
		$stock          = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
		$nostock        = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );
		$transient_name = 'wc_low_stock_count';

		if ( false === ( $lowinstock_count = get_transient( $transient_name ) ) ) {
			$query_from = apply_filters( 'woocommerce_report_low_in_stock_query_from', "FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
				INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
				WHERE 1=1
				AND posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status = 'publish'
				AND postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes'
				AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$stock}'
				AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) > '{$nostock}'
			" );
			$lowinstock_count = absint( $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" ) );
			set_transient( $transient_name, $lowinstock_count, DAY_IN_SECONDS * 30 );
		}

		$transient_name = 'wc_outofstock_count';

		if ( false === ( $outofstock_count = get_transient( $transient_name ) ) ) {
			$query_from = apply_filters( 'woocommerce_report_out_of_stock_query_from', "FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
				INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
				WHERE 1=1
				AND posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status = 'publish'
				AND postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes'
				AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$nostock}'
			" );
			$outofstock_count = absint( $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" ) );
			set_transient( $transient_name, $outofstock_count, DAY_IN_SECONDS * 30 );
		}
		?>
		<ul class="wc_status_list">
			<li class="sales-this-month">
				<a href="<?php echo admin_url( 'admin.php?page=wc-reports&tab=orders&range=month' ); ?>">
					<?php echo $reports->sales_sparkline( '', max( 7, date( 'd', current_time( 'timestamp' ) ) ) ); ?>
					<?php printf( __( "<strong>%s</strong> sales this month", 'woocommerce' ), wc_price( $sales ) ); ?>
				</a>
			</li>
			<?php if ( $top_seller && $top_seller->qty ) : ?>
				<li class="best-seller-this-month">
					<a href="<?php echo admin_url( 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=' . $top_seller->product_id ); ?>">
						<?php echo $reports->sales_sparkline( $top_seller->product_id, max( 7, date( 'd', current_time( 'timestamp' ) ) ), 'count' ); ?>
						<?php printf( __( "%s top seller this month (sold %d)", 'woocommerce' ), "<strong>" . get_the_title( $top_seller->product_id ) . "</strong>", $top_seller->qty ); ?>
					</a>
				</li>
			<?php endif; ?>
			<li class="processing-orders">
				<a href="<?php echo admin_url( 'edit.php?post_status=wc-processing&post_type=shop_order' ); ?>">
					<?php printf( _n( "<strong>%s order</strong> awaiting processing", "<strong>%s orders</strong> awaiting processing", $processing_count, 'woocommerce' ), $processing_count ); ?>
				</a>
			</li>
			<li class="on-hold-orders">
				<a href="<?php echo admin_url( 'edit.php?post_status=wc-on-hold&post_type=shop_order' ); ?>">
					<?php printf( _n( "<strong>%s order</strong> on-hold", "<strong>%s orders</strong> on-hold", $on_hold_count, 'woocommerce' ), $on_hold_count ); ?>
				</a>
			</li>
			<li class="low-in-stock">
				<a href="<?php echo admin_url( 'admin.php?page=wc-reports&tab=stock&report=low_in_stock' ); ?>">
					<?php printf( _n( "<strong>%s product</strong> low in stock", "<strong>%s products</strong> low in stock", $lowinstock_count, 'woocommerce' ), $lowinstock_count ); ?>
				</a>
			</li>
			<li class="out-of-stock">
				<a href="<?php echo admin_url( 'admin.php?page=wc-reports&tab=stock&report=out_of_stock' ); ?>">
					<?php printf( _n( "<strong>%s product</strong> out of stock", "<strong>%s products</strong> out of stock", $outofstock_count, 'woocommerce' ), $outofstock_count ); ?>
				</a>
			</li>

			<?php do_action( 'woocommerce_after_dashboard_status_widget', $reports ); ?>
		</ul>
		<?php
	}

	/**
	 * Recent reviews widget.
	 */
	public function recent_reviews() {
		global $wpdb;
		$comments = $wpdb->get_results( "SELECT *, SUBSTRING(comment_content,1,100) AS comment_excerpt
		FROM $wpdb->comments
		LEFT JOIN $wpdb->posts ON ($wpdb->comments.comment_post_ID = $wpdb->posts.ID)
		WHERE comment_approved = '1'
		AND comment_type = ''
		AND post_password = ''
		AND post_type = 'product'
		ORDER BY comment_date_gmt DESC
		LIMIT 8" );

		if ( $comments ) {
			echo '<ul>';
			foreach ( $comments as $comment ) {

				echo '<li>';

				echo get_avatar( $comment->comment_author, '32' );

				$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

				echo '<div class="star-rating" title="' . esc_attr( $rating ) . '">
					<span style="width:' . ( $rating * 20 ) . '%">' . $rating . ' ' . __( 'out of 5', 'woocommerce' ) . '</span></div>';

				echo '<h4 class="meta"><a href="' . get_permalink( $comment->ID ) . '#comment-' . absint( $comment->comment_ID ) .'">' . esc_html( apply_filters( 'woocommerce_admin_dashboard_recent_reviews', $comment->post_title, $comment ) ) . '</a> ' . __( 'reviewed by', 'woocommerce' ) . ' ' . esc_html( $comment->comment_author ) .'</h4>';
				echo '<blockquote>' . wp_kses_data( $comment->comment_excerpt ) . ' [...]</blockquote></li>';

			}
			echo '</ul>';
		} else {
			echo '<p>' . __( 'There are no product reviews yet.', 'woocommerce' ) . '</p>';
		}
	}

}

endif;

return new WC_Admin_Dashboard();
