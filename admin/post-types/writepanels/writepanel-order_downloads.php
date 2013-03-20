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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
				$download_permissions = $wpdb->get_results( $wpdb->prepare( "
					SELECT * FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE order_id = %d ORDER BY product_id
				", $post->ID ) );

				$product = null;
				if ( $download_permissions && sizeof( $download_permissions ) > 0 ) foreach ( $download_permissions as $download ) {

					if ( ! $product || $product->id != $download->product_id ) {
						$product = get_product( absint( $download->product_id ) );
						$file_count = $loop = 0;
					}

					// don't show permissions to files that have since been removed
					if ( ! $product->exists() || ! $product->has_file( $download->download_id ) )
						continue;

					include( 'order-download-permission-html.php' );

					$loop++;
					$file_count++;
				}
			?>
		</div>

		<div class="toolbar">
			<p class="buttons">
				<select name="grant_access_id" class="grant_access_id chosen_select_nostd" data-placeholder="<?php _e( 'Choose a downloadable product&hellip;', 'woocommerce' ) ?>">
					<?php
						echo '<option value=""></option>';

						$args = array(
							'post_type' 		=> array( 'product', 'product_variation' ),
							'posts_per_page' 	=> -1,
							'post_status'		=> 'publish',
							'order'				=> 'ASC',
							'orderby'			=> 'parent title',
							'meta_query'		=> array(
								array(
									'key' 	=> '_downloadable',
									'value' => 'yes'
								)
							)
						);
						$products = get_posts( $args );

						if ( $products ) foreach ( $products as $product ) {

							$product_object = get_product( $product->ID );
							$product_name   = woocommerce_get_formatted_product_name( $product_object );

							echo '<option value="' . esc_attr( $product->ID ) . '">' . esc_html( $product_name ) . '</option>';

						}
					?>
				</select>

				<button type="button" class="button grant_access"><?php _e( 'Grant Access', 'woocommerce' ); ?></button>
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
				loop:			jQuery('.order_download_permissions .wc-metabox').size(),
				order_id: 		'<?php echo $post->ID; ?>',
				security: 		'<?php echo wp_create_nonce("grant-access"); ?>'
			};

			jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function( response ) {

				if ( response ) {

				    jQuery('.order_download_permissions .wc-metaboxes').append( response );

				} else {

					alert('<?php _e( 'Could not grant access - the user may already have permission for this file or billing email is not set. Ensure the billing email is set, and the order has been saved.', 'woocommerce' ); ?>');

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
			var answer = confirm('<?php _e( 'Are you sure you want to revoke access to this download?', 'woocommerce' ); ?>');
			if (answer){

				var el = jQuery(this).parent().parent();

				var product = jQuery(this).attr('rel').split(",")[0];
				var file = jQuery(this).attr('rel').split(",")[1];

				if (product>0) {

					jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(<?php echo $woocommerce->plugin_url(); ?>/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

					var data = {
						action: 		'woocommerce_revoke_access_to_download',
						product_id: 	product,
						download_id:	file,
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

	if ( isset( $_POST['download_id'] ) ) {

		// Download data
		$download_ids			= $_POST['download_id'];
		$product_ids			= $_POST['product_id'];
		$downloads_remaining 	= $_POST['downloads_remaining'];
		$access_expires 		= $_POST['access_expires'];

		// Order data
		$order_key 				= get_post_meta( $post->ID, '_order_key', true );
		$customer_email 		= get_post_meta( $post->ID, '_billing_email', true );
		$customer_user 			= get_post_meta( $post->ID, '_customer_user', true );
		$product_ids_count 		= sizeof( $product_ids );

		for ( $i = 0; $i < $product_ids_count; $i ++ ) {

            $data = array(
				'user_id'				=> absint( $customer_user ),
				'user_email' 			=> woocommerce_clean( $customer_email ),
				'downloads_remaining'	=> woocommerce_clean( $downloads_remaining[$i] )
            );

            $format = array( '%d', '%s', '%s' );

            $expiry  = ( array_key_exists( $i, $access_expires ) && $access_expires[ $i ] != '' ) ? date_i18n( 'Y-m-d', strtotime( $access_expires[ $i ] ) ) : null;

            if ( ! is_null( $expiry ) ) {
                $data['access_expires'] = $expiry;
                $format[] = '%s';
            }

            $wpdb->update( $wpdb->prefix . "woocommerce_downloadable_product_permissions",
			    $data,
                array(
					'order_id' 		=> $post_id,
					'product_id' 	=> absint( $product_ids[$i] ),
					'download_id'	=> woocommerce_clean( $download_ids[$i] )
					),
				$format, array( '%d', '%d', '%s' )
			);

		}
	}
}

add_action( 'woocommerce_process_shop_order_meta', 'woocommerce_order_downloads_save', 5, 2 );