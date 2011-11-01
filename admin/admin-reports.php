<?php
/**
 * Functions used for displaying reports in admin
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

function woocommerce_reports() {

	$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'sales';
	$current_chart = (isset($_GET['chart'])) ? $_GET['chart'] : 0;
	
	$charts = array(
		__('sales', 'woothemes') => array(
			array(
				'title' => __('Overview', 'woothemes'),
				'description' => '',
				'hide_title' => true,
				'function' => 'woocommerce_sales_overview'
			),
			array(
				'title' => __('Daily Sales', 'woothemes'),
				'description' => '',
				'function' => 'woocommerce_daily_sales'
			),
			array(
				'title' => __('Monthly Sales', 'woothemes'),
				'description' => '',
				'function' => 'woocommerce_monthly_sales'
			),
			array(
				'title' => __('Product Sales', 'woothemes'),
				'description' => '',
				'function' => 'woocommerce_product_sales'
			),
			array(
				'title' => __('Top sellers', 'woothemes'),
				'description' => '',
				'function' => 'woocommerce_top_sellers'
			),
			array(
				'title' => __('Top earners', 'woothemes'),
				'description' => '',
				'function' => 'woocommerce_top_earners'
			)
		),
		__('customers', 'woothemes') => array(
			array(
				'title' => __('Overview', 'woothemes'),
				'description' => '',
				'hide_title' => true,
				'function' => 'woocommerce_customer_overview'
			),
		),
		__('stock', 'woothemes') => array(
			array(
				'title' => __('Overview', 'woothemes'),
				'description' => '',
				'hide_title' => true,
				'function' => 'woocommerce_stock_overview'
			),
		)
	);
    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-reports" id="icon-woocommerce"><br></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
				foreach ($charts as $name => $value) :
					echo '<a href="'.admin_url('admin.php?page=woocommerce_reports&tab='.$name).'" class="nav-tab ';
					if($current_tab==$name) echo 'nav-tab-active';
					echo '">'.ucfirst($name).'</a>';
				endforeach;
			?>
			<?php do_action('woocommerce_reports_tabs'); ?>
		</h2>
		
		<?php if (sizeof($charts[$current_tab])>1) : ?><ul class="subsubsub"><li><?php
			$links = array();
			foreach ($charts[$current_tab] as $key => $chart) :
				$link = '<a href="admin.php?page=woocommerce_reports&tab='.$current_tab.'&amp;chart='.$key.'" class="';
				if ($key==$current_chart) $link .= 'current';
				$link .= '">'.$chart['title'].'</a>';
				$links[] = $link;
			endforeach;
			echo implode(' | </li><li>', $links);
		?></li></ul><br class="clear" /><?php endif; ?>
		
		<?php if (isset($charts[$current_tab][$current_chart])) : ?> 
			<?php if (!isset($charts[$current_tab][$current_chart]['hide_title']) || $charts[$current_tab][$current_chart]['hide_title']!=true) : ?><h3><?php echo $charts[$current_tab][$current_chart]['title']; ?></h3>
			<?php if ($charts[$current_tab][$current_chart]['description']) : ?><p><?php echo $charts[$current_tab][$current_chart]['description']; ?></p><?php endif; ?>
			<?php endif; ?>
			<?php
				$func = $charts[$current_tab][$current_chart]['function'];
				if ($func && function_exists($func)) $func();
			?>
		<?php endif; ?>

	</div>
	<?php
}

/**
 * Javascript for highlighting weekends
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
 * Javascript for chart tooltips
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
                
                if (item.series.label=="Sales amount") {
                	
                	var y = item.datapoint[1].toFixed(2);
                	showTooltip(item.pageX, item.pageY, item.series.label + " - " + "<?php echo get_woocommerce_currency_symbol(); ?>" + y);
                	
                } else if (item.series.label=="Number of sales") {
                	
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
 * Javascript for date range
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
 * Orders for range filter function
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
 * Sales overview
 */
