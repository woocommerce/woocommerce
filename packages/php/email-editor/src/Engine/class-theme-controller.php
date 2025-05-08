<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use WP_Block_Template;
use WP_Post;
use WP_Theme_JSON;
use WP_Theme_JSON_Resolver;

/**
 * E-mail editor works with own theme.json which defines settings for the editor and styles for the e-mail.
 * This class is responsible for accessing data defined by the theme.json.
 */
class Theme_Controller {
	/**
	 * Core theme loaded from the WordPress core.
	 *
	 * @var WP_Theme_JSON
	 */
	private WP_Theme_JSON $core_theme;

	/**
	 * Base theme loaded from a file in the package directory.
	 *
	 * @var WP_Theme_JSON
	 */
	private WP_Theme_JSON $base_theme;

	/**
	 * User theme contains user custom styles and settings
	 *
	 * @var User_Theme
	 */
	private User_Theme $user_theme;

	/**
	 * Theme_Controller constructor.
	 */
	public function __construct() {
		$this->core_theme = WP_Theme_JSON_Resolver::get_core_data();
		$this->base_theme = new WP_Theme_JSON( (array) json_decode( (string) file_get_contents( __DIR__ . '/theme.json' ), true ), 'default' );
		$this->user_theme = new User_Theme();
	}

	/**
	 * Gets combined theme data from the core and base theme, merged with the user .
	 *
	 * @return WP_Theme_JSON
	 */
	public function get_theme(): WP_Theme_JSON {
		$theme = $this->get_base_theme();
		$theme->merge( $this->user_theme->get_theme() );

		return $theme;
	}

	/**
	 * Gets combined theme data from the core and base theme.
	 *
	 * @return WP_Theme_JSON
	 */
	public function get_base_theme(): WP_Theme_JSON {
		$theme = new WP_Theme_JSON();
		$theme->merge( $this->core_theme );
		$theme->merge( $this->base_theme );

		return apply_filters( 'woocommerce_email_editor_theme_json', $theme );
	}

