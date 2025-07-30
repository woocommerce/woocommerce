<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Abstract_Block_Renderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Button;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Buttons;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Column;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Columns;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Fallback;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Group;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Image;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\List_Block;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\List_Item;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Quote;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Social_Link;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Social_Links;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Text;

/**
 * Initializes the core blocks renderers.
 */
class Initializer {
	/**
	 * List of supported blocks in the email editor.
	 */
	const ALLOWED_BLOCK_TYPES = array(
		'core/button',
		'core/buttons',
		'core/column',
		'core/columns',
		'core/group',
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item',
		'core/paragraph',
		'core/quote',
		'core/spacer',
		'core/social-link',
		'core/social-links',
		'core/site-logo',
		'core/site-title',
	);

	/**
	 * Cache renderers by block name.
	 *
	 * @var array<string, Abstract_Block_Renderer>
	 */
	private array $renderers = array();

	/**
	 * Initializes the core blocks renderers.
	 */
	public function initialize(): void {
		add_filter( 'woocommerce_email_editor_theme_json', array( $this, 'adjust_theme_json' ), 10, 1 );
		add_filter( 'safe_style_css', array( $this, 'allow_styles' ) );
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

	/**
	 * Set `supports.email = true` and configure render_email_callback for supported blocks.
	 *
	 * @param array $settings Block settings.
	 * @return array
	 */
	public function update_block_settings( array $settings ): array {
		if ( in_array( $settings['name'], self::ALLOWED_BLOCK_TYPES, true ) ) {
			$settings['supports']['email']     = true;
			$settings['render_email_callback'] = array( $this, 'render_block' );
		}

		return $settings;
	}

	/**
	 * Returns the block content rendered by the block renderer.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block settings.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	public function render_block( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		if ( isset( $parsed_block['blockName'] ) ) {
			$block_renderer = $this->get_block_renderer( $parsed_block['blockName'] );
			return $block_renderer->render( $block_content, $parsed_block, $rendering_context );
		}

		return $block_content;
	}

	/**
	 * Return an instance of Abstract_Block_Renderer by the block name.
	 *
	 * @param string $block_name Block name.
	 * @return Abstract_Block_Renderer
	 */
	private function get_block_renderer( string $block_name ): Abstract_Block_Renderer {
		if ( isset( $this->renderers[ $block_name ] ) ) {
			return $this->renderers[ $block_name ];
		}

		switch ( $block_name ) {
			case 'core/heading':
			case 'core/paragraph':
			case 'core/site-title':
				$renderer = new Text();
				break;
			case 'core/column':
				$renderer = new Column();
				break;
			case 'core/columns':
				$renderer = new Columns();
				break;
			case 'core/list':
				$renderer = new List_Block();
				break;
			case 'core/list-item':
				$renderer = new List_Item();
				break;
			case 'core/image':
				$renderer = new Image();
				break;
			case 'core/button':
				$renderer = new Button();
				break;
			case 'core/buttons':
				$renderer = new Buttons( new Flex_Layout_Renderer() );
				break;
			case 'core/group':
				$renderer = new Group();
				break;
			case 'core/quote':
				$renderer = new Quote();
				break;
			case 'core/social-link':
				$renderer = new Social_Link();
				break;
			case 'core/social-links':
				$renderer = new Social_Links();
				break;
			default:
				$renderer = new Fallback();
				break;
		}

		$this->renderers[ $block_name ] = $renderer;
		return $renderer;
	}
}
