<?php
/**
 * Functions used for displaying the WooCommerce dashboard widgets
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Dashboard
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Only hook in admin parts if the user has admin access
if ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'publish_shop_orders' ) )
	add_action( 'wp_dashboard_setup', 'woocommerce_init_dashboard_widgets' );

/**
 * Init the dashboard widgets.
 *
 * @access public
 * @return void
 */
function woocommerce_init_dashboard_widgets() {

	global $current_month_offset, $the_month_num, $the_year;

	$current_month_offset = 0;

	if (isset($_GET['wc_sales_month'])) $current_month_offset = (int) $_GET['wc_sales_month'];

	$the_month_num 	= date('n', strtotime('NOW '.($current_month_offset).' MONTH'));
	$the_year 		= date('Y', strtotime('NOW '.($current_month_offset).' MONTH'));

	$sales_heading = '';

	if ($the_month_num!=date('m')) :
		$sales_heading .= '<a href="index.php?wc_sales_month='.($current_month_offset+1).'" class="next">'.date_i18n('F', strtotime('01-'.($the_month_num+1).'-2011')).' &rarr;</a>';
	endif;

	$sales_heading .= '<a href="index.php?wc_sales_month='.($current_month_offset-1).'" class="previous">&larr; '.date_i18n('F', strtotime('01-'.($the_month_num-1).'-2011')).'</a><span>'.__( 'Monthly Sales', 'woocommerce' ).'</span>';

	if ( current_user_can( 'publish_shop_orders' ) ) {
		wp_add_dashboard_widget( 'woocommerce_dashboard_right_now', __( 'WooCommerce Right Now', 'woocommerce' ), 'woocommerce_dashboard_widget_right_now' );
		wp_add_dashboard_widget( 'woocommerce_dashboard_recent_orders', __( 'WooCommerce Recent Orders', 'woocommerce' ), 'woocommerce_dashboard_recent_orders');
		wp_add_dashboard_widget( 'woocommerce_dashboard_recent_reviews', __( 'WooCommerce Recent Reviews', 'woocommerce' ), 'woocommerce_dashboard_recent_reviews' );
	}

	if ( current_user_can( 'view_woocommerce_reports' ) || current_user_can( 'publish_shop_orders' ) ) {
		//wp_add_dashboard_widget( 'woocommerce_dashboard_sales', $sales_heading, 'woocommerce_dashboard_sales' );
	}

}


/**
 * WooCommerce Right Now widget.
 *
 * Adds a dashboard widget with shop statistics.
 *
 * @access public
 * @return void
 */
