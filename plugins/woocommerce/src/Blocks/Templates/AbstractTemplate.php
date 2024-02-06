<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;

/**
 * AbstractTemplate class.
 *
 * Shared logic for templates.
 *
 * @internal
 */
abstract class AbstractTemplate {

	/**
	 * The slug of the template.
	 *
	 * @var string
	 */
	const SLUG = '';

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
	 * Template functionality is only initialized when using a block theme.
	 */
	protected function __construct() {
		if ( wc_current_theme_is_fse_theme() ) {
			BlockTemplatesRegistry::register_template( $this );
		}
	}
}
