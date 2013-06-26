<?php
/**
 * WC_Report_Top_Sellers class
 */
class WC_Report_Top_Sellers extends WC_Admin_Report {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->product_id = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : '';
	}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		if ( ! $this->product_id )
			return array();

		$legend   = array();

		$total_sales 	= $this->get_order_report_data( array(
			'data' => array(
				'_line_total' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function' => 'SUM',
					'name'     => 'order_item_amount'
				)
			),
			'where_meta' => array(
				array(
					'type'       => 'order_item_meta',
					'meta_key'   => '_product_id',
					'meta_value' => $this->product_id,
					'operator'   => '='
				)
			),
			'query_type'   => 'get_var',
			'filter_range' => true
		) );
		$total_items    = absint( $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count'
				)
			),
			'where_meta' => array(
				array(
					'type'       => 'order_item_meta',
					'meta_key'   => '_product_id',
					'meta_value' => $this->product_id,
					'operator'   => '='
				)
			),
			'query_type'   => 'get_var',
			'filter_range' => true
		) ) );

		$legend[] = array(
			'title' => sprintf( __( '%s sales for this item', 'woocommerce' ), '<strong>' . woocommerce_price( $total_sales ) . '</strong>' ),
			'color' => $this->chart_colours['sales_amount']
		);
		$legend[] = array(
			'title' => sprintf( __( '%s purchases of this item', 'woocommerce' ), '<strong>' . $total_items . '</strong>' ),
			'color' => $this->chart_colours['item_count']
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
			'item_count'   => '#d4d9dc',
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		switch ( $current_range ) {
			case 'custom' :
				$this->start_date = strtotime( sanitize_text_field( $_GET['start_date'] ) );
				$this->end_date   = strtotime( 'midnight', strtotime( sanitize_text_field( $_GET['end_date'] ) ) );

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
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'month';
			break;
			case 'last_month' :
				$this->start_date = strtotime( 'first day of last month', current_time('timestamp') );
				$this->end_date   = strtotime( 'last day of last month', current_time('timestamp') );
				$this->chart_groupby         = 'day';
			break;
			case 'month' :
				$this->start_date = strtotime( 'first day of this month', current_time('timestamp') );
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'day';
			break;
			case '7day' :
			default :
				$this->start_date = strtotime( 'midnight -6 days', current_time( 'timestamp' ) );
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'day';
			break;
		}

		// Group by
		switch ( $this->chart_groupby ) {
			case 'day' :
				$this->group_by_query       = 'YEAR(post_date), MONTH(post_date), DAY(post_date)';
				$this->chart_interval = max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) );
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

		include( WC()->plugin_path() . '/admin/views/html-report-by-date.php');
	}

	/**
	 * [get_chart_widgets description]
	 * @return array
	 */
	public function get_chart_widgets() {
		return array(
			array(
				'title'    => __( 'Top Sellers', 'woocommerce' ),
				'callback' => array( $this, 'top_seller_widget' )
			)
		);
	}

	/**
	 * Show the list of top sellers
	 * @return void
	 */
	public function top_seller_widget() {
		?>
		<table cellspacing="0">
			<?php
			$top_sellers = $this->get_order_report_data( array(
				'data' => array(
					'_product_id' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => '',
						'name'            => 'product_id'
					),
					'_qty' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'order_item_qty'
					)
				),
				'order_by' => 'order_item_qty DESC',
				'group_by' => 'product_id',
				'limit'    => 10,
				'query_type'    => 'get_results',
				'filter_range' => true
			) );

			if ( $top_sellers ) {
				foreach ( $top_sellers as $product ) {
					echo '<tr class="' . ( $this->product_id == $product->product_id ? 'active' : '' ) . '">
						<td class="count">' . $product->order_item_qty . '</td>
						<td class="name"><a href="' . add_query_arg( 'product_id', $product->product_id ) . '">' . get_the_title( $product->product_id ) . '</a></td>
						<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 14, 'count' ) . '</td>
					</tr>';
				}
			}
			?>
		</table>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		if ( ! $this->product_id ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php _e( '&larr; Choose a product to view stats', 'woocommerce' ); ?></p>
			</div>
			<?php
		} else {
			// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
			$order_item_counts = $this->get_order_report_data( array(
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
				'where_meta' => array(
					array(
						'type'       => 'order_item_meta',
						'meta_key'   => '_product_id',
						'meta_value' => $this->product_id,
						'operator'   => '='
					)
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true
			) );

			$order_item_amounts = $this->get_order_report_data( array(
				'data' => array(
					'_line_total' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function' => 'SUM',
						'name'     => 'order_item_amount'
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date'
					),
				),
				'where_meta' => array(
					array(
						'type'       => 'order_item_meta',
						'meta_key'   => '_product_id',
						'meta_value' => $this->product_id,
						'operator'   => '='
					)
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true
			) );

			// Prepare data for report
			$order_item_counts  = $this->prepare_chart_data( $order_item_counts, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
			$order_item_amounts = $this->prepare_chart_data( $order_item_amounts, 'post_date', 'order_item_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );

			// Encode in json format
			$chart_data = json_encode( array(
				'order_item_counts'  => array_values( $order_item_counts ),
				'order_item_amounts' => array_values( $order_item_amounts )
			) );
			?>
			<div class="chart-container">
				<div class="chart-placeholder main"></div>
			</div>
			<script type="text/javascript">
				jQuery(function(){
					var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

					jQuery.plot(
						jQuery('.chart-placeholder.main'),
						[
							{
								label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
								data: order_data.order_item_counts,
								color: '<?php echo $this->chart_colours['item_count']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
								shadowSize: 0,
								hoverable: false
							},
							{
								label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
								data: order_data.order_item_amounts,
								yaxis: 2,
								color: '<?php echo $this->chart_colours['sales_amount']; ?>',
								points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
								lines: { show: true, lineWidth: 4, fill: false },
								shadowSize: 0,
								prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
							}
						],
						{
							legend: {
								show: false
							},
					   		series: {
					   			stack: true
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
						    		color: '#ecf0f1',
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
				});
			</script>
			<?php
		}
	}
}