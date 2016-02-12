<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Report_Taxes_By_Code
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class WC_Report_Taxes_By_Code extends WC_Admin_Report {

	/**
	 * Get the legend for the main chart sidebar.
	 * @return array
	 */
	public function get_chart_legend() {
		return array();
	}

	/**
	 * Output an export link.
	 */
	public function get_export_button() {

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'last_month';
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="table"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_month'   => __( 'Last Month', 'woocommerce' ),
			'month'        => __( 'This Month', 'woocommerce' ),
		);

		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : 'last_month';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
			$current_range = 'last_month';
		}

		$this->calculate_current_range( $current_range );

		$hide_sidebar = true;

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}

	/**
	 * Get the main chart.
	 *
	 * @return string
	 */
	public function get_main_chart() {
		global $wpdb;

		$query_data = array(
			'order_item_name' => array(
				'type'     => 'order_item',
				'function' => '',
				'name'     => 'tax_rate'
			),
			'tax_amount' => array(
				'type'            => 'order_item_meta',
				'order_item_type' => 'tax',
				'function'        => '',
				'name'            => 'tax_amount'
			),
			'shipping_tax_amount' => array(
				'type'            => 'order_item_meta',
				'order_item_type' => 'tax',
				'function'        => '',
				'name'            => 'shipping_tax_amount'
			),
			'rate_id' => array(
				'type'            => 'order_item_meta',
				'order_item_type' => 'tax',
				'function'        => '',
				'name'            => 'rate_id'
			),
			'ID' => array(
				'type'     => 'post_data',
				'function' => '',
				'name'     => 'post_id'
			),
		);

		$query_where = array(
			array(
				'key'      => 'order_item_type',
				'value'    => 'tax',
				'operator' => '='
			),
			array(
				'key'      => 'order_item_name',
				'value'    => '',
				'operator' => '!='
			)
		);

		$tax_rows_orders = $this->get_order_report_data( array(
			'data'                => $query_data,
			'where'               => $query_where,
			'order_by'            => 'posts.post_date ASC',
			'query_type'          => 'get_results',
			'filter_range'        => true,
			'order_types'         => array_merge( wc_get_order_types( 'sales-reports' ), array( 'shop_order_refund' ) ),
			'order_status'        => array( 'completed', 'processing', 'on-hold' ),
			'parent_order_status' => array( 'completed', 'processing', 'on-hold' ) // Partial refunds inside refunded orders should be ignored
		) );

		// Merge
		$tax_rows = array();

		foreach ( $tax_rows_orders as $tax_row ) {
			$key              = $tax_row->rate_id;
			$tax_rows[ $key ] = isset( $tax_rows[ $key ] ) ? $tax_rows[ $key ] : (object) array( 'tax_amount' => 0, 'shipping_tax_amount' => 0, 'total_orders' => 0 );

			if ( 'shop_order_refund' !== get_post_type( $tax_row->post_id ) ) {
				$tax_rows[ $key ]->total_orders        += 1;
			}

			$tax_rows[ $key ]->tax_rate            = $tax_row->tax_rate;
			$tax_rows[ $key ]->tax_amount          += wc_round_tax_total( $tax_row->tax_amount );
			$tax_rows[ $key ]->shipping_tax_amount += wc_round_tax_total( $tax_row->shipping_tax_amount );
		}
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Tax', 'woocommerce' ); ?></th>
					<th><?php _e( 'Rate', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Number of Orders', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Tax Amount', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the sum of the "Tax Rows" tax amount within your orders.', 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Shipping Tax Amount', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the sum of the "Tax Rows" shipping tax amount within your orders.', 'woocommerce' ) ); ?></th>
					<th class="total_row"><?php _e( 'Total Tax', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the total tax for the rate (shipping tax + product tax).', 'woocommerce' ) ); ?></th>
				</tr>
			</thead>
			<?php if ( $tax_rows ) : ?>
				<tbody>
					<?php
					foreach ( $tax_rows as $rate_id => $tax_row ) {
						$rate = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d;", $rate_id ) );
						?>
						<tr>
							<th scope="row"><?php echo apply_filters( 'woocommerce_reports_taxes_tax_rate', $tax_row->tax_rate, $rate_id, $tax_row ); ?></th>
							<td><?php echo apply_filters( 'woocommerce_reports_taxes_rate', $rate, $rate_id, $tax_row ); ?>%</td>
							<td class="total_row"><?php echo $tax_row->total_orders; ?></td>
							<td class="total_row"><?php echo wc_price( $tax_row->tax_amount ); ?></td>
							<td class="total_row"><?php echo wc_price( $tax_row->shipping_tax_amount ); ?></td>
							<td class="total_row"><?php echo wc_price( $tax_row->tax_amount + $tax_row->shipping_tax_amount ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th scope="row" colspan="3"><?php _e( 'Total', 'woocommerce' ); ?></th>
						<th class="total_row"><?php echo wc_price( wc_round_tax_total( array_sum( wp_list_pluck( (array) $tax_rows, 'tax_amount' ) ) ) ); ?></th>
						<th class="total_row"><?php echo wc_price( wc_round_tax_total( array_sum( wp_list_pluck( (array) $tax_rows, 'shipping_tax_amount' ) ) ) ); ?></th>
						<th class="total_row"><strong><?php echo wc_price( wc_round_tax_total( array_sum( wp_list_pluck( (array) $tax_rows, 'tax_amount' ) ) + array_sum( wp_list_pluck( (array) $tax_rows, 'shipping_tax_amount' ) ) ) ); ?></strong></th>
					</tr>
				</tfoot>
			<?php else : ?>
				<tbody>
					<tr>
						<td><?php _e( 'No taxes found in this period', 'woocommerce' ); ?></td>
					</tr>
				</tbody>
			<?php endif; ?>
		</table>
		<?php
	}
}
