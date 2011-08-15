<?php
/**
 * Functions used for displaying the WooCommerce store dashboard
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

/**
 * Function for showing the dashboard
 * 
 * The dashboard shows widgets for things such as:
 *		- Products
 *		- Sales
 *		- Recent reviews
 *		- Inventory notifications
 *		- WooCommerce news and updates
 */
function woocommerce_dashboard() {
	?>
	<div class="wrap woocommerce">
        <div class="icon32 woocommerce_icon"><br/></div>
		<h2><?php _e('Dashboard', 'woothemes'); ?></h2>
		<div id="woocommerce_dashboard">
			
			<div id="dashboard-widgets" class="metabox-holder">
			
				<div class="postbox-container" style="width:49%;">
				
					<div id="woocommerce_right_now" class="woocommerce_right_now postbox">
						<h3><?php _e('Right Now', 'woothemes') ?></h3>
						<div class="inside">

							<div class="table table_content">
								<p class="sub"><?php _e('Shop Content', 'woothemes'); ?></p>
								<table>
									<tbody>
										<tr class="first">
											<td class="first b"><a href="edit.php?post_type=product"><?php
												$num_posts = wp_count_posts( 'product' );
												$num = number_format_i18n( $num_posts->publish );
												echo $num;
											?></a></td>
											<td class="t"><a href="edit.php?post_type=product"><?php _e('Products', 'woothemes'); ?></a></td>
										</tr>
										<tr>
											<td class="first b"><a href="edit-tags.php?taxonomy=product_cat&post_type=product"><?php
												echo wp_count_terms('product_cat');
											?></a></td>
											<td class="t"><a href="edit-tags.php?taxonomy=product_cat&post_type=product"><?php _e('Product Categories', 'woothemes'); ?></a></td>
										</tr>
										<tr>
											<td class="first b"><a href="edit-tags.php?taxonomy=product_tag&post_type=product"><?php
												echo wp_count_terms('product_tag');
											?></a></td>
											<td class="t"><a href="edit-tags.php?taxonomy=product_tag&post_type=product"><?php _e('Product Tag', 'woothemes'); ?></a></td>
										</tr>
										<tr>
											<td class="first b"><a href="admin.php?page=attributes"><?php 
												echo sizeof(woocommerce::$attribute_taxonomies);
											?></a></td>
											<td class="t"><a href="admin.php?page=attributes"><?php _e('Attribute taxonomies', 'woothemes'); ?></a></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="table table_discussion">
								<p class="sub"><?php _e('Orders', 'woothemes'); ?></p>
								<table>
									<tbody>
										<?php $woocommerce_orders = &new woocommerce_orders(); ?>
										<tr class="first">
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=pending"><span class="total-count"><?php echo $woocommerce_orders->pending_count; ?></span></a></td>
											<td class="last t"><a class="pending" href="edit.php?post_type=shop_order&shop_order_status=pending"><?php _e('Pending', 'woothemes'); ?></a></td>
										</tr>
										<tr>
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=on-hold"><span class="total-count"><?php echo $woocommerce_orders->on_hold_count; ?></span></a></td>
											<td class="last t"><a class="onhold" href="edit.php?post_type=shop_order&shop_order_status=on-hold"><?php _e('On-Hold', 'woothemes'); ?></a></td>
										</tr>
										<tr>
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=processing"><span class="total-count"><?php echo $woocommerce_orders->processing_count; ?></span></a></td>
											<td class="last t"><a class="processing" href="edit.php?post_type=shop_order&shop_order_status=processing"><?php _e('Processing', 'woothemes'); ?></a></td>
										</tr>
										<tr>
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=completed"><span class="total-count"><?php echo $woocommerce_orders->completed_count; ?></span></a></td>
											<td class="last t"><a class="complete" href="edit.php?post_type=shop_order&shop_order_status=completed"><?php _e('Completed', 'woothemes'); ?></a></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="versions">
							
								<p id="wp-version-message"><?php _e('You are using', 'woothemes'); ?> <strong>WooCommerce <?php echo WOOCOMMERCE_VERSION; ?></strong> <?php _e('and', 'woothemes'); ?> <strong><?php if (is_multisite()) echo 'WPMU'; else echo 'WP'; ?> <?php echo bloginfo('version'); ?></strong> <?php _e('running on PHP', 'woothemes'); ?> <?php if(function_exists('phpversion')) echo phpversion(); ?> (<?php echo $_SERVER['SERVER_SOFTWARE']; ?>). <?php if(function_exists('fsockopen')) echo '<span style="color:green">' . __('Your server supports fsockopen.', 'woothemes'). '</span>'; else echo '<span style="color:red">' . __('Your server does not support fsockopen.', 'woothemes'). '</span>'; ?></p>
								
							</div>
							<div class="clear"></div>
						</div>
					
					</div><!-- postbox end -->

					<div class="postbox">
						<h3 class="hndle" id="poststuff"><span><?php _e('Recent Orders', 'woothemes') ?></span></h3>
						<div class="inside">
							<?php
								$args = array(
								    'numberposts'     => 8,
								    'orderby'         => 'post_date',
								    'order'           => 'DESC',
								    'post_type'       => 'shop_order',
								    'post_status'     => 'publish' 
								);
								$orders = get_posts( $args );
								if ($orders) :
									echo '<ul class="recent-orders">';
									foreach ($orders as $order) :
										
										$this_order = &new woocommerce_order( $order->ID );
										
										echo '
										<li>
											<span class="order-status '.sanitize_title($this_order->status).'">'.ucwords($this_order->status).'</span> <a href="'.admin_url('post.php?post='.$order->ID).'&action=edit">'.date_i18n('l jS \of F Y h:i:s A', strtotime($this_order->order_date)).'</a><br />
											<small>'.sizeof($this_order->items).' '._n('item', 'items', sizeof($this_order->items), 'woothemes').' <span class="order-cost">'.__('Total: ', 'woothemes').woocommerce_price($this_order->order_total).'</span></small>
										</li>';

									endforeach;
									echo '</ul>';
								endif;
							?>
						</div>
					</div><!-- postbox end -->	
					
					<?php if (get_option('woocommerce_manage_stock')=='yes') : ?>
					<div class="postbox woocommerce_right_now">
						<h3 class="hndle" id="poststuff"><span><?php _e('Stock Report', 'woothemes') ?></span></h3>
						<div class="inside">
							
							<?php
							
							$lowstockamount = get_option('woocommerce_notify_low_stock_amount');
							if (!is_numeric($lowstockamount)) $lowstockamount = 1;
							
							$nostockamount = get_option('woocommerce_notify_no_stock_amount');
							if (!is_numeric($nostockamount)) $nostockamount = 1;
							
							$outofstock = array();
							$lowinstock = array();
							$args = array(
								'post_type'	=> 'product',
								'post_status' => 'publish',
								'ignore_sticky_posts'	=> 1,
								'posts_per_page' => -1
							);
							$my_query = new WP_Query($args);
							if ($my_query->have_posts()) : while ($my_query->have_posts()) : $my_query->the_post(); 
								
								$_product = &new woocommerce_product( $my_query->post->ID );
								if (!$_product->managing_stock()) continue;

								$thisitem = '<tr class="first">
									<td class="first b"><a href="post.php?post='.$my_query->post->ID.'&action=edit">'.$_product->stock.'</a></td>
									<td class="t"><a href="post.php?post='.$my_query->post->ID.'&action=edit">'.$my_query->post->post_title.'</a></td>
								</tr>';
								
								if ($_product->stock<=$nostockamount) :
									$outofstock[] = $thisitem;
									continue;
								endif;
								
								if ($_product->stock<=$lowstockamount) $lowinstock[] = $thisitem;

							endwhile; endif;
							wp_reset_query();
							
							if (sizeof($lowinstock)==0) :
								$lowinstock[] = '<tr><td colspan="2">'.__('No products are low in stock.', 'woothemes').'</td></tr>';
							endif;
							if (sizeof($outofstock)==0) :
								$outofstock[] = '<tr><td colspan="2">'.__('No products are out of stock.', 'woothemes').'</td></tr>';
							endif;
							?>
										
							<div class="table table_content">
								<p class="sub"><?php _e('Low Stock', 'woothemes'); ?></p>
								<table>
									<tbody>
										<?php echo implode('', $lowinstock); ?>
									</tbody>
								</table>
							</div>
							<div class="table table_discussion">
								<p class="sub"><?php _e('Out of Stock/Backorders', 'woothemes'); ?></p>
								<table>
									<tbody>
										<?php echo implode('', $outofstock); ?>
									</tbody>
								</table>
							</div>
							<div class="clear"></div>
							
						</div>
					</div><!-- postbox end -->
					<?php endif; ?>
					
				
				</div>
				<div class="postbox-container" style="width:49%; float:right; overflow:hidden;">
					
					<?php
						global $current_month_offset;
						
						$current_month_offset = (int) date('m');
						
						if (isset($_GET['month'])) $current_month_offset = (int) $_GET['month'];
					?>
					<div class="postbox stats" id="woocommerce-stats">
						<h3 class="hndle" id="poststuff">
							<?php if ($current_month_offset!=date('m')) : ?><a href="admin.php?page=woocommerce&amp;month=<?php echo $current_month_offset+1; ?>" class="next">Next Month &rarr;</a><?php endif; ?>
							<a href="admin.php?page=woocommerce&amp;month=<?php echo $current_month_offset-1; ?>" class="previous">&larr; Previous Month</a>
							<span><?php _e('Monthly Sales', 'woothemes') ?></span></h3>
						<div class="inside">
							<div id="placeholder" style="width:100%; height:300px; position:relative;"></div>
							<script type="text/javascript">
								/* <![CDATA[ */

								jQuery(function(){
									
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
								            // when we don't set yaxis, the rectangle automatically
								            // extends to infinity upwards and downwards
								            markings.push({ xaxis: { from: i, to: i + 2 * 24 * 60 * 60 * 1000 } });
								            i += 7 * 24 * 60 * 60 * 1000;
								        } while (i < axes.xaxis.max);
								 
								        return markings;
								    }
								    
								    <?php
			    
								    	function orders_this_month( $where = '' ) {
								    		global $current_month_offset;
								    		
								    		$month = $current_month_offset;
								    		$year = (int) date('Y');
								    		
								    		$first_day = strtotime("{$year}-{$month}-01");
								    		$last_day = strtotime('-1 second', strtotime('+1 month', $first_day));
								    		
								    		$after = date('Y-m-d', $first_day);
								    		$before = date('Y-m-d', $last_day);
								    		
											$where .= " AND post_date > '$after'";
											$where .= " AND post_date < '$before'";
											
											return $where;
										}
										add_filter( 'posts_where', 'orders_this_month' );

										$args = array(
										    'numberposts'     => -1,
										    'orderby'         => 'post_date',
										    'order'           => 'DESC',
										    'post_type'       => 'shop_order',
										    'post_status'     => 'publish' ,
										    'suppress_filters' => false
										);
										$orders = get_posts( $args );
										
										$order_counts = array();
										$order_amounts = array();
											
										// Blank date ranges to begin
										$month = $current_month_offset;
							    		$year = (int) date('Y');
							    		
							    		$first_day = strtotime("{$year}-{$month}-01");
							    		$last_day = strtotime('-1 second', strtotime('+1 month', $first_day));
		
										if ((date('m') - $current_month_offset)==0) :
											$up_to = date('d', strtotime('NOW'));
										else :
											$up_to = date('d', $last_day);
										endif;
										$count = 0;
										
										while ($count < $up_to) :
											
											$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $first_day))).'000';
											
											$order_counts[$time] = 0;
											$order_amounts[$time] = 0;

											$count++;
										endwhile;
										
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
										
										remove_filter( 'posts_where', 'orders_this_month' );
									?>
										
								    var d = [
								    	<?php
								    		$values = array();
								    		foreach ($order_counts as $key => $value) $values[] = "[$key, $value]";
								    		echo implode(',', $values);
								    	?>
									];
							    	
							    	for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;
							    	
							    	var d2 = [
								    	<?php
								    		$values = array();
								    		foreach ($order_amounts as $key => $value) $values[] = "[$key, $value]";
								    		echo implode(',', $values);
								    	?>
							    	];
								    
								    for (var i = 0; i < d2.length; ++i) d2[i][0] += 60 * 60 * 1000;

									var plot = jQuery.plot(jQuery("#placeholder"), [ { label: "Number of sales", data: d }, { label: "Sales amount", data: d2, yaxis: 2 } ], {
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
					               		colors: ["#21759B", "#ed8432"]
					             	});
						             
									function showTooltip(x, y, contents) {
								        jQuery('<div id="tooltip">' + contents + '</div>').css( {
								            position: 'absolute',
								            display: 'none',
								            top: y + 5,
								            left: x + 5,
								            border: '1px solid #fdd',
								            padding: '2px',
								            'background-color': '#fee',
								            opacity: 0.80
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
							                    	showTooltip(item.pageX, item.pageY, item.series.label + " - <?php echo get_woocommerce_currency_symbol(); ?>" + y);
							                    
							                    }
			
							                }
							            }
							            else {
							                jQuery("#tooltip").remove();
							                previousPoint = null;            
							            }
								    });
									
								});
								
								/* ]]> */
							</script>
						</div>
					</div><!-- postbox end -->	
					
					<div class="postbox">
						<h3 class="hndle" id="poststuff"><span><?php _e('Recent Product Reviews', 'woothemes') ?></span></h3>
						<div class="inside woocommerce-reviews-widget">
							<?php
								global $wpdb;
								$comments = $wpdb->get_results("SELECT *, SUBSTRING(comment_content,1,100) AS comment_excerpt
								FROM $wpdb->comments
								LEFT JOIN $wpdb->posts ON ($wpdb->comments.comment_post_ID = $wpdb->posts.ID)
								WHERE comment_approved = '1' 
								AND comment_type = '' 
								AND post_password = ''
								AND post_type = 'product'
								ORDER BY comment_date_gmt DESC
								LIMIT 5" );
								
								if ($comments) : 
									echo '<ul>';
									foreach ($comments as $comment) :
										
										echo '<li>';
										
										echo get_avatar($comment->comment_author, '32');
										
										$rating = get_comment_meta( $comment->comment_ID, 'rating', true );
										
										echo '<div class="star-rating" title="'.$rating.'">
											<span style="width:'.($rating*16).'px">'.$rating.' '.__('out of 5', 'woothemes').'</span></div>';
											
										echo '<h4 class="meta"><a href="'.get_permalink($comment->ID).'#comment-'.$comment->comment_ID .'">'.$comment->post_title.'</a> reviewed by ' .strip_tags($comment->comment_author) .'</h4>';
										echo '<blockquote>'.strip_tags($comment->comment_excerpt).' [...]</blockquote></li>';
										
									endforeach;
									echo '</ul>';
								else :
									echo '<p>'.__('There are no product reviews yet.', 'woothemes').'</p>';
								endif;
							?>
						</div>
					</div><!-- postbox end -->	
					
					<div class="postbox">
						<h3 class="hndle" id="poststuff"><span><?php _e('Useful Links', 'woothemes') ?></span></h3>
						<div class="inside woocommerce-links-widget">
				     		<ul class="links">
				     			<li><a href="http://www.woothemes.com/"><?php _e('WooThemes', 'woothemes'); ?></a> &ndash; <?php _e('Premium WordPress Themes', 'woothemes'); ?></li>
				     			<li><a href="https://github.com/mikejolley/woocommerce"><?php _e('WooCommerce on Github', 'woothemes'); ?></a> &ndash; <?php _e('Help extend and develop WooCommerce.', 'woothemes'); ?></li>
				     			<li><a href="http://wordpress.org/extend/plugins/woocommerce/"><?php _e('WooCommerce on WordPress.org', 'woothemes'); ?></a> &ndash; <?php _e('Leave us a rating if you like WooCommerce!', 'woothemes'); ?></li>
				     		</ul>
				     		<div class="social">
				     			
				     			<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwoocommerce.com&amp;send=false&amp;layout=standard&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=segoe+ui&amp;height=24" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:24px;" allowTransparency="true"></iframe>
				     			
				     			<p><a href="http://twitter.com/share" class="twitter-share-button" data-url="http://woocommerce.com/" data-text="WooCommerce" data-count="horizontal" data-via="woocommerce" data-related="WooThemes:Creators">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script></p>
				     			
				     			<p><g:plusone size="medium" href="http://woocommerce.com/"></g:plusone><script type="text/javascript" src="https://apis.google.com/js/plusone.js">{lang: 'en-GB'}</script></p>
				     			
				     		</div>
				     		<div class="clear"></div>
						</div>
					</div><!-- postbox end -->	
					
				</div>
			</div>
		</div>
	</div>
	<?php
}
