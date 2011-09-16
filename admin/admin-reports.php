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
				'title' => __('Top sellers', 'woothemes'),
				'description' => '',
				'function' => 'woocommerce_top_sellers'
			)
		),
		__('stock', 'woothemes') => array(),
		__('customers', 'woothemes') => array()
	);
    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
				foreach ($charts as $name => $value) :
					echo '<a href="'.admin_url('admin.php?page=woocommerce_reports&tab='.$name).'" class="nav-tab ';
					if($current_tab==$name) echo 'nav-tab-active';
					echo '">'.ucfirst($name).'</a>';
				endforeach;
			?>
			<?php do_action('woocommerce_reports_tabs'); ?>
		</h2>
		
		<ul class="subsubsub"><li><?php
			$links = array();
			foreach ($charts[$current_tab] as $key => $chart) :
				$link = '<a href="admin.php?page=woocommerce_reports&tab='.$current_tab.'&amp;chart='.$key.'" class="';
				if ($key==$current_chart) $link .= 'current';
				$link .= '">'.$chart['title'].'</a>';
				$links[] = $link;
			endforeach;
			echo implode(' | </li><li>', $links);
		?></li></ul><br class="clear" />
		
		<?php if (isset($charts[$current_tab][$current_chart])) : ?> 
			<h3><?php echo $charts[$current_tab][$current_chart]['title']; ?></h3>
			<?php if ($charts[$current_tab][$current_chart]['description']) : ?><p><?php echo $charts[$current_tab][$current_chart]['description']; ?></p><?php endif; ?>
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
                
                if (item.series.label=="Number of sales") {
                	
                	var y = item.datapoint[1];
                	showTooltip(item.pageX, item.pageY, item.series.label + " - " + y);
                	
                } else {
                	
                	var y = item.datapoint[1].toFixed(2);
                	showTooltip(item.pageX, item.pageY, item.series.label + " - " + "<?php echo get_woocommerce_currency_symbol(); ?>" + y);
                
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
		minDate: "-3M",
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
	$before = date('Y-m-d', $end_date);
	
	$where .= " AND post_date > '$after'";
	$where .= " AND post_date < '$before'";
	
	return $where;
}

/**
 * Daily sales chart
 */
function woocommerce_daily_sales() {
	
	global $start_date, $end_date, $woocommerce;
	
	$start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
	$end_date	= (isset($_POST['end_date'])) ? $_POST['end_date'] : '';
	
	if (!$start_date) $start_date = date('Ymd', strtotime( '2011'.date('m').'01' ));
	if (!$end_date) $end_date = date('Ymd', strtotime('NOW'));
	
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
	    'suppress_filters' => false
	);
	$orders = get_posts( $args );
	
	$order_counts = array();
	$order_amounts = array();
		
	$order_counts[$start_date.'000'] = 0;
	$order_amounts[$start_date.'000'] = 0;
	
	if ($orders) :
		foreach ($orders as $order) :
			
			$order_data = &new woocommerce_order($order->ID);
			
			if ($order_data->status=='cancelled' || $order_data->status=='refunded') continue;
			
			$time = strtotime(date('Ymd', strtotime($order->post_date))).'000';
			
			if (isset($order_counts[$time])) :
				$order_counts[$time]++;
			else :
				$order_counts[$time] = 1;
			endif;
			
			if (isset($order_amounts[$time])) :
				$order_amounts[$time] = $order_amounts[$time] + $order_data->order_total;
			else :
				$order_amounts[$time] = (float) $order_data->order_total;
			endif;
			
		endforeach;
	endif;
	
	if (!isset($order_counts[$end_date.'000'])) $order_counts[$end_date.'000'] = 0;
	if (!isset($order_amounts[$end_date.'000'])) $order_amounts[$end_date.'000'] = 0;
	
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
	<form method="post" action="">
		<p><label for="from"><?php _e('From:', 'woothemes'); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo date('Y-m-d', $start_date); ?>" /> <label for="to"><?php _e('To:', 'woothemes'); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo date('Y-m-d', $end_date); ?>" /> <input type="submit" class="button" value="<?php _e('Show', 'woothemes'); ?>" /></p>
	</form>
	<div id="placeholder" style="width:100%; overflow:hidden; height:520px; position:relative;"></div>
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


