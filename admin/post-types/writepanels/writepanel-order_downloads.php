<?php
/**
 * Order Downloads
 *
 * Functions for displaying order download permissions in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/WritePanels
 * @version     1.6.4
 */

/**
 * Displays the order downloads meta box.
 *
 * @access public
 * @return void
 */
function woocommerce_order_downloads_meta_box() {
	global $woocommerce, $post, $wpdb;

	?>
	<div class="order_download_permissions wc-metaboxes-wrapper">

		<div class="wc-metaboxes">

			<?php
				$i = -1;

				$download_permissions = $wpdb->get_results("
					SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE order_id = $post->ID
				");

				if ($download_permissions && sizeof($download_permissions)>0) foreach ($download_permissions as $download) :
					$i++;

					$product = new WC_Product( $download->product_id );
					?>
		    		<div class="wc-metabox closed">
						<h3 class="fixed">
							<button type="button" rel="<?php echo $download->product_id; ?>" class="revoke_access button"><?php _e('Revoke Access', 'woocommerce'); ?></button>
							<div class="handlediv" title="<?php _e('Click to toggle', 'woocommerce'); ?>"></div>
							<strong><?php echo '#' . $product->id . ' &mdash; ' . $product->get_title() . ' &mdash; ' . sprintf(_n('Downloaded %s time', 'Downloaded %s times', $download->download_count, 'woocommerce'), $download->download_count); ?></strong>
						</h3>
						<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
							<tbody>
								<tr>
									<td>
										<label><?php _e('Downloads Remaining', 'woocommerce'); ?>:</label>
										<input type="hidden" name="download_id[<?php echo $i; ?>]" value="<?php echo $download->product_id; ?>" />
										<input type="text" class="short" name="downloads_remaining[<?php echo $i; ?>]" value="<?php echo $download->downloads_remaining ?>" placeholder="<?php _e('Unlimited', 'woocommerce'); ?>" />
									</td>
									<td>
										<label><?php _e('Access Expires', 'woocommerce'); ?>:</label>
										<input type="text" class="short date-picker" name="access_expires[<?php echo $i; ?>]" value="<?php echo ($download->access_expires>0) ? date_i18n( 'Y-m-d', strtotime( $download->access_expires ) ) : ''; ?>" maxlength="10" placeholder="<?php _e('Never', 'woocommerce'); ?>" />
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<?php
				endforeach;
			?>
		</div>

		<div class="toolbar">
			<p class="buttons">
				<select name="grant_access_id" class="grant_access_id chosen_select_nostd" data-placeholder="<?php _e('Choose a downloadable product&hellip;', 'woocommerce') ?>">
					<?php
						echo '<option value=""></option>';

						$args = array(
							'post_type' 		=> 'product',
							'posts_per_page' 	=> -1,
							'post_status'		=> 'publish',
							'post_parent'		=> 0,
							'order'				=> 'ASC',
							'orderby'			=> 'title',
							'meta_query'		=> array(
								array(
									'key' 	=> '_downloadable',
									'value' => 'yes'
								)
							)
						);
						$products = get_posts( $args );

						if ($products) foreach ($products as $product) :

							$sku = get_post_meta($product->ID, '_sku', true);

							if ($sku) $sku = ' SKU: '.$sku;

							echo '<option value="'.$product->ID.'">'.$product->post_title.$sku.' (#'.$product->ID.''.$sku.')</option>';

							$args_get_children = array(
								'post_type' => array( 'product_variation', 'product' ),
								'posts_per_page' 	=> -1,
								'order'				=> 'ASC',
								'orderby'			=> 'title',
								'post_parent'		=> $product->ID
							);

							if ( $children_products =& get_children( $args_get_children ) ) :

								foreach ($children_products as $child) :

									echo '<option value="'.$child->ID.'">&nbsp;&nbsp;&mdash;&nbsp;'.$child->post_title.'</option>';

								endforeach;

							endif;

						endforeach;
					?>
				</select>

				<button type="button" class="button grant_access"><?php _e('Grant Access', 'woocommerce'); ?></button>
			</p>
			<div class="clear"></div>
		</div>

	</div>
	<?php
	/**
	 * Javascript
	 */
	ob_start();
	?>
	jQuery(function(){

		jQuery('.order_download_permissions').on('click', 'button.grant_access', function(){

			var product = jQuery('select.grant_access_id').val();

			if (!product) return;

			jQuery('.order_download_permissions').block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action: 		'woocommerce_grant_access_to_download',
				product_id: 	product,
				order_id: 		'<?php echo $post->ID; ?>',
				security: 		'<?php echo wp_create_nonce("grant-access"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {

				var loop = jQuery('.order_download_permissions .wc-metabox').size();

				new_download = jQuery.parseJSON( response );

				if ( new_download && new_download.success == 1 ) {

					jQuery('.order_download_permissions .wc-metaboxes').append('<div class="wc-metabox closed">\
						<h3 class="fixed">\
							<button type="button" rel="' + new_download.download_id + '" class="revoke_access button"><?php _e('Revoke Access', 'woocommerce'); ?></button>\
							<div class="handlediv" title="<?php _e('Click to toggle', 'woocommerce'); ?>"></div>\
							<strong>#' + new_download.download_id + ' &mdash; ' + new_download.title + '</strong>\
						</h3>\
						<table cellpadding="0" cellspacing="0" class="wc-metabox-content">\
							<tbody>\
								<tr>\
									<td>\
										<label><?php _e('Downloads Remaining', 'woocommerce'); ?>:</label>\
										<input type="hidden" name="download_id[' + loop + ']" value="' + new_download.download_id + '" />\
										<input type="text" class="short" name="downloads_remaining[' + loop + ']" value="' + new_download.remaining + '" placeholder="<?php _e('Unlimited', 'woocommerce'); ?>" />\
									</td>\
									<td>\
										<label><?php _e('Access Expires', 'woocommerce'); ?>:</label>\
										<input type="text" class="short date-picker" name="access_expires[' + loop + ']" value="' + new_download.expires + '" maxlength="10" placeholder="<?php _e('Never', 'woocommerce'); ?>" />\
									</td>\
								</tr>\
							</tbody>\
						</table>\
					</div>');

				} else {
					alert('<?php _e('Could not grant access - the user may already have permission for this file.', 'woocommerce'); ?>');
				}

				jQuery( ".date-picker" ).datepicker({
					dateFormat: "yy-mm-dd",
					numberOfMonths: 1,
					showButtonPanel: true,
					showOn: "button",
					buttonImage: woocommerce_writepanel_params.calendar_image,
					buttonImageOnly: true
				});

				jQuery('.order_download_permissions').unblock();

			});

			return false;

		});

		jQuery('.order_download_permissions').on('click', 'button.revoke_access', function(e){
			e.preventDefault();
			var answer = confirm('<?php _e('Are you sure you want to revoke access to this download?', 'woocommerce'); ?>');
			if (answer){

				var el = jQuery(this).parent().parent();

				var product = jQuery(this).attr('rel');

				if (product>0) {

					jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					var data = {
						action: 		'woocommerce_revoke_access_to_download',
						product_id: 	product,
						order_id: 		'<?php echo $post->ID; ?>',
						security: 		'<?php echo wp_create_nonce("revoke-access"); ?>'
					};

					jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
						// Success
						jQuery(el).fadeOut('300', function(){
							jQuery(el).remove();
						});
					});

				} else {
					jQuery(el).fadeOut('300', function(){
						jQuery(el).remove();
					});
				}

			}
			return false;
		});

	});
	<?php
	$javascript = ob_get_clean();
	$woocommerce->add_inline_js( $javascript );
}


