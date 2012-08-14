<?php
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Reports
 * @version     1.6.4
 */

/**
 * Reports page
 *
 * Handles the display of the reports page in admin.
 *
 * @access public
 * @return void
 */
function woocommerce_reports() {

	$current_tab 	= ( isset( $_GET['tab'] ) ) 	? urldecode( $_GET['tab'] ) : 'sales';
	$current_chart 	= ( isset( $_GET['chart'] ) )	? urldecode( $_GET['chart'] ) : 0;

	$charts = apply_filters( 'woocommerce_reports_charts', array(
		'sales' => array(
			'title' 	=>  __( 'Sales', 'woocommerce' ),
			'charts' 	=> array(
				array(
					'title' => __('Overview', 'woocommerce'),
					'description' => '',
					'hide_title' => true,
					'function' => 'woocommerce_sales_overview'
				),
				array(
					'title' => __('Sales by day', 'woocommerce'),
					'description' => '',
					'function' => 'woocommerce_daily_sales'
				),
				array(
					'title' => __('Sales by month', 'woocommerce'),
					'description' => '',
					'function' => 'woocommerce_monthly_sales'
				),
				array(
					'title' => __('Taxes by month', 'woocommerce'),
					'description' => '',
					'function' => 'woocommerce_monthly_taxes'
				),
				array(
					'title' => __('Product Sales', 'woocommerce'),
					'description' => '',
					'function' => 'woocommerce_product_sales'
				),
				array(
					'title' => __('Top sellers', 'woocommerce'),
					'description' => '',
					'function' => 'woocommerce_top_sellers'
				),
				array(
					'title' => __('Top earners', 'woocommerce'),
					'description' => '',
					'function' => 'woocommerce_top_earners'
				)
			)
		),
		'customers' => array(
			'title' 	=>  __( 'Customers', 'woocommerce' ),
			'charts' 	=> array(
				array(
					'title' => __('Overview', 'woocommerce'),
					'description' => '',
					'hide_title' => true,
					'function' => 'woocommerce_customer_overview'
				),
			)
		),
		'stock' => array(
			'title' 	=>  __( 'Stock', 'woocommerce' ),
			'charts' 	=> array(
				array(
					'title' => __('Overview', 'woocommerce'),
					'description' => '',
					'hide_title' => true,
					'function' => 'woocommerce_stock_overview'
				),
			)
		)
	));
    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-reports" id="icon-woocommerce"><br /></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
				foreach ( $charts as $key => $chart ) {
					echo '<a href="'.admin_url( 'admin.php?page=woocommerce_reports&tab=' . urlencode( $key ) ).'" class="nav-tab ';
					if ( $current_tab == $key ) echo 'nav-tab-active';
					echo '">' . esc_html( $chart[ 'title' ] ) . '</a>';
				}
			?>
			<?php do_action('woocommerce_reports_tabs'); ?>
		</h2>

		<?php if ( sizeof( $charts[ $current_tab ]['charts'] ) > 1 ) {
			?>
			<ul class="subsubsub">
				<li><?php

					$links = array();

					foreach ( $charts[ $current_tab ]['charts'] as $key => $chart ) {

						$link = '<a href="admin.php?page=woocommerce_reports&tab=' . urlencode( $current_tab ) . '&amp;chart=' . urlencode( $key ) . '" class="';

						if ( $key == $current_chart ) $link .= 'current';

						$link .= '">' . $chart['title'] . '</a>';

						$links[] = $link;

					}

					echo implode(' | </li><li>', $links);

				?></li>
			</ul>
			<br class="clear" />
			<?php
		}

		if ( isset( $charts[ $current_tab ][ 'charts' ][ $current_chart ] ) ) {

			$chart = $charts[ $current_tab ][ 'charts' ][ $current_chart ];

			if ( ! isset( $chart['hide_title'] ) || $chart['hide_title'] != true )
				echo '<h3>' . $chart['title'] . '</h3>';

			if ( $chart['description'] )
				echo '<p>' . $chart['description'] . '</p>';

			$func = $chart['function'];
			if ( $func && function_exists( $func ) )
				$func();
		}
		?>
	</div>
	<?php
}


/**
 * Output JavaScript for highlighting weekends on charts.
 *
 * @access public
 * @return void
 */
function woocommerce_weekend_area_js() {
	?>
	function weekendAreas(axes) {
        var markings = [];
        var d = new Date(axes.xaxis.min);
        // go to the first Saturday
        d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
        d.setUTCSeconds(0);
        d.setUTCMinutes(0);
        d.setUTCHours(0);
        var i = d.getTime();
        do {
            markings.push({ xaxis: { from: i, to: i + 2 * 24 * 60 * 60 * 1000 } });
            i += 7 * 24 * 60 * 60 * 1000;
        } while (i < axes.xaxis.max);

        return markings;
    }
    <?php
}


/**
 * Output JavaScript for chart tooltips.
 *
 * @access public
 * @return void
 */
