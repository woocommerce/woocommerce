<?php

if ( ! class_exists( 'WC_Report_Top_Sellers' ) )
	include( 'class-wc-report-top-sellers.php' );
/**
 * WC_Report_Top_Sellers class
 */
class WC_Report_Top_Earners extends WC_Report_Top_Sellers {

	/**
	 * [get_chart_widgets description]
	 * @return array
	 */
	public function get_chart_widgets() {
		return array(
			array(
				'title'    => __( 'Top Earners', 'woocommerce' ),
				'callback' => array( $this, 'top_earner_widget' )
			)
		);
	}

	/**
	 * Show the list of top eaners
	 * @return void
	 */
	public function top_earner_widget() {
		?>
		<table cellspacing="0">
			<?php
			$top_earners = $this->get_order_report_data( array(
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
						'function'        => 'SUM',
						'name'            => 'order_item_total'
					)
				),
				'order_by' => 'order_item_total DESC',
				'group_by' => 'product_id',
				'limit'    => 10,
				'query_type'    => 'get_results',
				'filter_range' => true
			) );

			if ( $top_earners ) {
				foreach ( $top_earners as $product ) {
					echo '<tr class="' . ( $this->product_id == $product->product_id ? 'active' : '' ) . '">
						<td class="count">' . woocommerce_price( $product->order_item_total ) . '</td>
						<td class="name"><a href="' . add_query_arg( 'product_id', $product->product_id ) . '">' . get_the_title( $product->product_id ) . '</a></td>
						<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 14, 'sales' ) . '</td>
					</tr>';
				}
			}
			?>
		</table>
		<?php
	}
}