/**
 * Save the order downloads meta box.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function woocommerce_order_downloads_save( $post_id, $post ) {
	global $wpdb, $woocommerce;

	if (isset($_POST['download_id'])) :

		// Download data
		$download_ids			= $_POST['download_id'];
		$downloads_remaining 	= $_POST['downloads_remaining'];
		$access_expires 		= $_POST['access_expires'];

		// Order data
		$order_key = get_post_meta($post->ID, '_order_key', true);
		$customer_email = get_post_meta($post->ID, '_billing_email', true);
		$customer_user = (int) get_post_meta($post->ID, '_customer_user', true);
		$download_ids_count = sizeof( $download_ids );
		for ($i=0; $i<$download_ids_count; $i++) :

            $data = array(
				'user_id'				=> $customer_user,
				'user_email' 			=> $customer_email,
				'downloads_remaining'	=> $downloads_remaining[$i],
            );

            $format = array( '%d', '%s', '%s');

            $expiry  = ( array_key_exists( $i, $access_expires ) && $access_expires[ $i ] != '' ) ? date_i18n( 'Y-m-d', strtotime( $access_expires[ $i ] ) ) : null;

            if ( ! is_null($expiry)) {
                $data['access_expires'] = $expiry;
                $format[] = '%s';
            }

            $wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions",
			    $data,
                array(
				'order_id' 		=> $post_id,
				'product_id' 	=> $download_ids[$i]
			), $format, array( '%d', '%d' ) );

		endfor;

	endif;

}

add_action('woocommerce_process_shop_order_meta', 'woocommerce_order_downloads_save', 5, 2);