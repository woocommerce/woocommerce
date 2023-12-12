<?php
/**
 * Add hooks related to uploading downloadable products.
 *
 * @package     WooCommerce\Admin
 * @version     8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Admin_Upload_Downloadable_Product', false ) ) {
	return new WC_Admin_Upload_Downloadable_Product();
}

/**
 * WC_Admin_Upload_Downloadable_Product Class.
 */
class WC_Admin_Upload_Downloadable_Product {
	/**
	 * Add hooks.
	 */
	public function __construct() {
		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		add_filter( 'wp_unique_filename', array( $this, 'update_filename' ), 10, 3 );
		add_action( 'media_upload_downloadable_product', array( $this, 'media_upload_downloadable_product' ) );
	}
	/**
	 * Change upload dir for downloadable files.
	 *
	 * @param array $pathdata Array of paths.
	 * @return array
	 */
	public function upload_dir( $pathdata ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST['type'] ) && 'downloadable_product' === $_POST['type'] ) {

			if ( empty( $pathdata['subdir'] ) ) {
				$pathdata['path']   = $pathdata['path'] . '/woocommerce_uploads';
				$pathdata['url']    = $pathdata['url'] . '/woocommerce_uploads';
				$pathdata['subdir'] = '/woocommerce_uploads';
			} else {
				$new_subdir = '/woocommerce_uploads' . $pathdata['subdir'];

				$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
				$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
				$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
			}
		}
		return $pathdata;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Change filename for WooCommerce uploads and prepend unique chars for security.
	 *
	 * @param string $full_filename Original filename.
	 * @param string $ext           Extension of file.
	 * @param string $dir           Directory path.
	 *
	 * @return string New filename with unique hash.
	 * @since 4.0
	 */
	public function update_filename( $full_filename, $ext, $dir ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['type'] ) || ! 'downloadable_product' === $_POST['type'] ) {
			return $full_filename;
		}

		if ( ! strpos( $dir, 'woocommerce_uploads' ) ) {
			return $full_filename;
		}

		if ( 'no' === get_option( 'woocommerce_downloads_add_hash_to_filename' ) ) {
			return $full_filename;
		}

		return $this->unique_filename( $full_filename, $ext );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Change filename to append random text.
	 *
	 * @param string $full_filename Original filename with extension.
	 * @param string $ext           Extension.
	 *
	 * @return string Modified filename.
	 */
	public function unique_filename( $full_filename, $ext ) {
		$ideal_random_char_length = 6;   // Not going with a larger length because then downloaded filename will not be pretty.
		$max_filename_length      = 255; // Max file name length for most file systems.
		$length_to_prepend        = min( $ideal_random_char_length, $max_filename_length - strlen( $full_filename ) - 1 );

		if ( 1 > $length_to_prepend ) {
			return $full_filename;
		}

		$suffix   = strtolower( wp_generate_password( $length_to_prepend, false, false ) );
		$filename = $full_filename;

		if ( strlen( $ext ) > 0 ) {
			$filename = substr( $filename, 0, strlen( $filename ) - strlen( $ext ) );
		}

		$full_filename = str_replace(
			$filename,
			"$filename-$suffix",
			$full_filename
		);

		return $full_filename;
	}

	/**
	 * Run a filter when uploading a downloadable product.
	 */
	public function woocommerce_media_upload_downloadable_product() {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'media_upload_file' );
	}
}
