<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

// Dummy WP classes.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
if ( ! class_exists( \WP_Theme_JSON::class ) ) {
	/**
	 * Class WP_Theme_JSON
	 */
	class WP_Theme_JSON {
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
