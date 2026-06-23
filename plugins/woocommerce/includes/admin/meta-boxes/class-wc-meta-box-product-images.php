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
use Automattic\WooCommerce\Internal\ProductGallery\ProductMediaGallery;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Meta_Box_Product_Images Class.
 */
class WC_Meta_Box_Product_Images {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $thepostid, $product_object;

		$thepostid      = $post->ID;
		$product_object = $thepostid ? wc_get_product( $thepostid ) : new WC_Product();
		$product_object = $product_object instanceof WC_Product ? $product_object : new WC_Product();
		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		?>
		<div id="product_images_container">
			<ul class="product_images">
				<?php
				$product_gallery_videos_enabled = ProductMediaGallery::is_feature_enabled();
				$product_media_gallery          = $product_gallery_videos_enabled
					? ProductMediaGallery::get_product_media_gallery_items(
						$product_object,
						array(
							'context'               => 'edit',
							'include_product_image' => false,
						)
					)
					: ProductMediaGallery::get_product_image_media_items( $product_object, false, 'edit' );
				$update_meta                    = false;
				$updated_gallery_ids            = array();
				$updated_media_gallery          = array();

				if ( ! empty( $product_media_gallery ) ) {
					// Prime caches to reduce future queries.
					ProductMediaGallery::prime_attachment_caches( $product_media_gallery, true );

					foreach ( $product_media_gallery as $media_item ) {
						$attachment_id = $media_item['id'];
						$media_type    = $media_item['media_type'];
						$poster_id     = $media_item['poster_id'] ?? 0;
						$attachment    = 'video' === $media_type
							? self::get_video_preview_html( $attachment_id )
							: wp_get_attachment_image( $attachment_id, 'thumbnail' );

						// If attachment is empty skip.
						if ( empty( $attachment ) ) {
							$update_meta = true;
							continue;
						}

						if ( 'image' === $media_type ) {
							$updated_gallery_ids[] = $attachment_id;
						}

						$updated_media_item = array(
							'media_type'  => $media_type,
							'source_type' => 'attachment',
							'id'          => $attachment_id,
						);

						if ( 'video' === $media_type && $poster_id ) {
							$updated_media_item['poster_id'] = $poster_id;
						}

						$updated_media_gallery[] = $updated_media_item;
						?>
						<li
							class="image <?php echo 'video' === $media_type ? 'video' : ''; ?>"
							data-attachment_id="<?php echo esc_attr( (string) $attachment_id ); ?>"
							data-media_type="<?php echo esc_attr( (string) $media_type ); ?>"
							data-source_type="attachment"
							data-poster_id="<?php echo esc_attr( (string) $poster_id ); ?>"
						>
							<?php echo $attachment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<ul class="actions">
								<li><a href="#" class="delete tips" data-tip="<?php esc_attr_e( 'Delete media', 'woocommerce' ); ?>"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a></li>
							</ul>
							<?php
							// Allow for extra info to be exposed or extra action to be executed for this attachment.
							do_action( 'woocommerce_admin_after_product_gallery_item', $thepostid, $attachment_id );
							?>
						</li>
						<?php
					}

					// Need to update product meta to set new gallery ids.
					if ( $update_meta ) {
						update_post_meta( $post->ID, '_product_image_gallery', implode( ',', $updated_gallery_ids ) );
					}
				}
				?>
			</ul>

			<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>" />
			<?php if ( $product_gallery_videos_enabled ) : ?>
				<input
					type="hidden"
					id="product_media_gallery"
					name="product_media_gallery"
					value="<?php echo esc_attr( self::get_media_gallery_json( $updated_media_gallery ) ); ?>"
				/>
			<?php endif; ?>

