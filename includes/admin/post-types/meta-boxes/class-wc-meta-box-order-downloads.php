<?php
/**
 * Order Downloads
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Meta_Box_Order_Downloads
 */
class WC_Meta_Box_Order_Downloads {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
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
					$loop    = 0;
					if ( $download_permissions && sizeof( $download_permissions ) > 0 ) foreach ( $download_permissions as $download ) {

						if ( ! $product || $product->id != $download->product_id ) {
							$product = get_product( absint( $download->product_id ) );
							$file_count = 0;
						}

						// don't show permissions to files that have since been removed
						if ( ! $product || ! $product->exists() || ! $product->has_file( $download->download_id ) )
							continue;

						include( 'views/html-order-download-permission.php' );

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

								echo '<option value="' . esc_attr( $product->ID ) . '">' . esc_html( $product_object->get_formatted_name() ) . '</option>';

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

						alert('<?php echo esc_js( __( 'Could not grant access - the user may already have permission for this file or billing email is not set. Ensure the billing email is set, and the order has been saved.', 'woocommerce' ) ); ?>');

					}

					jQuery( ".date-picker" ).datepicker({
						dateFormat: "yy-mm-dd",
						numberOfMonths: 1,
						showButtonPanel: true,
						showOn: "button",
						buttonImage: woocommerce_admin_meta_boxes.calendar_image,
						buttonImageOnly: true
					});

					jQuery('.order_download_permissions').unblock();

				});

				return false;

			});

			jQuery('.order_download_permissions').on('click', 'button.revoke_access', function(e){
				e.preventDefault();
				var answer = confirm('<?php echo esc_js( __( 'Are you sure you want to revoke access to this download?', 'woocommerce' ) ); ?>');
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
		wc_enqueue_js( $javascript );
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
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
}