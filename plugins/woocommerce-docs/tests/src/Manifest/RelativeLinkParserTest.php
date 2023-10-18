<?php

namespace WooCommerceDocs\Tests\Manifest;

use WooCommerceDocs\Data\DocsStore;
use WooCommerceDocs\Manifest\PostArgs;
use WP_UnitTestCase;
use WooCommerceDocs\Manifest\RelativeLinkParser;


/**
 * Class PostCreatorTest
 *
 * @package WooCommerceDocs\Tests\Manifest
 */
class RelativeLinkParserTest extends WP_UnitTestCase {

	/**
	 * Test that relative links are extracted from a manifest.
	 */
	public function test_extract_links_from_manifest() {
		$manifest = json_decode( file_get_contents( __DIR__ . '/fixtures/manifest.json' ), true );
		$links    = RelativeLinkParser::extract_links_from_manifest( $manifest );

		$this->assertEquals( 2, count( $links ) );

		$keys = array_keys( $links );
		$this->assertEquals( '../../testing/unit-tests.md', $keys[0] );
		$this->assertEquals( './installation/install-plugin.md', $keys[1] );
	}

	/**
	 * Test that relative links can be replaced from a manifest.
	 */
	public function test_replace_links_in_manifest() {
		$manifest = json_decode( file_get_contents( __DIR__ . '/fixtures/manifest.json' ), true );
		$links    = RelativeLinkParser::extract_links_from_manifest( $manifest );

		// First create a post with the relative links.
		$dummy_post = $manifest['categories'][0]['posts'][0];
		// ID from the manifest post that is being linked to in the example content.
		$dummy_post_id = '120770c899215a889246b47ac883e4dda1f97b8b';

		$post_args    = new PostArgs(
			$dummy_post,
			'
			<div>
				<a href="../../testing/unit-tests.md">Unit Tests</a>
				<a href="./does/not/exist.md">Install Plugin</a>
				<a href="https://example.com">External Link</a>
			</div>
			'
		);
		$post_id      = DocsStore::insert_docs_post( $post_args->get_args(), $dummy_post_id );
		$post_content = get_post( $post_id )->post_content;
		$replaced     = RelativeLinkParser::replace_relative_links( $post_content, $links, 123 );

		// Replaced the link available in the lookup object.
		$this->assertStringContainsString(
			get_permalink( $post_id ),
			$replaced
		);

		// Did not replace the link that is not available in the lookup object.
		$this->assertStringContainsString(
			'./does/not/exist.md',
			$replaced
		);

		// Did not replace the external link.
		$this->assertStringContainsString(
			'https://example.com',
			$replaced
		);
	}
}
