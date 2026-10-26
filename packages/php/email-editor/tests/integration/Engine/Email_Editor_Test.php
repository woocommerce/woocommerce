<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Api_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates;
use Automattic\WooCommerce\EmailEditor\Engine\Patterns\Patterns;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;

/**
 * Integration test for Email_Editor class
 */
class Email_Editor_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Email editor instance
	 *
	 * @var Email_Editor
	 */
	private $email_editor;

	/**
	 * Callback to register custom post type
	 *
	 * @var callable
	 */
	private $post_register_callback;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->email_editor           = $this->di_container->get( Email_Editor::class );
		$this->post_register_callback = function ( $post_types ) {
			$post_types[] = array(
				'name' => 'custom_email_type',
				'args' => array(),
				'meta' => array(),
			);
			return $post_types;
		};
		add_filter( 'woocommerce_email_editor_post_types', $this->post_register_callback );
		$this->email_editor->initialize();
	}

	/**
	 * Test if the email register custom post type
	 */
	public function testItRegistersCustomPostTypeAddedViaHook(): void {
		$post_types = get_post_types();
		$this->assertArrayHasKey( 'custom_email_type', $post_types );
	}

	/**
	 * A logged out visitor should not see a draft email passed via ?post=.
	 */
	public function testItDoesNotRenderUnpublishedEmailForAnonymousVisitor(): void {
		wp_set_current_user( 0 );
		$post_id      = $this->create_email_post( 'draft' );
		$_GET['post'] = (string) $post_id;
		$fallback     = 'fallback-template.php';

		$template = $this->email_editor->load_email_preview_template( $fallback );

		$this->assertSame( $fallback, $template );
	}

	/**
	 * Published emails stay public so preview in a new tab keeps working.
	 */
	public function testItRendersPublishedEmailForAnonymousVisitor(): void {
		wp_set_current_user( 0 );
		$post_id      = $this->create_email_post( 'publish' );
		$_GET['post'] = (string) $post_id;

		$template = $this->email_editor->load_email_preview_template( 'fallback-template.php' );

		$this->assertStringEndsWith( 'single-email-post-template.php', $template );
	}

	/**
	 * A user who can read the email can still preview it while it is a draft.
	 */
	public function testItRendersUnpublishedEmailForUserWithReadAccess(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->assertIsInt( $user_id );
		wp_set_current_user( $user_id );
		$post_id      = $this->create_email_post( 'draft' );
		$_GET['post'] = (string) $post_id;

		$template = $this->email_editor->load_email_preview_template( 'fallback-template.php' );

		$this->assertStringEndsWith( 'single-email-post-template.php', $template );
	}

	/**
	 * The preview link an email post opens keeps preview=true, which is what
	 * makes WordPress serve the unsaved revision, and drops the nonce.
	 */
	public function testItStripsOnlyThePreviewNonceFromAnEmailPreviewLink(): void {
		$post = get_post( $this->create_email_post( 'publish' ) );

		$link = apply_filters(
			'preview_post_link',
			'http://example.com/?p=1&preview=true&preview_nonce=abc123',
			$post
		);

		$this->assertSame( 'http://example.com/?p=1&preview=true', $link );
	}

	/**
	 * A post of another type keeps the preview link WordPress built for it.
	 */
	public function testItLeavesThePreviewLinkOfANonEmailPostUntouched(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$this->assertIsInt( $post_id );
		$link = 'http://example.com/?p=1&preview=true&preview_nonce=abc123';

		$this->assertSame( $link, apply_filters( 'preview_post_link', $link, get_post( $post_id ) ) );
	}

	/**
	 * Without a post to inspect the preview link passes through unchanged.
	 */
	public function testItLeavesThePreviewLinkUntouchedWhenThereIsNoPost(): void {
		$link = 'http://example.com/?p=1&preview=true&preview_nonce=abc123';

		$this->assertSame( $link, apply_filters( 'preview_post_link', $link, null ) );
	}

	/**
	 * On WordPress 7.2+ an email post type that opts in gets the email editor's trash action instead of core's.
	 */
	public function testItReplacesCoreMoveToTrashActionForEmailPostTypesThatOptIn(): void {
		if ( ! function_exists( 'wp_get_registered_entity_actions' ) ) {
			$this->markTestSkipped( 'The entity actions API requires WordPress 7.2.' );
		}
		$this->reset_entity_actions_registry();
		add_filter(
			'woocommerce_email_editor_replace_trash_action',
			function ( $replace, $post_type ) {
				return 'custom_email_type' === $post_type ? true : $replace;
			},
			10,
			2
		);

		$ids = array_column( wp_get_registered_entity_actions( 'postType', 'custom_email_type' ), 'id' );

		$this->assertContains( 'trash-email-post', $ids );
		$this->assertNotContains( 'move-to-trash', $ids );
		$this->assertSame( array( 'trash-email-post' ), $this->get_script_module_action_ids( 'custom_email_type' ) );
		$this->assertStringContainsString( Email_Editor::ENTITY_ACTIONS_SCRIPT_MODULE_ID, $this->get_import_map() );
	}

	/**
	 * Email post types keep core's trash action unless they opt in, since their editor's JavaScript may not provide the replacement.
	 */
	public function testItKeepsCoreMoveToTrashActionForEmailPostTypesThatDoNotOptIn(): void {
		if ( ! function_exists( 'wp_get_registered_entity_actions' ) ) {
			$this->markTestSkipped( 'The entity actions API requires WordPress 7.2.' );
		}
		$this->reset_entity_actions_registry();

		$ids = array_column( wp_get_registered_entity_actions( 'postType', 'custom_email_type' ), 'id' );

		$this->assertContains( 'move-to-trash', $ids );
		$this->assertNotContains( 'trash-email-post', $ids );
	}

	/**
	 * Post types the email editor does not handle keep core's trash action.
	 */
	public function testItKeepsCoreMoveToTrashActionForOtherPostTypes(): void {
		if ( ! function_exists( 'wp_get_registered_entity_actions' ) ) {
			$this->markTestSkipped( 'The entity actions API requires WordPress 7.2.' );
		}
		$this->reset_entity_actions_registry();

		$ids = array_column( wp_get_registered_entity_actions( 'postType', 'post' ), 'id' );

		$this->assertContains( 'move-to-trash', $ids );
		$this->assertNotContains( 'trash-email-post', $ids );
	}

	/**
	 * Returns the ids of the actions the email editor's script module provides for a post type.
	 * WordPress 7.2 Beta 1 keys the modules by id, later builds list them as `id` and `actions` entries.
	 *
	 * @param string $post_type The post type.
	 * @return array The action ids.
	 */
	private function get_script_module_action_ids( string $post_type ): array {
		if ( ! function_exists( 'wp_get_registered_entity_action_modules' ) ) {
			return array();
		}
		foreach ( wp_get_registered_entity_action_modules( 'postType', $post_type ) as $key => $module ) {
			if ( isset( $module['id'], $module['actions'] ) ) {
				if ( Email_Editor::ENTITY_ACTIONS_SCRIPT_MODULE_ID === $module['id'] ) {
					return $module['actions'];
				}
			} elseif ( Email_Editor::ENTITY_ACTIONS_SCRIPT_MODULE_ID === (string) $key ) {
				return $module;
			}
		}
		return array();
	}

	/**
	 * Returns the import map the editor page would print with the email editor's script module as a dependency.
	 */
	private function get_import_map(): string {
		wp_register_script_module(
			'email-editor-test',
			'https://example.com/test.js',
			array(
				array(
					'id'     => Email_Editor::ENTITY_ACTIONS_SCRIPT_MODULE_ID,
					'import' => 'dynamic',
				),
			)
		);
		wp_enqueue_script_module( 'email-editor-test' );
		$import_map = get_echo( array( wp_script_modules(), 'print_import_map' ) );
		wp_dequeue_script_module( 'email-editor-test' );
		wp_deregister_script_module( 'email-editor-test' );
		return $import_map;
	}

	/**
	 * Drops the entity actions registry, which fills once per request,
	 * so the next read fires `wp_entity_actions_api_init` again.
	 */
	private function reset_entity_actions_registry(): void {
		if ( ! class_exists( \WP_Entity_Actions_Registry::class ) ) {
			return;
		}
		$instance = new \ReflectionProperty( \WP_Entity_Actions_Registry::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Create an email post of the registered custom type.
	 *
	 * @param string $status The post status.
	 * @return int The created post ID.
	 */
	private function create_email_post( string $status ): int {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'custom_email_type',
				'post_status' => $status,
			)
		);
		$this->assertIsInt( $post_id );
		return $post_id;
	}

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		unset( $_GET['post'] );
		$this->reset_entity_actions_registry();
		parent::tearDown();
		remove_filter( 'woocommerce_email_editor_post_types', $this->post_register_callback );
	}
}
