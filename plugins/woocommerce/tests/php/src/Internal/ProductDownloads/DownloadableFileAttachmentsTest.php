<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductDownloads;

use WC_Unit_Test_Case;
use WP_Query;
use WP_REST_Request;

/**
 * Tests for the hooks registered by the DownloadableFileAttachments class.
 */
class DownloadableFileAttachmentsTest extends WC_Unit_Test_Case {
	/**
	 * Attachment stored in the woocommerce_uploads directory.
	 *
	 * @var int
	 */
	private int $downloadable_file_id;

	/**
	 * Attachment stored in the regular uploads directory, in a folder whose name contains "woocommerce_uploads".
	 *
	 * @var int
	 */
	private int $image_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->downloadable_file_id = self::factory()->attachment->create_object(
			array(
				'file'           => 'woocommerce_uploads/2026/10/ebook-abc123.pdf',
				'post_mime_type' => 'application/pdf',
			)
		);
		$this->image_id             = self::factory()->attachment->create_object(
			array(
				'file'           => '2026/10/my_woocommerce_uploads/photo.jpg',
				'post_mime_type' => 'image/jpeg',
				'comment_status' => 'open',
			)
		);
	}

	/**
	 * @testdox REST API media collections only include downloadable product files for users who can upload files.
	 * @testWith ["", false]
	 *           ["customer", false]
	 *           ["author", true]
	 *
	 * @param string $role                 Role of the current user, or empty for a visitor.
	 * @param bool   $expect_download_file Whether the downloadable product file should be listed.
	 */
	public function test_rest_media_collection_visibility( string $role, bool $expect_download_file ): void {
		wp_set_current_user( $role ? self::factory()->user->create( array( 'role' => $role ) ) : 0 );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/media' ) );
		$ids      = wp_list_pluck( $response->get_data(), 'id' );

		$this->assertSame( 200, $response->get_status(), 'The media collection should load.' );
		$this->assertContains( $this->image_id, $ids, 'Regular attachments should stay listed.' );
		$this->assertSame( $expect_download_file, in_array( $this->downloadable_file_id, $ids, true ), 'Downloadable product files should only be listed for users who can upload files.' );
	}

	/**
	 * @testdox REST API media collections stay filtered when another filter adds an OR condition.
	 */
	public function test_rest_media_collection_exclusion_survives_or_conditions(): void {
		global $wpdb;

		$or_filter = static function ( $where ) use ( $wpdb ) {
			return $where . " OR {$wpdb->posts}.post_title = 'never matches'";
		};
		add_filter( 'posts_where', $or_filter, 5 );
		wp_set_current_user( 0 );

		$ids = wp_list_pluck( rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/media' ) )->get_data(), 'id' );

		$this->assertNotContains( $this->downloadable_file_id, $ids, 'An OR added by another filter should not bring back downloadable product files.' );
		$this->assertContains( $this->image_id, $ids, 'Regular attachments should stay listed.' );
	}

	/**
	 * @testdox REST API media items are not found for downloadable product files unless the user can upload files.
	 * @testWith ["", 404]
	 *           ["customer", 404]
	 *           ["author", 200]
	 *
	 * @param string $role            Role of the current user, or empty for a visitor.
	 * @param int    $expected_status Expected response status for the downloadable product file.
	 */
	public function test_rest_media_item_visibility( string $role, int $expected_status ): void {
		wp_set_current_user( $role ? self::factory()->user->create( array( 'role' => $role ) ) : 0 );

		$download_response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/media/' . $this->downloadable_file_id ) );
		$image_response    = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/media/' . $this->image_id ) );

		$this->assertSame( $expected_status, $download_response->get_status(), 'Downloadable product files should only be returned to users who can upload files.' );
		$this->assertSame( 200, $image_response->get_status(), 'Regular attachments should stay readable.' );
	}

	/**
	 * @testdox oEmbed responses are not found for downloadable product image files requested by visitors.
	 */
	public function test_oembed_is_not_found_for_downloadable_files(): void {
		$downloadable_image_id = self::factory()->attachment->create_object(
			array(
				'file'           => 'woocommerce_uploads/2026/10/artwork-abc123.jpg',
				'post_mime_type' => 'image/jpeg',
			)
		);
		wp_set_current_user( 0 );

		$responses = array();
		foreach ( array( $downloadable_image_id, $this->image_id ) as $attachment_id ) {
			$request = new WP_REST_Request( 'GET', '/oembed/1.0/embed' );
			$request->set_param( 'url', add_query_arg( 'attachment_id', $attachment_id, home_url( '/' ) ) );
			$responses[ $attachment_id ] = rest_do_request( $request );
		}

		$this->assertSame( 404, $responses[ $downloadable_image_id ]->get_status(), 'Visitors should not get oEmbed data for downloadable product files.' );
		$this->assertSame( 200, $responses[ $this->image_id ]->get_status(), 'Regular attachments should keep their oEmbed data.' );
	}

	/**
	 * @testdox Attachment pages for downloadable product files show a 404 to visitors.
	 */
	public function test_attachment_page_is_not_found_for_visitors(): void {
		update_option( 'wp_attachment_pages_enabled', 0 );
		wp_set_current_user( 0 );
		$url = add_query_arg( 'attachment_id', $this->downloadable_file_id, home_url( '/' ) );
		$this->go_to( $url );

		$this->assertTrue( is_404(), 'Visitors should get a 404 instead of the attachment page or file redirect.' );
		$this->assertFalse( have_posts(), 'The attachment should not be available to templates.' );
		$this->assertStringNotContainsString( 'woocommerce_uploads', (string) redirect_canonical( $url, false ), 'The request should not redirect to the file.' );
	}

	/**
	 * @testdox Feeds for downloadable product files have no posts for visitors.
	 */
	public function test_attachment_feed_is_empty_for_visitors(): void {
		update_option( 'wp_attachment_pages_enabled', 0 );
		wp_set_current_user( 0 );
		$feed_url = add_query_arg(
			array(
				'attachment_id'   => $this->downloadable_file_id,
				'feed'            => 'rss2',
				'withoutcomments' => 1,
			),
			home_url( '/' )
		);
		$this->go_to( $feed_url );

		$this->assertTrue( is_feed(), 'The request should still be a feed.' );
		$this->assertFalse( have_posts(), 'The feed should have no attachment to render.' );
		$this->assertStringNotContainsString( 'woocommerce_uploads', (string) redirect_canonical( $feed_url, false ), 'The feed should not redirect to the file.' );
	}

	/**
	 * @testdox Pretty permalink feeds for downloadable product files are empty and don't redirect to the file for visitors.
	 */
	public function test_pretty_permalink_feed_is_empty_for_visitors(): void {
		update_option( 'wp_attachment_pages_enabled', 0 );
		$this->set_permalink_structure( '/%postname%/' );
		self::factory()->attachment->create_object(
			array(
				'file'           => 'woocommerce_uploads/2026/10/guide-abc123.pdf',
				'post_mime_type' => 'application/pdf',
				'post_title'     => 'guide-abc123',
			)
		);
		$feed_url = home_url( '/guide-abc123/feed/' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->go_to( $feed_url );
		$this->assertTrue( is_attachment(), 'The URL should resolve to the attachment for users who can see it.' );

		wp_set_current_user( 0 );
		$this->go_to( $feed_url );

		$this->assertFalse( have_posts(), 'The feed should have no attachment to render.' );
		$this->assertStringNotContainsString( 'guide-abc123', get_wp_title_rss(), 'The feed title should not reveal the file name.' );
		$this->assertStringNotContainsString( 'woocommerce_uploads', (string) redirect_canonical( $feed_url, false ), 'The feed should not redirect to the file.' );
	}

	/**
	 * @testdox Downloadable product files attached to a post show a 404 to visitors when requested by ID.
	 */
	public function test_attached_file_requested_by_id_is_not_found_for_visitors(): void {
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => 'woocommerce_uploads/2026/10/manual-abc123.pdf',
				'post_mime_type' => 'application/pdf',
				'post_parent'    => self::factory()->post->create(),
			)
		);
		wp_set_current_user( 0 );
		$this->go_to(
			add_query_arg(
				array(
					'p'         => $attachment_id,
					'post_type' => 'attachment',
				),
				home_url( '/' )
			)
		);

		$this->assertTrue( is_404(), 'Visitors should get a 404 for attached downloadable product files.' );
	}

	/**
	 * @testdox Links to downloadable product files leave out the file's slug unless the user can upload files.
	 */
	public function test_attachment_link_leaves_out_slug_for_visitors(): void {
		$this->set_permalink_structure( '/%postname%/' );
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'           => 'woocommerce_uploads/2026/10/manual-abc123.pdf',
				'post_mime_type' => 'application/pdf',
				'post_title'     => 'manual-abc123',
				'post_parent'    => self::factory()->post->create(),
			)
		);
		$id_only_link  = add_query_arg( 'attachment_id', $attachment_id, home_url( '/' ) );
		$requested_url = add_query_arg( 'p', $attachment_id, home_url( '/' ) );

		wp_set_current_user( 0 );
		// Runs the main query so redirect_canonical() below sees the same state as a real request.
		$this->go_to( $requested_url );

		$this->assertSame( $id_only_link, get_permalink( $attachment_id ), 'Visitors should get an ID-only link.' );
		$this->assertStringNotContainsString( 'manual-abc123', (string) redirect_canonical( $requested_url, false ), 'Canonical redirects should not reveal the slug.' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );
		$this->assertStringContainsString( 'manual-abc123', get_permalink( $attachment_id ), 'Users who can upload files should keep the usual link.' );
	}

	/**
	 * @testdox Downloadable product files take no comments and keep their file URL out of GUIDs for visitors.
	 */
	public function test_comments_and_guid(): void {
		wp_set_current_user( 0 );

		$this->assertFalse( comments_open( $this->downloadable_file_id ), 'Comments should be closed on downloadable product files.' );
		$this->assertFalse( pings_open( $this->downloadable_file_id ), 'Pings should be closed on downloadable product files.' );
		$this->assertSame( add_query_arg( 'attachment_id', $this->downloadable_file_id, home_url( '/' ) ), get_the_guid( $this->downloadable_file_id ), 'Visitors should not get the file URL as the GUID.' );
		$this->assertTrue( comments_open( $this->image_id ), 'Regular attachments should keep their comment status.' );
	}

	/**
	 * @testdox Previous and next image links to downloadable product files are left out for visitors.
	 * @testWith ["previous_image_link"]
	 *           ["next_image_link"]
	 *
	 * @param string $hook_name Adjacent image link filter.
	 */
	public function test_adjacent_image_links_leave_out_downloadable_files( string $hook_name ): void {
		wp_set_current_user( 0 );

		$this->assertSame( '', apply_filters( $hook_name, '<a href="#">link</a>', $this->downloadable_file_id, 'thumbnail', false ), 'Links to downloadable product files should be removed.' );
		$this->assertSame( '<a href="#">link</a>', apply_filters( $hook_name, '<a href="#">link</a>', $this->image_id, 'thumbnail', false ), 'Links to regular images should stay.' );
	}

	/**
	 * @testdox Queries other than the main query still find downloadable product files.
	 */
	public function test_secondary_queries_still_find_downloadable_files(): void {
		wp_set_current_user( 0 );

		$query = new WP_Query( array( 'attachment_id' => $this->downloadable_file_id ) );

		$this->assertSame( array( $this->downloadable_file_id ), wp_list_pluck( $query->posts, 'ID' ), 'Only the main query should drop the attachment.' );
	}

	/**
	 * @testdox Attachment pages stay available for regular attachments and for users who can manage WooCommerce.
	 */
	public function test_attachment_page_is_unchanged_when_not_hidden(): void {
		wp_set_current_user( 0 );
		$this->go_to( add_query_arg( 'attachment_id', $this->image_id, home_url( '/' ) ) );
		$this->assertTrue( is_attachment(), 'Regular attachment pages should not change.' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->go_to( add_query_arg( 'attachment_id', $this->downloadable_file_id, home_url( '/' ) ) );
		$this->assertTrue( is_attachment(), 'Users who can manage WooCommerce should still reach downloadable product file attachments.' );
	}
}
