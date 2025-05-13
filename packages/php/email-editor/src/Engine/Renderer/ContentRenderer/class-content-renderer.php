<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;
use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use WP_Block_Template;
use WP_Post;

/**
 * Class Content_Renderer
 */
class Content_Renderer {
	/**
	 * Blocks registry
	 *
	 * @var Blocks_Registry
	 */
	private Blocks_Registry $blocks_registry;

	/**
	 * Process manager
	 *
	 * @var Process_Manager
	 */
	private Process_Manager $process_manager;

	/**
	 * Settings controller
	 *
	 * @var Settings_Controller
	 */
	private Settings_Controller $settings_controller;

	/**
	 * Theme controller
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	const CONTENT_STYLES_FILE = 'content.css';

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
	 * Content_Renderer constructor.
	 *
	 * @param Process_Manager     $preprocess_manager Preprocess manager.
	 * @param Blocks_Registry     $blocks_registry Blocks registry.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @param Css_Inliner         $css_inliner Css inliner.
	 * @param Theme_Controller    $theme_controller Theme controller.
	 */
	public function __construct(
		Process_Manager $preprocess_manager,
		Blocks_Registry $blocks_registry,
		Settings_Controller $settings_controller,
		Css_Inliner $css_inliner,
		Theme_Controller $theme_controller
	) {
		$this->process_manager     = $preprocess_manager;
		$this->blocks_registry     = $blocks_registry;
		$this->settings_controller = $settings_controller;
		$this->theme_controller    = $theme_controller;
		$this->css_inliner         = $css_inliner;
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

		do_action( 'woocommerce_email_blocks_renderer_initialized', $this->blocks_registry );
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
		$renderer = $this->blocks_registry->get_block_renderer( $parsed_block['blockName'] );
		if ( ! $renderer ) {
			$renderer = $this->blocks_registry->get_fallback_renderer();
		}
		return $renderer ? $renderer->render( $block_content, $parsed_block, $this->settings_controller ) : $block_content;
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
		$this->backup_post             = $email_post;

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
		$this->blocks_registry->remove_all_block_renderers();
		remove_filter( 'render_block', array( $this, 'render_block' ) );
		remove_filter( 'block_parser_class', array( $this, 'block_parser' ) );
		remove_filter( 'woocommerce_email_blocks_renderer_parsed_blocks', array( $this, 'preprocess_parsed_blocks' ) );

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
