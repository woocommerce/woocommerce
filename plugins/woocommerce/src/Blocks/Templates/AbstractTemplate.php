<?php
declare( strict_types=1 );
namespace Automattic\WooCommerce\Blocks\Templates;

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
	 * Whether this is a taxonomy template.
	 *
	 * @var bool
	 */
	public bool $is_taxonomy_template = false;

	/**
	 * Initialization method.
	 */
	abstract public function init();

	/**
	 * Should return the title of the template.
	 *
	 * @return string
	 */
	abstract public function get_template_title();

	/**
	 * Should return the description of the template.
	 *
	 * @return string
	 */
	abstract public function get_template_description();
}