		</div>
		<p class="add_product_images hide-if-no-js">
			<?php if ( $product_gallery_videos_enabled ) : ?>
				<a
					href="#"
					data-choose="<?php esc_attr_e( 'Add media to product gallery', 'woocommerce' ); ?>"
					data-update="<?php esc_attr_e( 'Add to gallery', 'woocommerce' ); ?>"
					data-delete="<?php esc_attr_e( 'Delete media', 'woocommerce' ); ?>"
					data-text="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>"
					data-allow_videos="yes"
				><?php esc_html_e( 'Add media to product gallery', 'woocommerce' ); ?></a>
			<?php else : ?>
				<a
					href="#"
					data-choose="<?php esc_attr_e( 'Add images to product gallery', 'woocommerce' ); ?>"
					data-update="<?php esc_attr_e( 'Add to gallery', 'woocommerce' ); ?>"
					data-delete="<?php esc_attr_e( 'Delete image', 'woocommerce' ); ?>"
					data-text="<?php esc_attr_e( 'Delete', 'woocommerce' ); ?>"
					data-allow_videos="no"
				><?php esc_html_e( 'Add product gallery images', 'woocommerce' ); ?></a>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		$product_type = empty( $_POST['product-type'] ) ? WC_Product_Factory::get_product_type( $post_id ) : sanitize_title( wp_unslash( $_POST['product-type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$classname    = WC_Product_Factory::get_product_classname( $post_id, $product_type ? $product_type : ProductType::SIMPLE );
		$product      = new $classname( $post_id );

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$attachment_ids = isset( $_POST['product_image_gallery'] ) ? array_filter( explode( ',', wc_clean( $_POST['product_image_gallery'] ) ) ) : array();
		$videos_enabled = ProductMediaGallery::is_feature_enabled();

		if ( ! $videos_enabled ) {
			$product->set_gallery_image_ids( $attachment_ids );
			$product->save();
			return;
		}

		$media_gallery = array();

		if ( isset( $_POST['product_media_gallery'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted_media_gallery = wc_clean( wp_unslash( $_POST['product_media_gallery'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$media_gallery        = ProductMediaGallery::normalize_media_gallery_items(
				self::decode_media_gallery_json( $posted_media_gallery )
			);
			$attachment_ids       = ProductMediaGallery::get_image_ids( $media_gallery );
		}

		$product->set_gallery_image_ids( $attachment_ids );
		$product->save();

		ProductMediaGallery::set_stored_video_gallery_items(
			$product,
			ProductMediaGallery::get_positioned_video_gallery_items_from_media_gallery( $media_gallery )
		);
	}

	/**
	 * Get HTML preview for a video attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	private static function get_video_preview_html( $attachment_id ) {
		$poster_id = get_post_thumbnail_id( $attachment_id );
		$preview   = '';

		if ( $poster_id ) {
			$preview = wp_get_attachment_image(
				$poster_id,
				'thumbnail',
				false,
				array(
					'class' => 'woocommerce-product-gallery__video-preview',
				)
			);
		}

		if ( $preview ) {
			return $preview;
		}

		$video_src = wp_get_attachment_url( $attachment_id );

		if ( $video_src ) {
			return sprintf(
				'<video class="woocommerce-product-gallery__video-preview" src="%1$s" preload="metadata" muted="muted" aria-hidden="true"></video>',
				esc_url( $video_src )
			);
		}

		return wp_get_attachment_image(
			$attachment_id,
			'thumbnail',
			true,
			array(
				'class' => 'woocommerce-product-gallery__video-preview',
			)
		);
	}

	/**
	 * Encode media gallery items for the hidden form field.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return string
	 */
	private static function get_media_gallery_json( $media_gallery ) {
		$json = wp_json_encode( $media_gallery );

		return false === $json ? '[]' : $json;
	}

	/**
	 * Decode the posted media gallery hidden field.
	 *
	 * @param mixed $media_gallery_json JSON-encoded media gallery field value.
	 * @return array
	 */
	private static function decode_media_gallery_json( $media_gallery_json ) {
		if ( ! is_string( $media_gallery_json ) || '' === $media_gallery_json ) {
			return array();
		}

		$decoded = json_decode( $media_gallery_json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$decoded = wc_clean( $decoded );

		return is_array( $decoded ) ? $decoded : array();
	}
}
