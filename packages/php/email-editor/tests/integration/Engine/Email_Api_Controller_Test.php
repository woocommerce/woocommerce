<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

use Automattic\WooCommerce\EmailEditor\Engine\Email_Api_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Integration tests for Email_Api_Controller.
 */
class Email_Api_Controller_Test extends Email_Editor_Integration_Test_Case {
	/**
	 * The API controller instance.
	 *
	 * @var Email_Api_Controller
	 */
	private Email_Api_Controller $controller;

	/**
	 * The personalization tags registry.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private Personalization_Tags_Registry $registry;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry   = $this->di_container->get( Personalization_Tags_Registry::class );
		$this->controller = $this->di_container->get( Email_Api_Controller::class );

		// Register a test personalization tag.
		$this->registry->register(
			new Personalization_Tag(
				'Test Tag',
				'test_tag',
				'Test Category',
				function () {
					return 'Test Value';
				}
			)
		);
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_actions( 'woocommerce_email_editor_personalization_tags_for_post' );
	}

	/**
	 * Create a REST request for personalization tags endpoint.
	 *
	 * @return WP_REST_Request<array<string, mixed>>
	 */
	private function create_request(): WP_REST_Request {
		/**
		 * WP REST Request object.
		 *
		 * @var WP_REST_Request<array<string, mixed>> $request
		 */
		$request = new WP_REST_Request( 'GET', '/woocommerce-email-editor/v1/personalization_tags' );
		return $request;
	}

	/**
	 * Test that the action is fired when a valid post_id is provided.
	 */
	public function testActionFiredWithValidPostId(): void {
		$action_fired   = false;
		$received_value = null;

		add_action(
			'woocommerce_email_editor_personalization_tags_for_post',
			function ( $post_id ) use ( &$action_fired, &$received_value ) {
				$action_fired   = true;
				$received_value = $post_id;
			}
		);

		$request = $this->create_request();
		$request->set_param( 'post_id', 123 );

		$this->controller->get_personalization_tags_collection( $request );

		$this->assertTrue( $action_fired, 'Action should be fired when valid post_id is provided' );
		$this->assertSame( 123, $received_value, 'Action should receive the correct post_id' );
	}

	/**
	 * Test that the action is fired when post_id is provided as a string.
	 */
	public function testActionFiredWithNumericStringPostId(): void {
		$action_fired   = false;
		$received_value = null;

		add_action(
			'woocommerce_email_editor_personalization_tags_for_post',
			function ( $post_id ) use ( &$action_fired, &$received_value ) {
				$action_fired   = true;
				$received_value = $post_id;
			}
		);

		$request = $this->create_request();
		$request->set_param( 'post_id', '456' );

		$this->controller->get_personalization_tags_collection( $request );

		$this->assertTrue( $action_fired, 'Action should be fired when numeric string post_id is provided' );
		$this->assertSame( 456, $received_value, 'Action should receive the post_id converted to integer' );
	}

	/**
	 * Test that the action is NOT fired when post_id is not provided.
	 */
	public function testActionNotFiredWithoutPostId(): void {
		$action_fired = false;

		add_action(
			'woocommerce_email_editor_personalization_tags_for_post',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		$request = $this->create_request();

		$this->controller->get_personalization_tags_collection( $request );

		$this->assertFalse( $action_fired, 'Action should not be fired when post_id is not provided' );
	}

	/**
	 * Test that the action is NOT fired when post_id is 0.
	 */
	public function testActionNotFiredWithZeroPostId(): void {
		$action_fired = false;

		add_action(
			'woocommerce_email_editor_personalization_tags_for_post',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		$request = $this->create_request();
		$request->set_param( 'post_id', 0 );

		$this->controller->get_personalization_tags_collection( $request );

		$this->assertFalse( $action_fired, 'Action should not be fired when post_id is 0' );
	}

	/**
	 * Test that the action is NOT fired when post_id is a negative number.
	 */
	public function testActionNotFiredWithNegativePostId(): void {
		$action_fired = false;

		add_action(
			'woocommerce_email_editor_personalization_tags_for_post',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		$request = $this->create_request();
		$request->set_param( 'post_id', -1 );

		$this->controller->get_personalization_tags_collection( $request );

		$this->assertFalse( $action_fired, 'Action should not be fired when post_id is negative' );
	}

	/**
	 * Test that the action is NOT fired when post_id is a non-numeric string (like template IDs).
	 */
	public function testActionNotFiredWithNonNumericPostId(): void {
		$action_fired = false;

		add_action(
			'woocommerce_email_editor_personalization_tags_for_post',
			function () use ( &$action_fired ) {
				$action_fired = true;
			}
		);

		$request = $this->create_request();
		// Template IDs can be strings like "twentytwentyfive//wooemailtemplate".
		$request->set_param( 'post_id', 'twentytwentyfive//wooemailtemplate' );

		$this->controller->get_personalization_tags_collection( $request );

		$this->assertFalse( $action_fired, 'Action should not be fired when post_id is a non-numeric string' );
	}

	/**
	 * Test that the response returns personalization tags.
	 */
	public function testReturnsPersonalizationTags(): void {
		$request = $this->create_request();

		$response = $this->controller->get_personalization_tags_collection( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );

		// Find the test tag we registered.
		$found = false;
		foreach ( $data as $tag ) {
			if ( is_array( $tag ) && isset( $tag['token'] ) && '[test_tag]' === $tag['token'] ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Test tag should be in the response' );
	}
}