	/**
	 * Replace preset variables with their values.
	 *
	 * @param array $values Styles array.
	 * @param array $presets Presets array.
	 * @return array
	 */
	private function recursive_replace_presets( $values, $presets ) {
		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$values[ $key ] = $this->recursive_replace_presets( $value, $presets );
			} elseif ( is_string( $value ) ) {
				$values[ $key ] = preg_replace( array_keys( $presets ), array_values( $presets ), $value );
			} else {
				$values[ $key ] = $value;
			}
		}
		return $values;
	}

	/**
	 * Replace preset variables with their values.
	 *
	 * @param array $styles Styles array.
	 * @return array
	 */
	private function recursive_extract_preset_variables( $styles ) {
		foreach ( $styles as $key => $style_value ) {
			if ( is_array( $style_value ) ) {
				$styles[ $key ] = $this->recursive_extract_preset_variables( $style_value );
			} elseif ( strpos( $style_value, 'var:preset|' ) === 0 ) {
				/** @var string $style_value */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
				$styles[ $key ] = 'var(--wp--' . str_replace( '|', '--', str_replace( 'var:', '', $style_value ) ) . ')';
			} else {
				$styles[ $key ] = $style_value;
			}
		}
		return $styles;
	}

	/**
	 * Get styles for the e-mail.
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
	public function get_styles(): array {
		$theme_styles = $this->get_theme()->get_data()['styles'];

		// Extract preset variables.
		$theme_styles = $this->recursive_extract_preset_variables( $theme_styles );

		// Replace preset values.
		$variables = $this->get_variables_values_map();
		$presets   = array();

		foreach ( $variables as $name => $value ) {
			$pattern             = '/var\(' . preg_quote( $name, '/' ) . '\)/i';
			$presets[ $pattern ] = $value;
		}

		/* @phpstan-ignore-next-line Return type defined above. */
		return $this->recursive_replace_presets( $theme_styles, $presets );
	}

	/**
	 * Get settings from the theme.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$email_editor_theme_settings                              = $this->get_theme()->get_settings();
		$site_theme_settings                                      = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
		$email_editor_theme_settings['color']['palette']['theme'] = array();
		if ( isset( $site_theme_settings['color']['palette']['theme'] ) ) {
			$email_editor_theme_settings['color']['palette']['theme'] = $site_theme_settings['color']['palette']['theme'];
		}
		return $email_editor_theme_settings;
	}

	/**
	 * Get layout settings from the theme.
	 *
	 * @return array{contentSize: string, wideSize: string, allowEditing?: bool, allowCustomContentAndWideSize?: bool}
	 */
	public function get_layout_settings(): array {
		return $this->get_theme()->get_settings()['layout'];
	}

	/**
	 * Get stylesheet from context.
	 *
	 * @param string $context Context.
	 * @param array  $options Options.
	 * @return string
	 */
	public function get_stylesheet_from_context( $context, $options = array() ): string {
		return function_exists( 'gutenberg_style_engine_get_stylesheet_from_context' ) ? gutenberg_style_engine_get_stylesheet_from_context( $context, $options ) : wp_style_engine_get_stylesheet_from_context( $context, $options );
	}

	/**
	 * Get stylesheet for rendering.
	 *
	 * @param WP_Post|null           $post Post object.
	 * @param WP_Block_Template|null $template Template object.
	 * @return string
	 */
	public function get_stylesheet_for_rendering( ?WP_Post $post = null, $template = null ): string {
		$email_theme_settings = $this->get_settings();

		$css_presets = '';
		// Font family classes.
		foreach ( $email_theme_settings['typography']['fontFamilies']['default'] as $font_family ) {
			$css_presets .= ".has-{$font_family['slug']}-font-family { font-family: {$font_family['fontFamily']}; } \n";
		}
		// Font size classes.
		foreach ( $email_theme_settings['typography']['fontSizes']['default'] as $font_size ) {
			$css_presets .= ".has-{$font_size['slug']}-font-size { font-size: {$font_size['size']}; } \n";
		}
		// Color palette classes.
		$color_definitions = array_merge( $email_theme_settings['color']['palette']['theme'], $email_theme_settings['color']['palette']['default'] );
		foreach ( $color_definitions as $color ) {
			$css_presets .= ".has-{$color['slug']}-color { color: {$color['color']}; } \n";
			$css_presets .= ".has-{$color['slug']}-background-color { background-color: {$color['color']}; } \n";
			$css_presets .= ".has-{$color['slug']}-border-color { border-color: {$color['color']}; } \n";
		}

		// Block specific styles.
		$css_blocks = '';
		$blocks     = $this->get_theme()->get_styles_block_nodes();
		foreach ( $blocks as $block_metadata ) {
			$css_blocks .= $this->get_theme()->get_styles_for_block( $block_metadata );
		}

		// Remove `:root :where(...)` selectors since they are not supported in the CSS inliner.
		$css_blocks = preg_replace( '/:root\s:where\((.*?)\)/', '$1', $css_blocks );

		// Element specific styles.
		$elements_styles = $this->get_theme()->get_raw_data()['styles']['elements'] ?? array();

		// Because the section styles is not a part of the output the `get_styles_block_nodes` method, we need to get it separately.
		if ( $template && $template->wp_id ) {
			$template_theme    = (array) get_post_meta( $template->wp_id, Email_Editor::WOOCOMMERCE_EMAIL_META_THEME_TYPE, true );
			$template_styles   = (array) ( $template_theme['styles'] ?? array() );
			$template_elements = $template_styles['elements'] ?? array();
			$elements_styles   = array_replace_recursive( (array) $elements_styles, (array) $template_elements );
		}

		if ( $post ) {
			$post_theme      = (array) get_post_meta( $post->ID, 'woocommerce_email_theme', true );
			$post_styles     = (array) ( $post_theme['styles'] ?? array() );
			$post_elements   = $post_styles['elements'] ?? array();
			$elements_styles = array_replace_recursive( (array) $elements_styles, (array) $post_elements );
		}

		$css_elements = '';
		foreach ( $elements_styles as $key => $elements_style ) {
			$selector = $key;

			if ( 'button' === $key ) {
				$selector      = '.wp-block-button';
				$css_elements .= wp_style_engine_get_styles( $elements_style, array( 'selector' => '.wp-block-button' ) )['css'];
				// Add color to link element.
				$css_elements .= wp_style_engine_get_styles( array( 'color' => array( 'text' => $elements_style['color']['text'] ?? '' ) ), array( 'selector' => '.wp-block-button a' ) )['css'];
				continue;
			}

			switch ( $key ) {
				case 'heading':
					$selector = 'h1, h2, h3, h4, h5, h6';
					break;
				case 'link':
					$selector = 'a:not(.button-link)';
					break;
			}

			$css_elements .= wp_style_engine_get_styles( $elements_style, array( 'selector' => $selector ) )['css'];
		}

		$result = $css_presets . $css_blocks . $css_elements;
		// Because font-size can by defined by the clamp() function that is not supported in the e-mail clients, we need to replace it to the value.
		// Regular expression to match clamp() function and capture its max value.
		$pattern = '/clamp\([^,]+,\s*[^,]+,\s*([^)]+)\)/';
		// Replace clamp() with its maximum value.
		$result = (string) preg_replace( $pattern, '$1', $result );
		return $result;
	}

	/**
	 * Translate font family slug to font family name.
	 *
	 * @param string $font_size Font size slug.
	 * @return string
	 */
	public function translate_slug_to_font_size( string $font_size ): string {
		$settings = $this->get_settings();
		foreach ( $settings['typography']['fontSizes']['default'] as $font_size_definition ) {
			if ( $font_size_definition['slug'] === $font_size ) {
				return $font_size_definition['size'];
			}
		}
		return $font_size;
	}

	/**
	 * Translate color slug to color.
	 *
	 * @param string $color_slug Color slug.
	 * @return string
	 */
	public function translate_slug_to_color( string $color_slug ): string {
		$settings          = $this->get_settings();
		$color_definitions = array_merge( $settings['color']['palette']['theme'], $settings['color']['palette']['default'] );
		foreach ( $color_definitions as $color_definition ) {
			if ( $color_definition['slug'] === $color_slug ) {
				return strtolower( $color_definition['color'] );
			}
		}
		return $color_slug;
	}

	/**
	 * Returns the map of CSS variables and their values from the theme.
	 *
	 * @return array
	 */
	public function get_variables_values_map(): array {
		$variables_css = $this->get_theme()->get_stylesheet( array( 'variables' ) );
		$map           = array();
		// Regular expression to match CSS variable definitions.
		$pattern = '/--(.*?):\s*(.*?);/';

		if ( preg_match_all( $pattern, $variables_css, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				// '--' . $match[1] is the variable name, $match[2] is the variable value.
				$map[ '--' . $match[1] ] = $match[2];
			}
		}

		return $map;
	}
}