function woocommerce_tooltip_js() {
	?>
	function showTooltip(x, y, contents) {
        jQuery('<div id="tooltip">' + contents + '</div>').css( {
            position: 'absolute',
            display: 'none',
            top: y + 5,
            left: x + 5,
		    padding: '5px 10px',
			border: '3px solid #3da5d5',
			background: '#288ab7'
        }).appendTo("body").fadeIn(200);
    }

    var previousPoint = null;
    jQuery("#placeholder").bind("plothover", function (event, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;

                jQuery("#tooltip").remove();

                if (item.series.label=="<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>") {

                	var y = item.datapoint[1].toFixed(2);
                	showTooltip(item.pageX, item.pageY, item.series.label + " - " + "<?php echo get_woocommerce_currency_symbol(); ?>" + y);

                } else if (item.series.label=="<?php echo esc_js( __( 'Number of sales', 'woocommerce' ) ) ?>") {

                	var y = item.datapoint[1];
                	showTooltip(item.pageX, item.pageY, item.series.label + " - " + y);

                } else {

                	var y = item.datapoint[1];
                	showTooltip(item.pageX, item.pageY, y);

                }
            }
        }
        else {
            jQuery("#tooltip").remove();
            previousPoint = null;
        }
    });
    <?php
}


/**
 * Output Javascript for date ranges.
 *
 * @access public
 * @return void
 */
function woocommerce_datepicker_js() {
	global $woocommerce;
	?>
	var dates = jQuery( "#from, #to" ).datepicker({
		defaultDate: "",
		dateFormat: "yy-mm-dd",
		//changeMonth: true,
		//changeYear: true,
		numberOfMonths: 1,
		minDate: "-12M",
		maxDate: "+0D",
		showButtonPanel: true,
		showOn: "button",
		buttonImage: "<?php echo $woocommerce->plugin_url(); ?>/assets/images/calendar.png",
		buttonImageOnly: true,
		onSelect: function( selectedDate ) {
			var option = this.id == "from" ? "minDate" : "maxDate",
				instance = jQuery( this ).data( "datepicker" ),
				date = jQuery.datepicker.parseDate(
					instance.settings.dateFormat ||
					jQuery.datepicker._defaults.dateFormat,
					selectedDate, instance.settings );
			dates.not( this ).datepicker( "option", option, date );
		}
	});
	<?php
}


/**
 * Filter orders within a range.
 *
 * @access public
 * @param string $where (default: '') where clause
 * @return void
 */
function orders_within_range( $where = '' ) {
	global $start_date, $end_date;

	$after = date('Y-m-d', $start_date);
	$before = date('Y-m-d', strtotime('+1 day', $end_date));

	$where .= " AND post_date > '$after'";
	$where .= " AND post_date < '$before'";

	return $where;
}


/**
 * Output the sales overview chart.
 *
 * @access public
 * @return void
 */
