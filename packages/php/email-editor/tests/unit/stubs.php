<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Files.OneObjectStructurePerFile.MultipleFound

// Dummy WP functions.
if ( ! function_exists( 'wp_parse_args' ) ) {
	/**
	 * Mock wp_parse_args function.
	 *
	 * @param array|object $args     Value to merge with $defaults.
	 * @param array        $defaults Array that serves as the defaults.
	 * @return array Merged user defined values with defaults.
	 */
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args =& $args;
		} else {
			$parsed_args = array();
		}

		if ( is_array( $defaults ) && $defaults ) {
			return array_merge( $defaults, $parsed_args );
		}

		return $parsed_args;
	}
}

if ( ! function_exists( 'wp_style_engine_get_styles' ) ) {
	/**
	 * Mock wp_style_engine_get_styles function.
	 *
	 * @param array $block_styles Array of block styles.
	 * @param array $options Optional. Style engine options.
	 * @return array
	 */
	function wp_style_engine_get_styles( $block_styles, $options = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Capture last call for assertions in unit tests.
		global $__email_editor_last_wp_style_engine_get_styles_call;
		$__email_editor_last_wp_style_engine_get_styles_call = array(
			'block_styles' => $block_styles,
			'options'      => $options,
		);

		// Return empty structure for empty input.
		if ( empty( $block_styles ) ) {
			return array(
				'css'          => '',
				'declarations' => array(),
				'classnames'   => '',
			);
		}

		// Return basic structure for non-empty input.
		return array(
			'css'          => 'padding: 10px;',
			'declarations' => array( 'padding' => '10px' ),
			'classnames'   => '',
		);
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Mock esc_url_raw function.
	 *
	 * @param string $url URL to sanitize.
	 * @return string Sanitized URL or empty string if invalid.
	 */
	function esc_url_raw( $url ) {
		// Simple URL validation for testing.
		if ( empty( $url ) ) {
			return '';
		}

		// Allow http, https, mailto, and tel protocols.
		if ( preg_match( '/^(https?:\/\/|mailto:|tel:)/i', $url ) ) {
			return $url;
		}

		return '';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	/**
	 * Mock esc_url function.
	 *
	 * @param string $url URL to sanitize.
	 * @return string Sanitized URL or empty string if invalid.
	 */
	function esc_url( $url ) {
		return esc_url_raw( $url );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Mock esc_attr function.
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false );
	}
}


// Dummy WP classes.
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
if ( ! class_exists( \WP_Theme_JSON::class ) ) {
	/**
	 * Class WP_Theme_JSON
	 */
	class WP_Theme_JSON {
		/**
		 * Constructor.
		 *
		 * @param array|null $theme_data Optional. Theme data to load.
		 * @param string     $origin     Optional. Origin of the theme data.
		 */
		public function __construct( ?array $theme_data = null, string $origin = 'theme' ) {
		}

		/**
		 * Get data.
		 *
		 * @return array
		 */
		public function get_data() {
			return array();
		}

		/**
		 * Get settings.
		 *
		 * @return array
		 */
		public function get_settings() {
			return array();
		}

		/**
		 * Merge another theme.json into this one.
		 *
		 * @param WP_Theme_JSON $theme_json Theme JSON to merge.
		 * @return void
		 */
		public function merge( WP_Theme_JSON $theme_json ): void {
		}

		/**
		 * Get styles for block nodes.
		 *
		 * @return array
		 */
		public function get_styles_block_nodes(): array {
			return array();
		}

		/**
		 * Get styles for a specific block.
		 *
		 * @param string $block_name Block name.
		 * @return array
		 */
		public function get_styles_for_block( string $block_name ): array {
			return array();
		}

		/**
		 * Get raw data.
		 *
		 * @return array
		 */
		public function get_raw_data(): array {
			return array();
		}

		/**
		 * Get stylesheet.
		 *
		 * @param string[] $types   Types of styles to load. Will load all by default. It accepts:
		 *                          - `variables`: only the CSS Custom Properties for presets & custom ones.
		 *                          - `styles`: only the styles section in theme.json.
		 *                          - `presets`: only the classes for the presets.
		 *                          - `base-layout-styles`: only the base layout styles.
		 *                          - `custom-css`: only the custom CSS.
		 * @param string[] $origins A list of origins to include. By default it includes VALID_ORIGINS.
		 * @param array    $options {
		 *     Optional. An array of options for now used for internal purposes only (may change without notice).
		 *
		 *     @type string $scope                           Makes sure all style are scoped to a given selector
		 *     @type string $root_selector                   Overwrites and forces a given selector to be used on the root node
		 *     @type bool   $skip_root_layout_styles         Omits root layout styles from the generated stylesheet. Default false.
		 *     @type bool   $include_block_style_variations  Includes styles for block style variations in the generated stylesheet. Default false.
		 * }
		 * @return string The resulting stylesheet.
		 */
		public function get_stylesheet( $types = array( 'variables', 'styles', 'presets' ), $origins = null, $options = array() ): string {
			return '';
		}
	}
}

if ( ! class_exists( \WP_Style_Engine::class ) ) {
	/**
	 * Class WP_Style_Engine
	 */
	class WP_Style_Engine {
		/**
		 * Compile CSS from declarations.
		 *
		 * @param array  $declarations Array of CSS declarations.
		 * @param string $selector     CSS selector.
		 * @return string
		 */
		public static function compile_css( $declarations, $selector = '' ) {
			$css = '';
			foreach ( $declarations as $property => $value ) {
				$css .= $property . ': ' . $value . '; ';
			}
			return trim( $css );
		}
	}
}

if ( ! class_exists( \WP_Block_Templates_Registry::class ) ) {
	/**
	 * Dummy class to replace WP_Block_Templates_Registry in PHPUnit tests.
	 */
	class WP_Block_Templates_Registry {
		/**
		 * List of registered templates.
		 *
		 * @var array Stores registered templates.
		 */
		private static array $registered_templates = array();

		/**
		 * Singleton instance.
		 *
		 * @var self|null
		 */
		private static ?self $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return self
		 */
		public static function get_instance(): self {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Checks if a template is registered.
		 *
		 * @param string $name Template name.
		 * @return bool
		 */
		public function is_registered( string $name ): bool {
			return isset( self::$registered_templates[ $name ] );
		}
	}
}

if ( ! class_exists( \PHPUnit_Framework_Exception::class ) ) {
	/**
	 * Class needed by wordpress-stubs for PHPStan.
	 */
	class PHPUnit_Framework_Exception {}
}

if ( ! class_exists( \WP_HTML_Tag_Processor::class ) ) {
	/**
	 * Mock WP_HTML_Tag_Processor class for unit tests.
	 */
	class WP_HTML_Tag_Processor {
		/**
		 * The HTML content.
		 *
		 * @var string
		 */
		private $html;

		/**
		 * Current tag position.
		 *
		 * @var int
		 */
		private $position = 0;

		/**
		 * Parsed tags.
		 *
		 * @var array
		 */
		private $tags = array();

		/**
		 * Constructor.
		 *
		 * @param string $html HTML content to process.
		 */
		public function __construct( string $html ) {
			$this->html = $html;
			$this->parse_html();
		}

		/**
		 * Parse HTML to extract tags.
		 */
		private function parse_html(): void {
			// Simple HTML parsing for testing purposes.
			preg_match_all( '/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*>/i', $this->html, $matches, PREG_OFFSET_CAPTURE );

			foreach ( $matches[0] as $index => $match ) {
				$tag_html = $match[0];
				$offset   = $match[1];
				$tag_name = $matches[1][ $index ][0];

				// Extract attributes.
				$attributes = array();
				preg_match_all( '/(\w+)=["\']([^"\']*)["\']/', $tag_html, $attr_matches );
				foreach ( $attr_matches[1] as $attr_index => $attr_name ) {
					$attributes[ $attr_name ] = $attr_matches[2][ $attr_index ];
				}

				$this->tags[] = array(
					'tag'        => $tag_name,
					'attributes' => $attributes,
					'html'       => $tag_html,
					'offset'     => $offset,
				);
			}
		}

		/**
		 * Move to the next tag.
		 *
		 * @return bool True if there's a next tag, false otherwise.
		 */
		public function next_tag(): bool {
			if ( $this->position < count( $this->tags ) ) {
				++$this->position;
				return true;
			}
			return false;
		}

		/**
		 * Get the current tag name.
		 *
		 * @return string|null Tag name or null if no current tag.
		 */
		public function get_tag(): ?string {
			if ( $this->position > 0 && $this->position <= count( $this->tags ) ) {
				return $this->tags[ $this->position - 1 ]['tag'];
			}
			return null;
		}

		/**
		 * Get an attribute value.
		 *
		 * @param string $name Attribute name.
		 * @return string|null Attribute value or null if not found.
		 */
		public function get_attribute( string $name ): ?string {
			if ( $this->position > 0 && $this->position <= count( $this->tags ) ) {
				$attributes = $this->tags[ $this->position - 1 ]['attributes'];
				return $attributes[ $name ] ?? null;
			}
			return null;
		}

		/**
		 * Set an attribute value.
		 *
		 * @param string $name Attribute name.
		 * @param string $value Attribute value.
		 */
		public function set_attribute( string $name, string $value ): void {
			if ( $this->position > 0 && $this->position <= count( $this->tags ) ) {
				$this->tags[ $this->position - 1 ]['attributes'][ $name ] = $value;
			}
		}

		/**
		 * Remove an attribute.
		 *
		 * @param string $name Attribute name.
		 */
		public function remove_attribute( string $name ): void {
			if ( $this->position > 0 && $this->position <= count( $this->tags ) ) {
				unset( $this->tags[ $this->position - 1 ]['attributes'][ $name ] );
			}
		}

		/**
		 * Get all attribute names.
		 *
		 * @param string $prefix Attribute prefix.
		 * @return array Array of attribute names.
		 */
		public function get_attribute_names_with_prefix( string $prefix ): array {
			if ( $this->position > 0 && $this->position <= count( $this->tags ) ) {
				$attributes = $this->tags[ $this->position - 1 ]['attributes'];
				return array_keys( $attributes );
			}
			return array();
		}

		/**
		 * Get the updated HTML.
		 *
		 * @return string Updated HTML.
		 */
		public function get_updated_html(): string {
			// For testing purposes, return the original HTML.
			// In a real implementation, this would reconstruct the HTML with updated attributes.
			return $this->html;
		}
	}
}

if ( ! class_exists( \IntegrationTester::class ) ) {
	/**
	 * Class IntegrationTester
	 * Used for integration tests
	 */
	class IntegrationTester {
		/**
		 * Constructor
		 */
		public function __construct() {
		}
	}
}
