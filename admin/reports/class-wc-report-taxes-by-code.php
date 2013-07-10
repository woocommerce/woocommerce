<?php
/**
 * WC_Report_Taxes_By_Code class
 */
class WC_Report_Taxes_By_Code extends WC_Admin_Report {

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		$legend   = array();

		return array();
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
				'order_item_name' => array(
					'type'     => 'order_item',
					'function' => '',
					'name'     => 'tax_rate'
				),
				'tax_amount' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'tax',
					'function'        => 'SUM',
					'name'            => 'tax_amount'
				),
				'shipping_tax_amount' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'tax',
					'function'        => 'SUM',
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
					'function' => 'COUNT',
					'name'     => 'total_orders',
					'distinct' => true,
				),
			),
			'where' => array(
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
			),
			'group_by'     => 'tax_rate',
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Tax', 'woocommerce' ); ?></th>
					<th><?php _e( 'Rate', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Number of orders', 'woocommerce' ); ?></th>
					<th class="total_row"><?php _e( 'Tax Amount', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'This is the sum of the "Tax Rows" tax amount within your orders.', 'woocommerce' ); ?>" href="#">[?]</a></th>
					<th class="total_row"><?php _e( 'Shipping Tax Amount', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'This is the sum of the "Tax Rows" shipping tax amount within your orders.', 'woocommerce' ); ?>" href="#">[?]</a></th>
					<th class="total_row"><?php _e( 'Total Tax', 'woocommerce' ); ?> <a class="tips" data-tip="<?php esc_attr_e( 'This is the total tax for the rate (shipping tax + product tax).', 'woocommerce' ); ?>" href="#">[?]</a></th>
				</tr>
			</thead>
			<?php if ( $tax_rows ) : ?>
				<tfoot>
					<tr>
						<th scope="row" colspan="3"><?php _e( 'Total', 'woocommerce' ); ?></th>
						<th class="total_row"><?php echo woocommerce_price( array_sum( wp_list_pluck( (array) $tax_rows, 'tax_amount' ) ) ); ?></th>
						<th class="total_row"><?php echo woocommerce_price( array_sum( wp_list_pluck( (array) $tax_rows, 'shipping_tax_amount' ) ) ); ?></th>
						<th class="total_row"><strong><?php echo woocommerce_price( array_sum( wp_list_pluck( (array) $tax_rows, 'tax_amount' ) ) + array_sum( wp_list_pluck( (array) $tax_rows, 'shipping_tax_amount' ) ) ); ?></strong></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
					foreach ( $tax_rows as $tax_row ) {
						$rate = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d;", $tax_row->rate_id ) );
						?>
						<tr>
							<th scope="row"><?php echo $tax_row->tax_rate; ?></th>
							<td><?php echo $rate; ?>%</td>
							<td class="total_row"><?php echo $tax_row->total_orders; ?></td>
							<td class="total_row"><?php echo woocommerce_price( $tax_row->tax_amount ); ?></td>
							<td class="total_row"><?php echo woocommerce_price( $tax_row->shipping_tax_amount ); ?></td>
							<td class="total_row"><?php echo woocommerce_price( $tax_row->tax_amount + $tax_row->shipping_tax_amount ); ?></td>
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