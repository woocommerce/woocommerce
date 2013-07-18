<?php
/**
 * WC_Report_Taxes_By_Date class
 */
class WC_Report_Taxes_By_Date extends WC_Admin_Report {

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		$legend   = array();

		return array();
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : 'last_month';
		?>
		<a
			href="#"
			download="report-<?php echo $current_range; ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="table"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		global $woocommerce, $wpdb, $wp_locale;

		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_month'   => __( 'Last Month', 'woocommerce' ),
			'month'        => __( 'This Month', 'woocommerce' ),
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : 'last_month';

		switch ( $current_range ) {
			case 'custom' :
				$this->start_date = strtotime( sanitize_text_field( $_GET['start_date'] ) );
				$this->end_date   = strtotime( '12am + 1 day', strtotime( sanitize_text_field( $_GET['end_date'] ) ) );

				if ( ! $this->end_date )
					$this->end_date = current_time('timestamp');

				$interval = 0;
				$min_date = $this->start_date;
				while ( ( $min_date = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
				    $interval ++;
				}

				// 3 months max for day view
				if ( $interval > 3 )
					$this->chart_groupby         = 'month';
				else
					$this->chart_groupby         = 'day';
			break;
			case 'year' :
				$this->start_date = strtotime( 'first day of january', current_time('timestamp') );
				$this->end_date   = strtotime( '12am + 1 day', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'month';
			break;
			default :
			case 'last_month' :
				$this->start_date = strtotime( 'first day of last month', current_time('timestamp') );
				$this->end_date   = strtotime( 'last day of last month', current_time('timestamp') );
				$this->chart_groupby         = 'day';
			break;
			case 'month' :
				$this->start_date = strtotime( 'first day of this month', current_time('timestamp') );
				$this->end_date   = strtotime( '12am + 1 day', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'day';
			break;
		}

		// Group by
		switch ( $this->chart_groupby ) {
			case 'day' :
				$this->group_by_query       = 'YEAR(post_date), MONTH(post_date), DAY(post_date)';
				$this->chart_interval       = max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) );
				$this->barwidth             = 60 * 60 * 24 * 1000;
			break;
			case 'month' :
				$this->group_by_query       = 'YEAR(post_date), MONTH(post_date)';
				$this->chart_interval = 0;
				$min_date             = $this->start_date;
				while ( ( $min_date   = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
					$this->chart_interval ++;
				}
				$this->barwidth             = 60 * 60 * 24 * 7 * 4 * 1000;
			break;
		}

		$hide_sidebar = true;

		include( WC()->plugin_path() . '/admin/views/html-report-by-date.php');
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wpdb;

		$tax_rows = $this->get_order_report_data( array(
			'data' => array(
				'_order_tax' => array(
					'type'            => 'meta',
					'function'        => 'SUM',
					'name'            => 'tax_amount'
				),
				'_order_shipping_tax' => array(
					'type'            => 'meta',
					'function'        => 'SUM',
					'name'            => 'shipping_tax_amount'
				),
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_sales'
				),
				'_order_shipping' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_shipping'
				),
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders',
					'distinct' => true,
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Period', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Number of orders', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Total Sales', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Order Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
					<th class="total_row"><?php _e( 'Total Shipping', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
					<th class="total_row"><?php _e( 'Total Tax', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'This is the total tax for the rate (shipping tax + product tax).', 'woocommerce' ); ?>" href="#">[?]</a></th>
					<th class="total_row"><?php _e( 'Net profit', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("Total sales minus shipping and tax.", 'woocommerce'); ?>" href="#">[?]</a></th>
				</tr>
			</thead>
			<?php if ( $tax_rows ) :
				$gross = array_sum( wp_list_pluck( (array) $tax_rows, 'total_sales' ) ) - array_sum( wp_list_pluck( (array) $tax_rows, 'total_shipping' ) );
				$total_tax = array_sum( wp_list_pluck( (array) $tax_rows, 'tax_amount' ) ) - array_sum( wp_list_pluck( (array) $tax_rows, 'shipping_tax_amount' ) );
				?>
				<tfoot>
					<tr>
						<th scope="row"><?php _e( 'Totals', 'woocommerce' ); ?></th>
						<th class="total_row"><?php echo array_sum( wp_list_pluck( (array) $tax_rows, 'total_orders' ) ); ?></th>
						<th class="total_row"><?php echo woocommerce_price( $gross ); ?></th>
						<th class="total_row"><?php echo woocommerce_price( array_sum( wp_list_pluck( (array) $tax_rows, 'total_shipping' ) ) ); ?></th>
						<th class="total_row"><?php echo woocommerce_price( $total_tax ); ?></th>
						<th class="total_row"><?php echo woocommerce_price( $gross - $total_tax ); ?></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
					foreach ( $tax_rows as $tax_row ) {
						$gross     = $tax_row->total_sales - $tax_row->total_shipping;
						$total_tax = $tax_row->tax_amount + $tax_row->shipping_tax_amount;
						?>
						<tr>
							<th scope="row"><?php
								if ( $this->chart_groupby == 'month' )
									echo date_i18n( 'F', strtotime( $tax_row->post_date ) );
								else
									echo date_i18n( get_option( 'date_format' ), strtotime( $tax_row->post_date ) );
							?></th>
							<td class="total_row"><?php echo $tax_row->total_orders; ?></td>
							<td class="total_row"><?php echo woocommerce_price( $gross ); ?></td>
							<td class="total_row"><?php echo woocommerce_price( $tax_row->total_shipping ); ?></td>
							<td class="total_row"><?php echo woocommerce_price( $total_tax ); ?></td>
							<td class="total_row"><?php echo woocommerce_price( $gross - $total_tax ); ?></td>
						</tr>
						<?php
					}
					?>
				</tbody>
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