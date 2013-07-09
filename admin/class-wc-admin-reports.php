<?php
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Reports
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Admin_Reports Class
 */
class WC_Admin_Reports {

	private $start_date;
	private $end_date;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'admin_menu', array( $this, 'add_menu_item' ), 20 );
		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * Add menu item
	 */
	public function add_menu_item() {
		add_submenu_page( 'woocommerce', __( 'Reports', 'woocommerce' ),  __( 'Reports', 'woocommerce' ) , 'view_woocommerce_reports', 'wc_reports', array( $this, 'admin_page' ) );
	}

	/**
	 * Add screen ID
	 * @param array $ids
	 */
	public function add_screen_id( $ids ) {
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );
		$ids[]        = $wc_screen_id . '_page_wc_reports';
		return $ids;
	}

	/**
	 * Script and styles
	 */
	public function scripts_and_styles() {
		$screen       = get_current_screen();
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( in_array( $screen->id, apply_filters( 'woocommerce_reports_screen_ids', array( $wc_screen_id . '_page_wc_reports' ) ) ) ) {
			wp_enqueue_script( 'wc-reports', WC()->plugin_url() . '/assets/js/admin/reports' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0' );
			wp_enqueue_script( 'flot', WC()->plugin_url() . '/assets/js/admin/jquery.flot' . $suffix . '.js', array( 'jquery' ), '1.0' );
			wp_enqueue_script( 'flot-resize', WC()->plugin_url() . '/assets/js/admin/jquery.flot.resize' . $suffix . '.js', array('jquery', 'flot'), '1.0' );
			wp_enqueue_script( 'flot-time', WC()->plugin_url() . '/assets/js/admin/jquery.flot.time' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
			wp_enqueue_script( 'flot-pie', WC()->plugin_url() . '/assets/js/admin/jquery.flot.pie' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
			wp_enqueue_script( 'flot-stack', WC()->plugin_url() . '/assets/js/admin/jquery.flot.stack' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
		}
	}

	/**
	 * Returns the definitions for the reports to show in admin.
	 *
	 * @return array
	 */
	public function get_reports() {
		$reports = array(
			'orders'     => array(
				'title'  => __( 'Orders', 'woocommerce' ),
				'reports' => array(
					"sales_by_date"    => array(
						'title'       => __( 'Sales by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"sales_by_product"     => array(
						'title'       => __( 'Sales by product', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"sales_by_category" => array(
						'title'       => __( 'Sales by category', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"coupon_usage" => array(
						'title'       => __( 'Coupons by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					)
				)
			),
			'customers' => array(
				'title'  => __( 'Customers', 'woocommerce' ),
				'reports' => array(
					"customers" => array(
						'title'       => __( 'Customers vs. Guests', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"customer_list" => array(
						'title'       => __( 'Customer List', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
				)
			),
			'stock'     => array(
				'title'  => __( 'Stock', 'woocommerce' ),
				'reports' => array(
					"low_in_stock" => array(
						'title'       => __( 'Low in stock', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"out_of_stock" => array(
						'title'       => __( 'Out of stock', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
					"most_stocked" => array(
						'title'       => __( 'Most Stocked', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'get_report' )
					),
				)
			)
		);

		if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
			$reports['taxes'] = array(
				'title'  => __( 'Tax', 'woocommerce' ),
				'reports' => array(
					"taxes_by_month" => array(
						'title'       => __( 'Taxes by month', 'woocommerce' ),
						'description' => '',
						'callback'    => 'woocommerce_monthly_taxes'
					)
				)
			);
		}

		$reports = apply_filters( 'woocommerce_admin_reports', $reports );

		// Backwards compat
		$reports = apply_filters( 'woocommerce_reports_charts', $reports );

		foreach ( $reports as $key => $report_group ) {
			if ( isset( $reports[ $key ]['charts'] ) )
				$reports[ $key ]['reports'] = $reports[ $key ]['charts'];

			foreach ( $reports[ $key ]['reports'] as $report_key => $report ) {
				if ( isset( $reports[ $key ]['reports'][ $report_key ]['function'] ) )
					$reports[ $key ]['reports'][ $report_key ]['callback'] = $reports[ $key ]['reports'][ $report_key ]['function'];
			}
		}

		return $reports;
	}

	/**
	 * Handles output of the reports page in admin.
	 */
	public function admin_page() {
		$reports        = $this->get_reports();
		$first_tab      = array_keys( $reports );
		$current_tab    = ! empty( $_GET['tab'] ) ? sanitize_title( urldecode( $_GET['tab'] ) ) : $first_tab[0];
		$current_report = isset( $_GET['report'] ) ? sanitize_title( urldecode( $_GET['report'] ) ) : current( array_keys( $reports[ $current_tab ]['reports'] ) );

		include_once( 'reports/class-wc-admin-report.php' );
		include_once( 'views/html-admin-page-reports.php' );
	}

	/**
	 * Get a report from our reports subfolder
	 */
	public function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Report_' . str_replace( '-', '_', $name );

		include_once( 'reports/class-wc-report-' . $name . '.php' );

		if ( ! class_exists( $class ) )
			return;

		$report = new $class();
		$report->output_report();
	}
}

new WC_Admin_Reports();
















/**
 * Output the monthly tax stats.
 *
 * @access public
 * @return void
 */
function woocommerce_monthly_taxes() {
	global $start_date, $end_date, $woocommerce, $wpdb;

	$first_year = $wpdb->get_var( "SELECT post_date FROM $wpdb->posts WHERE post_date != 0 ORDER BY post_date ASC LIMIT 1;" );

	if ( $first_year )
		$first_year = date( 'Y', strtotime( $first_year ) );
	else
		$first_year = date( 'Y' );

	$current_year 	= isset( $_POST['show_year'] ) 	? $_POST['show_year'] 	: date( 'Y', current_time( 'timestamp' ) );
	$start_date 	= strtotime( $current_year . '0101' );

	$total_tax = $total_sales_tax = $total_shipping_tax = $count = 0;
	$taxes = $tax_rows = $tax_row_labels = array();

	for ( $count = 0; $count < 12; $count++ ) {

		$time = strtotime( date('Ym', strtotime( '+ ' . $count . ' MONTH', $start_date ) ) . '01' );

		if ( $time > current_time( 'timestamp' ) )
			continue;

		$month = date( 'Ym', strtotime( date( 'Ym', strtotime( '+ ' . $count . ' MONTH', $start_date ) ) . '01' ) );

		$gross = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_total'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s					= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$shipping = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_shipping'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s		 			= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$order_tax = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_tax'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s		 			= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$shipping_tax = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_shipping_tax'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s		 			= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$tax_rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT
				order_items.order_item_name as name,
				SUM( order_item_meta.meta_value ) as tax_amount,
				SUM( order_item_meta_2.meta_value ) as shipping_tax_amount,
				SUM( order_item_meta.meta_value + order_item_meta_2.meta_value ) as total_tax_amount

			FROM 		{$wpdb->prefix}woocommerce_order_items as order_items

			LEFT JOIN 	{$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN 	{$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id

			LEFT JOIN 	{$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			LEFT JOIN 	{$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
			LEFT JOIN 	{$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN 	{$wpdb->terms} AS term USING( term_id )

			WHERE 		order_items.order_item_type = 'tax'
			AND 		posts.post_type 	= 'shop_order'
			AND 		posts.post_status 	= 'publish'
			AND 		tax.taxonomy		= 'shop_order_status'
			AND			term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND			%s = date_format( posts.post_date,'%%Y%%m' )
			AND 		order_item_meta.meta_key = 'tax_amount'
			AND 		order_item_meta_2.meta_key = 'shipping_tax_amount'

			GROUP BY 	order_items.order_item_name
		", $month ) );

		if ( $tax_rows ) {
			foreach ( $tax_rows as $tax_row ) {
				if ( $tax_row->total_tax_amount > 0 )
					$tax_row_labels[] = $tax_row->name;
			}
		}

		$taxes[ date( 'M', strtotime( $month . '01' ) ) ] = array(
			'gross'			=> $gross,
			'shipping'		=> $shipping,
			'order_tax' 	=> $order_tax,
			'shipping_tax' 	=> $shipping_tax,
			'total_tax' 	=> $shipping_tax + $order_tax,
			'tax_rows'		=> $tax_rows
		);

		$total_sales_tax += $order_tax;
		$total_shipping_tax += $shipping_tax;
	}
	$total_tax = $total_sales_tax + $total_shipping_tax;
	?>
	<form method="post" action="">
		<p><label for="show_year"><?php _e( 'Year:', 'woocommerce' ); ?></label>
		<select name="show_year" id="show_year">
			<?php
				for ( $i = $first_year; $i <= date('Y'); $i++ )
					printf( '<option value="%s" %s>%s</option>', $i, selected( $current_year, $i, false ), $i );
			?>
		</select> <input type="submit" class="button" value="<?php _e( 'Show', 'woocommerce' ); ?>" /></p>
	</form>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e( 'Total taxes for year', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php
						if ( $total_tax > 0 )
							echo woocommerce_price( $total_tax );
						else
							_e( 'n/a', 'woocommerce' );
					?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total product taxes for year', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php
						if ( $total_sales_tax > 0 )
							echo woocommerce_price( $total_sales_tax );
						else
							_e( 'n/a', 'woocommerce' );
					?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total shipping tax for year', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php
						if ( $total_shipping_tax > 0 )
							echo woocommerce_price( $total_shipping_tax );
						else
							_e( 'n/a', 'woocommerce' );
					?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e( 'Month', 'woocommerce' ); ?></th>
						<th class="total_row"><?php _e( 'Total Sales', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Order Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Shipping', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Product Taxes', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Cart Tax' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Shipping Taxes', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Tax' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Taxes', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Cart Tax' and 'Shipping Tax' fields within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Net profit', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("Total sales minus shipping and tax.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<?php
							$tax_row_labels = array_filter( array_unique( $tax_row_labels ) );
							foreach ( $tax_row_labels as $label )
								echo '<th class="tax_row">' . $label . '</th>';
						?>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<?php
							$total = array();

							foreach ( $taxes as $month => $tax ) {
								$total['gross'] = isset( $total['gross'] ) ? $total['gross'] + $tax['gross'] : $tax['gross'];
								$total['shipping'] = isset( $total['shipping'] ) ? $total['shipping'] + $tax['shipping'] : $tax['shipping'];
								$total['order_tax'] = isset( $total['order_tax'] ) ? $total['order_tax'] + $tax['order_tax'] : $tax['order_tax'];
								$total['shipping_tax'] = isset( $total['shipping_tax'] ) ? $total['shipping_tax'] + $tax['shipping_tax'] : $tax['shipping_tax'];
								$total['total_tax'] = isset( $total['total_tax'] ) ? $total['total_tax'] + $tax['total_tax'] : $tax['total_tax'];

								foreach ( $tax_row_labels as $label )
									foreach ( $tax['tax_rows'] as $tax_row )
										if ( $tax_row->name == $label ) {
											$total['tax_rows'][ $label ] = isset( $total['tax_rows'][ $label ] ) ? $total['tax_rows'][ $label ] + $tax_row->total_tax_amount : $tax_row->total_tax_amount;
										}

							}

							echo '
								<td>' . __( 'Total', 'woocommerce' ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['gross'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['shipping'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['order_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['shipping_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['total_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['gross'] - $total['shipping'] - $total['total_tax'] ) . '</td>';

							foreach ( $tax_row_labels as $label )
								if ( isset( $total['tax_rows'][ $label ] ) )
									echo '<td class="tax_row">' . woocommerce_price( $total['tax_rows'][ $label ] ) . '</td>';
								else
									echo '<td class="tax_row">' .  woocommerce_price( 0 ) . '</td>';
						?>
					</tr>
					<tr>
						<th colspan="<?php echo 7 + sizeof( $tax_row_labels ); ?>"><button class="button toggle_tax_rows"><?php _e( 'Toggle tax rows', 'woocommerce' ); ?></button></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
						foreach ( $taxes as $month => $tax ) {
							$alt = ( isset( $alt ) && $alt == 'alt' ) ? '' : 'alt';
							echo '<tr class="' . $alt . '">
								<td>' . $month . '</td>
								<td class="total_row">' . woocommerce_price( $tax['gross'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['shipping'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['order_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['shipping_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['total_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['gross'] - $tax['shipping'] - $tax['total_tax'] ) . '</td>';



							foreach ( $tax_row_labels as $label ) {

								$row_total = 0;

								foreach ( $tax['tax_rows'] as $tax_row ) {
									if ( $tax_row->name == $label ) {
										$row_total = $tax_row->total_tax_amount;
									}
								}

								echo '<td class="tax_row">' . woocommerce_price( $row_total ) . '</td>';
							}

							echo '</tr>';
						}
					?>
				</tbody>
			</table>
			<script type="text/javascript">
				jQuery('.toggle_tax_rows').click(function(){
					jQuery('.tax_row').toggle();
					jQuery('.total_row').toggle();
				});
				jQuery('.tax_row').hide();
			</script>
		</div>
	</div>
	<?php
}

