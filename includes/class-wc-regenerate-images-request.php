<?php
/**
 * All functionality to regenerate images in the background when settings change.
 *
 * @package WooCommerce/Classes
 * @version 3.3.0
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once dirname( __FILE__ ) . '/libraries/wp-async-request.php';
}

if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once dirname( __FILE__ ) . '/libraries/wp-background-process.php';
}

/**
 * Class that extends WP_Background_Process to process image regeneration in the background
 */
class WC_Regenerate_Images_Request extends WP_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'wc_regenerate_images';

		parent::__construct();
	}

	/**
	 * Fires when the job should start
	 *
	 * @return void
	 */
	public function dispatch() {
		$log = wc_get_logger();
		$log->info( __( 'Starting product image regeneration job.', 'woocommerce' ),
			array(
				'source' => 'wc-image-regeneration',
			)
		);
		parent::dispatch();
	}

	/**
	 * Determines whether an attachment can have its thumbnails regenerated.
	 *
	 * Adapted from Regenerate Thumbnails by Alex Mills.
	 *
	 * @param WP_Post $attachment An attachment's post object.
	 * @return bool Whether the given attachment can have its thumbnails regenerated.
	 */
	protected function is_regeneratable( $attachment ) {
		if ( 'site-icon' === get_post_meta( $attachment->ID, '_wp_attachment_context', true ) ) {
			return false;
		}

		if ( wp_attachment_is_image( $attachment ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Code to execute for each item in the queue
	 *
	 * @param mixed $item Queue item to iterate over.
	 * @return bool
	 */
	protected function task( $item ) {
		if ( ! is_array( $item ) && ! isset( $item['attachment_id'] ) ) {
			return false;
		}

		$attachment_id = absint( $item['attachment_id'] );
		$attachment    = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type || ! $this->is_regeneratable( $attachment ) ) {
			return false;
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		// This is needed to prevent timeouts due to threading. See https://core.trac.wordpress.org/ticket/36534.
		@putenv( 'MAGICK_THREAD_LIMIT=1' ); // @codingStandardsIgnoreLine.

		$log = wc_get_logger();

		// translators: %s: ID of the attachment.
		$log->info( sprintf( __( 'Regenerating images for attachment ID: %s', 'woocommerce' ), absint( $attachment->ID ) ),
			array(
				'source' => 'wc-image-regeneration',
			)
		);

		$fullsizepath = get_attached_file( $attachment->ID );

		// Check if the file exists, if not just remove item from queue.
		if ( false === $fullsizepath || ! file_exists( $fullsizepath ) ) {
			return false;
		}

		// We only want to regen WC images.
		add_filter( 'intermediate_image_sizes', array( $this, 'adjust_intermediate_image_sizes' ) );

		// This function will generate the new image sizes.
		$new_metadata = wp_generate_attachment_metadata( $attachment->ID, $fullsizepath );

		// Remove custom filter.
		remove_filter( 'intermediate_image_sizes', array( $this, 'adjust_intermediate_image_sizes' ) );

		// If something went wrong lets just remove the item from the queue.
		if ( is_wp_error( $new_metadata ) || empty( $new_metadata ) ) {
			return false;
		}

		$old_metadata = wp_get_attachment_metadata( $attachment->ID );

		if ( ! empty( $old_metadata ) && ! empty( $old_metadata['sizes'] ) && is_array( $old_metadata['sizes'] ) ) {
			foreach ( $old_metadata['sizes'] as $old_size => $old_size_data ) {
				if ( empty( $new_metadata['sizes'][ $old_size ] ) ) {
					$new_metadata['sizes'][ $old_size ] = $old_metadata['sizes'][ $old_size ];
				}
			}
		}

		// Update the meta data with the new size values.
		wp_update_attachment_metadata( $attachment->ID, $new_metadata );

		// We made it till the end, now lets remove the item from the queue.
		return false;
	}

	/**
	 * Returns the sizes we want to regenerate.
	 *
	 * @param array $sizes Sizes to generate.
	 * @return array
	 */
	public function adjust_intermediate_image_sizes( $sizes ) {
		return apply_filters( 'woocommerce_regenerate_images_intermediate_image_sizes', array( 'woocommerce_thumbnail', 'woocommerce_thumbnail_2x', 'woocommerce_single' ) );
	}

	/**
	 * This runs once the job has completed all items on the queue.
	 *
	 * @return void
	 */
	protected function complete() {
		parent::complete();
		$log = wc_get_logger();
		$log->info( __( 'Completed product image regeneration job.', 'woocommerce' ),
			array(
				'source' => 'wc-image-regeneration',
			)
		);
	}
}
