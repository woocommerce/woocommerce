<?php
/**
 * Product Images
 *
 * Display the product images meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Meta_Box_Product_Images
 */
class WC_Meta_Box_Product_Images {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		?>
		<div id="product_images_container">
			<ul class="product_images">
				<?php
					if ( metadata_exists( 'post', $post->ID, '_product_image_gallery' ) ) {
						$product_image_gallery = get_post_meta( $post->ID, '_product_image_gallery', true );
					} else {
						$args = apply_filters( 'woocommerce_product_gallery_attachment_args', array(
							'post_parent' => $post->ID,
							'numberposts' => '-1',
							'post_type' => 'attachment',
							'orderby' => 'menu_order',
							'order' => 'ASC',
							'post_mime_type' => 'image',
							'fields' => 'ids',
							'meta_key' => '_woocommerce_exclude_image',
							'meta_value' => 0,
						) );
						$attachment_ids = get_posts( $args );
						// Backwards compat
						$attachment_ids = array_diff( $attachment_ids, array( get_post_thumbnail_id() ) );
						$product_image_gallery = implode( ',', $attachment_ids );
					}

					$attachments = apply_filters( 'woocommerce_product_gallery_attachment_ids', array_filter( explode( ',', $product_image_gallery ) ) );

					if ( $attachments )
						foreach ( $attachments as $attachment_id ) {
							echo '<li class="image" data-attachment_id="' . $attachment_id . '">
								' . wp_get_attachment_image( $attachment_id, 'thumbnail' ) . '
								<ul class="actions">
									<li><a href="#" class="delete tips" data-tip="' . __( 'Delete image', 'woocommerce' ) . '">' . __( 'Delete', 'woocommerce' ) . '</a></li>
								</ul>
							</li>';
						}
				?>
			</ul>

			<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php echo esc_attr( $product_image_gallery ); ?>" />

		</div>
		<p class="add_product_images hide-if-no-js">
			<a href="#"><?php _e( 'Add product gallery images', 'woocommerce' ); ?></a>
		</p>
		<script type="text/javascript">
			jQuery(document).ready(function($){

				// Uploading files
				var product_gallery_frame;
				var $image_gallery_ids = $('#product_image_gallery');
				var $product_images = $('#product_images_container ul.product_images');

				jQuery('.add_product_images').on( 'click', 'a', function( event ) {

					var $el = $(this);
					var attachment_ids = $image_gallery_ids.val();

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( product_gallery_frame ) {
						product_gallery_frame.open();
						return;
					}

					// Create the media frame.
					product_gallery_frame = wp.media.frames.downloadable_file = wp.media({
						// Set the title of the modal.
						title: '<?php _e( 'Add Images to Product Gallery', 'woocommerce' ); ?>',
						button: {
							text: '<?php _e( 'Add to gallery', 'woocommerce' ); ?>',
						},
						multiple: true
					});

					// When an image is selected, run a callback.
					product_gallery_frame.on( 'select', function() {

						var selection = product_gallery_frame.state().get('selection');

						selection.map( function( attachment ) {

							attachment = attachment.toJSON();

							if ( attachment.id ) {
								attachment_ids = attachment_ids ? attachment_ids + "," + attachment.id : attachment.id;

								$product_images.append('\
									<li class="image" data-attachment_id="' + attachment.id + '">\
										<img src="' + attachment.url + '" />\
										<ul class="actions">\
											<li><a href="#" class="delete" title="<?php _e( 'Delete image', 'woocommerce' ); ?>"><?php _e( 'Delete', 'woocommerce' ); ?></a></li>\
										</ul>\
									</li>');
							}

						} );

						$image_gallery_ids.val( attachment_ids );
					});

					// Finally, open the modal.
					product_gallery_frame.open();
				});

				// Image ordering
				$product_images.sortable({
					items: 'li.image',
					cursor: 'move',
					scrollSensitivity:40,
					forcePlaceholderSize: true,
					forceHelperSize: false,
					helper: 'clone',
					opacity: 0.65,
					placeholder: 'wc-metabox-sortable-placeholder',
					start:function(event,ui){
						ui.item.css('background-color','#f6f6f6');
					},
					stop:function(event,ui){
						ui.item.removeAttr('style');
					},
					update: function(event, ui) {
						var attachment_ids = '';

						$('#product_images_container ul li.image').css('cursor','default').each(function() {
							var attachment_id = jQuery(this).attr( 'data-attachment_id' );
							attachment_ids = attachment_ids + attachment_id + ',';
						});

						$image_gallery_ids.val( attachment_ids );
					}
				});

				// Remove images
				$('#product_images_container').on( 'click', 'a.delete', function() {

					$(this).closest('li.image').remove();

					var attachment_ids = '';

					$('#product_images_container ul li.image').css('cursor','default').each(function() {
						var attachment_id = jQuery(this).attr( 'data-attachment_id' );
						attachment_ids = attachment_ids + attachment_id + ',';
					});

					$image_gallery_ids.val( attachment_ids );

					return false;
				} );

			});
		</script>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		$attachment_ids = array_filter( explode( ',', woocommerce_clean( $_POST['product_image_gallery'] ) ) );

		update_post_meta( $post_id, '_product_image_gallery', implode( ',', $attachment_ids ) );
	}
}