function woocommerce_sales_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb;
	
	$total_sales = 0;
	$total_orders = 0;
	$order_items = 0;
	
	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish',
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
	foreach ($orders as $order) :
		$order_items_array = (array) get_post_meta($order->ID, '_order_items', true);
		foreach ($order_items_array as $item) $order_items += (int) $item['qty'];
		$total_sales += get_post_meta($order->ID, '_order_total', true);
		$total_orders++;
	endforeach;
	
	?>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total sales', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_sales>0) echo woocommerce_price($total_sales); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total orders', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo $total_orders. ' ('.$order_items.__(' items', 'woothemes').')'; else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order total', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo woocommerce_price($total_sales/$total_orders); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order items', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo number_format($order_items/$total_orders, 2); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Last 5 orders', 'woothemes'); ?></span></h3>
				<div class="inside">
					<?php
					if ($orders) :
						$count = 0;
						echo '<ul class="recent-orders">';
						foreach ($orders as $order) :
							
							$this_order = &new woocommerce_order( $order->ID );
							
							if ($this_order->user_id > 0) :
								$customer = get_user_by('id', $this_order->user_id);
								$customer = $customer->user_login;
							else :
								$customer = __('Guest', 'woothemes');
							endif;
							
							echo '
							<li>
								<span class="order-status '.sanitize_title($this_order->status).'">'.ucwords($this_order->status).'</span> <a href="'.admin_url('post.php?post='.$order->ID).'&action=edit">'.date_i18n('jS M Y (h:i A)', strtotime($this_order->order_date)).'</a><br />
								<small>'.sizeof($this_order->items).' '._n('item', 'items', sizeof($this_order->items), 'woothemes').' <span class="order-cost">'.__('Total:', 'woothemes') . ' ' . woocommerce_price($this_order->order_total).'</span> <span class="order-customer">'.$customer.'</span></small>
							</li>';
							
							$count++;
							if ($count==5) break;
						endforeach;
						echo '</ul>';
					endif;
					?>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('This months sales', 'woothemes'); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
				</div>
			</div>
		</div>
	</div>
	<?php
	
	$start_date = strtotime(date('Ymd', strtotime( date('Ym').'01' )));
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
				'terms' => array('completed', 'processing', 'on-hold'),
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
			 
			var plot = jQuery.plot(placeholder, [ { label: "Number of sales", data: d }, { label: "Sales amount", data: d2, yaxis: 2 } ], {
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
					tickLength: 1,
					minTickSize: [1, "day"]
				},
				yaxes: [ { min: 0, tickSize: 1, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
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
 * Daily sales chart
 */
function woocommerce_daily_sales() {
	
	global $start_date, $end_date, $woocommerce, $wpdb;
	
	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';
	
	if (!$start_date) $start_date = date('Ymd', strtotime( date('Ym').'01' ));
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
				'terms' => array('completed', 'processing', 'on-hold'),
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
		<p><label for="from"><?php _e('From:', 'woothemes'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woothemes'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woothemes'); ?>" /></p>
	</form>
	
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total sales in range', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_sales>0) echo woocommerce_price($total_sales); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total orders in range', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo $total_orders. ' ('.$order_items.__(' items', 'woothemes').')'; else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order total in range', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo woocommerce_price($total_sales/$total_orders); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order items in range', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo number_format($order_items/$total_orders, 2); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('Sales in range', 'woothemes'); ?></span></h3>
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
			 
			var plot = jQuery.plot(placeholder, [ { label: "Number of sales", data: d }, { label: "Sales amount", data: d2, yaxis: 2 } ], {
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
					tickLength: 1,
					minTickSize: [1, "day"]
				},
				yaxes: [ { min: 0, tickSize: 1, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
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
 * Monthly sales chart
 */
function woocommerce_monthly_sales() {
	
	global $start_date, $end_date, $woocommerce, $wpdb;
	
	$first_year = $wpdb->get_var("SELECT post_date FROM $wpdb->posts ORDER BY post_date ASC LIMIT 1;");
	if ($first_year) $first_year = date('Y', strtotime($first_year)); else $first_year = date('Y');
	
	$current_year = (isset($_POST['show_year'])) ? $_POST['show_year'] : date('Y');
	
	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';
	
	if (!$start_date) $start_date = $current_year.'0101';
	if (!$end_date) $end_date = date('Ym', current_time('timestamp')).'31';
	
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
				'terms' => array('completed', 'processing', 'on-hold'),
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
	$months = ($end_date - $start_date) / (60 * 60 * 24 * 7 * 4);

	while ($count < $months) :
		$time = strtotime(date('Ym', strtotime('+ '.$count.' MONTH', $start_date)).'01').'000';

		$order_counts[$time] = 0;
		$order_amounts[$time] = 0;

		$count++;
	endwhile;
	
	if ($orders) :
		foreach ($orders as $order) :
			
			$order_total = get_post_meta($order->ID, '_order_total', true);			
			$time = strtotime(date('Ym', strtotime($order->post_date)).'01').'000';
			
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
		<p><label for="show_year"><?php _e('Year:', 'woothemes'); ?></label> 
		<select name="show_year" id="show_year">
			<?php
				for ($i = $first_year; $i <= date('Y'); $i++) printf('<option value="%u" %u>%u</option>', $i, selected($current_year, $i, false), $i);
			?>
		</select> <input type="submit" class="button" value="<?php _e('Show', 'woothemes'); ?>" /></p>
	</form>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total sales for year', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_sales>0) echo woocommerce_price($total_sales); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total orders for year', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo $total_orders. ' ('.$order_items.__(' items', 'woothemes').')'; else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order total for year', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo woocommerce_price($total_sales/$total_orders); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average order items for year', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_orders>0) echo number_format($order_items/$total_orders, 2); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('Monthly sales for year', 'woothemes'); ?></span></h3>
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
			 
			var plot = jQuery.plot(placeholder, [ { label: "Number of sales", data: d }, { label: "Sales amount", data: d2, yaxis: 2 } ], {
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
					tickLength: 1,
					minTickSize: [1, "month"]
				},
				yaxes: [ { min: 0, tickSize: 1, tickDecimals: 0 }, { position: "right", min: 0, tickDecimals: 2 } ],
		   		colors: ["#8a4b75", "#47a03e"]
		 	});
		 	
		 	placeholder.resize();
	 	
			<?php woocommerce_tooltip_js(); ?>
		});
	</script>
	<?php
}


/**
 * Top sellers chart
 */
function woocommerce_top_sellers() {
	
	global $start_date, $end_date, $woocommerce;
	
	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';
	
	if (!$start_date) $start_date = date('Ymd', strtotime( date('Ym').'01' ));
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
	$found_products = array_slice($found_products, 0, 25, true);
	$found_products = array_reverse($found_products, true);
	reset($found_products);
	
	remove_filter( 'posts_where', 'orders_within_range' );
	?>
	<form method="post" action="">
		<p><label for="from"><?php _e('From:', 'woothemes'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woothemes'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woothemes'); ?>" /></p>
	</form>
	<table class="bar_chart">
		<thead>
			<tr>
				<th><?php _e('Product', 'woothemes'); ?></th>
				<th><?php _e('Sales', 'woothemes'); ?></th>
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
					else :
						$product_name = __('Product does not exist', 'woothemes');
					endif;
					
					echo '<tr><th>'.$product_name.'</th><td><span style="width:'.$width.'%"><span>'.$sales.'</span></span></td></tr>';
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
 * Top earners chart
 */
function woocommerce_top_earners() {
	
	global $start_date, $end_date, $woocommerce;
	
	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';
	
	if (!$start_date) $start_date = date('Ymd', strtotime( date('Ym').'01' ));
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
				$found_products[$item['id']] = isset($found_products[$item['id']]) ? $found_products[$item['id']] + ($item['qty'] + $item['cost']) : ($item['qty'] + $item['cost']);
			endforeach;
		endforeach;
	endif;

	asort($found_products);
	$found_products = array_slice($found_products, 0, 25, true);
	$found_products = array_reverse($found_products, true);
	reset($found_products);
	
	remove_filter( 'posts_where', 'orders_within_range' );
	?>
	<form method="post" action="">
		<p><label for="from"><?php _e('From:', 'woothemes'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e('To:', 'woothemes'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woothemes'); ?>" /></p>
	</form>
	<table class="bar_chart">
		<thead>
			<tr>
				<th><?php _e('Product', 'woothemes'); ?></th>
				<th><?php _e('Sales', 'woothemes'); ?></th>
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
					else :
						$product_name = __('Product does not exist', 'woothemes');
					endif;
					
					echo '<tr><th>'.$product_name.'</th><td><span style="width:'.$width.'%"><span>'.woocommerce_price($sales).'</span></span></td></tr>';
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
 * Individual product sales chart
 */
function woocommerce_product_sales() {
	
	global $start_date, $end_date, $woocommerce;
	
	$chosen_product_id = (isset($_POST['product_id'])) ? $_POST['product_id'] : '';
	
	if ($chosen_product_id) :
		$start_date = date('Ym', strtotime( '-12 MONTHS' )).'01';
		$end_date = date('Ymd', current_time('timestamp'));
		
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
		
		$max_sales = 0;
		$max_totals = 0;
		$product_sales = array();
		$product_totals = array();
		
		if ($orders) :
			foreach ($orders as $order) :
				$date = date('Ym', strtotime( $order->post_date ));
				$order_items = (array) get_post_meta( $order->ID, '_order_items', true );
				foreach ($order_items as $item) :
					if ($item['id']!=$chosen_product_id) continue;
					$product_sales[$date] = isset($product_sales[$date]) ? $product_sales[$date] + $item['qty'] : $item['qty'];
					$product_totals[$date] = isset($product_totals[$date]) ? $product_totals[$date] + ($item['qty'] * $item['cost']) : ($item['qty'] * $item['cost']);
					
					if ($product_sales[$date] > $max_sales) $max_sales = $product_sales[$date];
					if ($product_totals[$date] > $max_totals) $max_totals = $product_totals[$date];
				endforeach;
			endforeach;
		endif;
		
		remove_filter( 'posts_where', 'orders_within_range' );
	endif;
	?>
	<form method="post" action="">
		<p><label for="from"><?php _e('Product:', 'woothemes'); ?></label>
		<select name="product_id" id="product_id">
			<?php
				echo '<option value="">'.__('Choose an product&hellip;', 'woothemes').'</option>';
				
				$args = array(
					'post_type' 		=> 'product',
					'posts_per_page' 	=> -1,
					'post_status'		=> 'publish',
					'post_parent'		=> 0,
					'order'				=> 'ASC',
					'orderby'			=> 'title'
				);
				$products = get_posts( $args );
				
				if ($products) foreach ($products as $product) :
					
					$sku = get_post_meta($product->ID, 'sku', true);
					
					if ($sku) $sku = ' SKU: '.$sku;
					
					echo '<option value="'.$product->ID.'" '.selected($chosen_product_id, $product->ID, false).'>'.$product->post_title.$sku.' (#'.$product->ID.''.$sku.')</option>';
					
				endforeach;
			?>
		</select> <input type="submit" class="button" value="<?php _e('Show', 'woothemes'); ?>" /></p>
	</form>
	<?php if ($chosen_product_id) : ?>
	<table class="bar_chart">
		<thead>
			<tr>
				<th><?php _e('Month', 'woothemes'); ?></th>
				<th><?php _e('Sales', 'woothemes'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				if (sizeof($product_sales)>0) foreach ($product_sales as $date => $sales) :
					$width = ($sales>0) ? (round($sales) / round($max_sales)) * 100 : 0;
					$width2 = ($product_totals[$date]>0) ? (round($product_totals[$date]) / round($max_totals)) * 100 : 0;

					echo '<tr><th>'.date('F', strtotime($date.'01')).'</th><td>
						<span style="width:'.$width.'%"><span>'.$sales.'</span></span>
						<span class="alt" style="width:'.$width2.'%"><span>'.woocommerce_price($product_totals[$date]).'</span></span>
					</td></tr>';
				endforeach; else echo '<tr><td colspan="2">'.__('No sales :(', 'woothemes').'</td></tr>';
			?>
		</tbody>
	</table>
	<?php
	endif;
}


/**
 * Customer overview
 */
function woocommerce_customer_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb;
	
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
	
	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
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
	foreach ($orders as $order) :
		if (get_post_meta( $order->ID, '_customer_user', true )>0) :
			$total_customer_sales += get_post_meta($order->ID, '_order_total', true);
			$total_customer_orders++;
		else :
			$total_guest_sales += get_post_meta($order->ID, '_order_total', true);
			$total_guest_orders++;
		endif;
	endforeach;
	
	?>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e('Total customers', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customers>0) echo $total_customers; else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total customer sales', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_sales>0) echo woocommerce_price($total_customer_sales); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total guest sales', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_guest_sales>0) echo woocommerce_price($total_guest_sales); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total customer orders', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_orders>0) echo $total_customer_orders; else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Total guest orders', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_guest_orders>0) echo $total_guest_orders; else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e('Average orders per customer', 'woothemes'); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_orders>0 && $total_customers>0) echo number_format($total_customer_orders/$total_customers, 2); else _e('n/a', 'woothemes'); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e('Signups per day', 'woothemes'); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
				</div>
			</div>
		</div>
	</div>
	<?php
	
	$start_date = strtotime('-30 days');
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
 * Stock overview
 */
function woocommerce_stock_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb;
	
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
	
	$args = array(
	    'numberposts'     => -1,
	    'orderby'         => 'post_date',
	    'order'           => 'DESC',
	    'post_type'       => 'shop_order',
	    'post_status'     => 'publish' ,
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
	foreach ($orders as $order) :
		if (get_post_meta( $order->ID, '_customer_user', true )>0) :
			$total_customer_sales += get_post_meta($order->ID, '_order_total', true);
			$total_customer_orders++;
		else :
			$total_guest_sales += get_post_meta($order->ID, '_order_total', true);
			$total_guest_orders++;
		endif;
	endforeach;
	
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
				'key' 		=> 'manage_stock',
				'value' 	=> 'yes'
			),
			array(
				'key' 		=> 'stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			)
		),
		'tax_query' => array(
			array(
				'taxonomy' 	=> 'product_type',
				'field' 	=> 'slug',
				'terms' 	=> array('simple', 'downloadable', 'virtual'),
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
				'key' 		=> 'stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			),
			array(
				'key' 		=> 'stock',
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
				'key' 		=> 'manage_stock',
				'value' 	=> 'yes'
			),
			array(
				'key' 		=> 'stock',
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
				<h3><span><?php _e('Low stock', 'woothemes'); ?></span></h3>
				<div class="inside">
					<?php
					if ($low_in_stock) :
						echo '<ul class="stock_list">';
						foreach ($low_in_stock as $product) :
							$stock = get_post_meta($product->ID, 'stock', true);
							if ($stock<=$nostockamount) continue;
							echo '<li><a href="';
							if ($product->post_type=='product') echo admin_url('post.php?post='.$product->ID.'&action=edit'); else echo admin_url('post.php?post='.$product->post_parent.'&action=edit');
							echo '"><small>'.$stock.__(' in stock', 'woothemes').'</small> '.$product->post_title.'</a></li>';
						endforeach;
						echo '</ul>';
					else :
						echo '<p>'.__('No products are low in stock.', 'woothemes').'</p>';
					endif;
					?>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-right">
			<div class="postbox">
				<h3><span><?php _e('Out of stock', 'woothemes'); ?></span></h3>
				<div class="inside">
					<?php
					if ($low_in_stock) :
						echo '<ul class="stock_list">';
						foreach ($low_in_stock as $product) :
							$stock = get_post_meta($product->ID, 'stock', true);
							if ($stock>$nostockamount) continue;
							echo '<li><a href="';
							if ($product->post_type=='product') echo admin_url('post.php?post='.$product->ID.'&action=edit'); else echo admin_url('post.php?post='.$product->post_parent.'&action=edit');
							echo '"><small>'.$stock.__(' in stock', 'woothemes').'</small> '.$product->post_title.'</a></li>';
						endforeach;
						echo '</ul>';
					else :
						echo '<p>'.__('No products are out in stock.', 'woothemes').'</p>';
					endif;
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}