function woocommerce_dashboard_widget_right_now() {
	global $woocommerce;

	$product_count      = wp_count_posts( 'product' );
	$product_cat_count  = wp_count_terms( 'product_cat' );
	$product_tag_count  = wp_count_terms( 'product_tag' );
	$product_attr_count = count( $woocommerce->get_helper( 'attribute' )->get_attribute_taxonomies() );

	$pending_count      = get_term_by( 'slug', 'pending', 'shop_order_status' )->count;
	$completed_count    = get_term_by( 'slug', 'completed', 'shop_order_status' )->count;
	$on_hold_count      = get_term_by( 'slug', 'on-hold', 'shop_order_status' )->count;
	$processing_count   = get_term_by( 'slug', 'processing', 'shop_order_status' )->count;
	?>

	<div class="table table_shop_content">
		<p class="sub woocommerce_sub"><?php _e( 'Shop Content', 'woocommerce' ); ?></p>
		<table>
			<tr class="first">

				<?php
					$num  = number_format_i18n( $product_count->publish );
					$text = _n( 'Product', 'Products', intval( $product_count->publish ), 'woocommerce' );
					$link = add_query_arg( array( 'post_type' => 'product' ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="first b b-products"><?php echo $num; ?></td>
				<td class="t products"><?php echo $text; ?></td>
			</tr>

			<tr>

				<?php
					$num  = number_format_i18n( $product_cat_count );
					$text = _n( 'Product Category', 'Product Categories', $product_cat_count, 'woocommerce' );
					$link = add_query_arg( array( 'taxonomy' => 'product_cat', 'post_type' => 'product' ), get_admin_url( null, 'edit-tags.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="first b b-product_cats"><?php echo $num; ?></td>
				<td class="t product_cats"><?php echo $text; ?></td>
			</tr>

			<tr>

				<?php
					$num  = number_format_i18n( $product_tag_count );
					$text = _n( 'Product Tag', 'Product Tags', $product_tag_count, 'woocommerce' );
					$link = add_query_arg( array( 'taxonomy' => 'product_tag', 'post_type' => 'product' ), get_admin_url( null, 'edit-tags.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="first b b-product_tag"><?php echo $num; ?></td>
				<td class="t product_tag"><?php echo $text; ?></td>
			</tr>

			<tr>

				<?php
					$num  = number_format_i18n( $product_attr_count );
					$text = _n( 'Attribute', 'Attributes', $product_attr_count, 'woocommerce' );
					$link = add_query_arg( array( 'page' => 'woocommerce_attributes' ), get_admin_url( null, 'admin.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="first b b-attributes"><?php echo $num; ?></td>
				<td class="t attributes"><?php echo $text; ?></td>
			</tr>

			<?php do_action( 'woocommerce_right_now_shop_content_table_end' ); ?>

		</table>
	</div>

	<div class="table table_orders">
		<p class="sub woocommerce_sub"><?php _e( 'Orders', 'woocommerce' ); ?></p>
		<table>
			<tr class="first">

				<?php
					$num  = number_format_i18n( $pending_count );
					$text = __( 'Pending', 'woocommerce' );
					$link = add_query_arg( array( 'post_type' => 'shop_order', 'shop_order_status' => 'pending' ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="b b-pending"><?php echo $num; ?></td>
				<td class="last t pending"><?php echo $text; ?></td>
			</tr>

			<tr>

				<?php
					$num  = number_format_i18n( $on_hold_count );
					$text = __( 'On-Hold', 'woocommerce' );
					$link = add_query_arg( array( 'post_type' => 'shop_order', 'shop_order_status' => 'on-hold' ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="b b-on-hold"><?php echo $num; ?></td>
				<td class="last t on-hold"><?php echo $text; ?></td>
			</tr>

			<tr>

				<?php
					$num  = number_format_i18n( $processing_count );
					$text = __( 'Processing', 'woocommerce' );
					$link = add_query_arg( array( 'post_type' => 'shop_order', 'shop_order_status' => 'processing' ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="b b-processing"><?php echo $num; ?></td>
				<td class="last t processing"><?php echo $text; ?></td>
			</tr>

			<tr>

				<?php
					$num  = number_format_i18n( $completed_count );
					$text = __( 'Completed', 'woocommerce' );
					$link = add_query_arg( array( 'post_type' => 'shop_order', 'shop_order_status' => 'completed' ), get_admin_url( null, 'edit.php' ) );
					$num  = '<a href="' . esc_url($link ) . '">' . esc_html( $num )  . '</a>';
					$text = '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
				?>

				<td class="b b-completed"><?php echo $num; ?></td>
				<td class="last t completed"><?php echo $text; ?></td>
			</tr>

			<?php do_action( 'woocommerce_right_now_orders_table_end' ); ?>

		</table>
	</div>

	<div class="versions">
		<p id="wp-version-message">
			<?php printf( __( 'You are using <strong>WooCommerce %s</strong>.', 'woocommerce' ), $woocommerce->version ); ?>
		</p>
	</div>
	<?php
}


/**
 * Recent orders widget
 *
 * @access public
 * @return void
 */
function woocommerce_dashboard_recent_orders() {

	$args = array(
	    'numberposts'     => 8,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish'
	);
	$orders = get_posts( $args );
	if ($orders) :
		echo '<ul class="recent-orders">';
		foreach ($orders as $order) :

			$this_order = new WC_Order( $order->ID );

			echo '
			<li>
				<span class="order-status ' . sanitize_title( $this_order->status ) . '">' . ucwords( __( $this_order->status, 'woocommerce' ) ) . '</span> <a href="' . admin_url( 'post.php?post=' . $order->ID ) . '&action=edit">' . get_the_time( __( 'l jS \of F Y h:i:s A', 'woocommerce' ), $order->ID ) . '</a><br />
				<small>' . $this_order->get_item_count() . ' ' . _n( 'item', 'items', $this_order->get_item_count(), 'woocommerce' ) . ' <span class="order-cost">'.__('Total:', 'woocommerce' ) . ' ' . woocommerce_price( $this_order->order_total ).'</span></small>
			</li>';

		endforeach;
		echo '</ul>';
	else:
		echo '<p>' . __( 'There are no product orders yet.', 'woocommerce' ) . '</p>';
	endif;
}


/**
 * Recent reviews widget
 *
 * @access public
 * @return void
 */
function woocommerce_dashboard_recent_reviews() {
	global $wpdb;
	$comments = $wpdb->get_results( "SELECT *, SUBSTRING(comment_content,1,100) AS comment_excerpt
	FROM $wpdb->comments
	LEFT JOIN $wpdb->posts ON ($wpdb->comments.comment_post_ID = $wpdb->posts.ID)
	WHERE comment_approved = '1'
	AND comment_type = ''
	AND post_password = ''
	AND post_type = 'product'
	ORDER BY comment_date_gmt DESC
	LIMIT 5" );

	if ( $comments ) {
		echo '<ul>';
		foreach ( $comments as $comment ) {

			echo '<li>';

			echo get_avatar( $comment->comment_author, '32' );

			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );

			echo '<div class="star-rating" title="' . $rating . '">
				<span style="width:'. ( $rating * 20 ) . '%">' . $rating . ' ' . __( 'out of 5', 'woocommerce' ) . '</span></div>';

			echo '<h4 class="meta"><a href="' . get_permalink( $comment->ID ) . '#comment-' . absint( $comment->comment_ID ) .'">' . esc_html__( $comment->post_title ) . '</a> reviewed by ' . esc_html( $comment->comment_author ) .'</h4>';
			echo '<blockquote>' . wp_kses_data( $comment->comment_excerpt ) . ' [...]</blockquote></li>';

		}
		echo '</ul>';
	} else {
		echo '<p>' . __( 'There are no product reviews yet.', 'woocommerce' ) . '</p>';
	}
}