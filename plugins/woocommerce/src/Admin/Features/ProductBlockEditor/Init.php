<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\SimpleProductTemplate;
use Automattic\WooCommerce\Internal\Admin\Features\ProductBlockEditor\ProductTemplates\ProductVariationTemplate;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry\BlockTemplateRegistry;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\Block;
use Automattic\WooCommerce\Internal\Admin\BlockTemplates\BlockTemplateLogger;
use WP_Block_Editor_Context;

/**
 * Loads assets related to the product block editor.
 */
class Init {
	/**
	 * The context name used to identify the editor.
	 */
	const EDITOR_CONTEXT_NAME = 'woocommerce/edit-product';

	/**
	 * Supported post types.
	 *
	 * @var array
	 */
	private $supported_post_types = array( 'simple' );

	/**
	 * Redirection controller.
	 *
	 * @var RedirectionController
	 */
	private $redirection_controller;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( Features::is_enabled( 'product-variation-management' ) ) {
			array_push( $this->supported_post_types, 'variable' );
		}

		if ( Features::is_enabled( 'product-external-affiliate' ) ) {
			array_push( $this->supported_post_types, 'external' );
		}

		if ( Features::is_enabled( 'product-grouped' ) ) {
			array_push( $this->supported_post_types, 'grouped' );
		}

		$this->redirection_controller = new RedirectionController( $this->supported_post_types );

		if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_block_editor' ) ) {
			if ( ! Features::is_enabled( 'new-product-management-experience' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_conflicting_styles' ), 100 );
				add_action( 'get_edit_post_link', array( $this, 'update_edit_product_link' ), 10, 2 );
			}
			add_filter( 'woocommerce_admin_get_user_data_fields', array( $this, 'add_user_data_fields' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'woocommerce_register_post_type_product_variation', array( $this, 'enable_rest_api_for_product_variation' ) );

			add_action( 'current_screen', array( $this, 'set_current_screen_to_block_editor_if_wc_admin' ) );

			// Make sure the block registry is initialized so that core blocks are registered.
			BlockRegistry::get_instance();

			$tracks = new Tracks();
			$tracks->init();

			// Make sure the block template logger is initialized before any templates are created.
			BlockTemplateLogger::get_instance();
		}
	}

	/**
	 * Enqueue scripts needed for the product form block editor.
	 */
	public function enqueue_scripts() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		$this->register_product_editor_templates();
		$editor_settings = $this->get_product_editor_settings();

		$script_handle = 'wc-admin-edit-product';
		wp_register_script( $script_handle, '', array(), '0.1.0', true );
		wp_enqueue_script( $script_handle );
		wp_add_inline_script(
			$script_handle,
			'var productBlockEditorSettings = productBlockEditorSettings || ' . wp_json_encode( $editor_settings ) . ';',
			'before'
		);
		wp_add_inline_script(
			$script_handle,
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( $editor_settings['blockCategories'] ) ),
			'before'
		);
		wp_tinymce_inline_scripts();
		wp_enqueue_media();
	}

	/**
	 * Enqueue styles needed for the rich text editor.
	 */
	public function enqueue_styles() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}
		wp_enqueue_style( 'wp-edit-blocks' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_editor();
		/**
		 * Enqueue any block editor related assets.
		 *
		 * @since 7.1.0
		*/
		do_action( 'enqueue_block_editor_assets' );
	}

	/**
	 * Dequeue conflicting styles.
	 */
	public function dequeue_conflicting_styles() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}
		// Dequeing this to avoid conflicts, until we remove the 'woocommerce-page' class.
		wp_dequeue_style( 'woocommerce-blocktheme' );
	}

	/**
	 * Update the edit product links when the new experience is enabled.
	 *
	 * @param string $link    The edit link.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function update_edit_product_link( $link, $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return $link;
		}

		if ( $product->get_type() === 'simple' ) {
			return admin_url( 'admin.php?page=wc-admin&path=/product/' . $product->get_id() );
		}

		return $link;
	}

	/**
	 * Enables variation post type in REST API.
	 *
	 * @param array $args Array of post type arguments.
	 * @return array Array of post type arguments.
	 */
	public function enable_rest_api_for_product_variation( $args ) {
		$args['show_in_rest'] = true;

		return $args;
	}

	/**
	 * Adds fields so that we can store user preferences for the variations block.
	 *
	 * @param array $user_data_fields User data fields.
	 * @return array
	 */
	public function add_user_data_fields( $user_data_fields ) {
		return array_merge(
			$user_data_fields,
			array(
				'variable_product_block_tour_shown',
				'local_attributes_notice_dismissed_ids',
				'variable_items_without_price_notice_dismissed',
			)
		);
	}

	/**
	 * Sets the current screen to the block editor if a wc-admin page.
	 */
	public function set_current_screen_to_block_editor_if_wc_admin() {
		$screen = get_current_screen();

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// (no idea why I need that phpcs:ignore above, but I'm tired trying to re-write this comment to get it to pass)
		// we can't check the 'path' query param because client-side routing is used within wc-admin,
		// so this action handler is only called on the initial page load from the server, which might
		// not be the product edit page (it mostly likely isn't).
		if ( PageController::is_admin_page() ) {
			$screen->is_block_editor( true );

			wp_add_inline_script(
				'wp-blocks',
				'wp.blocks && wp.blocks.unstable__bootstrapServerSideBlockDefinitions && wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
			);
		}
	}

	/**
	 * Get the product editor settings.
	 */
	private function get_product_editor_settings() {
		$editor_settings = array();

		$template_registry     = wc_get_container()->get( BlockTemplateRegistry::class );
		$block_template_logger = BlockTemplateLogger::get_instance();

		$block_template_logger->log_template_events_to_file( 'simple-product' );
		$block_template_logger->log_template_events_to_file( 'product-variation' );

		$editor_settings['templates'] = array(
			'product'           => $template_registry->get_registered( 'simple-product' )->get_formatted_template(),
			'product_variation' => $template_registry->get_registered( 'product-variation' )->get_formatted_template(),
		);

		$editor_settings['templateEvents'] = array(
			'product'           => $block_template_logger->get_formatted_template_events( 'simple-product' ),
			'product_variation' => $block_template_logger->get_formatted_template_events( 'product-variation' ),
		);

		$block_editor_context = new WP_Block_Editor_Context( array( 'name' => self::EDITOR_CONTEXT_NAME ) );

		return get_block_editor_settings( $editor_settings, $block_editor_context );
	}

	/**
	 * Register product editor templates.
	 */
	private function register_product_editor_templates() {
		$template_registry = wc_get_container()->get( BlockTemplateRegistry::class );

		$template_registry->register( new SimpleProductTemplate() );
		$template_registry->register( new ProductVariationTemplate() );
	}
}
