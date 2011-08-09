<?php
/**
 * Functions used for displaying the jigoshop dashboard
 *
 * @author 		Jigowatt
 * @category 	Admin
 * @package 	JigoShop
 */

/**
 * Function for showing the dashboard
 * 
 * The dashboard shows widget for things such as:
 *		- Products
 *		- Sales
 *		- Recent reviews
 *
 * @since 		1.0
 * @usedby 		jigoshop_admin_menu()
 */
function jigoshop_dashboard() {
	?>
	<div class="wrap jigoshop">
        <div class="icon32 jigoshop_icon"><br/></div>
		<h2><?php _e('Jigoshop Dashboard','jigoshop'); ?></h2>
		<div id="jigoshop_dashboard">
			
			<div id="dashboard-widgets" class="metabox-holder">
			
				<div class="postbox-container" style="width:49%;">
				
					<div id="jigoshop_right_now" class="jigoshop_right_now postbox">
						<h3><?php _e('Right Now', 'jigoshop') ?></h3>
						<div class="inside">

							<div class="table table_content">
								<p class="sub"><?php _e('Shop Content', 'jigoshop'); ?></p>
								<table>
									<tbody>
										<tr class="first">
											<td class="first b"><a href="edit.php?post_type=product"><?php
												$num_posts = wp_count_posts( 'product' );
												$num = number_format_i18n( $num_posts->publish );
												echo $num;
											?></a></td>
											<td class="t"><a href="edit.php?post_type=product"><?php _e('Products', 'jigoshop'); ?></a></td>
										</tr>
										<tr>
											<td class="first b"><a href="edit-tags.php?taxonomy=product_cat&post_type=product"><?php
												echo wp_count_terms('product_cat');
											?></a></td>
											<td class="t"><a href="edit-tags.php?taxonomy=product_cat&post_type=product"><?php _e('Product Categories', 'jigoshop'); ?></a></td>
										</tr>
										<tr>
											<td class="first b"><a href="edit-tags.php?taxonomy=product_tag&post_type=product"><?php
												echo wp_count_terms('product_tag');
											?></a></td>
											<td class="t"><a href="edit-tags.php?taxonomy=product_tag&post_type=product"><?php _e('Product Tag', 'jigoshop'); ?></a></td>
										</tr>
										<tr>
											<td class="first b"><a href="admin.php?page=attributes"><?php 
												echo sizeof(jigoshop::$attribute_taxonomies);
											?></a></td>
											<td class="t"><a href="admin.php?page=attributes"><?php _e('Attribute taxonomies', 'jigoshop'); ?></a></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="table table_discussion">
								<p class="sub"><?php _e('Orders', 'jigoshop'); ?></p>
								<table>
									<tbody>
										<?php $jigoshop_orders = &new jigoshop_orders(); ?>
										<tr class="first">
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=pending"><span class="total-count"><?php echo $jigoshop_orders->pending_count; ?></span></a></td>
											<td class="last t"><a class="pending" href="edit.php?post_type=shop_order&shop_order_status=pending"><?php _e('Pending', 'jigoshop'); ?></a></td>
										</tr>
										<tr>
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=on-hold"><span class="total-count"><?php echo $jigoshop_orders->on_hold_count; ?></span></a></td>
											<td class="last t"><a class="onhold" href="edit.php?post_type=shop_order&shop_order_status=on-hold"><?php _e('On-Hold', 'jigoshop'); ?></a></td>
										</tr>
										<tr>
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=processing"><span class="total-count"><?php echo $jigoshop_orders->processing_count; ?></span></a></td>
											<td class="last t"><a class="processing" href="edit.php?post_type=shop_order&shop_order_status=processing"><?php _e('Processing', 'jigoshop'); ?></a></td>
										</tr>
										<tr>
											<td class="b"><a href="edit.php?post_type=shop_order&shop_order_status=completed"><span class="total-count"><?php echo $jigoshop_orders->completed_count; ?></span></a></td>
											<td class="last t"><a class="complete" href="edit.php?post_type=shop_order&shop_order_status=completed"><?php _e('Completed', 'jigoshop'); ?></a></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="versions">
								<p id="wp-version-message"><?php _e('You are using', 'jigoshop'); ?> <strong>JigoShop <?php echo JIGOSHOP_VERSION; ?>.</strong></p>
							</div>
							<div class="clear"></div>
						</div>
					
					</div><!-- postbox end -->

					<div class="postbox">
						<h3 class="hndle" id="poststuff"><span><?php _e('Recent Orders', 'jigoshop') ?></span></h3>
						<div class="inside">
							<?php
								$args = array(
								    'numberposts'     => 10,
								    'orderby'         => 'post_date',
								    'order'           => 'DESC',
								    'post_type'       => 'shop_order',
								    'post_status'     => 'publish' 
								);
								$orders = get_posts( $args );
								if ($orders) :
									echo '<ul class="recent-orders">';
									foreach ($orders as $order) :
										
										$this_order = &new jigoshop_order( $order->ID );
										
										echo '
										<li>
											<span class="order-status '.sanitize_title($this_order->status).'">'.ucwords($this_order->status).'</span> <a href="'.admin_url('post.php?post='.$order->ID).'&action=edit">'.date_i18n('l jS \of F Y h:i:s A', strtotime($this_order->order_date)).'</a><br />
											<small>'.sizeof($this_order->items).' '._n('item', 'items', sizeof($this_order->items), 'jigoshop').' <span class="order-cost">'.__('Total: ', 'jigoshop').jigoshop_price($this_order->order_total).'</span></small>
										</li>';

									endforeach;
									echo '</ul>';
								endif;
							?>
						</div>
					</div><!-- postbox end -->	
					
					<?php if (get_option('jigoshop_manage_stock')=='yes') : ?>
					<div class="postbox jigoshop_right_now">
						<h3 class="hndle" id="poststuff"><span><?php _e('Stock Report', 'jigoshop') ?></span></h3>
						<div class="inside">
							
							<?php
							
							$lowstockamount = get_option('jigoshop_notify_low_stock_amount');
							if (!is_numeric($lowstockamount)) $lowstockamount = 1;
							
							$nostockamount = get_option('jigoshop_notify_no_stock_amount');
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
								
								$_product = &new jigoshop_product( $my_query->post->ID );
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
								$lowinstock[] = '<tr><td colspan="2">'.__('No products are low in stock.', 'jigoshop').'</td></tr>';
							endif;
							if (sizeof($outofstock)==0) :
								$outofstock[] = '<tr><td colspan="2">'.__('No products are out of stock.', 'jigoshop').'</td></tr>';
							endif;
							?>
										
							<div class="table table_content">
								<p class="sub"><?php _e('Low Stock', 'jigoshop'); ?></p>
								<table>
									<tbody>
										<?php echo implode('', $lowinstock); ?>
									</tbody>
								</table>
							</div>
							<div class="table table_discussion">
								<p class="sub"><?php _e('Out of Stock/Backorders', 'jigoshop'); ?></p>
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
				<div class="postbox-container" style="width:49%; float:right;">
					
					<?php
						global $current_month_offset;
						
						$current_month_offset = (int) date('m');
						
						if (isset($_GET['month'])) $current_month_offset = (int) $_GET['month'];
					?>
					<div class="postbox stats" id="jigoshop-stats">
						<h3 class="hndle" id="poststuff">
							<?php if ($current_month_offset!=date('m')) : ?><a href="admin.php?page=jigoshop&amp;month=<?php echo $current_month_offset+1; ?>" class="next">Next Month &rarr;</a><?php endif; ?>
							<a href="admin.php?page=jigoshop&amp;month=<?php echo $current_month_offset-1; ?>" class="previous">&larr; Previous Month</a>
							<span><?php _e('Monthly Sales', 'jigoshop') ?></span></h3>
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
												
												$order_data = &new jigoshop_order($order->ID);
												
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
							                    	showTooltip(item.pageX, item.pageY, item.series.label + " - <?php echo get_jigoshop_currency_symbol(); ?>" + y);
							                    
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
						<h3 class="hndle" id="poststuff"><span><?php _e('Recent Product Reviews', 'jigoshop') ?></span></h3>
						<div class="inside jigoshop-reviews-widget">
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
											<span style="width:'.($rating*16).'px">'.$rating.' '.__('out of 5', 'jigoshop').'</span></div>';
											
										echo '<h4 class="meta"><a href="'.get_permalink($comment->ID).'#comment-'.$comment->comment_ID .'">'.$comment->post_title.'</a> reviewed by ' .strip_tags($comment->comment_author) .'</h4>';
										echo '<blockquote>'.strip_tags($comment->comment_excerpt).' [...]</blockquote></li>';
										
									endforeach;
									echo '</ul>';
								else :
									echo '<p>'.__('There are no product reviews yet.', 'jigoshop').'</p>';
								endif;
							?>
						</div>
					</div><!-- postbox end -->	

					<div class="postbox">
						<h3 class="hndle" id="poststuff"><span><?php _e('Latest News', 'jigoshop') ?></span></h3>
						<div class="inside jigoshop-rss-widget">
				     		<?php
				    			if (file_exists(ABSPATH.WPINC.'/class-simplepie.php')) {
					    			
					    			include_once(ABSPATH.WPINC.'/class-simplepie.php');
					    			
									$rss = fetch_feed('http://jigoshop.com/feed');
									
									if (!is_wp_error( $rss ) ) :
									
										$maxitems = $rss->get_item_quantity(5); 
										$rss_items = $rss->get_items(0, $maxitems); 					
									
										if ( $maxitems > 0 ) :
										
											echo '<ul>';
										
												foreach ( $rss_items as $item ) :
											
												$title = wptexturize($item->get_title(), ENT_QUOTES, "UTF-8");
				
												$link = $item->get_permalink();
															
							  					$date = $item->get_date('U');
							  
												if ( ( abs( time() - $date) ) < 86400 ) : // 1 Day
													$human_date = sprintf(__('%s ago','jigoshop'), human_time_diff($date));
												else :
													$human_date = date(__('F jS Y','jigoshop'), $date);
												endif;
							
												echo '<li><a href="'.$link.'">'.$title.'</a> &ndash; <span class="rss-date">'.$human_date.'</span></li>';
										
											endforeach;
										
											echo '</ul>';
											
										else :
											echo '<ul><li>'.__('No items found.','jigoshop').'</li></ul>';
										endif;
									
									else :
										echo '<ul><li>'.__('No items found.','jigoshop').'</li></ul>';
									endif;
								
								}
				    		?>
						</div>
					</div><!-- postbox end -->
					
					<div class="postbox">
						<h3 class="hndle" id="poststuff"><span><?php _e('Useful Links', 'jigoshop') ?></span></h3>
						<div class="inside jigoshop-links-widget">
				     		<ul class="links">
				     			<li><a href="http://jigoshop.com/"><?php _e('Jigoshop', 'jigoshop'); ?></a> &ndash; <?php _e('Learn more about the Jigoshop plugin', 'jigoshop'); ?></li>
				     			<li><a href="http://jigoshop.com/tour/"><?php _e('Tour', 'jigoshop'); ?></a> &ndash; <?php _e('Take a tour of the plugin', 'jigoshop'); ?></li>
				     			<li><a href="http://jigoshop.com/user-guide/"><?php _e('Documentation', 'jigoshop'); ?></a> &ndash; <?php _e('Stuck? Read the plugin\'s documentation.', 'jigoshop'); ?></li>
				     			<li><a href="http://jigoshop.com/forums/"><?php _e('Forums', 'jigoshop'); ?></a> &ndash; <?php _e('Help from the community or our dedicated support team.', 'jigoshop'); ?></li>
				     			<li><a href="http://jigoshop.com/extend/extensions/"><?php _e('Jigoshop Extensions', 'jigoshop'); ?></a> &ndash; <?php _e('Extend Jigoshop with extra plugins and modules.', 'jigoshop'); ?></li>
				     			<li><a href="http://jigoshop.com/extend/themes/"><?php _e('Jigoshop Themes', 'jigoshop'); ?></a> &ndash; <?php _e('Extend Jigoshop with themes.', 'jigoshop'); ?></li>
				     			<li><a href="http://twitter.com/#!/jigoshop"><?php _e('@Jigoshop', 'jigoshop'); ?></a> &ndash; <?php _e('Follow us on Twitter.', 'jigoshop'); ?></li>
				     			<li><a href="https://github.com/mikejolley/Jigoshop"><?php _e('Jigoshop on Github', 'jigoshop'); ?></a> &ndash; <?php _e('Help extend Jigoshop.', 'jigoshop'); ?></li>
				     			<li><a href="http://wordpress.org/extend/plugins/jigoshop/"><?php _e('Jigoshop on WordPress.org', 'jigoshop'); ?></a> &ndash; <?php _e('Leave us a rating!', 'jigoshop'); ?></li>
				     		</ul>
				     		<div class="social">
				     			
				     			<h4 class="first"><?php _e('Show your support &amp; Help promote Jigoshop!', 'jigoshop'); ?></h4>
				     			
				     			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="margin: 0 0 1em 0;">
									<input type="hidden" name="cmd" value="_s-xclick">
									<input type="hidden" name="hosted_button_id" value="TKRER2WH7UAD6">
									<input type="image" src="https://www.paypalobjects.com/en_GB/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online." style="margin:0; padding:0; ">
									<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
								</form>
								
				     			<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fjigoshop.com&amp;send=false&amp;layout=standard&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font=segoe+ui&amp;height=24" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:24px;" allowTransparency="true"></iframe>
				     			
				     			<p><a href="http://twitter.com/share" class="twitter-share-button" data-url="http://jigoshop.com/" data-text="Jigoshop: A WordPress eCommerce solution that works" data-count="horizontal" data-via="jigoshop" data-related="Jigowatt:Creators">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script></p>
				     			
				     			<p><g:plusone size="medium" href="http://jigoshop.com/"></g:plusone><script type="text/javascript" src="https://apis.google.com/js/plusone.js">{lang: 'en-GB'}</script></p>
				     			
				     			<h4><?php _e('Jigoshop is bought to you by&hellip;', 'jigoshop'); ?></h4>

				     			<p><a href="http://jigowatt.co.uk/"><img src="<?php echo jigoshop::plugin_url(); ?>/assets/images/jigowatt.png" alt="Jigowatt" /></a></p>
				     			
				     			<p>From design to deployment Jigowatt delivers expert solutions to enterprise customers using Magento & WordPress open source platforms.</p>
				     			
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
