<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

/**
 * Class managing the settings for the email editor.
 */
class Settings_Controller {

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
	);

	const DEFAULT_SETTINGS = array(
		'enableCustomUnits' => array( 'px', '%' ),
	);

	/**
	 * Theme controller.
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	/**
	 * Assets for iframe editor (component styles, scripts, etc.)
	 *
	 * @var array
	 */
	private array $iframe_assets = array();

	/**
	 * Class constructor.
	 *
	 * @param Theme_Controller $theme_controller Theme controller.
	 */
	public function __construct(
		Theme_Controller $theme_controller
	) {
		$this->theme_controller = $theme_controller;
	}

	/**
	 * Get the settings for the email editor.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$this->init_iframe_assets();

		$core_default_settings = \get_default_block_editor_settings();
		$theme_settings        = $this->theme_controller->get_settings();

		$settings                      = array_merge( $core_default_settings, self::DEFAULT_SETTINGS );
		$settings['allowedBlockTypes'] = self::ALLOWED_BLOCK_TYPES;
		// Assets for iframe editor (component styles, scripts, etc.).
		$settings['__unstableResolvedAssets'] = $this->iframe_assets;
		$editor_content_styles                = file_get_contents( __DIR__ . '/content-editor.css' );
		$shares_content_styles                = file_get_contents( __DIR__ . '/content-shared.css' );
		$settings['styles']                   = array(
			array( 'css' => $editor_content_styles ),
			array( 'css' => $shares_content_styles ),
		);

		$settings['__experimentalFeatures'] = $theme_settings;
		// Controls which alignment options are available for blocks.
		$settings['supportsLayout']              = true; // Allow using default layouts.
		$settings['__unstableIsBlockBasedTheme'] = true; // For default setting this to true disables wide and full alignments.
		return $settings;
	}

	/**
	 * Returns the layout settings for the email editor.
	 *
	 * @return array{contentSize: string, wideSize: string}
	 */
	public function get_layout(): array {
		$layout_settings = $this->theme_controller->get_layout_settings();
		return array(
			'contentSize' => $layout_settings['contentSize'],
			'wideSize'    => $layout_settings['wideSize'],
		);
	}

	/**
	 * Get the email styles.
	 *
	 * @return array{
	 *   spacing: array{
	 *     blockGap: string,
	 *     padding: array{bottom: string, left: string, right: string, top: string}
	 *   },
	 *   color: array{
	 *     background: string
	 *   },
	 *   typography: array{
	 *     fontFamily: string
	 *   }
	 * }
	 */
	public function get_email_styles(): array {
		$theme = $this->get_theme();
		return $theme->get_data()['styles'];
	}

	/**
	 * Returns the width of the layout without padding.
	 *
	 * @return string
	 */
	public function get_layout_width_without_padding(): string {
		$styles = $this->get_email_styles();
		$layout = $this->get_layout();
		$width  = $this->parse_number_from_string_with_pixels( $layout['contentSize'] );
		$width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['left'] );
		$width -= $this->parse_number_from_string_with_pixels( $styles['spacing']['padding']['right'] );
		return "{$width}px";
	}

	/**
	 * Parse styles string to array.
	 *
	 * @param string $styles Styles string.
	 * @return array
	 */
	public function parse_styles_to_array( string $styles ): array {
		$styles        = explode( ';', $styles );
		$parsed_styles = array();
		foreach ( $styles as $style ) {
			$style = explode( ':', $style );
			if ( count( $style ) === 2 ) {
				$parsed_styles[ trim( $style[0] ) ] = trim( $style[1] );
			}
		}
		return $parsed_styles;
	}

	/**
	 * Returns float number parsed from string with pixels.
	 *
	 * @param string $value Value with pixels.
	 * @return float
	 */
	public function parse_number_from_string_with_pixels( string $value ): float {
		return (float) str_replace( 'px', '', $value );
	}

	/**
	 * Returns the theme.
	 *
	 * @return \WP_Theme_JSON
	 */
	public function get_theme(): \WP_Theme_JSON {
		return $this->theme_controller->get_theme();
	}

	/**
	 * Translate slug to font size.
	 *
	 * @param string $font_size Font size slug.
	 * @return string
	 */
	public function translate_slug_to_font_size( string $font_size ): string {
		return $this->theme_controller->translate_slug_to_font_size( $font_size );
	}

	/**
	 * Translate slug to color.
	 *
	 * @param string $color_slug Color slug.
	 * @return string
	 */
	public function translate_slug_to_color( string $color_slug ): string {
		return $this->theme_controller->translate_slug_to_color( $color_slug );
	}

	/**
	 * Method to initialize iframe assets.
	 *
	 * @return void
	 */
	private function init_iframe_assets(): void {
		if ( ! empty( $this->iframe_assets ) ) {
			return;
		}

		$this->iframe_assets = _wp_get_iframed_editor_assets();

		// Remove layout styles and block library for classic themes. They are added only when a classic theme is active
		// and they add unwanted margins and paddings in the editor content.
		$cleaned_styles = array();
		foreach ( explode( "\n", (string) $this->iframe_assets['styles'] ) as $asset ) {
			if ( strpos( $asset, 'wp-editor-classic-layout-styles-css' ) !== false ) {
				continue;
			}
			if ( strpos( $asset, 'wp-block-library-theme-css' ) !== false ) {
				continue;
			}
			$cleaned_styles[] = $asset;
		}
		$this->iframe_assets['styles'] = implode( "\n", $cleaned_styles );
	}
}
