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
}
