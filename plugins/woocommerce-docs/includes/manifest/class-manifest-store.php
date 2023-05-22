<?php
/**
 * Manifest_Store class file
 *
 * @package  WooCommerce_Docs
 */

/**
 * A class to store the list of manifests in the WordPress options table.
 */
class Manifest_Store {
	private const MANIFEST_OPTION = 'woocommerce_docs_manifest';

	/**
	 * The list of manifests
	 *
	 * @var array
	 */
	private $manifest_list;

	/**
	 * Define the constructor
	 */
	public function __construct() {
		$this->manifest_list = array();
	}

	/**
	 * Add a manifest url to the list
	 *
	 * @param string $manifest_url The url of the manifest to add.
	 */
	public function add_manifest_url( $manifest_url ) {
		$manifest_list   = get_option( MANIFEST_OPTION, array() );
		$manifest_list[] = $manifest_url;
		$this->save( $manifest_list );
	}

	/**
	 * Remove a manifest url from the list
	 *
	 * @param string $manifest_url The url of the manifest to remove.
	 */
	public function remove_manifest_url( $manifest_url ) {
		$manifest_list = get_option( MANIFEST_OPTION, array() );
		$index         = array_search( $manifest_url, $manifest_list, true );
		if ( false !== $index ) {
			unset( $manifest_list[ $index ] );
			$this->save( $manifest_list );
		}
	}

	/**
	 * Retrieve the list of manifest urls
	 */
	public function get_manifest_list() {
		return get_option( MANIFEST_OPTION, array() );
	}


	/**
	 * Save a manifest url list
	 *
	 * @param array $manifest_list The list of manifest urls to save.
	 */
	private function save( $manifest_list ) {
		update_option( MANIFEST_OPTION, $manifest_list );
	}
}
