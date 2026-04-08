<?php
/**
 * Product Images
 *
 * Display the product images meta box.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce\Admin\Meta Boxes
 * @version     2.1.0
 */

use Automattic\WooCommerce\Enums\ProductType;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Meta_Box_Product_Images Class.
 */
class WC_Meta_Box_Product_Images {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $thepostid, $product_object;

		$thepostid      = $post->ID;
		$product_object = $thepostid ? wc_get_product( $thepostid ) : new WC_Product();
		$all_ids        = $product_object ? $product_object->get_image_ids( 'edit' ) : array();

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		?>
		<div id="wc-product-images__list" class="wc-product-images__list<?php echo ! empty( $all_ids ) ? ' wc-product-images__list--has-images' : ''; ?>">
			<?php
			if ( ! empty( $all_ids ) ) {
				foreach ( $all_ids as $index => $attachment_id ) {
					$is_featured = ( 0 === $index );
					$size        = $is_featured ? 'medium' : 'thumbnail';
					$img         = wp_get_attachment_image( $attachment_id, $size );

					if ( empty( $img ) ) {
						continue;
					}

					$modifier = $is_featured ? 'featured' : 'gallery';
					?>
					<div class="wc-product-images__image wc-product-images__image--<?php echo esc_attr( $modifier ); ?>" data-attachment-id="<?php echo esc_attr( (string) $attachment_id ); ?>" tabindex="0">
						<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<button type="button" class="wc-product-images__remove" tabindex="-1" aria-label="<?php esc_attr_e( 'Remove image', 'woocommerce' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 4c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8Zm3.8 10.7-1.1 1.1-2.7-2.7-2.7 2.7-1.1-1.1 2.7-2.7-2.7-2.7 1.1-1.1 2.7 2.7 2.7-2.7 1.1 1.1-2.7 2.7 2.7 2.7Z" /></svg></button>
						<?php
						if ( ! $is_featured ) {
							/**
							 * Fires after a product gallery item is rendered in the meta box.
							 *
							 * @param int $thepostid     The product post ID.
							 * @param int $attachment_id The attachment ID.
							 *
							 * @since 2.4.0
							 */
							do_action( 'woocommerce_admin_after_product_gallery_item', $thepostid, $attachment_id );
						}
						?>
					</div>
					<?php
				}
			}

			$slot_modifier = empty( $all_ids ) ? 'featured' : 'gallery';
			?>
			<div id="wc-product-images__add-slot" class="wc-product-images__add-slot wc-product-images__add-slot--<?php echo esc_attr( $slot_modifier ); ?> hide-if-no-js" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Add product images', 'woocommerce' ); ?>">
				<span class="wc-product-images__add-label"><?php esc_html_e( 'Add an image', 'woocommerce' ); ?></span>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11V12.5Z"/></svg>
			</div>
		</div>

		<input type="hidden" id="wc_product_image_ids" name="wc_product_image_ids" value="<?php echo esc_attr( implode( ',', $all_ids ) ); ?>" />
		<div id="wc-product-images__live-region" class="screen-reader-text" aria-live="polite"></div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in WC_Admin_Meta_Boxes::save_meta_boxes().
		$product_type = empty( $_POST['product-type'] ) ? WC_Product_Factory::get_product_type( $post_id ) : sanitize_title( wp_unslash( $_POST['product-type'] ) );
		$classname    = WC_Product_Factory::get_product_classname( $post_id, $product_type ? $product_type : ProductType::SIMPLE );
		/**
		 * Product instance.
		 *
		 * @var WC_Product $product
		 */
		$product   = new $classname( $post_id );
		$raw_ids   = isset( $_POST['wc_product_image_ids'] ) ? wc_clean( wp_unslash( $_POST['wc_product_image_ids'] ) ) : '';
		$image_ids = array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$product->set_image_ids( $image_ids );
		$product->save();
	}
}
