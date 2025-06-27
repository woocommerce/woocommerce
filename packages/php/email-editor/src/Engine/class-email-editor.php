<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\Patterns\Patterns;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use WP_Post;
use WP_Theme_JSON;

/**
 * Email editor class.
 *
 * @phpstan-type EmailPostType array{name: string, args: array, meta: array{key: string, args: array}[]}
 * See register_post_type for details about EmailPostType args.
 */
class Email_Editor {
	public const WOOCOMMERCE_EMAIL_META_THEME_TYPE = 'woocommerce_email_theme';

	/**
	 * Property for the email API controller.
	 *
	 * @var Email_Api_Controller Email API controller.
	 */
	private Email_Api_Controller $email_api_controller;
	/**
	 * Property for the templates.
	 *
	 * @var Templates Templates.
	 */
	private Templates $templates;
	/**
	 * Property for the patterns.
	 *
	 * @var Patterns Patterns.
	 */
	private Patterns $patterns;
	/**
	 * Property for the send preview email controller.
	 *
	 * @var Send_Preview_Email Send Preview controller.
	 */
	private Send_Preview_Email $send_preview_email;

	/**
	 * Property for Personalization_Tags_Controller that allows initializing personalization tags.
	 *
	 * @var Personalization_Tags_Registry Personalization tags registry.
	 */
	private Personalization_Tags_Registry $personalization_tags_registry;

	/**
	 * Property for the logger.
	 *
	 * @var Email_Editor_Logger Logger instance.
	 */
	private Email_Editor_Logger $logger;

	/**
	 * Constructor.
	 *
	 * @param Email_Api_Controller          $email_api_controller Email API controller.
	 * @param Templates                     $templates Templates.
	 * @param Patterns                      $patterns Patterns.
	 * @param Send_Preview_Email            $send_preview_email Preview email controller.
	 * @param Personalization_Tags_Registry $personalization_tags_controller Personalization tags registry that allows initializing personalization tags.
	 * @param Email_Editor_Logger           $logger Logger instance.
	 */
	public function __construct(
		Email_Api_Controller $email_api_controller,
		Templates $templates,
		Patterns $patterns,
		Send_Preview_Email $send_preview_email,
		Personalization_Tags_Registry $personalization_tags_controller,
		Email_Editor_Logger $logger
	) {
		$this->email_api_controller          = $email_api_controller;
		$this->templates                     = $templates;
		$this->patterns                      = $patterns;
		$this->send_preview_email            = $send_preview_email;
		$this->personalization_tags_registry = $personalization_tags_controller;
		$this->logger                        = $logger;
	}

	/**
	 * Initialize the email editor.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->logger->info( 'Initializing email editor' );
		do_action( 'woocommerce_email_editor_initialized' );
		add_filter( 'woocommerce_email_editor_rendering_theme_styles', array( $this, 'extend_email_theme_styles' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		$this->register_block_patterns();
		$this->register_email_post_types();
		$this->register_block_templates();
		$this->register_email_post_sent_status();
		$this->register_personalization_tags();
		$is_editor_page = apply_filters( 'woocommerce_is_email_editor_page', false );
		if ( $is_editor_page ) {
			$this->extend_email_post_api();
		}
		add_action( 'rest_api_init', array( $this, 'register_email_editor_api_routes' ) );
		add_filter( 'woocommerce_email_editor_send_preview_email', array( $this->send_preview_email, 'send_preview_email' ), 11, 1 ); // allow for other filter methods to take precedent.
		add_filter( 'single_template', array( $this, 'load_email_preview_template' ) );
		add_filter( 'preview_post_link', array( $this, 'update_preview_post_link' ), 10, 2 );
		$this->logger->info( 'Email editor initialized successfully' );
	}

	/**
	 * Register block templates.
	 *
	 * @return void
	 */
	private function register_block_templates(): void {
		// Since we cannot currently disable blocks in the editor for specific templates, disable templates when viewing site editor. @see https://github.com/WordPress/gutenberg/issues/41062.
		$request_uri = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		if ( strstr( $request_uri, 'site-editor.php' ) === false ) {
			$post_types = array_column( $this->get_post_types(), 'name' );
			$this->templates->initialize( $post_types );
		}
	}

	/**
	 * Register block patterns.
	 *
	 * @return void
	 */
	private function register_block_patterns(): void {
		$this->patterns->initialize();
	}

	/**
	 * Register all custom post types that should be edited via the email editor
	 * The post types are added via woocommerce_email_editor_post_types filter.
	 *
	 * @return void
	 */
	private function register_email_post_types(): void {
		foreach ( $this->get_post_types() as $post_type ) {
			register_post_type(
				$post_type['name'],
				array_merge( $this->get_default_email_post_args(), $post_type['args'] )
			);
		}
	}

	/**
	 * Register all personalization tags registered via
	 * the woocommerce_email_editor_register_personalization_tags filter.
	 *
	 * @return void
	 */
	private function register_personalization_tags(): void {
		$this->personalization_tags_registry->initialize();
	}

	/**
	 * Returns the email post types.
	 *
	 * @return array
	 * @phpstan-return EmailPostType[]
	 */
	private function get_post_types(): array {
		$post_types = array();
		return apply_filters( 'woocommerce_email_editor_post_types', $post_types );
	}

