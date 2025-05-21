<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;

/**
 * Initializes the core blocks renderers.
 */
class Initializer {
	/**
	 * Initializes the core blocks renderers.
	 */
	public function initialize(): void {
		add_action( 'woocommerce_email_blocks_renderer_initialized', array( $this, 'register_core_blocks_renderers' ), 10, 1 );
		add_filter( 'woocommerce_email_editor_theme_json', array( $this, 'adjust_theme_json' ), 10, 1 );
		add_filter( 'safe_style_css', array( $this, 'allow_styles' ) );
	}

	/**
	 * Register core blocks email renderers when the blocks renderer is initialized.
	 *
	 * @param Blocks_Registry $blocks_registry Blocks registry.
	 */
	public function register_core_blocks_renderers( Blocks_Registry $blocks_registry ): void {
		$blocks_registry->add_block_renderer( 'core/paragraph', new Renderer\Blocks\Text() );
		$blocks_registry->add_block_renderer( 'core/heading', new Renderer\Blocks\Text() );
		$blocks_registry->add_block_renderer( 'core/column', new Renderer\Blocks\Column() );
		$blocks_registry->add_block_renderer( 'core/columns', new Renderer\Blocks\Columns() );
		$blocks_registry->add_block_renderer( 'core/list', new Renderer\Blocks\List_Block() );
		$blocks_registry->add_block_renderer( 'core/list-item', new Renderer\Blocks\List_Item() );
		$blocks_registry->add_block_renderer( 'core/image', new Renderer\Blocks\Image() );
		$blocks_registry->add_block_renderer( 'core/buttons', new Renderer\Blocks\Buttons( new Flex_Layout_Renderer() ) );
		$blocks_registry->add_block_renderer( 'core/button', new Renderer\Blocks\Button() );
		$blocks_registry->add_block_renderer( 'core/group', new Renderer\Blocks\Group() );
		$blocks_registry->add_block_renderer( 'core/quote', new Renderer\Blocks\Quote() );
		// Render used for all other blocks.
		$blocks_registry->add_fallback_renderer( new Renderer\Blocks\Fallback() );
	}

	/**
	 * Adjusts the editor's theme to add blocks specific settings for core blocks.
	 *
	 * @param \WP_Theme_JSON $editor_theme_json Editor theme JSON.
	 */
	public function adjust_theme_json( \WP_Theme_JSON $editor_theme_json ): \WP_Theme_JSON {
		$theme_json = (string) file_get_contents( __DIR__ . '/theme.json' );
		$theme_json = json_decode( $theme_json, true );
		/**
		 * Loaded theme json.
		 *
		 * @var array $theme_json
		 */
		$editor_theme_json->merge( new \WP_Theme_JSON( $theme_json, 'default' ) );
		return $editor_theme_json;
	}

	/**
	 * Allow styles for the email editor.
	 *
	 * @param array|null $allowed_styles Allowed styles.
	 */
	public function allow_styles( ?array $allowed_styles ): array {
		// The styles can be null in some cases.
		if ( ! is_array( $allowed_styles ) ) {
			$allowed_styles = array();
		}
		$allowed_styles[] = 'display';
		$allowed_styles[] = 'mso-padding-alt';
		$allowed_styles[] = 'mso-font-width';
		$allowed_styles[] = 'mso-text-raise';
		return $allowed_styles;
	}
}