function woocommerce_sales_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$total_sales = 0;
	$total_orders = 0;
	$order_items = 0;
	$discount_total = 0;
	$shipping_total = 0;

	$order_totals = $wpdb->get_row("
		SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_total'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
	");

	$total_sales 	= $order_totals->total_sales;
	$total_orders 	= $order_totals->total_orders;

	$discount_total = $wpdb->get_var("
		SELECT SUM(meta.meta_value) AS total_sales FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		IN ('_order_discount', '_cart_discount')
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
	");

	$shipping_total = $wpdb->get_var("
		SELECT SUM(meta.meta_value) AS total_sales FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_shipping'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
	");

	$order_items_serialized = $wpdb->get_col("
		SELECT meta.meta_value AS items FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_items'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
	");

	if ($order_items_serialized) foreach ($order_items_serialized as $order_items_array) {
		$order_items_array = maybe_unserialize($order_items_array);
		if (is_array($order_items_array)) foreach ($order_items_array as $item) $order_items += (int) $item['qty'];
	}

	?>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total sales', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_sales>0) echo woocommerce_price($total_sales); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total orders', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ( $total_orders > 0 ) echo $total_orders . ' (' . $order_items . ' ' . __( 'items', 'woocommerce' ) . ')'; else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order total', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo woocommerce_price($total_sales/$total_orders); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order items', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo number_format($order_items/$total_orders, 2); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Discounts used', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($discount_total>0) echo woocommerce_price($discount_total); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total shipping costs', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($shipping_total>0) echo woocommerce_price($shipping_total); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('This months sales', 'woocommerce'); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
				</div>
			</div>
		</div>
	</div>
	<?php

	$start_date = strtotime(date('Ymd', strtotime( date('Ym', current_time('timestamp')).'01' )));
	$end_date = strtotime(date('Ymd', current_time('timestamp')));

	// Get orders to display in widget
	add_filter( 'posts_where', 'orders_within_range' );

	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'ASC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
	    'suppress_filters'=> 0,
	    'tax_query' => array(
	    	array(
		    	'taxonomy' => 'shop_order_status',
				'terms' => apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ),
				'field' => 'slug',
				'operator' => 'IN'
			)
	    )
	);
	$orders = get_posts( $args );

	$order_counts = array();
	$order_amounts = array();

	// Blank date ranges to begin
	$count = 0;
	$days = ($end_date - $start_date) / (60 * 60 * 24);
	if ($days==0) $days = 1;

	while ($count < $days) :
		$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $start_date))).'000';

		$order_counts[$time] = 0;
		$order_amounts[$time] = 0;

		$count++;
	endwhile;

	if ($orders) :
		foreach ($orders as $order) :

			$order_total = get_post_meta($order->ID, '_order_total', true);
			$time = strtotime(date('Ymd', strtotime($order->post_date))).'000';

			if (isset($order_counts[$time])) :
				$order_counts[$time]++;
			else :
				$order_counts[$time] = 1;
			endif;

			if (isset($order_amounts[$time])) :
				$order_amounts[$time] = $order_amounts[$time] + $order_total;
			else :
				$order_amounts[$time] = (float) $order_total;
			endif;

		endforeach;
	endif;

	remove_filter( 'posts_where', 'orders_within_range' );

	$order_counts_array = array();
	foreach ($order_counts as $key => $count) :
		$order_counts_array[] = array($key, $count);
	endforeach;

	$order_amounts_array = array();
	foreach ($order_amounts as $key => $amount) :
		$order_amounts_array[] = array($key, $amount);
	endforeach;

	$order_data = array( 'order_counts' => $order_counts_array, 'order_amounts' => $order_amounts_array );

	$chart_data = json_encode($order_data);
	?>
	<script type="text/javascript">
		jQuery(function(){
			var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

			var d = order_data.order_counts;
		    var d2 = order_data.order_amounts;

			for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;
		    for (var i = 0; i < d2.length; ++i) d2[i][0] += 60 * 60 * 1000;

			var placeholder = jQuery("#placeholder");

			var plot = jQuery.plot(placeholder, [ { label: "<?php echo esc_js( __( 'Number of sales', 'woocommerce' ) ) ?>", data: d }, { label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>", data: d2, yaxis: 2 } ], {
				series: {
					lines: { show: true },
					points: { show: true }
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true,
					markings: weekendAreas
				},
				xaxis: {
					mode: "time",
					timeformat: "%d %b",
					monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
					tickLength: 1,
					minTickSize: [1, "day"]
				},
				yaxes: [ { min: 0, tickSize: 10, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
		   		colors: ["#8a4b75", "#47a03e"]
		 	});

		 	placeholder.resize();

			<?php woocommerce_weekend_area_js(); ?>
			<?php woocommerce_tooltip_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the daily sales chart.
 *
 * @access public
 * @return void
 */
function woocommerce_daily_sales() {

	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';

	if (!$start_date) $start_date = date('Ymd', strtotime( date('Ym', current_time('timestamp')).'01' ));
	if (!$end_date) $end_date = date('Ymd', current_time('timestamp'));

	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);

	$total_sales = 0;
	$total_orders = 0;
	$order_items = 0;

	// Get orders to display in widget
	add_filter( 'posts_where', 'orders_within_range' );

	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'ASC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
	    'suppress_filters'=> 0,
	    'tax_query' => array(
	    	array(
		    	'taxonomy' => 'shop_order_status',
				'terms' => apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ),
				'field' => 'slug',
				'operator' => 'IN'
			)
	    )
	);
	$orders = get_posts( $args );

	$order_counts = array();
	$order_amounts = array();

	// Blank date ranges to begin
	$count = 0;
	$days = ($end_date - $start_date) / (60 * 60 * 24);
	if ($days==0) $days = 1;

	while ($count < $days) :
		$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $start_date))).'000';

		$order_counts[$time] = 0;
		$order_amounts[$time] = 0;

		$count++;
	endwhile;

	if ($orders) :
		foreach ($orders as $order) :

			$order_total = get_post_meta($order->ID, '_order_total', true);
			$time = strtotime(date('Ymd', strtotime($order->post_date))).'000';

			$order_items_array = (array) get_post_meta($order->ID, '_order_items', true);
			foreach ($order_items_array as $item) $order_items += (int) $item['qty'];
			$total_sales += $order_total;
			$total_orders++;

			if (isset($order_counts[$time])) :
				$order_counts[$time]++;
			else :
				$order_counts[$time] = 1;
			endif;

			if (isset($order_amounts[$time])) :
				$order_amounts[$time] = $order_amounts[$time] + $order_total;
			else :
				$order_amounts[$time] = (float) $order_total;
			endif;

		endforeach;
	endif;

	remove_filter( 'posts_where', 'orders_within_range' );

	?>
	<form method="post" action="">
		<p><label for="from"><?php _e('From:', 'woocommerce'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woocommerce'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
	</form>

	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total sales in range', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_sales>0) echo woocommerce_price($total_sales); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total orders in range', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ( $total_orders > 0 ) echo $total_orders . ' (' . $order_items . ' ' . __( 'items', 'woocommerce' ) . ')'; else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order total in range', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo woocommerce_price($total_sales/$total_orders); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order items in range', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo number_format($order_items/$total_orders, 2); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('Sales in range', 'woocommerce'); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
				</div>
			</div>
		</div>
	</div>
	<?php

	$order_counts_array = array();
	foreach ($order_counts as $key => $count) :
		$order_counts_array[] = array($key, $count);
	endforeach;

	$order_amounts_array = array();
	foreach ($order_amounts as $key => $amount) :
		$order_amounts_array[] = array($key, $amount);
	endforeach;

	$order_data = array( 'order_counts' => $order_counts_array, 'order_amounts' => $order_amounts_array );

	$chart_data = json_encode($order_data);
	?>
	<script type="text/javascript">
		jQuery(function(){
			var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

			var d = order_data.order_counts;
		    var d2 = order_data.order_amounts;

			for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;
		    for (var i = 0; i < d2.length; ++i) d2[i][0] += 60 * 60 * 1000;

			var placeholder = jQuery("#placeholder");

			var plot = jQuery.plot(placeholder, [ { label: "<?php echo esc_js( __( 'Number of sales', 'woocommerce' ) ) ?>", data: d }, { label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>", data: d2, yaxis: 2 } ], {
				series: {
					lines: { show: true },
					points: { show: true }
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true,
					markings: weekendAreas
				},
				xaxis: {
					mode: "time",
					timeformat: "%d %b",
					monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
					tickLength: 1,
					minTickSize: [1, "day"]
				},
				yaxes: [ { min: 0, tickSize: 10, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
		   		colors: ["#8a4b75", "#47a03e"]
		 	});

		 	placeholder.resize();

			<?php woocommerce_weekend_area_js(); ?>
			<?php woocommerce_tooltip_js(); ?>
			<?php woocommerce_datepicker_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the monthly sales chart.
 *
 * @access public
 * @return void
 */
function woocommerce_monthly_sales() {

	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$first_year = $wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE post_date != 0 ORDER BY post_date ASC LIMIT 1;");
	if ($first_year) $first_year = date('Y', strtotime($first_year)); else $first_year = date('Y');

	$current_year 	= isset( $_POST['show_year'] ) 	? $_POST['show_year'] 	: date( 'Y', current_time( 'timestamp' ) );
	$start_date 	= strtotime( $current_year . '0101' );

	$total_sales = $total_orders = $order_items = 0;
	$order_counts = $order_amounts = array();

	for ( $count = 0; $count < 12; $count++ ) {
		$time = strtotime( date('Ym', strtotime( '+ ' . $count . ' MONTH', $start_date ) ) . '01' ) . '000';

		if ( $time > current_time( 'timestamp' ) . '000' )
			continue;

		$month = date( 'Ym', strtotime(date('Ym', strtotime('+ '.$count.' MONTH', $start_date)).'01') );

		$months_orders = $wpdb->get_row("
			SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )

			WHERE 	meta.meta_key 		= '_order_total'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		$order_counts[$time] 	= (int) $months_orders->total_orders;
		$order_amounts[$time] 	= (float) $months_orders->total_sales;

		$total_orders			+= (int) $months_orders->total_orders;
		$total_sales			+= (float) $months_orders->total_sales;

		// Count order items
		$order_items_serialized = $wpdb->get_col("
			SELECT meta.meta_value AS items FROM {$wpdb->posts} AS posts

			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )

			WHERE 	meta.meta_key 		= '_order_items'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		if ($order_items_serialized) foreach ($order_items_serialized as $order_items_array) {
			$order_items_array = maybe_unserialize($order_items_array);
			if (is_array($order_items_array)) foreach ($order_items_array as $item) $order_items += (int) $item['qty'];
		}

	}
	?>
	<form method="post" action="">
		<p><label for="show_year"><?php _e('Year:', 'woocommerce'); ?></label>
		<select name="show_year" id="show_year">
			<?php
				for ($i = $first_year; $i <= date('Y'); $i++) printf('<option value="%s" %s>%s</option>', $i, selected($current_year, $i, false), $i);
			?>
		</select> <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
	</form>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total sales for year', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_sales>0) echo woocommerce_price($total_sales); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total orders for year', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ( $total_orders > 0 ) echo $total_orders . ' (' . $order_items . ' ' . __( 'items', 'woocommerce' ) . ')'; else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order total for year', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo woocommerce_price($total_sales/$total_orders); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order items for year', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo number_format($order_items/$total_orders, 2); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('Monthly sales for year', 'woocommerce'); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
				</div>
			</div>
		</div>
	</div>
	<?php

	$order_counts_array = array();
	foreach ($order_counts as $key => $count) :
		$order_counts_array[] = array($key, $count);
	endforeach;

	$order_amounts_array = array();
	foreach ($order_amounts as $key => $amount) :
		$order_amounts_array[] = array($key, $amount);
	endforeach;

	$order_data = array( 'order_counts' => $order_counts_array, 'order_amounts' => $order_amounts_array );

	$chart_data = json_encode($order_data);
	?>
	<script type="text/javascript">
		jQuery(function(){
			var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

			var d = order_data.order_counts;
			var d2 = order_data.order_amounts;

			var placeholder = jQuery("#placeholder");

			var plot = jQuery.plot(placeholder, [ { label: "<?php echo esc_js( __( 'Number of sales', 'woocommerce' ) ) ?>", data: d }, { label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>", data: d2, yaxis: 2 } ], {
				series: {
					lines: { show: true },
					points: { show: true, align: "left" }
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true
				},
				xaxis: {
					mode: "time",
					timeformat: "%b %y",
					monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
					tickLength: 1,
					minTickSize: [1, "month"]
				},
				yaxes: [ { min: 0, tickSize: 10, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
		   		colors: ["#8a4b75", "#47a03e"]
		 	});

		 	placeholder.resize();

			<?php woocommerce_tooltip_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the top sellers chart.
 *
 * @access public
 * @return void
 */
function woocommerce_top_sellers() {

	global $start_date, $end_date, $woocommerce;

	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';

	if (!$start_date) $start_date = date('Ymd', strtotime( date('Ym', current_time('timestamp')).'01' ));
	if (!$end_date) $end_date = date('Ymd', current_time('timestamp'));

	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);

	// Get orders to display in widget
	add_filter( 'posts_where', 'orders_within_range' );

	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'ASC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
	    'suppress_filters'=> 0,
	    'tax_query' => array(
	    	array(
		    	'taxonomy' => 'shop_order_status',
				'terms' => array('completed', 'processing', 'on-hold'),
				'field' => 'slug',
				'operator' => 'IN'
			)
	    )
	);
	$orders = get_posts( $args );

	$found_products = array();

	if ($orders) :
		foreach ($orders as $order) :
			$order_items = (array) get_post_meta( $order->ID, '_order_items', true );
			foreach ($order_items as $item) :
				$found_products[$item['id']] = isset($found_products[$item['id']]) ? $found_products[$item['id']] + $item['qty'] : $item['qty'];
			endforeach;
		endforeach;
	endif;

	asort($found_products);
	$found_products = array_reverse($found_products, true);
	$found_products = array_slice($found_products, 0, 25, true);
	reset($found_products);

	remove_filter( 'posts_where', 'orders_within_range' );
	?>
	<form method="post" action="">
		<p><label for="from"><?php _e('From:', 'woocommerce'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woocommerce'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
	</form>
	<table class="bar_chart">
		<thead>
			<tr>
				<th><?php _e('Product', 'woocommerce'); ?></th>
				<th><?php _e('Sales', 'woocommerce'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$max_sales = current($found_products);
				foreach ($found_products as $product_id => $sales) :
					$width = ($sales>0) ? ($sales / $max_sales) * 100 : 0;

					$product = get_post($product_id);
					if ($product) :
						$product_name = '<a href="'.get_permalink($product->ID).'">'.$product->post_title.'</a>';
						$orders_link = admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&s=' . urlencode($product->post_title) . '&shop_order_status=completed,processing,on-hold');
					else :
						$product_name = __('Product does not exist', 'woocommerce');
						$orders_link = admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&s=&shop_order_status=completed,processing,on-hold');
					endif;

					echo '<tr><th>'.$product_name.'</th><td width="1%"><span>'.$sales.'</span></td><td class="bars"><a href="'.$orders_link.'" style="width:'.$width.'%">&nbsp;</a></td></tr>';
				endforeach;
			?>
		</tbody>
	</table>
	<script type="text/javascript">
		jQuery(function(){
			<?php woocommerce_datepicker_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the top earners chart.
 *
 * @access public
 * @return void
 */
function woocommerce_top_earners() {

	global $start_date, $end_date, $woocommerce;

	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';

	if (!$start_date) $start_date = date('Ymd', strtotime( date('Ym', current_time('timestamp')).'01' ));
	if (!$end_date) $end_date = date('Ymd', current_time('timestamp'));

	$start_date = strtotime($start_date);
	$end_date = strtotime($end_date);

	// Get orders to display in widget
	add_filter( 'posts_where', 'orders_within_range' );

	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'ASC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
	    'suppress_filters'=> 0,
	    'tax_query' => array(
	    	array(
		    	'taxonomy' => 'shop_order_status',
				'terms' => array('completed', 'processing', 'on-hold'),
				'field' => 'slug',
				'operator' => 'IN'
			)
	    )
	);
	$orders = get_posts( $args );

	$found_products = array();

	if ($orders) :
		foreach ($orders as $order) :
			$order_items = (array) get_post_meta( $order->ID, '_order_items', true );
			foreach ($order_items as $item) :
				if (isset($item['line_total'])) $row_cost = $item['line_total'];
				else $row_cost = $item['cost'] * $item['qty'];

				$found_products[$item['id']] = isset($found_products[$item['id']]) ? $found_products[$item['id']] + $row_cost : $row_cost;
			endforeach;
		endforeach;
	endif;

	asort($found_products);
	$found_products = array_reverse($found_products, true);
	$found_products = array_slice($found_products, 0, 25, true);
	reset($found_products);

	remove_filter( 'posts_where', 'orders_within_range' );
	?>
	<form method="post" action="">
		<p><label for="from"><?php _e('From:', 'woocommerce'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woocommerce'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
	</form>
	<table class="bar_chart">
		<thead>
			<tr>
				<th><?php _e('Product', 'woocommerce'); ?></th>
				<th colspan="2"><?php _e('Sales', 'woocommerce'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$max_sales = current($found_products);
				foreach ($found_products as $product_id => $sales) :
					$width = ($sales>0) ? (round($sales) / round($max_sales)) * 100 : 0;

					$product = get_post($product_id);
					if ($product) :
						$product_name = '<a href="'.get_permalink($product->ID).'">'.$product->post_title.'</a>';
						$orders_link = admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&s=' . urlencode($product->post_title) . '&shop_order_status=completed,processing,on-hold');
					else :
						$product_name = __('Product no longer exists', 'woocommerce');
						$orders_link = admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&s=&shop_order_status=completed,processing,on-hold');
					endif;

					echo '<tr><th>'.$product_name.'</th><td width="1%"><span>'.woocommerce_price($sales).'</span></td><td class="bars"><a href="'.$orders_link.'" style="width:'.$width.'%">&nbsp;</a></td></tr>';
				endforeach;
			?>
		</tbody>
	</table>
	<script type="text/javascript">
		jQuery(function(){
			<?php woocommerce_datepicker_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the product sales chart for single products.
 *
 * @access public
 * @return void
 */
function woocommerce_product_sales() {

	global $wpdb, $woocommerce;

	$chosen_product_ids = ( isset( $_POST['product_ids'] ) ) ? (array) $_POST['product_ids'] : '';

	if ( $chosen_product_ids && is_array( $chosen_product_ids ) ) {

		$start_date = date( 'Ym', strtotime( '-12 MONTHS', current_time('timestamp') ) ) . '01';
		$end_date 	= date( 'Ymd', current_time( 'timestamp' ) );

		$max_sales = $max_totals = 0;
		$product_sales = $product_totals = array();

		// Get titles and ID's related to product
		$chosen_product_titles = array();
		$children_ids = array();

		foreach ( $chosen_product_ids as $product_id ) {
			$children = (array) get_posts( 'post_parent=' . $product_id . '&fields=ids&post_status=any&numberposts=-1' );
			$children_ids = $children_ids + $children;
			$chosen_product_titles[] = get_the_title( $product_id );
		}

		// Get order items
		$order_items = $wpdb->get_results("
			SELECT meta.meta_value AS items, posts.post_date FROM {$wpdb->posts} AS posts

			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )

			WHERE 	meta.meta_key 		= '_order_items'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		posts.post_date		> date_sub( NOW(), INTERVAL 1 YEAR )
			ORDER BY posts.post_date ASC
		");

		if ( $order_items ) {

			foreach ( $order_items as $order_item ) {

				$date 	= date( 'Ym', strtotime( $order_item->post_date ) );
				$items 	= maybe_unserialize( $order_item->items );

				foreach ( $items as $item ) {

					if ( ! in_array( $item['id'], $chosen_product_ids ) && ! in_array( $item['id'], $children_ids ) )
						continue;

					if ( isset( $item['line_total'] ) ) $row_cost = $item['line_total'];
					else $row_cost = $item['cost'] * $item['qty'];

					if ( ! $row_cost ) continue;

					$product_sales[ $date ] = isset( $product_sales[ $date ] ) ? $product_sales[$date] + $item['qty'] : $item['qty'];
					$product_totals[ $date ] = isset( $product_totals[ $date ] ) ? $product_totals[ $date ] + $row_cost : $row_cost;

					if ( $product_sales[ $date ] > $max_sales ) $max_sales = $product_sales[ $date ];
					if ( $product_totals[ $date ] > $max_totals ) $max_totals = $product_totals[ $date ];
				}

			}

		}
		?>
		<h4><?php printf( __( 'Sales for %s:', 'woocommerce' ), implode( ', ', $chosen_product_titles ) ); ?></h4>
		<table class="bar_chart">
			<thead>
				<tr>
					<th><?php _e('Month', 'woocommerce'); ?></th>
					<th colspan="2"><?php _e('Sales', 'woocommerce'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					if (sizeof($product_sales)>0) foreach ($product_sales as $date => $sales) :
						$width = ($sales>0) ? (round($sales) / round($max_sales)) * 100 : 0;
						$width2 = ($product_totals[$date]>0) ? (round($product_totals[$date]) / round($max_totals)) * 100 : 0;

						$orders_link = admin_url('edit.php?s&post_status=all&post_type=shop_order&action=-1&s=' . urlencode( implode( ' ', $chosen_product_titles ) ) . '&m=' . date('Ym', strtotime($date.'01')) . '&shop_order_status=completed,processing,on-hold');

						echo '<tr><th><a href="'.$orders_link.'">'.date_i18n('F', strtotime($date.'01')).'</a></th>
						<td width="1%"><span>'.$sales.'</span><span class="alt">'.woocommerce_price($product_totals[$date]).'</span></td>
						<td class="bars">
							<span style="width:'.$width.'%">&nbsp;</span>
							<span class="alt" style="width:'.$width2.'%">&nbsp;</span>
						</td></tr>';
					endforeach; else echo '<tr><td colspan="3">'.__('No sales :(', 'woocommerce').'</td></tr>';
				?>
			</tbody>
		</table>
		<?php

	} else {
		?>
		<form method="post" action="">
			<p><select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e('Search for a product&hellip;', 'woocommerce'); ?>" style="width: 400px;"></select> <input type="submit" style="vertical-align: top;" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
			<script type="text/javascript">
				jQuery(function(){
					// Ajax Chosen Product Selectors
					jQuery("select.ajax_chosen_select_products").ajaxChosen({
					    method: 	'GET',
					    url: 		'<?php echo admin_url('admin-ajax.php'); ?>',
					    dataType: 	'json',
					     afterTypeDelay: 100,
					    data:		{
					    	action: 		'woocommerce_json_search_products',
							security: 		'<?php echo wp_create_nonce("search-products"); ?>'
					    }
					}, function (data) {

						var terms = {};

					    jQuery.each(data, function (i, val) {
					        terms[i] = val;
					    });

					    return terms;
					});
				});
			</script>
		</form>
		<?php
	}
}


/**
 * Output the customer overview stats.
 *
 * @access public
 * @return void
 */
function woocommerce_customer_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$total_customers = 0;
	$total_customer_sales = 0;
	$total_guest_sales = 0;
	$total_customer_orders = 0;
	$total_guest_orders = 0;

	$users_query = new WP_User_Query( array(
		'fields' => array('user_registered'),
		'role' => 'customer'
		) );
	$customers = $users_query->get_results();
	$total_customers = (int) sizeof($customers);

	$customer_orders = $wpdb->get_row("
		SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_total'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND		posts.ID			IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE 	meta_key 		= '_customer_user'
			AND		meta_value		> 0
		)
	");

	$total_customer_sales	= $customer_orders->total_sales;
	$total_customer_orders	= $customer_orders->total_orders;

	$guest_orders = $wpdb->get_row("
		SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_total'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND		posts.ID			IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE 	meta_key 		= '_customer_user'
			AND		meta_value		= 0
		)
	");

	$total_guest_sales	= $guest_orders->total_sales;
	$total_guest_orders	= $guest_orders->total_orders;
	?>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total customers', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customers>0) echo $total_customers; else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total customer sales', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_sales>0) echo woocommerce_price($total_customer_sales); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total guest sales', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_guest_sales>0) echo woocommerce_price($total_guest_sales); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total customer orders', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_orders>0) echo $total_customer_orders; else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total guest orders', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_guest_orders>0) echo $total_guest_orders; else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average orders per customer', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_orders>0 && $total_customers>0) echo number_format($total_customer_orders/$total_customers, 2); else _e('n/a', 'woocommerce'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('Signups per day', 'woocommerce'); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
				</div>
			</div>
		</div>
	</div>
	<?php

	$start_date = strtotime('-30 days', current_time('timestamp'));
	$end_date = current_time('timestamp');
	$signups = array();

	// Blank date ranges to begin
	$count = 0;
	$days = ($end_date - $start_date) / (60 * 60 * 24);
	if ($days==0) $days = 1;

	while ($count < $days) :
		$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $start_date))).'000';

		$signups[$time] = 0;

		$count++;
	endwhile;

	foreach ($customers as $customer) :
		if (strtotime($customer->user_registered) > $start_date) :
			$time = strtotime(date('Ymd', strtotime($customer->user_registered))).'000';

			if (isset($signups[$time])) :
				$signups[$time]++;
			else :
				$signups[$time] = 1;
			endif;
		endif;
	endforeach;

	$signups_array = array();
	foreach ($signups as $key => $count) :
		$signups_array[] = array($key, $count);
	endforeach;

	$chart_data = json_encode($signups_array);
	?>
	<script type="text/javascript">
		jQuery(function(){
			var d = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

			for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;

			var placeholder = jQuery("#placeholder");

			var plot = jQuery.plot(placeholder, [ { data: d } ], {
				series: {
					bars: {
						barWidth: 60 * 60 * 24 * 1000,
						align: "center",
						show: true
					}
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true,
					markings: weekendAreas
				},
				xaxis: {
					mode: "time",
					timeformat: "%d %b",
					monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
					tickLength: 1,
					minTickSize: [1, "day"]
				},
				yaxes: [ { position: "right", min: 0, tickSize: 1, tickDecimals: 0 } ],
		   		colors: ["#8a4b75"]
		 	});

		 	placeholder.resize();

			<?php woocommerce_weekend_area_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the stock overview stats.
 *
 * @access public
 * @return void
 */
function woocommerce_stock_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb;

	// Low/No stock lists
	$lowstockamount = get_option('woocommerce_notify_low_stock_amount');
	if (!is_numeric($lowstockamount)) $lowstockamount = 1;

	$nostockamount = get_option('woocommerce_notify_no_stock_amount');
	if (!is_numeric($nostockamount)) $nostockamount = 0;

	$outofstock = array();
	$lowinstock = array();

	// Get low in stock simple/downloadable/virtual products. Grouped don't have stock. Variations need a separate query.
	$args = array(
		'post_type'			=> 'product',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			array(
				'key' 		=> '_manage_stock',
				'value' 	=> 'yes'
			),
			array(
				'key' 		=> '_stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			)
		),
		'tax_query' => array(
			array(
				'taxonomy' 	=> 'product_type',
				'field' 	=> 'slug',
				'terms' 	=> array('simple'),
				'operator' 	=> 'IN'
			)
		)
	);
	$low_stock_products = (array) get_posts($args);

	// Get low stock product variations
	$args = array(
		'post_type'			=> 'product_variation',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			array(
				'key' 		=> '_stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			),
			array(
				'key' 		=> '_stock',
				'value' 	=> array('', false, null),
				'compare' 	=> 'NOT IN'
			)
		)
	);
	$low_stock_variations = (array) get_posts($args);

	// Finally, get low stock variable products (where stock is set for the parent)
	$args = array(
		'post_type'			=> array('product'),
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' 		=> '_manage_stock',
				'value' 	=> 'yes'
			),
			array(
				'key' 		=> '_stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			)
		),
		'tax_query' => array(
			array(
				'taxonomy' 	=> 'product_type',
				'field' 	=> 'slug',
				'terms' 	=> array('variable'),
				'operator' 	=> 'IN'
			)
		)
	);
	$low_stock_variable_products = (array) get_posts($args);

	// Merge results
	$low_in_stock = array_merge($low_stock_products, $low_stock_variations, $low_stock_variable_products);

	?>
	<div id="poststuff" class="woocommerce-reports-wrap halved">
		<div class="woocommerce-reports-left">
			<div class="postbox">
				<h3><span><?php _e('Low stock', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<?php
					if ( $low_in_stock ) {
						echo '<ul class="stock_list">';
						foreach ( $low_in_stock as $product ) {

							$stock 	= (int) get_post_meta( $product->ID, '_stock', true );
							$sku	= get_post_meta( $product->ID, '_sku', true );

							if ( $stock <= $nostockamount ) continue;

							$title = $product->post_title;

							if ( $sku )
								$title .= ' (' . __('SKU', 'woocommerce') . ': ' . $sku . ')';

							if ( $product->post_type=='product' )
								$product_url = admin_url( 'post.php?post=' . $product->ID . '&action=edit' );
							else
								$product_url = admin_url( 'post.php?post=' . $product->post_parent . '&action=edit' );

							printf( '<li><a href="%s"><small>' .  _n('%d in stock', '%d in stock', $stock, 'woocommerce') . '</small> %s</a></li>', $product_url, $stock, $title );

						}
						echo '</ul>';
					} else {
						echo '<p>'.__('No products are low in stock.', 'woocommerce').'</p>';
					}
					?>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-right">
			<div class="postbox">
				<h3><span><?php _e('Out of stock', 'woocommerce'); ?></span></h3>
				<div class="inside">
					<?php
					if ( $low_in_stock ) {
						echo '<ul class="stock_list">';
						foreach ( $low_in_stock as $product ) {

							$stock 	= (int) get_post_meta( $product->ID, '_stock', true );
							$sku	= get_post_meta( $product->ID, '_sku', true );

							if ( $stock > $nostockamount ) continue;

							$title = $product->post_title;

							if ( $sku )
								$title .= ' (' . __('SKU', 'woocommerce') . ': ' . $sku . ')';

							if ( $product->post_type=='product' )
								$product_url = admin_url( 'post.php?post=' . $product->ID . '&action=edit' );
							else
								$product_url = admin_url( 'post.php?post=' . $product->post_parent . '&action=edit' );

							printf( '<li><a href="%s"><small>' .  _n('%d in stock', '%d in stock', $stock, 'woocommerce') . '</small> %s</a></li>', $product_url, $stock, $title );

						}
						echo '</ul>';
					} else {
						echo '<p>'.__('No products are out in stock.', 'woocommerce').'</p>';
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}


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

		$gross = $wpdb->get_var("
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
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		$shipping = $wpdb->get_var("
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
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		$order_tax = $wpdb->get_var("
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
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		$shipping_tax = $wpdb->get_var("
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
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		$order_taxes = $wpdb->get_col("
			SELECT meta.meta_value AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_taxes'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		'{$month}' 			= date_format(posts.post_date,'%Y%m')
		");

		$tax_rows = array();

		if ( $order_taxes ) {
			foreach ( $order_taxes as $order_tax_rows ) {
				$order_tax_rows = maybe_unserialize( $order_tax_rows );
				if ( $order_tax_rows )
					foreach ( $order_tax_rows as $tax_row )
						if ( isset( $tax_row['cart_tax'] ) ) {

							$tax_row_labels[] = $tax_row['label'];

							$tax_rows[ $tax_row['label'] ] = isset( $tax_rows[ $tax_row['label'] ] ) ? $tax_rows[ $tax_row['label'] ] + $tax_row['cart_tax'] + $tax_row['shipping_tax'] : $tax_row['cart_tax'] + $tax_row['shipping_tax'];

						}
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
		<p><label for="show_year"><?php _e('Year:', 'woocommerce'); ?></label>
		<select name="show_year" id="show_year">
			<?php
				for ( $i = $first_year; $i <= date('Y'); $i++ )
					printf( '<option value="%s" %s>%s</option>', $i, selected( $current_year, $i, false ), $i );
			?>
		</select> <input type="submit" class="button" value="<?php _e('Show', 'woocommerce'); ?>" /></p>
	</form>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total taxes for year', 'woocommerce'); ?></span></h3>
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
				<h3><span><?php _e('Total product taxes for year', 'woocommerce'); ?></span></h3>
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
				<h3><span><?php _e('Total shipping tax for year', 'woocommerce'); ?></span></h3>
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
						<th><?php _e('Month', 'woocommerce'); ?></th>
						<th class="total_row"><?php _e('Total Sales', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Order Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e('Total Shipping', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e('Total Product Taxes', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Cart Tax' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e('Total Shipping Taxes', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Tax' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e('Total Taxes', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Cart Tax' and 'Shipping Tax' fields within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e('Net profit', 'woocommerce'); ?> <a class="tips" data-tip="<?php _e("Total sales minus shipping and tax.", 'woocommerce'); ?>" href="#">[?]</a></th>
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
									if ( isset( $tax['tax_rows'][ $label ] ) )
										$total['tax_rows'][ $label ] = isset( $total['tax_rows'][ $label ] ) ? $total['tax_rows'][ $label ] + $tax['tax_rows'][ $label ] : $tax['tax_rows'][ $label ];

							}

							echo '
								<td>' . __('Total', 'woocommerce') . '</td>
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

							foreach ( $tax_row_labels as $label )
								if ( isset( $tax['tax_rows'][ $label ] ) )
									echo '<td class="tax_row">' . woocommerce_price( $tax['tax_rows'][ $label ] ) . '</td>';
								else
									echo '<td class="tax_row">' .  woocommerce_price( 0 ) . '</td>';

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