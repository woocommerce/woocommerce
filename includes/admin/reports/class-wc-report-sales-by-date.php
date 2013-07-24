<?php
/**
 * WC_Report_Sales_By_Date class
 */
class WC_Report_Sales_By_Date extends WC_Admin_Report {

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		$legend   = array();

		$order_totals = $this->get_order_report_data( array(
			'data' => array(
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
					'name'     => 'total_orders'
				)
			),
			'filter_range' => true
		) );
		$total_sales    = $order_totals->total_sales;
		$total_shipping = $order_totals->total_shipping;
		$total_orders   = absint( $order_totals->total_orders );
		$total_items    = absint( $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				)
			),
			'query_type' => 'get_var',
			'filter_range' => true
		) ) );
		// Get discount amounts in range
		$total_coupons = $this->get_order_report_data( array(
			'data' => array(
				'discount_amount' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'coupon',
					'function'        => 'SUM',
					'name'            => 'discount_amount'
				)
			),
			'where' => array(
				array(
					'key'      => 'order_item_type',
					'value'    => 'coupon',
					'operator' => '='
				)
			),
			'query_type' => 'get_var',
			'filter_range' => true
		) );

		$this->average_sales = $total_sales / ( $this->chart_interval + 1 );

		switch ( $this->chart_groupby ) {
			case 'day' :
				$average_sales_title = sprintf( __( '%s average daily sales', 'woocommerce' ), '<strong>' . woocommerce_price( $this->average_sales ) . '</strong>' );
			break;
			case 'month' :
				$average_sales_title = sprintf( __( '%s average monthly sales', 'woocommerce' ), '<strong>' . woocommerce_price( $this->average_sales ) . '</strong>' );
			break;
		}

		$legend[] = array(
			'title' => sprintf( __( '%s sales in this period', 'woocommerce' ), '<strong>' . woocommerce_price( $total_sales ) . '</strong>' ),
			'color' => $this->chart_colours['sales_amount'],
			'highlight_series' => 5
		);
		$legend[] = array(
			'title' => $average_sales_title,
			'color' => $this->chart_colours['average'],
			'highlight_series' => 2
		);
		$legend[] = array(
			'title' => sprintf( __( '%s orders placed', 'woocommerce' ), '<strong>' . $total_orders . '</strong>' ),
			'color' => $this->chart_colours['order_count'],
			'highlight_series' => 1
		);
		$legend[] = array(
			'title' => sprintf( __( '%s items purchased', 'woocommerce' ), '<strong>' . $total_items . '</strong>' ),
			'color' => $this->chart_colours['item_count'],
			'highlight_series' => 0
		);
		$legend[] = array(
			'title' => sprintf( __( '%s charged for shipping', 'woocommerce' ), '<strong>' . woocommerce_price( $total_shipping ) . '</strong>' ),
			'color' => $this->chart_colours['shipping_amount'],
			'highlight_series' => 4
		);
		$legend[] = array(
			'title' => sprintf( __( '%s worth of coupons used', 'woocommerce' ), '<strong>' . woocommerce_price( $total_coupons ) . '</strong>' ),
			'color' => $this->chart_colours['coupon_amount'],
			'highlight_series' => 3
		);

		return $legend;
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
			'7day'         => __( 'Last 7 Days', 'woocommerce' )
		);

		$this->chart_colours = array(
			'sales_amount' => '#3498db',
			'average'      => '#75b9e7',
			'order_count'  => '#b8c0c5',
			'item_count'   => '#d4d9dc',
			'coupon_amount' => '#e67e22',
			'shipping_amount' => '#1abc9c'
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

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
			case '7day' :
			default :
				$this->start_date = strtotime( '-6 days', current_time( 'timestamp' ) );
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

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo $current_range; ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php _e( 'Date', 'woocommerce' ); ?>"
			data-exclude_series="2"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
		$orders = $this->get_order_report_data( array(
			'data' => array(
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

		// Order items
		$order_items = $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'order_item_type',
					'value'    => 'line_item',
					'operator' => '='
				)
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

		// Get discount amounts in range
		$coupons = $this->get_order_report_data( array(
			'data' => array(
				'order_item_name' => array(
					'type'     => 'order_item',
					'function' => '',
					'name'     => 'order_item_name'
				),
				'discount_amount' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'coupon',
					'function'        => 'SUM',
					'name'            => 'discount_amount'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'order_item_type',
					'value'    => 'coupon',
					'operator' => '='
				)
			),
			'group_by'     => $this->group_by_query . ', order_item_name',
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

		// Prepare data for report
		$order_counts      = $this->prepare_chart_data( $orders, 'post_date', 'total_orders', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$order_item_counts = $this->prepare_chart_data( $order_items, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$order_amounts     = $this->prepare_chart_data( $orders, 'post_date', 'total_sales', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$coupon_amounts    = $this->prepare_chart_data( $coupons, 'post_date', 'discount_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$shipping_amounts    = $this->prepare_chart_data( $orders, 'post_date', 'total_shipping', $this->chart_interval, $this->start_date, $this->chart_groupby );

		// Encode in json format
		$chart_data = json_encode( array(
			'order_counts'      => array_values( $order_counts ),
			'order_item_counts' => array_values( $order_item_counts ),
			'order_amounts'     => array_values( $order_amounts ),
			'coupon_amounts'    => array_values( $coupon_amounts ),
			'shipping_amounts'  => array_values( $shipping_amounts )
		) );
		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );
				var drawGraph = function( highlight ) {
					var series = [
						{
							label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
							data: order_data.order_item_counts,
							color: '<?php echo $this->chart_colours['item_count']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Number of orders', 'woocommerce' ) ) ?>",
							data: order_data.order_counts,
							color: '<?php echo $this->chart_colours['order_count']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colours['order_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average sales amount', 'woocommerce' ) ) ?>",
							data: [ [ <?php echo min( array_keys( $order_amounts ) ); ?>, <?php echo $this->average_sales; ?> ], [ <?php echo max( array_keys( $order_amounts ) ); ?>, <?php echo $this->average_sales; ?> ] ],
							yaxis: 2,
							color: '<?php echo $this->chart_colours['average']; ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Coupon amount', 'woocommerce' ) ) ?>",
							data: order_data.coupon_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['coupon_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						},
						{
							label: "<?php echo esc_js( __( 'Shipping amount', 'woocommerce' ) ) ?>",
							data: order_data.shipping_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['shipping_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						},
						{
							label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
							data: order_data.order_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colours['sales_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						}
					];

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars )
							highlight_series.bars.fillColor = '#9c5d90';

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery('.chart-placeholder.main'),
						series,
						{
							legend: {
								show: false
							},
						    grid: {
						        color: '#aaa',
						        borderColor: 'transparent',
						        borderWidth: 0,
						        hoverable: true
						    },
						    xaxes: [ {
						    	color: '#aaa',
						    	position: "bottom",
						    	tickColor: 'transparent',
								mode: "time",
								timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
								monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
								tickLength: 1,
								minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
								font: {
						    		color: "#aaa"
						    	}
							} ],
						    yaxes: [
						    	{
						    		min: 0,
						    		minTickSize: 1,
						    		tickDecimals: 0,
						    		color: '#d4d9dc',
						    		font: { color: "#aaa" }
						    	},
						    	{
						    		position: "right",
						    		min: 0,
						    		tickDecimals: 2,
						    		alignTicksWithAxis: 1,
						    		color: 'transparent',
						    		font: { color: "#aaa" }
						    	}
						    ],
				 		}
				 	);

					jQuery('.chart-placeholder').resize();
				}

				drawGraph();

				jQuery('.highlight_series').hover(
					function() {
						drawGraph( jQuery(this).data('series') );
					},
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}
}