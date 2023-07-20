<?php

namespace WooCommerceDocs\Tests\Manifest;

use WP_UnitTestCase;
use WooCommerceDocs\Manifest\PostRemover;


/**
 * Class PostRemoverTest
 *
 * @package WooCommerceDocs\Tests\Manifest
 */
class PostRemoverTest extends WP_UnitTestCase {
	/**
	 * Test collecting doc IDs from a manifest.
	 */
	public function test_collect_doc_ids_from_manifest() {
		$manifest = json_decode( file_get_contents( __DIR__ . '/fixtures/manifest.json' ), true );
		$doc_ids  = PostRemover::collect_doc_ids_from_manifest( $manifest );

		$this->assertEquals( 4, count( $doc_ids ) );
		$this->assertContains( 'c068ce54044fa44c760a69bd71ef21274f2a5a37', $doc_ids );
		$this->assertContains( 'fb59bd01dda7b090e5b0a557948e155a6b679d6a', $doc_ids );
		$this->assertContains( '1f88c4d039e72c059c928ab475ad1ea0a02c8abb', $doc_ids );
		$this->assertContains( '120770c899215a889246b47ac883e4dda1f97b8b', $doc_ids );
	}

	/**
	 * Test getting doc IDs to delete.
	 */
	public function test_get_doc_ids_to_delete() {
		// Test identical manifests that should return no doc IDs to delete.
		$manifest          = json_decode( file_get_contents( __DIR__ . '/fixtures/manifest.json' ), true );
		$previous_manifest = json_decode( file_get_contents( __DIR__ . '/fixtures/manifest.json' ), true );
		$doc_ids_to_delete = PostRemover::get_doc_ids_to_delete( $manifest, $previous_manifest );

		$this->assertEquals( 0, count( $doc_ids_to_delete ) );

		// Test a post removed by adding a post to the previous_manifest.
		$updated_previous_manifest                             = $manifest;
		$updated_previous_manifest['categories'][0]['posts'][] = array(
			'id'         => '123456789',
			'post_title' => 'Test Post Title',
		);

		$doc_ids_to_delete = PostRemover::get_doc_ids_to_delete( $manifest, $updated_previous_manifest );

		$this->assertEquals( 1, count( $doc_ids_to_delete ) );
		$this->assertContains( '123456789', $doc_ids_to_delete );
	}
}
