<?php
namespace Automattic\WooCommerce\Blocks\Templates;

use Automattic\WooCommerce\Blocks\BlockTemplatesRegistry;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

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
	public function __construct() {
		if ( BlockTemplateUtils::supports_block_templates( 'wp_template' ) ) {
			BlockTemplatesRegistry::register_template( $this );
			$this->init();
		}
	}

	/**
	 * Initialization method.
	 */
	abstract public function init();
}
