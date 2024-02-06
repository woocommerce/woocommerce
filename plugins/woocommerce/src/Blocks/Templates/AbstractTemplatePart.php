<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * AbstractTemplatePart class.
 *
 * Shared logic for templates parts.
 *
 * @internal
 */
abstract class AbstractTemplatePart {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	public static $slug;

	/**
	 * The title of the template.
	 *
	 * @var string
	 */
	public $template_title;

	/**
	 * The description of the template.
	 *
	 * @var string
	 */
	public $template_description;

	/**
	 * The template part area where the template part belongs.
	 *
	 * @var string
	 */
	public $template_area;

	/**
	 * Template part functionality is only initialized when using a theme that supports template parts.
	 */
	protected function __construct() {
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template_part' ) ) {
			BlockTemplatesRegistry::register_template( $this );
		}
	}
}
