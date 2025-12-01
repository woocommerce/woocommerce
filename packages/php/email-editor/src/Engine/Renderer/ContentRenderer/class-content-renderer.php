<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Fallback;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Post_Content;
use WP_Block_Template;
use WP_Block_Type_Registry;
use WP_Post;

/**
 * Class Content_Renderer
 */
class Content_Renderer {
	/**
	 * Process manager
	 *
	 * @var Process_Manager
	 */
	private Process_Manager $process_manager;

	/**
	 * Theme controller
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	const CONTENT_STYLES_FILE = 'content.css';

	/**
	 * WordPress Block Type Registry.
	 *
	 * @var WP_Block_Type_Registry
	 */
	private WP_Block_Type_Registry $block_type_registry;

	/**
	 * CSS inliner
	 *
	 * @var Css_Inliner
	 */
	private Css_Inliner $css_inliner;

	/**
	 * Property to store the backup of the current template content.
	 *
	 * @var string|null
	 */
	private $backup_template_content;

	/**
	 * Property to store the backup of the current template ID.
	 *
	 * @var int|null
	 */
	private $backup_template_id;

	/**
	 * Property to store the backup of the current post.
	 *
	 * @var WP_Post|null
	 */
	private $backup_post;

	/**
	 * Property to store the backup of the current query.
	 *
	 * @var \WP_Query|null
	 */
	private $backup_query;

	/**
	 * Fallback renderer that is used when render_email_callback is not set for the rendered blockType.
	 *
	 * @var Fallback
	 */
	private Fallback $fallback_renderer;

	/**
	 * Logger instance.
	 *
	 * @var Email_Editor_Logger
	 */
	private Email_Editor_Logger $logger;

	/**
	 * Backup of the original core/post-content render callback.
	 *
	 * @var callable|null
	 */
	private $backup_post_content_callback;

	/**
	 * Content_Renderer constructor.
	 *
	 * @param Process_Manager     $preprocess_manager Preprocess manager.
	 * @param Css_Inliner         $css_inliner Css inliner.
	 * @param Theme_Controller    $theme_controller Theme controller.
	 * @param Email_Editor_Logger $logger Logger instance.
	 */
	public function __construct(
		Process_Manager $preprocess_manager,
		Css_Inliner $css_inliner,
		Theme_Controller $theme_controller,
		Email_Editor_Logger $logger
	) {
		$this->process_manager     = $preprocess_manager;
		$this->theme_controller    = $theme_controller;
		$this->css_inliner         = $css_inliner;
		$this->logger              = $logger;
		$this->block_type_registry = WP_Block_Type_Registry::get_instance();
		$this->fallback_renderer   = new Fallback();
	}

	/**
	 * Initialize the content renderer
	 *
	 * @return void
	 */
	private function initialize() {
		add_filter( 'render_block', array( $this, 'render_block' ), 10, 2 );
		add_filter( 'block_parser_class', array( $this, 'block_parser' ) );
		add_filter( 'woocommerce_email_blocks_renderer_parsed_blocks', array( $this, 'preprocess_parsed_blocks' ) );

		// Swap core/post-content render callback for email rendering.
		// This prevents issues with WordPress's static $seen_ids array when rendering
		// multiple emails in a single request (e.g., MailPoet batch processing).
		$post_content_type = $this->block_type_registry->get_registered( 'core/post-content' );
		if ( $post_content_type ) {
			// Save the original callback (may be null or WordPress's default).
			$this->backup_post_content_callback = $post_content_type->render_callback;

			// Replace with our stateless renderer.
			$post_content_renderer              = new Post_Content();
			$post_content_type->render_callback = array( $post_content_renderer, 'render_stateless' );
		}
	}

