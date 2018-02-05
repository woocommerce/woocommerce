<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Represents a file which can be downloaded.
 *
 * @version     3.0.0
 * @since       3.0.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Product_Download implements ArrayAccess {

	/**
	 * Data array.
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array(
		'id'            => '',
		'name'          => '',
		'file'          => '',
	);

	/**
	 * Returns all data for this object.
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Get allowed mime types.
	 * @return array
	 */
	public function get_allowed_mime_types() {
		return apply_filters( 'woocommerce_downloadable_file_allowed_mime_types', get_allowed_mime_types() );
	}

	/**
	 * Get type of file path set.
	 * @param  string $file_path optional.
	 * @return string absolute, relative, or shortcode.
	 */
	public function get_type_of_file_path( $file_path = '' ) {
		$file_path = $file_path ? $file_path : $this->get_file();
		if ( 0 === strpos( $file_path, 'http' ) || 0 === strpos( $file_path, '//' ) ) {
			return 'absolute';
		} elseif ( '[' === substr( $file_path, 0, 1 ) && ']' === substr( $file_path, -1 ) ) {
			return 'shortcode';
		} else {
			return 'relative';
		}
	}

	/**
	 * Get file type.
	 * @return string
	 */
	public function get_file_type() {
		$type = wp_check_filetype( strtok( $this->get_file(), '?' ), $this->get_allowed_mime_types() );
		return $type['type'];
	}

	/**
	 * Get file extension.
	 * @return string
	 */
	public function get_file_extension() {
		$parsed_url = parse_url( $this->get_file(), PHP_URL_PATH );
		return pathinfo( $parsed_url, PATHINFO_EXTENSION );
	}

	/**
	 * Check if file is allowed.
	 * @return boolean
	 */
	public function is_allowed_filetype() {
		if ( 'relative' !== $this->get_type_of_file_path() ) {
			return true;
		}
		return ! $this->get_file_extension() || in_array( $this->get_file_type(), $this->get_allowed_mime_types() );
	}

	/**
	 * Validate file exists.
	 * @return boolean
	 */
	public function file_exists() {
		if ( 'relative' !== $this->get_type_of_file_path() ) {
			return true;
		}
		$file_url = $this->get_file();
		if ( '..' === substr( $file_url, 0, 2 ) || '/' !== substr( $file_url, 0, 1 ) ) {
			$file_url = realpath( ABSPATH . $file_url );
		} elseif ( '/wp-content' === substr( $file_url, 0, 11 ) ) {
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
	 * @param string $value
	 */
	public function set_id( $value ) {
		$this->data['id'] = wc_clean( $value );
	}

	/**
	 * Set name.
	 * @param string $value
	 */
	public function set_name( $value ) {
		$this->data['name'] = wc_clean( $value );
	}

	/**
	 * Set previous_hash.
	 * @deprecated 3.3.0 No longer using filename based hashing to keep track of files.
	 * @param string $value
	 */
	public function set_previous_hash( $value ) {
		wc_deprecated_function( __FUNCTION__, '3.3' );
		$this->data['previous_hash'] = wc_clean( $value );
	}

	/**
	 * Set file.
	 * @param string $value
	 */
	public function set_file( $value ) {
		switch ( $this->get_type_of_file_path( $value ) ) {
			case 'absolute' :
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
	 * @return string
	 */
	public function get_id() {
		return $this->data['id'];
	}

	/**
	 * Get name.
	 * @return string
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * Get previous_hash.
	 * @deprecated 3.3.0 No longer using filename based hashing to keep track of files.
	 * @return string
	 */
	public function get_previous_hash() {
		wc_deprecated_function( __FUNCTION__, '3.3' );
		return $this->data['previous_hash'];
	}

	/**
	 * Get file.
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
	 * offsetGet
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		switch ( $offset ) {
			default :
				if ( is_callable( array( $this, "get_$offset" ) ) ) {
					return $this->{"get_$offset"}();
				}
				break;
		}
		return '';
	}

	/**
	 * offsetSet
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		switch ( $offset ) {
			default :
				if ( is_callable( array( $this, "set_$offset" ) ) ) {
					return $this->{"set_$offset"}( $value );
				}
				break;
		}
	}

	/**
	 * offsetUnset
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {}

	/**
	 * offsetExists
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return in_array( $offset, array_keys( $this->data ) );
	}
}
