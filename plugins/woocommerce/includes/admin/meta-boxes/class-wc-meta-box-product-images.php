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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		$featured_id    = $product_object instanceof WC_Product ? (int) $product_object->get_image_id( 'edit' ) : 0;
		$gallery_ids    = $product_object instanceof WC_Product ? $product_object->get_gallery_image_ids( 'edit' ) : array();
		$rendered_ids   = array();
		$gallery_ids    = array_filter( array_map( 'absint', $gallery_ids ) );

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		if ( $featured_id ) {
			$featured_image = wp_get_attachment_image( $featured_id, 'medium' );

			if ( ! empty( $featured_image ) ) {
				$rendered_ids[] = $featured_id;
			}
		}

		// Render the legacy gallery scaffold first; extension-facing gallery fields remain canonical.
		ob_start();
		if ( ! empty( $gallery_ids ) ) {
			_prime_post_caches( $gallery_ids );

			foreach ( $gallery_ids as $attachment_id ) {
				$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

				if ( empty( $attachment ) || in_array( (int) $attachment_id, $rendered_ids, true ) ) {
					continue;
				}

				$rendered_ids[] = (int) $attachment_id;
				?>
				<li class="image" data-attachment_id="<?php echo esc_attr( (string) $attachment_id ); ?>">
					<?php echo $attachment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<ul class="actions">
						<li>
							<a href="#" class="delete tips" data-tip="<?php esc_attr_e( 'Remove image', 'woocommerce' ); ?>">
								<?php esc_html_e( 'Remove image', 'woocommerce' ); ?>
							</a>
						</li>
					</ul>
				</li>
				<?php
			}
		}
		$legacy_gallery_items = ob_get_clean();

		// Buffer image tiles so $rendered_ids is fully populated before the wrapper div is emitted.
		ob_start();
		if ( ! empty( $rendered_ids ) ) {
			foreach ( $rendered_ids as $index => $attachment_id ) {
				$is_featured = ( 0 === $index );
				$img         = wp_get_attachment_image( $attachment_id, $is_featured ? 'medium' : 'thumbnail' );

				if ( empty( $img ) ) {
					continue;
				}

				$modifier = $is_featured ? 'featured' : 'gallery';
				?>
				<div class="wc-product-images__image wc-product-images__image--<?php echo esc_attr( $modifier ); ?>" data-attachment-id="<?php echo esc_attr( (string) $attachment_id ); ?>" tabindex="0">
					<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<button type="button" class="wc-product-images__remove" tabindex="-1" aria-label="<?php esc_attr_e( 'Remove image', 'woocommerce' ); ?>"><?php echo self::get_remove_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<?php
					/**
					 * Fires after a product image tile is rendered in the unified images meta box.
					 *
					 * @param int $thepostid     The product post ID.
					 * @param int $attachment_id The attachment ID.
					 *
					 * @since 2.4.0
					 */
					do_action( 'woocommerce_admin_after_product_gallery_item', $thepostid, $attachment_id );
					?>
				</div>
				<?php
			}
		}
		$image_tiles = ob_get_clean();

		$slot_modifier = empty( $rendered_ids ) ? 'featured' : 'gallery';
		?>
		<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( self::get_product_images_tip() ); ?>"></span>
		<div id="wc-product-images__list" class="wc-product-images__list<?php echo ! empty( $rendered_ids ) ? ' wc-product-images__list--has-images' : ''; ?>">
			<?php echo $image_tiles; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div id="wc-product-images__add-slot" class="wc-product-images__add-slot wc-product-images__add-slot--<?php echo esc_attr( $slot_modifier ); ?> hide-if-no-js" role="button" tabindex="0" aria-label="<?php esc_attr_e( 'Add product images', 'woocommerce' ); ?>">
				<span class="wc-product-images__add-label"><?php esc_html_e( 'Add images', 'woocommerce' ); ?></span>
				<?php echo self::get_add_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<script type="text/html" id="tmpl-wc-product-image-tile">
			<div class="wc-product-images__image wc-product-images__image--{{ data.modifier }}" data-attachment-id="{{ data.attachmentId }}" tabindex="0">
				<img src="{{ data.imgUrl }}" />
				<button type="button" class="wc-product-images__remove" tabindex="-1" aria-label="{{ data.removeLabel }}"><?php echo self::get_remove_icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
		</script>

		<div id="product_images_container" class="wc-product-images__legacy-controls" hidden aria-hidden="true">
			<ul class="product_images">
				<?php echo $legacy_gallery_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</ul>
			<input type="hidden" id="product_image_gallery" name="product_image_gallery" value="<?php echo esc_attr( implode( ',', array_slice( $rendered_ids, 1 ) ) ); ?>" />
		</div>
		<p class="add_product_images wc-product-images__legacy-controls hide-if-no-js" hidden aria-hidden="true">
			<a href="#" data-choose="<?php esc_attr_e( 'Add images to product gallery', 'woocommerce' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'woocommerce' ); ?>" data-delete="<?php esc_attr_e( 'Remove image', 'woocommerce' ); ?>" data-text="<?php esc_attr_e( 'Remove image', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Add product gallery images', 'woocommerce' ); ?>
			</a>
		</p>

		<input type="hidden" id="wc_product_image_ids" name="wc_product_image_ids" value="<?php echo esc_attr( implode( ',', $rendered_ids ) ); ?>" />
		<div id="wc-product-images__live-region" class="screen-reader-text" aria-live="polite"></div>
		<?php
	}

	/**
	 * Get the product images help tip text.
	 *
	 * @return string
	 */
	private static function get_product_images_tip(): string {
		return sprintf(
			/* translators: %s: maximum upload file size */
			__( 'For best results, upload JPEG or PNG files that are 1000 by 1000 pixels or larger. The first image will be used as the main product image. Maximum upload file size: %s.', 'woocommerce' ),
			size_format( wp_max_upload_size() )
		);
	}

	/**
	 * Get the remove image icon SVG.
	 *
	 * @return string
	 */
	private static function get_remove_icon_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 4c-4.4 0-8 3.6-8 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8Zm3.8 10.7-1.1 1.1-2.7-2.7-2.7 2.7-1.1-1.1 2.7-2.7-2.7-2.7 1.1-1.1 2.7 2.7 2.7-2.7 1.1 1.1-2.7 2.7 2.7 2.7Z" /></svg>';
	}

	/**
	 * Get the add image icon SVG.
	 *
	 * @return string
	 */
	private static function get_add_icon_svg(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11V12.5Z"/></svg>';
	}

	/**
	 * Parse a posted comma-separated image IDs field.
	 *
	 * @param mixed $posted_value Posted field value.
	 * @return int[]
	 */
	private static function parse_posted_image_ids( $posted_value ): array {
		if ( is_array( $posted_value ) ) {
			$posted_value = wp_unslash( $posted_value );
			$posted_value = array_filter( $posted_value, 'is_scalar' );
			$posted_value = implode( ',', array_map( 'strval', $posted_value ) );
		}

		if ( ! is_scalar( $posted_value ) ) {
			return array();
		}

		$raw_ids = (string) sanitize_text_field( wp_unslash( (string) $posted_value ) );

		if ( '' === $raw_ids ) {
			return array();
		}

		return array_filter( array_map( 'absint', explode( ',', $raw_ids ) ) );
	}

	/**
	 * Set product image IDs from a unified ordered list.
	 *
	 * @param WC_Product $product Product object.
	 * @param int[]      $image_ids Ordered image IDs.
	 */
	private static function set_product_image_ids( WC_Product $product, array $image_ids ): void {
		$image_ids = wp_parse_id_list( $image_ids );

		if ( empty( $image_ids ) ) {
			$product->set_image_id( 0 );
			$product->set_gallery_image_ids( array() );
			return;
		}

		$product->set_image_id( array_shift( $image_ids ) );
		$product->set_gallery_image_ids( $image_ids );
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function save( $post_id, $post ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in WC_Admin_Meta_Boxes::save_meta_boxes().

		// If neither field was submitted, leave product images untouched to avoid data loss.
		if ( ! isset( $_POST['wc_product_image_ids'] ) && ! isset( $_POST['product_image_gallery'] ) ) {
			return;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( isset( $_POST['product_image_gallery'] ) ) {
			// Legacy gallery field remains canonical. Core handles the featured image via _thumbnail_id.
			$gallery_ids = self::parse_posted_image_ids( $_POST['product_image_gallery'] );
			$product->set_gallery_image_ids( $gallery_ids );
		} else {
			// Fallback for non-UI callers that submit the unified field only.
			$image_ids = self::parse_posted_image_ids( $_POST['wc_product_image_ids'] );
			self::set_product_image_ids( $product, $image_ids );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$product->save();
	}
}