	/**
	 * Render the content
	 *
	 * @param WP_Post           $post Post object.
	 * @param WP_Block_Template $template Block template.
	 * @return string
	 */
	public function render( WP_Post $post, WP_Block_Template $template ): string {
		$this->set_template_globals( $post, $template );
		$this->initialize();
		$rendered_html = get_the_block_template_html();
		$this->reset();

		return $this->process_manager->postprocess( $this->inline_styles( $rendered_html, $post, $template ) );
	}

	/**
	 * Get block parser class
	 *
	 * @return string
	 */
	public function block_parser() {
		return 'Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Parser';
	}

	/**
	 * Preprocess parsed blocks
	 *
	 * @param array $parsed_blocks Parsed blocks.
	 * @return array
	 */
	public function preprocess_parsed_blocks( array $parsed_blocks ): array {
		return $this->process_manager->preprocess( $parsed_blocks, $this->theme_controller->get_layout_settings(), $this->theme_controller->get_styles() );
	}

	/**
	 * Renders block
	 * Translates block's HTML to HTML suitable for email clients. The method is intended as a callback for 'render_block' filter.
	 *
	 * @param string $block_content Block content.
	 * @param array  $parsed_block Parsed block.
	 * @return string
	 */
	public function render_block( string $block_content, array $parsed_block ): string {
		/**
		 * Filter the email-specific context data passed to block renderers.
		 *
		 * This allows email sending systems to provide context data such as user ID,
		 * email address, order information, etc., that can be used by blocks during rendering.
		 *
		 * Blocks that need cart product information can derive it from the user_id or recipient_email
		 * using CartCheckoutUtils::get_cart_product_ids_for_user().
		 *
		 * @since 1.9.0
		 *
		 * @param array $email_context {
		 *     Email-specific context data.
		 *
		 *     @type int    $user_id         The ID of the user receiving the email.
		 *     @type string $recipient_email The recipient's email address.
		 *     @type int    $order_id        The order ID (for order-related emails).
		 *     @type string $email_type      The type of email being rendered.
		 * }
		 */
		$email_context = apply_filters( 'woocommerce_email_editor_rendering_email_context', array() );

		$context = new Rendering_Context( $this->theme_controller->get_theme(), $email_context );

		$block_type = $this->block_type_registry->get_registered( $parsed_block['blockName'] );
		try {
			if ( $block_type && isset( $block_type->render_email_callback ) && is_callable( $block_type->render_email_callback ) ) {
				return call_user_func( $block_type->render_email_callback, $block_content, $parsed_block, $context );
			}
		} catch ( \Exception $error ) {
			$this->logger->error(
				'Error thrown while rendering block.',
				array(
					'exception'    => $error,
					'block_name'   => $parsed_block['blockName'],
					'parsed_block' => $parsed_block,
					'message'      => $error->getMessage(),
				)
			);
			// Returning the original content.
			return $block_content;
		}

		return $this->fallback_renderer->render( $block_content, $parsed_block, $context );
	}

