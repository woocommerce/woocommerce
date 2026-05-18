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
		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		?>
		<div id="product_images_container">
			<ul class="product_images">
				<?php
				$product_gallery_videos_enabled = self::product_gallery_videos_enabled();
				$product_media_gallery          = $product_gallery_videos_enabled
					? self::get_product_media_gallery_items( $product_object )
					: self::get_product_image_gallery_items( $product_object );
				$attachment_ids                 = array_filter( wp_list_pluck( $product_media_gallery, 'id' ) );
				$update_meta                    = false;
				$updated_gallery_ids            = array();
				$updated_media_gallery          = array();

				if ( ! empty( $attachment_ids ) ) {
					// Prime caches to reduce future queries.
					_prime_post_caches( $attachment_ids );

					foreach ( $product_media_gallery as $media_item ) {
						$attachment_id = absint( $media_item['id'] ?? 0 );
						$media_type    = isset( $media_item['media_type'] ) && is_string( $media_item['media_type'] )
							? sanitize_key( $media_item['media_type'] )
							: 'image';
						$poster_id     = absint( $media_item['poster_id'] ?? 0 );
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
		$videos_enabled = self::product_gallery_videos_enabled();
		$media_posted   = $videos_enabled && isset( $_POST['product_media_gallery'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$media_gallery  = array();

		if ( $media_posted ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$media_gallery = self::sanitize_media_gallery( wc_clean( wp_unslash( $_POST['product_media_gallery'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( $media_posted ) {
			$attachment_ids = self::get_image_ids_from_media_gallery( $media_gallery );
		}

		$product->set_gallery_image_ids( $attachment_ids );

		if ( $videos_enabled ) {
			$product->set_media_gallery( self::media_gallery_has_videos( $media_gallery ) ? $media_gallery : array() );
		} else {
			$product->set_media_gallery( array() );
		}

		$product->save();
	}

	/**
	 * Get product media gallery items to display in the product gallery metabox.
	 *
	 * @param WC_Product|false|null $product Product object.
	 * @return array
	 */
	private static function get_product_media_gallery_items( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return array();
		}

		$product_image_id = absint( $product->get_image_id( 'edit' ) );
		$media_gallery    = $product->get_media_gallery( 'edit' );
		$media_gallery    = self::sanitize_media_gallery( $media_gallery );

		if ( ! empty( $media_gallery ) ) {
			// The product image can be represented in media gallery data, but
			// this metabox only manages gallery items below the product image.
			if (
				$product_image_id &&
				isset( $media_gallery[0] ) &&
				'image' === ( $media_gallery[0]['media_type'] ?? '' ) &&
				absint( $media_gallery[0]['id'] ?? 0 ) === $product_image_id
			) {
				array_shift( $media_gallery );
			}

			return $media_gallery;
		}

		return self::get_product_image_gallery_items( $product );
	}

	/**
	 * Get legacy product gallery image items.
	 *
	 * @param WC_Product|false|null $product Product object.
	 * @return array
	 */
	private static function get_product_image_gallery_items( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return array();
		}

		$items = array();

		foreach ( $product->get_gallery_image_ids( 'edit' ) as $attachment_id ) {
			$attachment_id = absint( $attachment_id );

			if ( $attachment_id ) {
				$items[] = array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => $attachment_id,
				);
			}
		}

		return $items;
	}

	/**
	 * Check if product gallery videos are enabled.
	 *
	 * @return bool
	 */
	private static function product_gallery_videos_enabled() {
		return function_exists( 'wc_product_gallery_videos_enabled' ) && wc_product_gallery_videos_enabled();
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
	 * Sanitize media gallery items.
	 *
	 * @param array|string $media_gallery Media gallery data.
	 * @return array
	 */
	private static function sanitize_media_gallery( $media_gallery ) {
		if ( is_string( $media_gallery ) ) {
			$decoded = json_decode( $media_gallery, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return array();
			}
			$media_gallery = $decoded;
		}

		if ( ! is_array( $media_gallery ) ) {
			return array();
		}

		$items = array();

		foreach ( $media_gallery as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$media_type    = wc_clean( $item['media_type'] ?? '' );
			$attachment_id = absint( $item['id'] ?? 0 );

			if ( ! $attachment_id ) {
				continue;
			}

			if ( 'image' === $media_type ) {
				if ( ! wp_attachment_is_image( $attachment_id ) ) {
					continue;
				}

				$items[] = array(
					'media_type'  => 'image',
					'source_type' => 'attachment',
					'id'          => $attachment_id,
				);
				continue;
			}

			if ( 'video' !== $media_type || 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'video/' ) ) {
				continue;
			}

			$video_item = array(
				'media_type'  => 'video',
				'source_type' => 'attachment',
				'id'          => $attachment_id,
			);
			$poster_id  = absint( $item['poster_id'] ?? 0 );

			if ( $poster_id && wp_attachment_is_image( $poster_id ) ) {
				$video_item['poster_id'] = $poster_id;
			}

			$items[] = $video_item;
		}

		return $items;
	}

	/**
	 * Get image IDs from media gallery items.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return array
	 */
	private static function get_image_ids_from_media_gallery( $media_gallery ) {
		$image_ids = array();

		foreach ( $media_gallery as $item ) {
			if ( 'image' === ( $item['media_type'] ?? '' ) && ! empty( $item['id'] ) ) {
				$image_ids[] = absint( $item['id'] );
			}
		}

		return array_filter( $image_ids );
	}

	/**
	 * Check if media gallery contains video items.
	 *
	 * @param array $media_gallery Media gallery items.
	 * @return bool
	 */
	private static function media_gallery_has_videos( $media_gallery ) {
		foreach ( $media_gallery as $item ) {
			if ( 'video' === ( $item['media_type'] ?? '' ) ) {
				return true;
			}
		}

		return false;
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
}
