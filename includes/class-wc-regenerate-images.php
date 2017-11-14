<?php
/**
 * Regenerate Images Functionality
 *
 * All functionality pertaining to regenerating product images in realtime.
 *
 * @category Images
 * @package WooCommerce/Classes
 * @author Automattic
 * @version 3.3.0
 * @since 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Regenerate Images Class
 */
class WC_Regenerate_Images {

	/**
	 * Init function
	 */
	public static function init() {
		add_action( 'wp_get_attachment_image_src', array( __CLASS__, 'maybe_resize_image' ), 10, 4 );
	}

	/**
	 * Check if we should maybe generate a new image size if not already there.
	 *
	 * @param array        $image Properties of the image.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size Image size.
	 * @param bool         $icon If icon or not.
	 * @return array
	 */
	public static function maybe_resize_image( $image, $attachment_id, $size, $icon ) {
		if ( ! apply_filters( 'woocommerce_resize_image', true ) ) {
			return $image;
		}

		$imagemeta = wp_get_attachment_metadata( $attachment_id );

		if ( false === $imagemeta || empty( $imagemeta ) ) {
			return $image;
		}

		$size_settings = wc_get_image_size( $size );

		if ( isset( $imagemeta['sizes'][ $size ] ) ) {
			if ( $imagemeta['sizes'][ $size ]['width'] !== $size_settings['width'] || $imagemeta['sizes'][ $size ]['height'] !== $size_settings['height'] ) {
				$image = self::resize_and_return_image( $attachment_id, $image, $size, $icon );
			}
		} else {
			$image = self::resize_and_return_image( $attachment_id, $image, $size, $icon );
		}
		return $image;
	}

	/**
	 * Regenerate the image sizes
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $image Original Image.
	 * @param string $size Size to return for new URL.
	 * @param bool   $icon If icon or not.
	 * @return string
	 */
	private static function resize_and_return_image( $attachment_id, $image, $size, $icon ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type || 'image/' != substr( $attachment->post_mime_type, 0, 6 ) ) {
			return $image;
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$wp_uploads     = wp_upload_dir( null, false );
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		$original_image_file_path   = get_attached_file( $attachment->ID );

		$rel_path = str_replace( $wp_uploads_dir, '', $original_image_file_path );

		if ( ! file_exists( $original_image_file_path ) || ! getimagesize( $original_image_file_path ) ) {
			return $image;
		}

		$info = pathinfo( $original_image_file_path );
		$ext = $info['extension'];
		list( $orig_w, $orig_h ) = getimagesize( $original_image_file_path );
		// Get image size after cropping.
		$image_size = wc_get_image_size( $size );
		$dimensions = image_resize_dimensions( $orig_w, $orig_h, $image_size['width'], $image_size['height'], $image_size['crop'] );
		if ( ! $dimensions || ! is_array( $dimensions ) ) {
			return $image;
		}

		$dst_w        = $dimensions[4];
		$dst_h        = $dimensions[5];
		$suffix       = "{$dst_w}x{$dst_h}";
		$dst_rel_path = str_replace( '.' . $ext, '', $original_image_file_path );
		$destfilename = "{$dst_rel_path}-{$suffix}.{$ext}";

		// If the file is already there perhaps just load it.
		if ( file_exists( $destfilename ) ) {
			return array(
				0 => str_replace( $wp_uploads_dir, $wp_uploads_url, $destfilename ),
				1 => $image_size['width'],
				2 => $image_size['height'],
			);
		}

		// Lets resize the image if it does not exist yet.
		$editor = wp_get_image_editor( $original_image_file_path );
		if ( is_wp_error( $editor ) || is_wp_error( $editor->resize( $image_size['width'], $image_size['height'], $image_size['crop'] ) ) ) {
			return $image;
		}
		$resized_file = $editor->save();
		if ( ! is_wp_error( $resized_file ) ) {
			$img_url = str_replace( $wp_uploads_dir, $wp_uploads_url, $resized_file['path'] );
			return array(
				0 => $img_url,
				1 => $image_size['width'],
				2 => $image_size['height'],
			);
		}

		// Lets just add this here as a fallback.
		return $image;
	}

	/**
	 * Regenerate the image sizes
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $image Original Image.
	 * @param string $size Size to return for new URL.
	 * @param bool   $icon If icon or not.
	 * @return string
	 */
	private static function resize_and_return_image_background( $attachment_id, $image, $size, $icon ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type || 'image/' != substr( $attachment->post_mime_type, 0, 6 ) ) {
			return $image;
		}

		$fullsizepath = get_attached_file( $attachment->ID );

		// Check if the file exists, if not just return the original image.
		if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
			return $image;
		}

		// This function will generate the new image sizes.
		$metadata = wp_generate_attachment_metadata( $attachment->ID, $fullsizepath );

		// If something went wrong lets just return the original image.
		if ( is_wp_error( $metadata ) || empty( $metadata ) ) {
			return $image;
		}

		// Update the meta data with the new size values.
		wp_update_attachment_metadata( $attachment->ID, $metadata );

		$new_image = wp_get_attachment_image_src( $attachment->ID, $size, $icon );
		if ( false === $new_image ) {
			return $image;
		}
		return $new_image;
	}
}
WC_Regenerate_Images::init();
