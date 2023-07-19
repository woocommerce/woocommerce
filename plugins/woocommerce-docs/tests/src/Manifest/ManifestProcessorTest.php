<?php

namespace WooCommerceDocs\Tests\Manifest;

use WooCommerceDocs\Manifest\ManifestProcessor;
use WP_UnitTestCase;
use WooCommerceDocs\Data\DocsStore;

/**
 * Class ManifestProcessorTest
 *
 * @package WooCommerceDocs\Tests\Manifest
 */
class ManifestProcessorTest extends WP_UnitTestCase {

	/**
	 * Test processing a manifest into WordPress posts and categories.
	 */
	public function test_process_manifest() {

		$manifest = json_decode( file_get_contents( __DIR__ . '/fixtures/manifest.json' ), true );
		$md_file  = file_get_contents( __DIR__ . '/fixtures/test.md' );

		// Mock the wp_remote_get function with a filter.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( $md_file ) {
				return array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'body'     => $md_file,
				);
			},
			10,
			3
		);

		ManifestProcessor::process_manifest( $manifest, 123 );

		$get_started_category = get_term_by( 'name', 'Getting Started with WooCommerce', 'category' );
		$this->assertNotFalse( $get_started_category );

		$install_category = get_term_by( 'name', 'Installation', 'category' );
		$this->assertNotFalse( $install_category );
		// Check parent is correct.
		$this->assertEquals( $get_started_category->term_id, $install_category->parent );

		$troubleshoot_category = get_term_by( 'name', 'Troubleshooting Problems', 'category' );
		$this->assertNotFalse( $troubleshoot_category );
		// Check parent is correct.
		$this->assertEquals( $get_started_category->term_id, $troubleshoot_category->parent );

		$testing_category = get_term_by( 'name', 'Testing WooCommerce', 'category' );
		$this->assertNotFalse( $testing_category );

		// check posts exist using the DocStore.
		$posts = DocsStore::get_posts();
		$this->assertEquals( 4, count( $posts ) );

		$install_plugin_post  = $posts[0];
		$local_dev_post       = $posts[1];
		$unit_testing_post    = $posts[2];
		$what_went_wrong_post = $posts[3];

		// check doc titles (note that they are returned in alpha order).
		$this->assertEquals( 'Install the Plugin', $install_plugin_post->post_title );
		$this->assertEquals( 'Local Development', $local_dev_post->post_title );
		$this->assertEquals( 'Unit Testing', $unit_testing_post->post_title );
		$this->assertEquals( 'What Went Wrong?', $what_went_wrong_post->post_title );

		// Assert that post was assigned to categories.
		$this->assertTrue( has_category( $install_category->term_id, $install_plugin_post->ID ) );
		$this->assertTrue( has_category( $troubleshoot_category->term_id, $what_went_wrong_post->ID ) );
		$this->assertTrue( has_category( $testing_category->term_id, $unit_testing_post->ID ) );
		$this->assertTrue( has_category( $troubleshoot_category->term_id, $what_went_wrong_post->ID ) );
	}
}
