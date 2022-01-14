<?php
/**
 * Represents a file which can be downloaded.
 *
 * @package WooCommerce\Classes
 * @version 3.0.0
 * @since   3.0.0
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Product download class.
 */
class WC_Product_Download implements ArrayAccess {

	/**
	 * Data array.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array(
		'id'   => '',
		'name' => '',
		'file' => '',
	);

	/**
	 * Returns all data for this object.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get allowed mime types.
	 *
	 * @return array
	 */
	public function get_allowed_mime_types() {
		return apply_filters( 'woocommerce_downloadable_file_allowed_mime_types', get_allowed_mime_types() );
	}

	/**
	 * Get type of file path set.
	 *
	 * @param  string $file_path optional.
	 * @return string absolute, relative, or shortcode.
	 */
	public function get_type_of_file_path( $file_path = '' ) {
		$file_path  = $file_path ? $file_path : $this->get_file();
		$parsed_url = wp_parse_url( $file_path );
		if (
			$parsed_url &&
			isset( $parsed_url['host'] ) && // Absolute url means that it has a host.
			( // Theoretically we could permit any scheme (like ftp as well), but that has not been the case before. So we allow none or http(s).
				! isset( $parsed_url['scheme'] ) ||
				in_array( $parsed_url['scheme'], array( 'http', 'https' ), true )
			)
		) {
			return 'absolute';
		} elseif ( '[' === substr( $file_path, 0, 1 ) && ']' === substr( $file_path, -1 ) ) {
			return 'shortcode';
		} else {
			return 'relative';
		}
	}

	/**
	 * Get file type.
	 *
	 * @return string
	 */
	public function get_file_type() {
		$type = wp_check_filetype( strtok( $this->get_file(), '?' ), $this->get_allowed_mime_types() );
		return $type['type'];
	}

	/**
	 * Get file extension.
	 *
	 * @return string
	 */
	public function get_file_extension() {
		$parsed_url = wp_parse_url( $this->get_file(), PHP_URL_PATH );
		return pathinfo( $parsed_url, PATHINFO_EXTENSION );
	}

	/**
	 * Check if file is allowed.
	 *
	 * @return boolean
	 */
	public function is_allowed_filetype() {
		$file_path = $this->get_file();

		// File types for URL-based files located on the server should get validated.
		$parsed_file_path  = WC_Download_Handler::parse_file_path( $file_path );
		$is_file_on_server = ! $parsed_file_path['remote_file'];
		$file_path_type    = $this->get_type_of_file_path( $file_path );

		// Shortcodes are allowed, validations should be done by the shortcode provider in this case.
		if ( 'shortcode' === $file_path_type ) {
			return true;
		}

		// Remote paths are allowed.
		if ( ! $is_file_on_server && 'relative' !== $file_path_type ) {
			return true;
		}

		// On windows system, local files ending with `.` are not allowed.
		// @link https://docs.microsoft.com/en-us/windows/win32/fileio/naming-a-file?redirectedfrom=MSDN#naming-conventions.
		if ( $is_file_on_server && ! $this->get_file_extension() && 'WIN' === strtoupper( substr( Constants::get_constant( 'PHP_OS' ), 0, 3 ) ) ) {
			if ( '.' === substr( $file_path, -1 ) ) {
				return false;
			}
		}

		return ! $this->get_file_extension() || in_array( $this->get_file_type(), $this->get_allowed_mime_types(), true );
	}

	/**
	 * Validate file exists.
	 *
	 * @return boolean
	 */
	public function file_exists() {
		if ( 'relative' !== $this->get_type_of_file_path() ) {
			return true;
		}
		$file_url = $this->get_file();
		if ( '..' === substr( $file_url, 0, 2 ) || '/' !== substr( $file_url, 0, 1 ) ) {
			$file_url = realpath( ABSPATH . $file_url );
		} elseif ( substr( WP_CONTENT_DIR, strlen( untrailingslashit( ABSPATH ) ) ) === substr( $file_url, 0, strlen( substr( WP_CONTENT_DIR, strlen( untrailingslashit( ABSPATH ) ) ) ) ) ) {
			$file_url = realpath( WP_CONTENT_DIR . substr( $file_url, 11 ) );
		}
		return apply_filters( 'woocommerce_downloadable_file_exists', file_exists( $file_url ), $this->get_file() );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set ID.
	 *
	 * @param string $value Download ID.
	 */
	public function set_id( $value ) {
		$this->data['id'] = wc_clean( $value );
	}

	/**
	 * Set name.
	 *
	 * @param string $value Download name.
	 */
	public function set_name( $value ) {
		$this->data['name'] = wc_clean( $value );
	}

	/**
	 * Set previous_hash.
	 *
	 * @deprecated 3.3.0 No longer using filename based hashing to keep track of files.
	 * @param string $value Previous hash.
	 */
	public function set_previous_hash( $value ) {
		wc_deprecated_function( __FUNCTION__, '3.3' );
		$this->data['previous_hash'] = wc_clean( $value );
	}

	/**
	 * Set file.
	 *
	 * @param string $value File URL/Path.
	 */
	public function set_file( $value ) {
		// A `///` is recognized as an "absolute", but on the filesystem, so it bypasses the mime check in `self::is_allowed_filetype`.
		// This will strip extra prepending / to the maximum of 2.
		if ( preg_match( '#^//+(/[^/].+)$#i', $value, $matches ) ) {
			$value = $matches[1];
		}
		switch ( $this->get_type_of_file_path( $value ) ) {
			case 'absolute':
				$this->data['file'] = esc_url_raw( $value );
				break;
			default:
				$this->data['file'] = wc_clean( $value );
				break;
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->data['id'];
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * Get previous_hash.
	 *
	 * @deprecated 3.3.0 No longer using filename based hashing to keep track of files.
	 * @return string
	 */
	public function get_previous_hash() {
		wc_deprecated_function( __FUNCTION__, '3.3' );
		return $this->data['previous_hash'];
	}

	/**
	 * Get file.
	 *
	 * @return string
	 */
	public function get_file() {
		return $this->data['file'];
	}

	/*
	|--------------------------------------------------------------------------
	| ArrayAccess/Backwards compatibility.
	|--------------------------------------------------------------------------
	*/

	/**
	 * OffsetGet.
	 *
	 * @param string $offset Offset.
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		switch ( $offset ) {
			default:
				if ( is_callable( array( $this, "get_$offset" ) ) ) {
					return $this->{"get_$offset"}();
				}
				break;
		}
		return '';
	}

	/**
	 * OffsetSet.
	 *
	 * @param string $offset Offset.
	 * @param mixed  $value Offset value.
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		switch ( $offset ) {
			default:
				if ( is_callable( array( $this, "set_$offset" ) ) ) {
					$this->{"set_$offset"}( $value );
				}
				break;
		}
	}

	/**
	 * OffsetUnset.
	 *
	 * @param string $offset Offset.
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {}

	/**
	 * OffsetExists.
	 *
	 * @param string $offset Offset.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return in_array( $offset, array_keys( $this->data ), true );
	}
}
