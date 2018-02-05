<?php
/**
 * Regenerate Images Functionality
 *
 * All functionality pertaining to regenerating product images in realtime.
 *
 * @package WooCommerce/Classes
 * @version 3.3.0
 * @since 3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Regenerate Images Class
 */
class WC_Regenerate_Images {

	/**
	 * Background process to regenerate all images
	 *
	 * @var WC_Regenerate_Images_Request
	 */
	protected static $background_process;

	/**
	 * Init function
	 */
	public static function init() {
		include_once WC_ABSPATH . 'includes/class-wc-regenerate-images-request.php';
		self::$background_process = new WC_Regenerate_Images_Request();

		if ( ! is_admin() ) {
			// Handle on-the-fly image resizing.
			add_filter( 'wp_get_attachment_image_src', array( __CLASS__, 'maybe_resize_image' ), 10, 4 );
		}

		if ( apply_filters( 'woocommerce_background_image_regeneration', true ) ) {
			// Actions to handle image generation when settings change.
			add_action( 'update_option_woocommerce_thumbnail_cropping', array( __CLASS__, 'maybe_regenerate_images_option_update' ), 10, 3 );
			add_action( 'update_option_woocommerce_thumbnail_image_width', array( __CLASS__, 'maybe_regenerate_images_option_update' ), 10, 3 );
			add_action( 'update_option_woocommerce_single_image_width', array( __CLASS__, 'maybe_regenerate_images_option_update' ), 10, 3 );
			add_action( 'after_switch_theme', array( __CLASS__, 'maybe_regenerate_image_theme_switch' ) );
		}
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

		if ( ! apply_filters( 'woocommerce_resize_images', true ) ) {
			return $image;
		}

		// Use a whitelist of sizes we want to resize. Ignore others.
		if ( ! in_array( $size, apply_filters( 'woocommerce_image_sizes_to_resize', array( 'woocommerce_thumbnail', 'woocommerce_single', 'shop_thumbnail', 'shop_catalog', 'shop_single' ) ), true ) ) {
			return $image;
		}

		// Get image metadata - we need it to proceed.
		$imagemeta = wp_get_attachment_metadata( $attachment_id );

		if ( false === $imagemeta || empty( $imagemeta ) ) {
			return $image;
		}

		$size_settings = wc_get_image_size( $size );

		// If size differs from image meta, regen.
		if ( ! isset( $imagemeta['sizes'], $imagemeta['sizes'][ $size ] ) || $imagemeta['sizes'][ $size ]['width'] !== $size_settings['width'] || ( $size_settings['crop'] && $imagemeta['sizes'][ $size ]['height'] !== $size_settings['height'] ) ) {
			$image = self::resize_and_return_image( $attachment_id, $image, $size, $icon );
		}

		return $image;
	}

	/**
	 * Regenerate the image according to the required size
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $image Original Image.
	 * @param string $size Size to return for new URL.
	 * @param bool   $icon If icon or not.
	 * @return string
	 */
	private static function resize_and_return_image( $attachment_id, $image, $size, $icon ) {
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type || 'image/' !== substr( $attachment->post_mime_type, 0, 6 ) ) {
			return $image;
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		$wp_uploads     = wp_upload_dir( null, false );
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		$original_image_file_path = get_attached_file( $attachment->ID );

		if ( ! file_exists( $original_image_file_path ) || ! getimagesize( $original_image_file_path ) ) {
			return $image;
		}

		$info = pathinfo( $original_image_file_path );
		$ext  = $info['extension'];

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
	 * Check if we should regenerate the product images when options change.
	 *
	 * @param mixed  $old_value Old option value.
	 * @param mixed  $new_value New option value.
	 * @param string $option Option name.
	 */
	public static function maybe_regenerate_images_option_update( $old_value, $new_value, $option ) {
		if ( $new_value === $old_value ) {
			return;
		}

		self::queue_image_regeneration();
	}

	/**
	 * Check if we should generate images when new themes declares custom sizes.
	 */
	public static function maybe_regenerate_image_theme_switch() {
		if ( wc_get_theme_support( 'single_image_width' ) || wc_get_theme_support( 'thumbnail_image_width' ) ) {
			self::queue_image_regeneration();
		}
	}

	/**
	 * Get list of images and queue them for regeneration
	 *
	 * @return void
	 */
	private static function queue_image_regeneration() {
		global $wpdb;
		// First lets cancel existing running queue to avoid running it more than once.
		self::$background_process->kill_process();

		// Now lets find all product image attachments IDs and pop them onto the queue.
		$images = $wpdb->get_results( // @codingStandardsIgnoreLine
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image/%'
			ORDER BY ID DESC"
		);
		foreach ( $images as $image ) {
			self::$background_process->push_to_queue( array(
				'attachment_id' => $image->ID,
			) );
		}

		// Lets dispatch the queue to start processing.
		self::$background_process->save()->dispatch();
	}
}
WC_Regenerate_Images::init();
