<?php
/**
 * Downloadable product trait.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Downloadable product trait class.
 */
class WC_Product_Trait_Downloadable extends WC_Product_Trait {

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'Downloadable';
	}

    /**
	 * Get the slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'downloadable';
	}

    /**
	 * Checks if a product is downloadable.
	 *
	 * @return bool
	 */
	public function is_downloadable() {
		return apply_filters( 'woocommerce_is_downloadable', true === $this->get_downloadable(), $this->product );
	}

    /**
	 * Get downloadable.
	 *
	 * @since  3.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return bool
	 */
	public function get_downloadable( $context = 'view' ) {
		return $this->product->get_prop( 'downloadable', $context );
	}

    /**
	 * Set if the product is downloadable.
	 *
	 * @since 3.0.0
	 * @param bool|string $downloadable Whether product is downloadable or not.
	 */
	public function set_downloadable( $downloadable ) {
		$this->product->set_prop( 'downloadable', wc_string_to_bool( $downloadable ) );
	}

	/**
	 * Set downloads.
	 *
	 * @throws WC_Data_Exception If an error relating to one of the downloads is encountered.
	 *
	 * @param array $downloads_array Array of WC_Product_Download objects or arrays.
	 *
	 * @since 3.0.0
	 */
	public function set_downloads( $downloads_array ) {
		// When the object is first hydrated, only the previously persisted downloads will be passed in.
		$existing_downloads = $this->product->get_object_read() ? (array) $this->product->get_prop( 'downloads' ) : $downloads_array;
		$downloads          = array();
		$errors             = array();

		$downloads_array    = $this->product->build_downloads_map( $downloads_array );
		$existing_downloads = $this->product->build_downloads_map( $existing_downloads );

		foreach ( $downloads_array as $download ) {
			$download_id = $download->get_id();
			$is_new      = ! isset( $existing_downloads[ $download_id ] );
			$has_changed = ! $is_new && $existing_downloads[ $download_id ]->get_file() !== $downloads_array[ $download_id ]->get_file();

			try {
				$download->check_is_valid( $this->product->get_object_read() );
				$downloads[ $download_id ] = $download;
			} catch ( Exception $e ) {
				// We only add error messages for newly added downloads (let's not overwhelm the user if there are
				// multiple existing files which are problematic).
				if ( $is_new || $has_changed ) {
					$errors[] = $e->getMessage();
				}

				// If the problem is with an existing download, disable it.
				if ( ! $is_new ) {
					$download->set_enabled( false );
					$downloads[ $download_id ] = $download;
				}
			}
		}

		$this->product->set_prop( 'downloads', $downloads );

		if ( $errors && $this->product->get_object_read() ) {
			$this->product->error( 'product_invalid_download', $errors[0] );
		}
	}

	/**
	 * Takes an array of downloadable file representations and converts it into an array of
	 * WC_Product_Download objects, indexed by download ID.
	 *
	 * @param array[]|WC_Product_Download[] $downloads Download data to be re-mapped.
	 *
	 * @return WC_Product_Download[]
	 */
	public function build_downloads_map( array $downloads ): array {
		$downloads_map = array();

		foreach ( $downloads as $download_data ) {
			// If the item is already a WC_Product_Download we can add it to the map and move on.
			if ( is_a( $download_data, 'WC_Product_Download' ) ) {
				$downloads_map[ $download_data->get_id() ] = $download_data;
				continue;
			}

			// If the item is not an array, there is nothing else we can do (bad data).
			if ( ! is_array( $download_data ) ) {
				continue;
			}

			// Otherwise, transform the array to a WC_Product_Download and add to the map.
			$download_object = new WC_Product_Download();

			// If we don't have a previous hash, generate UUID for download.
			if ( empty( $download_data['download_id'] ) ) {
				$download_data['download_id'] = wp_generate_uuid4();
			}

			$download_object->set_id( $download_data['download_id'] );
			$download_object->set_name( $download_data['name'] );
			$download_object->set_file( $download_data['file'] );
			$download_object->set_enabled( isset( $download_data['enabled'] ) ? $download_data['enabled'] : true );

			$downloads_map[ $download_object->get_id() ] = $download_object;
		}

		return $downloads_map;
	}

	/**
	 * Set download limit.
	 *
	 * @since 3.0.0
	 * @param int|string $download_limit Product download limit.
	 */
	public function set_download_limit( $download_limit ) {
		$this->product->set_prop( 'download_limit', -1 === (int) $download_limit || '' === $download_limit ? -1 : absint( $download_limit ) );
	}

	/**
	 * Set download expiry.
	 *
	 * @since 3.0.0
	 * @param int|string $download_expiry Product download expiry.
	 */
	public function set_download_expiry( $download_expiry ) {
		$this->product->set_prop( 'download_expiry', -1 === (int) $download_expiry || '' === $download_expiry ? -1 : absint( $download_expiry ) );
	}


	/**
	 * Get downloads.
	 *
	 * @since  3.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return array
	 */
	public function get_downloads( $context = 'view' ) {
		return $this->product->get_prop( 'downloads', $context );
	}

	/**
	 * Get download expiry.
	 *
	 * @since  3.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_download_expiry( $context = 'view' ) {
		return $this->product->get_prop( 'download_expiry', $context );
	}

	/**
	 * Get download limit.
	 *
	 * @since  3.0.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int
	 */
	public function get_download_limit( $context = 'view' ) {
		return $this->product->get_prop( 'download_limit', $context );
	}

    /**
	 * Check if downloadable product has a file attached.
	 *
	 * @since 1.6.2
	 *
	 * @param  string $download_id file identifier.
	 * @return bool Whether downloadable product has a file attached.
	 */
	public function has_file( $download_id = '' ) {
		return $this->product->is_downloadable() && $this->product->get_file( $download_id );
	}


	/**
	 * Get a file by $download_id.
	 *
	 * @param  string $download_id file identifier.
	 * @return array|false if not found
	 */
	public function get_file( $download_id = '' ) {
		$files = $this->product->get_downloads();

		if ( '' === $download_id ) {
			$file = count( $files ) ? current( $files ) : false;
		} elseif ( isset( $files[ $download_id ] ) ) {
			$file = $files[ $download_id ];
		} else {
			$file = false;
		}

		return apply_filters( 'woocommerce_product_file', $file, $this, $download_id );
	}

	/**
	 * Get file download path identified by $download_id.
	 *
	 * @param  string $download_id file identifier.
	 * @return string
	 */
	public function get_file_download_path( $download_id ) {
		$files     = $this->product->get_downloads();
		$file_path = isset( $files[ $download_id ] ) ? $files[ $download_id ]->get_file() : '';

		// allow overriding based on the particular file being requested.
		return apply_filters( 'woocommerce_product_file_download_path', $file_path, $this->product, $download_id );
	}


}