	/**
	 * Returns the default arguments for email post types.
	 *
	 * @return array
	 */
	private function get_default_email_post_args(): array {
		return array(
			'public'                 => false,
			'hierarchical'           => false,
			'show_ui'                => true,
			'show_in_menu'           => false,
			'show_in_nav_menus'      => false,
			'supports'               => array(
				'editor' => array(
					'default-mode' => 'template-locked',
				),
				'title',
				'custom-fields',
			), // 'custom-fields' is required for loading meta fields via API.
			'has_archive'            => true,
			'show_in_rest'           => true, // Important to enable Gutenberg editor.
			'default_rendering_mode' => 'template-locked',
			'publicly_queryable'     => true,  // required by the preview in new tab feature.
		);
	}

	/**
	 * Register the 'sent' post status for emails.
	 *
	 * @return void
	 */
	private function register_email_post_sent_status(): void {
		$default_args = array(
			'public'                    => false,
			'exclude_from_search'       => true,
			'internal'                  => true, // for now, we hide it, if we use the status in the listings we may flip this and following values.
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
			'private'                   => true, // required by the preview in new tab feature for sent post (newsletter). Posts are only visible to site admins and editors.
		);
		$args         = apply_filters( 'woocommerce_email_editor_post_sent_status_args', $default_args );
		register_post_status(
			'sent',
			$args
		);
	}

	/**
	 * Extends the email post types with email specific data.
	 *
	 * @return void
	 */
	public function extend_email_post_api() {
		$email_post_types = array_column( $this->get_post_types(), 'name' );
		register_rest_field(
			$email_post_types,
			'email_data',
			array(
				'get_callback'    => array( $this->email_api_controller, 'get_email_data' ),
				'update_callback' => array( $this->email_api_controller, 'save_email_data' ),
				'schema'          => $this->email_api_controller->get_email_data_schema(),
			)
		);
	}

	/**
	 * Registers the API route endpoint for the email editor
	 *
	 * @return void
	 */
	public function register_email_editor_api_routes() {
		register_rest_route(
			'woocommerce-email-editor/v1',
			'/send_preview_email',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this->email_api_controller, 'send_preview_email_data' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_rest_route(
			'woocommerce-email-editor/v1',
			'/get_personalization_tags',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this->email_api_controller, 'get_personalization_tags' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Extends the email theme styles with the email specific styles.
	 *
	 * @param WP_Theme_JSON $theme Email theme styles.
	 * @param WP_Post       $post Email post object.
	 * @return WP_Theme_JSON
	 */
	public function extend_email_theme_styles( WP_Theme_JSON $theme, WP_Post $post ): WP_Theme_JSON {
		$email_theme = get_post_meta( $post->ID, self::WOOCOMMERCE_EMAIL_META_THEME_TYPE, true );
		if ( $email_theme && is_array( $email_theme ) ) {
			$theme->merge( new WP_Theme_JSON( $email_theme ) );
		}
		return $theme;
	}

	/**
	 * Enqueue admin styles that are needed by the email editor.
	 *
	 * @return void
	 */
	public function enqueue_admin_styles(): void {
		// Calling action that loads registered blockTypes.
		do_action( 'enqueue_block_editor_assets' );
	}

	/**
	 * Get the current post object
	 *
	 * @return array|mixed|WP_Post|null
	 */
	public function get_current_post() {
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = 0;
			if ( is_string( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- data valid
				$post_id = intval( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- data valid
			}
			$current_post = get_post( $post_id );
		} else {
			$current_post = $GLOBALS['post'];
		}
		return $current_post;
	}

	/**
	 * Check if the current post type is an email post type.
	 *
	 * @param string $current_post_type The current post type.
	 * @return bool
	 */
	private function current_post_is_email_post_type( $current_post_type ): bool {
		if ( ! $current_post_type ) {
			return false;
		}
		$email_post_types = array_column( $this->get_post_types(), 'name' );
		return in_array( $current_post_type, $email_post_types, true );
	}

	/**
	 * Use a custom page template for the email editor frontend rendering.
	 *
	 * @param string $template post template.
	 * @return string
	 */
	public function load_email_preview_template( $template ) {
		$post = $this->get_current_post();

		if ( ! $post instanceof \WP_Post ) {
			return $template;
		}

		if ( ! $this->current_post_is_email_post_type( $post->post_type ) ) {
			return $template;
		}

		add_filter(
			'woocommerce_email_editor_preview_post_template_html',
			function () use ( $post ) {
				// Generate HTML content for email editor post.
				return $this->send_preview_email->render_html( $post );
			}
		);

		return __DIR__ . '/Templates/single-email-post-template.php';
	}

	/**
	 * Update the preview post link to remove the preview nonce.
	 *
	 * @param string  $preview_link The preview post link.
	 * @param WP_Post $post The post object.
	 * @return string
	 */
	public function update_preview_post_link( $preview_link, $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return $preview_link;
		}

		if ( ! $this->current_post_is_email_post_type( $post->post_type ) ) {
			return $preview_link;
		}

		// Remove preview_nonce from the link.
		return remove_query_arg( 'preview_nonce', $preview_link );
	}
}
