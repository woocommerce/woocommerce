<?php
/**
 * ManifestStore class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Data;

/**
 * A class to store the list of manifests in the WordPress options table. It is represented
 * as a tuple, with url as key and value being json data.
 */
class ManifestStore {
	private const MANIFEST_OPTION = 'woocommerce_docs_manifest_list';

	/**
	 * Remove a manifest url from the list
	 *
	 * @param string $manifest_url The url of the manifest to remove.
	 */
	public static function remove_manifest( $manifest_url ) {
		$default_value = wp_json_encode( array() );
		$json          = get_option( self::MANIFEST_OPTION, $default_value );
		$data          = json_decode( $json, true );
		$data          = array_filter(
			$data,
			function( $tuple ) use ( $manifest_url ) {
				return $tuple[0] !== $manifest_url;
			}
		);

		$data = array_values( $data );
		$json = wp_json_encode( $data );
		self::save( $json );
	}

	/**
	 * Retrieve the list of manifest tuples
	 */
	public static function get_manifest_list() {
		$default_value = wp_json_encode( array() );
		$json          = get_option( self::MANIFEST_OPTION, $default_value );
		$data          = json_decode( $json, true );

		foreach ( $data as &$tuple ) {
			$tuple[1] = json_decode( $tuple[1], true );
		}
		unset( $tuple );

		return $data;
	}

	/**
	 * Retrieve the manifest data for a given url
	 *
	 * @param string $url The url of the manifest to retrieve.
	 */
	public static function get_manifest_by_url( $url ) {
		$json = get_option( self::MANIFEST_OPTION, wp_json_encode( array() ) );
		$data = json_decode( $json, true );

		foreach ( $data as $tuple ) {
			if ( $tuple[0] === $url ) {
				return json_decode( $tuple[1], true );
			}
		}

		return null;
	}

	/**
	 * Add an empty manifest to the list.
	 *
	 * @param string $url The url of the manifest to add. The JSON value
	 * will be an empty object.
	 */
	public static function add_manifest( $url ) {
		$json   = get_option( self::MANIFEST_OPTION, wp_json_encode( array() ) );
		$data   = json_decode( $json, true );
		$data[] = array( $url, wp_json_encode( new \stdClass() ) );
		$json   = wp_json_encode( $data );

		self::save( $json );
	}

	/**
	 * Update the manifest data for a given url
	 *
	 * @param string $url The url of the manifest to update.
	 * @param array  $new_data The data to update the manifest with.
	 */
	public static function update_manifest( $url, $new_data ) {
		$default_value = wp_json_encode( array() );
		$existing_data = json_decode( get_option( self::MANIFEST_OPTION, $default_value ), true );

		$updated_data = array_map(
			function( $tuple ) use ( $url, $new_data ) {
				if ( $tuple[0] === $url ) {
					return array( $url, wp_json_encode( $new_data, true ) );
				}

				return $tuple;
			},
			$existing_data
		);

		$json = wp_json_encode( $updated_data );
		self::save( $json );
	}


	/**
	 * Save the manifest list
	 *
	 * @param array $manifest_list The list of manifest tuples to save.
	 */
	private static function save( $manifest_list ) {
		update_option( self::MANIFEST_OPTION, $manifest_list );
	}
}
