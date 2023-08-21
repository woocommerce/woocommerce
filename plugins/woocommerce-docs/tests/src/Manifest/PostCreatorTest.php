<?php

namespace WooCommerceDocs\Tests\Manifest;

use WP_UnitTestCase;
use WooCommerceDocs\Manifest\PostCreator;


/**
 * Class PostCreatorTest
 *
 * @package WooCommerceDocs\Tests\Manifest
 */
class PostCreatorTest extends WP_UnitTestCase {

	/**
	 * Test a post is created from a manifest entry.
	 */
	public function test_create_post() {
		$manifest_post = array(
			'post_title'     => 'Test Post Title',
			'post_status'    => 'draft',
			'post_date'      => '2019-01-01 00:00:00',
			'comment_status' => 'closed',
		);

		$actual_content = 'Hello World';

		$post_id = PostCreator::create_or_update_post_from_manifest_entry( $manifest_post, $actual_content, 123 );

		$post = get_post( $post_id );

		$block_content = '<!-- wp:paragraph -->
<p>Hello World</p>
<!-- /wp:paragraph -->
';

		$this->assertEquals( 'Test Post Title', $post->post_title );
		$this->assertEquals( $block_content, $post->post_content );
		$this->assertEquals( 'draft', $post->post_status );
	}

	/**
	 * Test a  post is updated from a manifest entry if it exists.
	 */
	public function test_update_post() {
		$manifest_post = array(
			'post_title'     => 'Test Post Title',
			'post_status'    => 'draft',
			'post_date'      => '2019-01-01 00:00:00',
			'comment_status' => 'closed',
		);

		$actual_content = 'Hello World';
		$post_id        = PostCreator::create_or_update_post_from_manifest_entry( $manifest_post, $actual_content, 123 );
		$post           = get_post( $post_id );

		$manifest_post = array(
			'post_title'     => 'Test Post Title 2',
			'post_status'    => 'publish',
			'post_date'      => '2019-01-01 00:00:00',
			'comment_status' => 'closed',
		);

		$actual_content = 'Hello World 2';

		$post_id = PostCreator::create_or_update_post_from_manifest_entry( $manifest_post, $actual_content, 123 );

		$post = get_post( $post_id );

		$block_content = '<!-- wp:paragraph -->
<p>Hello World 2</p>
<!-- /wp:paragraph -->
';

		$this->assertEquals( 'Test Post Title 2', $post->post_title );
		$this->assertEquals( $block_content, $post->post_content );
		$this->assertEquals( 'publish', $post->post_status );
	}

	/**
	 * Test a post has edit url post meta added if its provided.
	 */
	public function test_edit_url_post_meta() {
		$manifest_post = array(
			'post_title'     => 'Test Post Title',
			'post_status'    => 'draft',
			'post_date'      => '2019-01-01 00:00:00',
			'comment_status' => 'closed',
			'edit_url'       => 'https://example.com/edit',
		);

		$actual_content = 'Hello World';

		$post_id = PostCreator::create_or_update_post_from_manifest_entry( $manifest_post, $actual_content, 123 );

		$this->assertEquals( 'https://example.com/edit', get_post_meta( $post_id, 'edit_url', true ) );
	}

}
