<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

// Dummy WP classes.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
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
