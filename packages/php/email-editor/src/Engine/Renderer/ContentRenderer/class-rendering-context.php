<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use WP_Theme_JSON;

/**
 * Class Rendering_Context
 */
class Rendering_Context {
	/**
	 * Instance of the WP Theme.
	 *
	 * @var WP_Theme_JSON
	 */
	private WP_Theme_JSON $theme_json;

	/**
	 * Rendering_Context constructor.
	 *
	 * @param WP_Theme_JSON $theme_json Theme Json used in the email.
	 */
	public function __construct( WP_Theme_JSON $theme_json ) {
		$this->theme_json = $theme_json;
	}

	/**
	 * Returns WP_Theme_JSON instance that should be used during the email rendering.
	 *
	 * @return WP_Theme_JSON
	 */
	public function get_theme_json(): WP_Theme_JSON {
		return $this->theme_json;
	}

	/**
	 * Get the email theme styles.
	 *
	 * @return array{
	 *   spacing: array{
	 *     blockGap: string,
	 *     padding: array{bottom: string, left: string, right: string, top: string}
	 *   },
	 *   color: array{
	 *     background: string,
	 *     text: string
	 *   },
	 *   typography: array{
	 *     fontFamily: string
	 *   }
	 * }
	 */
	public function get_theme_styles(): array {
		$theme = $this->get_theme_json();
		return $theme->get_data()['styles'] ?? array();
	}

	/**
	 * Get settings from the theme.
	 *
	 * @return array
	 */
	public function get_theme_settings() {
		return $this->get_theme_json()->get_settings();
	}

	/**
	 * Returns the width of the layout without padding.
	 *
	 * @return string
	 */
	public function get_layout_width_without_padding(): string {
		$styles          = $this->get_theme_styles();
		$layout_settings = $this->get_theme_settings()['layout'] ?? array();
		$width           = Styles_Helper::parse_value( $layout_settings['contentSize'] ?? '0px' );
		$padding         = $styles['spacing']['padding'] ?? array();
		$width          -= Styles_Helper::parse_value( $padding['left'] ?? '0px' );
		$width          -= Styles_Helper::parse_value( $padding['right'] ?? '0px' );
		return "{$width}px";
	}

	/**
	 * Translate color slug to color.
	 *
	 * @param string $color_slug Color slug.
	 * @return string
	 */
	public function translate_slug_to_color( string $color_slug ): string {
		$settings = $this->get_theme_settings();

		$color_definitions = array_merge(
			$settings['color']['palette']['theme'] ?? array(),
			$settings['color']['palette']['default'] ?? array()
		);
		foreach ( $color_definitions as $color_definition ) {
			if ( $color_definition['slug'] === $color_slug ) {
				return strtolower( $color_definition['color'] );
			}
		}
		return $color_slug;
	}
}
