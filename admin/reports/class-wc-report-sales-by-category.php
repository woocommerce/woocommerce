<?php
/**
 * WC_Report_Sales_By_Category class
 */
class WC_Report_Sales_By_Category extends WC_Admin_Report {

	public $show_categories = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( isset( $_GET['show_categories'] ) && is_array( $_GET['show_categories'] ) )
			$this->show_categories = array_map( 'absint', $_GET['show_categories'] );
		elseif ( isset( $_GET['show_categories'] ) )
			$this->show_categories = array( absint( $_GET['show_categories'] ) );
	}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		if ( ! $this->show_categories )
			return array();

		$legend    = array();
		$total     = 0;
		$found_ids = array();

		foreach( $this->show_categories as $category ) {
			$term_ids 		= get_term_children( $category, 'product_cat' );
			$term_ids[] 	= $category;
			$product_ids 	= get_objects_in_term( $term_ids, 'product_cat' );

			foreach ( $product_ids as $id ) {
				if ( ! in_array( $id, $found_ids ) && isset( $this->item_sales[ $id ] ) ) {
					$total += $this->item_sales[ $id ];
					$found_ids[] = $id;
				}
			}
		}

		$legend[] = array(
			'title' => sprintf( __( '%s sales in the selected categories', 'woocommerce' ), '<strong>' . woocommerce_price( $total ) . '</strong>' ),
			'color' => $this->chart_colours['sales_amount']
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

		// Get item sales data
		if ( $this->show_categories ) {
			$order_items = $this->get_order_report_data( array(
				'data' => array(
					'_product_id' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => '',
						'name'            => 'product_id'
					),
					'_line_total' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function' => 'SUM',
						'name'     => 'order_item_amount'
					)
				),
				'group_by'     => 'product_id',
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true
			) );

			$this->item_sales = array();

			if ( $order_items ) {
				foreach ( $order_items as $order_item ) {
					$this->item_sales[ $order_item->product_id ] = isset( $this->item_sales[ $order_item->product_id ] ) ? $this->item_sales[ $order_item->product_id ] + $order_item->order_item_amount : $order_item->order_item_amount;
				}
			}
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
				'title'    => __( 'Categories', 'woocommerce' ),
				'callback' => array( $this, 'category_widget' )
			)
		);
	}

	/**
	 * Category selection
	 * @return void
	 */
	public function category_widget() {
		$categories = get_terms( 'product_cat', array( 'orderby' => 'name' ) );
		?>
		<form method="GET">
			<div>
				<select multiple="multiple" class="chosen_select" id="show_categories" name="show_categories[]" style="width: 205px;">
					<?php
						$r = array();
						$r['pad_counts'] 	= 1;
						$r['hierarchical'] 	= 1;
						$r['hide_empty'] 	= 1;
						$r['value']			= 'id';
						$r['selected'] 		= $this->show_categories;

						include_once( WC()->plugin_path() . '/includes/walkers/class-product-cat-dropdown-walker.php' );

						echo woocommerce_walk_category_dropdown_tree( $categories, 0, $r );
					?>
				</select>
				<a href="#" class="select_none"><?php _e( 'None', 'woocommerce' ); ?></a>
				<a href="#" class="select_all"><?php _e( 'All', 'woocommerce' ); ?></a>
				<input type="submit" class="submit button" value="<?php _e( 'Show', 'woocommerce' ); ?>" />
				<input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ) ?>" />
				<input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ) ?>" />
				<input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ) ?>" />
				<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
				<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
				<input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ) ?>" />
			</div>
			<script type="text/javascript">
				jQuery(function(){
					jQuery("select.chosen_select").chosen();

					// Select all/none
					jQuery('.select_all').live('click', function() {
						jQuery(this).closest( 'div' ).find( 'select option' ).attr( "selected", "selected" );
						jQuery(this).closest( 'div' ).find('select').trigger( 'liszt:updated' );
						return false;
					});

					jQuery('.select_none').live('click', function() {
						jQuery(this).closest( 'div' ).find( 'select option' ).removeAttr( "selected" );
						jQuery(this).closest( 'div' ).find('select').trigger( 'liszt:updated' );
						return false;
					});
				});
			</script>
		</form>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		if ( ! $this->show_categories ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php _e( '&larr; Choose a category to view stats', 'woocommerce' ); ?></p>
			</div>
			<?php
		} else {
			$include_categories = array();
			$chart_data         = array();
			$chart_ticks        = array();
			$index              = 0;

			foreach ( $this->show_categories as $category ) {
				$category       = get_term( $category, 'product_cat' );
				$term_ids 		= get_term_children( $category->term_id, 'product_cat' );
				$term_ids[] 	= $category->term_id;
				$product_ids 	= get_objects_in_term( $term_ids, 'product_cat' );
				$category_total = 0;
				$found_ids      = array();

				foreach ( $product_ids as $id ) {
					if ( ! in_array( $id, $found_ids ) && isset( $this->item_sales[ $id ] ) ) {
						$category_total += $this->item_sales[ $id ];
						$found_ids[] = $id;
					}
				}

				$chart_data[]   = array( $index, $category_total );
				$chart_ticks[]  = array( $index, $category->name );

				$index ++;
			}
			?>
			<div class="chart-container">
				<div class="chart-placeholder main"></div>
			</div>
			<script type="text/javascript">
				jQuery(function(){
					var data = jQuery.parseJSON( '<?php echo json_encode( $chart_data ); ?>' );
					var ticks = jQuery.parseJSON( '<?php echo json_encode( $chart_ticks ); ?>' );

					jQuery.plot(
						jQuery('.chart-placeholder.main'),
						[
							{
								label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
								data: data,
								color: '<?php echo $this->chart_colours['sales_amount']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['sales_amount']; ?>', fill: true, show: true, lineWidth: 2, align: 'center', barWidth: 0.75 },
								prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>",
								enable_tooltip: true
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
						    	ticks: ticks,
								font: {
						    		color: "#aaa"
						    	}
							} ],
						    yaxes: [
						    	{
						    		min: 0,
						    		tickDecimals: 2,
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