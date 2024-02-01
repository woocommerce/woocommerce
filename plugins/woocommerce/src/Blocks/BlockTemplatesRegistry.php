<?php
namespace Automattic\WooCommerce\Blocks;

/**
 * BlockTemplatesRegistry class.
 *
 * @internal
 */
class BlockTemplatesRegistry {
	private static $templates = array();

	public static function register_template( $template ) {
		self::$templates[ $template->slug ] = $template;
	}

	public static function get_templates() {
		return self::$templates;
	}

	public static function get_template( $template_slug ) {
		if ( array_key_exists( $template_slug, self::$templates ) ) {
			$registered_template = self::$templates[ $template_slug ];
			return $registered_template;
		}
		return null;
	}
}
