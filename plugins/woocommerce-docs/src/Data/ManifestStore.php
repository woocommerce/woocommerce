<?php
/**
 * ManifestStore class file
 *
 * @package  WooCommerceDocs
 */

namespace WooCommerceDocs\Data;

/**
 * A class to store the list of manifests in the WordPress options table.
 */
class ManifestStore {
	private const MANIFEST_OPTION = 'woocommerce_docs_manifest';

	/**
	 * Add a manifest url to the list
	 *
	 * @param string $manifest_url The url of the manifest to add.
	 */
	public static function add_manifest_url( $manifest_url ) {
		$manifest_list = get_option( self::MANIFEST_OPTION, array() );
		$index         = array_search( $manifest_url, $manifest_list, true );

		if ( false !== $index ) {
			return;
		}

		$manifest_list[] = $manifest_url;
		self::save( $manifest_list );
	}

	/**
	 * Remove a manifest url from the list
	 *
	 * @param string $manifest_url The url of the manifest to remove.
	 */
	public static function remove_manifest_url( $manifest_url ) {
		$manifest_list = get_option( self::MANIFEST_OPTION, array() );
		$index         = array_search( $manifest_url, $manifest_list, true );

		if ( false !== $index ) {
			array_splice( $manifest_list, $index, 1 );
			self::save( $manifest_list );
		}
	}

	/**
	 * Retrieve the list of manifest urls
	 */
	public static function get_manifest_list() {
		return get_option( self::MANIFEST_OPTION, array() );
	}


	/**
	 * Save a manifest url list
	 *
	 * @param array $manifest_list The list of manifest urls to save.
	 */
	public static function save( $manifest_list ) {
		update_option( self::MANIFEST_OPTION, $manifest_list );
	}
}
