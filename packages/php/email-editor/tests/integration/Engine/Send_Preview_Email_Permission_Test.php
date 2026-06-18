<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

/**
 * Integration test for the send_preview_email endpoint permission callback.
 */
class Send_Preview_Email_Permission_Test extends \Email_Editor_Integration_Test_Case {

	/**
	 * Email editor instance.
	 *
	 * @var Email_Editor
	 */
	private $email_editor;

	/**
	 * REST server instance.
	 *
	 * @var \WP_REST_Server
	 */
	private $server;

	/**
	 * The endpoint route.
	 *
	 * @var string
	 */
	private const ROUTE = '/woocommerce-email-editor/v1/send_preview_email';

	/**
	 * Creates a user and returns an integer ID.
	 *
	 * @param array $args Arguments for user creation.
	 * @return int
	 */
	private function create_user( array $args ): int {
		$result = self::factory()->user->create( $args );
		$this->assertIsInt( $result );
		return $result;
	}

	/**
	 * Creates a post and returns an integer ID.
	 *
	 * @param array $args Arguments for post creation.
	 * @return int
	 */
	private function create_post( array $args = array() ): int {
		$result = self::factory()->post->create( $args );
		$this->assertIsInt( $result );
		return $result;
	}

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->email_editor = $this->di_container->get( Email_Editor::class );

		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );
		$this->email_editor->register_email_editor_api_routes();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Test that an admin can send a preview email for their own post.
	 */
	public function testAdminCanSendPreviewForOwnPost(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		$post_id  = $this->create_post( array( 'post_author' => $admin_id ) );

		wp_set_current_user( $admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => $post_id,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotEquals( 403, $response->get_status(), 'Admin should be allowed to send preview for own post' );
	}

	/**
	 * Test that an editor can send a preview email for another user's post.
	 */
	public function testEditorCanSendPreviewForOtherUsersPost(): void {
		$author_id = $this->create_user( array( 'role' => 'author' ) );
		$editor_id = $this->create_user( array( 'role' => 'editor' ) );
		$post_id   = $this->create_post( array( 'post_author' => $author_id ) );

		wp_set_current_user( $editor_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => $post_id,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertNotEquals( 403, $response->get_status(), 'Editor should be allowed to send preview for another user\'s post' );
	}

	/**
	 * Test that a contributor cannot send a preview email for another user's private post.
	 */
	public function testContributorCannotSendPreviewForOtherUsersPrivatePost(): void {
		$admin_id       = $this->create_user( array( 'role' => 'administrator' ) );
		$contributor_id = $this->create_user( array( 'role' => 'contributor' ) );
		$post_id        = $this->create_post(
			array(
				'post_author' => $admin_id,
				'post_status' => 'private',
			)
		);

		wp_set_current_user( $contributor_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => $post_id,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Contributor should not be allowed to send preview for another user\'s private post' );
	}

	/**
	 * Test that a subscriber cannot send a preview email.
	 */
	public function testSubscriberCannotSendPreview(): void {
		$admin_id      = $this->create_user( array( 'role' => 'administrator' ) );
		$subscriber_id = $this->create_user( array( 'role' => 'subscriber' ) );
		$post_id       = $this->create_post( array( 'post_author' => $admin_id ) );

		wp_set_current_user( $subscriber_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => $post_id,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Subscriber should not be allowed to send preview email' );
	}

	/**
	 * Test that a request without postId is denied.
	 */
	public function testRequestWithoutPostIdIsDenied(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email' => 'test@example.com',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request without postId should be denied' );
	}

	/**
	 * Test that a request with non-numeric postId is denied.
	 */
	public function testRequestWithNonNumericPostIdIsDenied(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => 'abc',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request with non-numeric postId should be denied' );
	}

	/**
	 * Test that a request with zero postId is denied.
	 */
	public function testRequestWithZeroPostIdIsDenied(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => 0,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request with zero postId should be denied' );
	}

	/**
	 * Test that a request with negative postId is denied.
	 */
	public function testRequestWithNegativePostIdIsDenied(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => -1,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request with negative postId should be denied' );
	}

	/**
	 * Test that an unauthenticated request is denied.
	 */
	public function testUnauthenticatedRequestIsDenied(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		$post_id  = $this->create_post( array( 'post_author' => $admin_id ) );

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => $post_id,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ), 'Unauthenticated request should be denied' );
	}

	/**
	 * Test that a request with non-existent postId is denied.
	 */
	public function testRequestWithNonExistentPostIdIsDenied(): void {
		$admin_id = $this->create_user( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new \WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params(
			array(
				'email'  => 'test@example.com',
				'postId' => 999999,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request with non-existent postId should be denied' );
	}
}
