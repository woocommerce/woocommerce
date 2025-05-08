<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\EmailEditor\EmailPatterns\PatternsController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplatesController;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmails;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use Automattic\WooCommerce\Internal\EmailEditor\TransactionalEmailPersonalizer;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplateApiController;
use Throwable;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Integration class for the Email Editor functionality.
 */
class Integration {
	const EMAIL_POST_TYPE = 'woo_email';

	/**
	 * The email editor page renderer instance.
	 *
	 * @var PageRenderer
	 */
	private PageRenderer $editor_page_renderer;

	/**
	 * The dependency check instance.
	 *
	 * @var Dependency_Check
	 */
	private Dependency_Check $dependency_check;

	/**
	 * The template API controller instance.
	 *
	 * @var TemplateApiController
	 */
	private TemplateApiController $template_api_controller;

	/**
	 * The email data API controller instance.
	 *
	 * @var EmailApiController
	 */
	private EmailApiController $email_api_controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$editor_container       = Email_Editor_Container::container();
		$this->dependency_check = $editor_container->get( Dependency_Check::class );
	}

	/**
	 * Initialize the integration.
	 *
	 * @internal
	 */
	final public function init(): void {
		if ( ! $this->dependency_check->are_dependencies_met() ) {
			// If dependencies are not met, do not initialize the email editor integration.
			return;
		}

		add_action( 'woocommerce_init', array( $this, 'initialize' ) );
	}

	/**
	 * Initialize the integration.
	 */
	public function initialize() {
		$this->init_hooks();
		$this->extend_post_api();
		$this->extend_template_post_api();
		$this->register_hooks();
	}

	/**
	 * Initialize hooks for required classes.
	 */
	public function init_hooks() {
		$container = wc_get_container();
		$container->get( PatternsController::class );
		$container->get( TemplatesController::class );
		$container->get( PersonalizationTagManager::class );
		$container->get( BlockEmailRenderer::class );
		$container->get( WCTransactionalEmails::class );
		$this->editor_page_renderer    = $container->get( PageRenderer::class );
		$this->template_api_controller = $container->get( TemplateApiController::class );
		$this->email_api_controller    = $container->get( EmailApiController::class );
	}

	/**
	 * Register hooks for the integration.
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_email_editor_post_types', array( $this, 'add_email_post_type' ) );
		add_filter( 'woocommerce_is_email_editor_page', array( $this, 'is_editor_page' ), 10, 1 );
		add_filter( 'replace_editor', array( $this, 'replace_editor' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, 'delete_email_template_associated_with_email_editor_post' ), 10, 2 );
		add_filter( 'woocommerce_email_editor_send_preview_email_rendered_data', array( $this, 'update_send_preview_email_rendered_data' ) );
		add_filter( 'woocommerce_email_editor_send_preview_email_personalizer_context', array( $this, 'update_send_preview_email_personalizer_context' ) );
		add_filter( 'woocommerce_email_editor_preview_post_template_html', array( $this, 'update_preview_post_template_html_data' ), 100, 1 );
	}

	/**
	 * Add WooCommerce email post type to the list of supported post types.
	 *
	 * @param array $post_types List of post types.
	 * @return array Modified list of post types.
	 */
	public function add_email_post_type( array $post_types ): array {
		$post_types[] = array(
			'name' => self::EMAIL_POST_TYPE,
			'args' => array(
				'labels'   => array(
					'name'          => __( 'Woo Emails', 'woocommerce' ),
					'singular_name' => __( 'Woo Email', 'woocommerce' ),
					'add_new_item'  => __( 'Add New Woo Email', 'woocommerce' ),
					'edit_item'     => __( 'Edit Woo Email', 'woocommerce' ),
					'new_item'      => __( 'New Woo Email', 'woocommerce' ),
					'view_item'     => __( 'View Woo Email', 'woocommerce' ),
					'search_items'  => __( 'Search Woo Emails', 'woocommerce' ),
				),
				'rewrite'  => array( 'slug' => self::EMAIL_POST_TYPE ),
				'supports' => array(
					'title',
					'editor' => array(
						'default-mode' => 'template-locked',
					),
				),
			),
		);
		return $post_types;
	}

	/**
	 * Check if current page is email editor page.
	 *
	 * @param bool $is_editor_page Current editor page status.
	 * @return bool Whether current page is email editor page.
	 */
	public function is_editor_page( bool $is_editor_page ): bool {
		if ( $is_editor_page ) {
			return $is_editor_page;
		}

		// We need to check early if we are on the email editor page. The check runs early so we can't use current_screen() here.
		if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
			$post = get_post( (int) $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We are not verifying the nonce here because we are not using the nonce in the function and the data is okay in this context (WP-admin errors out gracefully).
			return $post && self::EMAIL_POST_TYPE === $post->post_type;
		}

		return false;
	}

	/**
	 * Replace the default editor with our custom email editor.
	 *
	 * @param bool    $replace Whether to replace the editor.
	 * @param WP_Post $post    Post object.
	 * @return bool Whether the editor was replaced.
	 */
	public function replace_editor( $replace, $post ) {
		$current_screen = get_current_screen();
		if ( self::EMAIL_POST_TYPE === $post->post_type && $current_screen ) {
			$this->editor_page_renderer->render();
			return true;
		}
		return $replace;
	}

	/**
	 * Delete the email template associated with the email editor post when the post is permanently deleted.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public function delete_email_template_associated_with_email_editor_post( $post_id, $post ) {
		if ( self::EMAIL_POST_TYPE !== $post->post_type ) {
			return;
		}

		$post_manager = WCTransactionalEmailPostsManager::get_instance();

		$email_type = $post_manager->get_email_type_from_post_id( $post_id );

		if ( empty( $email_type ) ) {
			return;
		}

		$post_manager->delete_email_template( $email_type );
	}

	/**
	 * Extend the post API for the wp_template post type to add and save the woocommerce_data field.
	 */
	public function extend_template_post_api(): void {
		register_rest_field(
			'wp_template',
			'woocommerce_data',
			array(
				'get_callback'    => array( $this->template_api_controller, 'get_template_data' ),
				'update_callback' => array( $this->template_api_controller, 'save_template_data' ),
				'schema'          => $this->template_api_controller->get_template_data_schema(),
			)
		);
	}

	/**
	 * Filter email preview data to replace placeholders with actual content.
	 *
	 * This method retrieves the appropriate email type based on the request,
	 * generates the email content using the WooContentProcessor, and replaces
	 * the placeholder in the preview HTML.
	 *
	 * @param string $data       The preview data.
	 * @param string $email_type The email type identifier (e.g., 'customer_processing_order').
	 * @return string The updated preview data with placeholders replaced.
	 */
	private function update_email_preview_data( $data, string $email_type ) {
		$type_param = EmailPreview::DEFAULT_EMAIL_TYPE;

		if ( ! empty( $email_type ) ) {
			$type_param = WCTransactionalEmailPostsManager::get_instance()->get_email_type_class_name_from_template_name( $email_type );
		}

		$email_preview = wc_get_container()->get( EmailPreview::class );

		try {
			$message = $email_preview->generate_placeholder_content( $type_param );
		} catch ( \InvalidArgumentException $e ) {
			// If the provided type was invalid, fall back to the default.
			try {
				$message = $email_preview->generate_placeholder_content( EmailPreview::DEFAULT_EMAIL_TYPE );
			} catch ( \Throwable $e ) {
				return $data;
			}
		} catch ( \Throwable $e ) {
			return $data;
		}

		return str_replace( BlockEmailRenderer::WOO_EMAIL_CONTENT_PLACEHOLDER, $message, $data );
	}

	/**
	 * Filter email preview data used when sending a preview email.
	 *
	 * @param string $data The preview data.
	 * @return string The updated preview data with placeholders replaced.
	 */
	public function update_send_preview_email_rendered_data( $data ) {
		$email_type = '';
		$post_body  = file_get_contents( 'php://input' );

		if ( $post_body ) {
			$decoded_body = json_decode( $post_body );

			if ( json_last_error() === JSON_ERROR_NONE && isset( $decoded_body->postId ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$post_id = absint( $decoded_body->postId ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

				$email_type = WCTransactionalEmailPostsManager::get_instance()->get_email_type_from_post_id( $post_id );
				if ( ! empty( $email_type ) ) {
					return $this->update_email_preview_data( $data, $email_type );
				}
			}
		}
		return $data;
	}

	/**
	 * Update the personalizer context for the send preview email.
	 *
	 * @param array $context The personalizer context.
	 * @return array The updated personalizer context.
	 */
	public function update_send_preview_email_personalizer_context( $context ) {
		$post_manager             = WCTransactionalEmailPostsManager::get_instance();
		$email_type_template_name = $post_manager->get_email_type_from_post_id( get_the_ID() );
		$email_type               = $email_type_template_name ? $post_manager->get_email_type_class_name_from_template_name( $email_type_template_name ) : EmailPreview::DEFAULT_EMAIL_TYPE;
		$email_preview            = wc_get_container()->get( EmailPreview::class );

		try {
			$email_preview->set_email_type( $email_type );
		} catch ( \InvalidArgumentException $e ) {
			// If the email type is invalid, return the context data as is.
			return $context;
		}

		$email            = $email_preview->get_email();
		$email->recipient = $context['recipient_email'] ?? '';
		$personalizer     = wc_get_container()->get( TransactionalEmailPersonalizer::class );

		return $personalizer->prepare_context_data( $context, $email );
	}

	/**
	 * Filter email preview data used when previewing the email in new tab.
	 *
	 * @param string $data The preview HTML string.
	 * @return string The updated preview HTML with placeholders replaced.
	 */
	public function update_preview_post_template_html_data( $data ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// Nonce verification is disabled here because the preview action doesn't modify data,
		// and the check caused issues with the 'Preview in new tab' feature due to context changes.
		$type_param = isset( $_GET['woo_email'] ) ? sanitize_text_field( wp_unslash( $_GET['woo_email'] ) ) : '';
		// phpcs:enable
		return $this->update_email_preview_data( $data, $type_param );
	}

	/**
	 * Extend the post API for the woo_email post type to add and save the woocommerce_data field.
	 */
	public function extend_post_api(): void {
		register_rest_field(
			self::EMAIL_POST_TYPE,
			'woocommerce_data',
			array(
				'get_callback'    => array( $this->email_api_controller, 'get_email_data' ),
				'update_callback' => array( $this->email_api_controller, 'save_email_data' ),
				'schema'          => $this->email_api_controller->get_email_data_schema(),
			)
		);
	}
}
