<?php

use WooCommerceDocs\Data\ManifestStore;

/**
 * Class ManifestStoreTest
 */
class ManifestStoreTest extends WP_UnitTestCase {

	/**
	 * Test the manifest store stores manifests in a list.
	 */
	public function test_adding_a_manifest_stores_it_in_the_list() {
		ManifestStore::add_manifest( 'https://example.com/manifest.json' );
		ManifestStore::update_manifest( 'https://example.com/manifest.json', array( 'foo' => 'bar' ) );

		$manifest_list = ManifestStore::get_manifest_list();

		$this->assertEquals( $manifest_list, array( array( 'https://example.com/manifest.json', array( 'foo' => 'bar' ) ) ) );
	}

	/**
	 * Test retrieving a single manifest by url.
	 */
	public function test_retrieving_a_single_manifest_by_url() {
		ManifestStore::add_manifest( 'https://example.com/manifest.json' );
		ManifestStore::update_manifest( 'https://example.com/manifest.json', array( 'foo' => 'bar' ) );

		$manifest = ManifestStore::get_manifest_by_url( 'https://example.com/manifest.json' );
		$this->assertEquals( $manifest['foo'], 'bar' );
	}

	/**
	 * Test removing a manifest by url.
	 */
	public function test_removing_a_manifest_by_url() {
		ManifestStore::add_manifest( 'https://example.com/manifest.json' );
		ManifestStore::remove_manifest( 'https://example.com/manifest.json' );
		$manifest_list = ManifestStore::get_manifest_list();
		$this->assertEquals( $manifest_list, array() );
	}

	/**
	 * Test updating an existing manifest.
	 */
	public function test_updating_a_manifest() {
		ManifestStore::add_manifest( 'https://example.com/manifest.json' );
		ManifestStore::update_manifest( 'https://example.com/manifest.json', array( 'foo' => 'bar' ) );

		$manifest = ManifestStore::get_manifest_by_url( 'https://example.com/manifest.json' );
		$this->assertEquals( $manifest['foo'], 'bar' );

		ManifestStore::update_manifest( 'https://example.com/manifest.json', array( 'foo' => 'baz' ) );
		$updated_manifest = ManifestStore::get_manifest_by_url( 'https://example.com/manifest.json' );
		$this->assertEquals( $updated_manifest['foo'], 'baz' );
	}
}
