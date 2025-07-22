<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use WP_Theme_JSON;
use WP_Theme_JSON_Resolver;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;

/**
 * Site Style Sync Controller
 *
 * Manages the live synchronization of site styles to email templates.
 * Converts site theme styles to email-compatible formats while maintaining
 * visual consistency between the site and emails.
 */
class Site_Style_Sync_Controller {
	/**
	 * Current site theme data
	 *
	 * @var WP_Theme_JSON|null
	 */
	private ?WP_Theme_JSON $site_theme = null;

	/**
	 * Email-safe fonts
	 *
	 * @var array
	 */
	private $email_safe_fonts = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize' ), 20 );
	}

	/**
	 * Initialize the sync controller
	 *
	 * Hook into theme changes to trigger automatic sync
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'switch_theme', array( $this, 'invalidate_site_theme_cache' ) );
		add_action( 'customize_save_after', array( $this, 'invalidate_site_theme_cache' ) );
	}

	/**
	 * Sync site styles to email theme format
	 *
	 * @return array Email-compatible theme data.
	 */
	public function sync_site_styles(): array {
		$site_theme = $this->get_site_theme();
		$site_data  = $site_theme->get_data();

		$synced_data = array(
			'version'  => 3,
			'settings' => $this->sync_settings_data( $site_data['settings'] ?? array() ),
			'styles'   => $this->sync_styles_data( $site_data['styles'] ?? array() ),
		);

		/**
		 * Filter the synced site style data before applying to email theme
		 *
		 * @param array $synced_data The converted email-compatible theme data.
		 * @param array $site_data The original site theme data.
		 */
		$synced_data = apply_filters( 'woocommerce_email_editor_synced_site_styles', $synced_data, $site_data );

		return $synced_data;
	}

	/**
	 * Getter for site theme.
	 *
	 * @return ?WP_Theme_JSON Synced site theme.
	 */
	public function get_theme(): ?WP_Theme_JSON {
		if ( ! $this->is_sync_enabled() ) {
			return null;
		}

		$synced_data = $this->sync_site_styles();

		if ( empty( $synced_data ) || ! isset( $synced_data['version'] ) ) {
			return null;
		}

		return new WP_Theme_JSON( $synced_data, 'theme' );
	}

	/**
	 * Check if site style sync is enabled
	 *
	 * @return bool
	 */
	public function is_sync_enabled(): bool {
		/**
		 * Filter to enable/disable site style sync functionality
		 *
		 * @param bool $enabled Whether site style sync is enabled.
		 */
		return apply_filters( 'woocommerce_email_editor_site_style_sync_enabled', true );
	}

	/**
	 * Invalidate cached site theme data
	 *
	 * @return void
	 */
	public function invalidate_site_theme_cache(): void {
		if ( ! $this->is_sync_enabled() ) {
			return;
		}
		$this->site_theme = null;
	}

	/**
	 * Get site theme data
	 *
	 * @return WP_Theme_JSON
	 */
	private function get_site_theme(): WP_Theme_JSON {
		if ( null === $this->site_theme ) {
			// Get only the theme and user customizations (e.g. from site editor).
			$this->site_theme = new WP_Theme_JSON();
			$this->site_theme->merge( WP_Theme_JSON_Resolver::get_theme_data() );
			$this->site_theme->merge( WP_Theme_JSON_Resolver::get_user_data() );

			if ( isset( $this->site_theme->get_raw_data()['styles'] ) ) {
				$this->site_theme = WP_Theme_JSON::resolve_variables( $this->site_theme );
			}
		}
		return $this->site_theme;
	}

	/**
	 * Sync settings data from site theme to email-compatible format
	 *
	 * @param array $site_settings Site theme settings.
	 * @return array Email-compatible settings.
	 */
	private function sync_settings_data( array $site_settings ): array {
		$email_settings = array();

		// Sync color palette.
		if ( isset( $site_settings['color']['palette'] ) ) {
			$email_settings['color']['palette'] = $site_settings['color']['palette'];
		}

		return $email_settings;
	}

	/**
	 * Sync styles data from site theme to email-compatible format
	 *
	 * @param array $site_styles Site theme styles.
	 * @return array Email-compatible styles.
	 */
	private function sync_styles_data( array $site_styles ): array {
		$email_styles = array();

		// Sync color styles.
		if ( ! empty( $site_styles['color'] ) ) {
			$email_styles['color'] = $this->convert_color_styles( $site_styles['color'] );
		}

		// Sync typography styles.
		if ( ! empty( $site_styles['typography'] ) ) {
			$email_styles['typography'] = $this->convert_typography_styles( $site_styles['typography'] );
		}

		// Sync spacing styles.
		if ( ! empty( $site_styles['spacing'] ) ) {
			$email_styles['spacing'] = $this->convert_spacing_styles( $site_styles['spacing'] );
		}

		// Sync element styles.
		if ( ! empty( $site_styles['elements'] ) ) {
			$email_styles['elements'] = $this->convert_element_styles( $site_styles['elements'] );
		}

		return $email_styles;
	}

	/**
	 * Get email-safe fonts
	 *
	 * @return array Email-safe fonts.
	 */
	public function get_email_safe_fonts(): array {
		if ( empty( $this->email_safe_fonts ) ) {
			/**
			 * Pull email-safe fonts from theme.json (src/Engine/theme.json).
			 *
			 * @var array{settings?: array{typography?: array{fontFamilies?: array<array{name: string, slug: string, fontFamily: string}>}}} $theme_data
			 */
			$theme_data    = (array) json_decode( (string) file_get_contents( __DIR__ . '/theme.json' ), true );
			$font_families = $theme_data['settings']['typography']['fontFamilies'] ?? array();

			if ( ! empty( $font_families ) ) {
				foreach ( $font_families as $font_family ) {
					$this->email_safe_fonts[ strtolower( $font_family['slug'] ) ] = $font_family['fontFamily'];
				}
			}
		}
		return $this->email_safe_fonts;
	}

	/**
	 * Convert site color styles to email format
	 *
	 * @param array $color_styles Site color styles.
	 * @return array Email-compatible color styles.
	 */
	private function convert_color_styles( array $color_styles ): array {
		$email_colors = array();

		if ( isset( $color_styles['background'] ) ) {
			$email_colors['background'] = $color_styles['background'];
		}

		if ( isset( $color_styles['text'] ) ) {
			$email_colors['text'] = $color_styles['text'];
		}

		return $email_colors;
	}

	/**
	 * Convert site typography styles to email format
	 *
	 * @param array $typography_styles Site typography styles.
	 * @return array Email-compatible typography styles.
	 */
	private function convert_typography_styles( array $typography_styles ): array {
		$email_typography = array();

		// Convert font family to email-safe alternative.
		if ( isset( $typography_styles['fontFamily'] ) ) {
			$email_typography['fontFamily'] = $this->convert_to_email_safe_font( $typography_styles['fontFamily'] );
		}

		// Convert font size to px if needed.
		if ( isset( $typography_styles['fontSize'] ) ) {
			$email_typography['fontSize'] = $this->convert_to_px_size( $typography_styles['fontSize'] );
		}

		// Preserve email-compatible typography properties.
		$compatible_props = array( 'fontWeight', 'fontStyle', 'lineHeight', 'letterSpacing', 'textTransform', 'textDecoration' );
		foreach ( $compatible_props as $prop ) {
			if ( isset( $typography_styles[ $prop ] ) ) {
				$email_typography[ $prop ] = $typography_styles[ $prop ];
			}
		}

		return $email_typography;
	}

	/**
	 * Convert site spacing styles to email format
	 *
	 * @param array $spacing_styles Site spacing styles.
	 * @return array Email-compatible spacing styles.
	 */
	private function convert_spacing_styles( array $spacing_styles ): array {
		$email_spacing = array();

		// Convert padding to px values.
		if ( isset( $spacing_styles['padding'] ) ) {
			$email_spacing['padding'] = $this->convert_spacing_values( $spacing_styles['padding'] );
		}

		// Convert blockGap to px if present.
		if ( isset( $spacing_styles['blockGap'] ) ) {
			$email_spacing['blockGap'] = $this->convert_to_px_size( $spacing_styles['blockGap'] );
		}

		// Note: We intentionally skip margin as it's not supported in email renderer.

		return $email_spacing;
	}

	/**
	 * Convert site element styles to email format
	 *
	 * @param array $element_styles Site element styles.
	 * @return array Email-compatible element styles.
	 */
	private function convert_element_styles( array $element_styles ): array {
		$email_elements = array();

		// Process supported elements.
		$supported_elements = array( 'heading', 'button', 'link', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

		foreach ( $supported_elements as $element ) {
			if ( isset( $element_styles[ $element ] ) ) {
				$email_elements[ $element ] = $this->convert_element_style( $element_styles[ $element ] );
			}
		}

		return $email_elements;
	}

	/**
	 * Convert individual element style to email format
	 *
	 * @param array $element_style Site element style.
	 * @return array Email-compatible element style.
	 */
	private function convert_element_style( array $element_style ): array {
		$email_element = array();

		// Convert typography if present.
		if ( isset( $element_style['typography'] ) ) {
			$email_element['typography'] = $this->convert_typography_styles( $element_style['typography'] );
		}

		// Convert color if present.
		if ( isset( $element_style['color'] ) ) {
			$email_element['color'] = $this->convert_color_styles( $element_style['color'] );
		}

		// Convert spacing if present.
		if ( isset( $element_style['spacing'] ) ) {
			$email_element['spacing'] = $this->convert_spacing_styles( $element_style['spacing'] );
		}

		return $email_element;
	}

	/**
	 * Convert font family to email-safe alternative
	 *
	 * @param string $font_family Original font family.
	 * @return string Email-safe font family.
	 */
	private function convert_to_email_safe_font( string $font_family ): string {
		// Get email-safe fonts.
		$email_safe_fonts = $this->get_email_safe_fonts();

		// Map common web fonts to email-safe alternatives.
		$font_map = array(
			'helvetica' => $email_safe_fonts['arial'], // Arial fallback.
			'times'     => $email_safe_fonts['georgia'], // Georgia fallback.
			'courier'   => $email_safe_fonts['courier-new'], // Courier New.
			'trebuchet' => $email_safe_fonts['trebuchet-ms'],
		);

		$email_safe_fonts = array_merge( $email_safe_fonts, $font_map );

		$get_font_family = function ( $font_name ) use ( $email_safe_fonts ) {
			$font_name_lower = strtolower( $font_name );

			// First check for match in the email-safe slug.
			if ( isset( $email_safe_fonts[ $font_name_lower ] ) ) {
				return $email_safe_fonts[ $font_name_lower ];
			}

			// If no match in the slug, check for match in the font family name.
			foreach ( $email_safe_fonts as $safe_font_slug => $safe_font ) {
				if ( stripos( $safe_font, $font_name_lower ) !== false ) {
					return $safe_font;
				}
			}
			return null;
		};

		// Check if it's already an email-safe font.
		$font_family_array = explode( ',', $font_family );
		$safe_font_family  = $get_font_family( trim( $font_family_array[0] ) );
		if ( $safe_font_family ) {
			return $safe_font_family;
		}

		// Default to arial font if no match found.
		return $email_safe_fonts['arial'];
	}

	/**
	 * Convert size value to px format.
	 *
	 * @param string $size Original size value.
	 * @return string Size in px format.
	 */
	private function convert_to_px_size( string $size ): string {
		// Replace clamp() with its average value.
		if ( stripos( $size, 'clamp(' ) !== false ) {
			return Styles_Helper::clamp_to_static_px( $size, 'avg' ) ?? $size;
		}
		return Styles_Helper::convert_to_px( $size, false ) ?? $size; // Fallback to original value if conversion fails.
	}

	/**
	 * Convert spacing values to px format.
	 *
	 * @param string|array $spacing_values Original spacing values.
	 * @return string|array Spacing values in px format.
	 */
	private function convert_spacing_values( $spacing_values ) {
		if ( ! is_string( $spacing_values ) && ! is_array( $spacing_values ) ) {
			return $spacing_values;
		}

		if ( is_string( $spacing_values ) ) {
			return $this->convert_to_px_size( $spacing_values );
		}

		$px_values = array();

		foreach ( $spacing_values as $side => $value ) {
			if ( is_string( $value ) ) {
				$px_values[ $side ] = $this->convert_to_px_size( $value );
			} else {
				$px_values[ $side ] = $value;
			}
		}

		return $px_values;
	}
}
