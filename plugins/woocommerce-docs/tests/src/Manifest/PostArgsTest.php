<?php

namespace WooCommerceDocs\Tests\Manifest;

use WP_UnitTestCase;
use WooCommerceDocs\Manifest\PostArgs;


/**
 * Class PostArgsTest
 *
 * @package WooCommerceDocs\Tests\Manifest
 */
class PostArgsTest extends WP_UnitTestCase {

	/**
	 * Test post attributes are filtered to a subset of allowed attributes.
	 */
	public function test_post_attributes() {
		$manifest_post = array(
			'post_title'     => 'Test Post Title',
			'post_content'   => 'Test Content',
			'post_excerpt'   => 'Test Excerpt',
			'post_status'    => 'draft',
			'post_author'    => 1,
			'post_type'      => 'post',
			'post_date'      => '2019-01-01 00:00:00',
			'comment_status' => 'closed',
		);

		$actual_content = 'Hello World';
		$post_args      = new PostArgs( $manifest_post, $actual_content );
		$args           = $post_args->get_args();

		// Attributes that are set.
		$this->assertEquals( 'Test Post Title', $args['post_title'] );
		$this->assertEquals( 'Hello World', $args['post_content'] );
		$this->assertEquals( 'draft', $args['post_status'] );
		$this->assertEquals( 1, $args['post_author'] );
		$this->assertEquals( 'closed', $args['comment_status'] );
		$this->assertEquals( '2019-01-01 00:00:00', $args['post_date'] );

		// Attributes that are not set.
		$this->assertArrayNotHasKey( 'post_type', $args );
		$this->assertArrayNotHasKey( 'post_excerpt', $args );
	}
}