	/**
	 * Set template globals
	 *
	 * @param WP_Post           $email_post Post object.
	 * @param WP_Block_Template $template Block template.
	 * @return void
	 */
	private function set_template_globals( WP_Post $email_post, WP_Block_Template $template ) {
		global $_wp_current_template_content, $_wp_current_template_id, $wp_query, $post;

		// Backup current values of globals.
		// Because overriding the globals can affect rendering of the page itself, we need to backup the current values.
		$this->backup_template_content = $_wp_current_template_content;
		$this->backup_template_id      = $_wp_current_template_id;
		$this->backup_query            = $wp_query;
		$this->backup_post             = $post;

		$_wp_current_template_id      = $template->id;
		$_wp_current_template_content = $template->content;
		$wp_query                     = new \WP_Query( array( 'p' => $email_post->ID ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to set the query for correct rendering the blocks.
		$post                         = $email_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to set the post for correct rendering the blocks.
	}

	/**
	 * As we use default WordPress filters, we need to remove them after email rendering
	 * so that we don't interfere with possible post rendering that might happen later.
	 */
	private function reset(): void {
		remove_filter( 'render_block', array( $this, 'render_block' ) );
		remove_filter( 'block_parser_class', array( $this, 'block_parser' ) );
		remove_filter( 'woocommerce_email_blocks_renderer_parsed_blocks', array( $this, 'preprocess_parsed_blocks' ) );

		// Restore the original core/post-content render callback.
		// Note: We always restore it, even if it was null originally.
		$post_content_type = $this->block_type_registry->get_registered( 'core/post-content' );
		if ( $post_content_type ) {
			// @phpstan-ignore-next-line -- WordPress core allows null for render_callback despite type definition.
			$post_content_type->render_callback = $this->backup_post_content_callback;
		}

		// Restore globals to their original values.
		global $_wp_current_template_content, $_wp_current_template_id, $wp_query, $post;

		$_wp_current_template_content = $this->backup_template_content;
		$_wp_current_template_id      = $this->backup_template_id;
		$wp_query                     = $this->backup_query;  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring of the query.
		$post                         = $this->backup_post;  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring of the post.
	}

	/**
	 * Method to inline styles into the HTML
	 *
	 * @param string                 $html HTML content.
	 * @param WP_Post                $post Post object.
	 * @param WP_Block_Template|null $template Block template.
	 * @return string
	 */
	private function inline_styles( $html, WP_Post $post, $template = null ) {
		$styles  = (string) file_get_contents( __DIR__ . '/' . self::CONTENT_STYLES_FILE );
		$styles .= (string) file_get_contents( __DIR__ . '/../../content-shared.css' );

		// Apply default contentWidth to constrained blocks.
		$layout  = $this->theme_controller->get_layout_settings();
		$styles .= sprintf(
			'
      .is-layout-constrained > *:not(.alignleft):not(.alignright):not(.alignfull) {
        max-width: %1$s;
        margin-left: auto !important;
        margin-right: auto !important;
      }
      .is-layout-constrained > .alignwide {
        max-width: %2$s;
        margin-left: auto !important;
        margin-right: auto !important;
      }
      ',
			$layout['contentSize'],
			$layout['wideSize']
		);

		// Get styles from theme.
		$styles              .= $this->theme_controller->get_stylesheet_for_rendering( $post, $template );
		$block_support_styles = $this->theme_controller->get_stylesheet_from_context( 'block-supports', array() );
		// Get styles from block-supports stylesheet. This includes rules such as layout (contentWidth) that some blocks use.
		// @see https://github.com/WordPress/WordPress/blob/3c5da9c74344aaf5bf8097f2e2c6a1a781600e03/wp-includes/script-loader.php#L3134
		// @internal :where is not supported by emogrifier, so we need to replace it with *.
		$block_support_styles = str_replace(
			':where(:not(.alignleft):not(.alignright):not(.alignfull))',
			'*:not(.alignleft):not(.alignright):not(.alignfull)',
			$block_support_styles
		);

		/*
		 * Layout CSS assumes the top level block will have a single DIV wrapper with children. Since our blocks use tables,
		 * we need to adjust this to look for children in the TD element. This may requires more advanced replacement but
		 * this works in the current version of Gutenberg.
		 * Example rule we're targetting: .wp-container-core-group-is-layout-1.wp-container-core-group-is-layout-1 > *
		 */
		$block_support_styles = preg_replace(
			'/group-is-layout-(\d+) >/',
			'group-is-layout-$1 > tbody tr td >',
			$block_support_styles
		);

		$styles .= $block_support_styles;

		/*
		 * Debugging for content styles. Remember these get inlined.
		 * echo '<pre>';
		 * var_dump($styles);
		 * echo '</pre>';
		 */

		$styles = '<style>' . wp_strip_all_tags( (string) apply_filters( 'woocommerce_email_content_renderer_styles', $styles, $post ) ) . '</style>';

		return $this->css_inliner->from_html( $styles . $html )->inline_css()->render();
	}
}
