<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Templates\AbstractTemplate;
use Automattic\WooCommerce\Blocks\Templates\AbstractTemplatePart;

/**
 * BlockTemplatesRegistry class.
 *
 * @internal
 */
class BlockTemplatesRegistry {

	/**
	 * The array of registered templates.
	 *
	 * @var AbstractTemplate[]|AbstractTemplatePart[]
	 */
	private static $templates = array();

	/**
	 * Method to register a template.
	 *
	 * @param AbstractTemplate|AbstractTemplatePart $template Template to register.
	 */
	public static function register_template( $template ) {
		self::$templates[ $template::SLUG ] = $template;
	}

	/**
	 * Returns the template matching the slug
	 *
	 * @param string $template_slug Slug of the template to retrieve.
	 *
	 * @return AbstractTemplate|AbstractTemplatePart|null
	 */
	public static function get_template( $template_slug ) {
		if ( array_key_exists( $template_slug, self::$templates ) ) {
			$registered_template = self::$templates[ $template_slug ];
			return $registered_template;
		}
		return null;
	}
}
