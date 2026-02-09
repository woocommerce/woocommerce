<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Patterns;

/**
 * Abstract class for block patterns.
 */
abstract class Abstract_Pattern {
	/**
	 * Name of the pattern.
	 *
	 * @var string $name
	 */
	protected $name = '';
	/**
	 * Namespace of the pattern.
	 *
	 * @var string $namespace
	 */
	protected $namespace = '';
	/**
	 * List of block types.
	 *
	 * @var array $block_types
	 */
	protected $block_types = array();
	/**
	 * List of template types.
	 *
	 * @var string[] $template_types
	 */
	protected $template_types = array();
	/**
	 * List of supported post types.
	 *
	 * @var string[] $post_types
	 */
	protected $post_types = array();
	/**
	 * Flag to enable/disable inserter.
	 *
	 * @var bool $inserter
	 */
	protected $inserter = true;
	/**
	 * Source of the pattern.
	 *
	 * @var string $source
	 */
	protected $source = 'plugin';
	/**
	 * List of categories.
	 *
	 * @var array $categories
	 */
	protected $categories = array();
	/**
	 * Viewport width.
	 *
	 * @var int $viewport_width
	 */
	protected $viewport_width = 620;

	/**
	 * Get name of the pattern.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get namespace of the pattern.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Return properties of the pattern.
	 *
	 * @return array
	 */
	public function get_properties(): array {
		return array(
			'title'         => $this->get_title(),
			'content'       => $this->get_content(),
			'description'   => $this->get_description(),
			'categories'    => $this->categories,
			'inserter'      => $this->inserter,
			'blockTypes'    => $this->block_types,
			'templateTypes' => $this->template_types,
			'postTypes'     => $this->post_types,
			'source'        => $this->source,
			'viewportWidth' => $this->viewport_width,
		);
	}

	/**
	 * Get content.
	 *
	 * @return string
	 */
	abstract protected function get_content(): string;

	/**
	 * Get title.
	 *
	 * @return string
	 */
	abstract protected function get_title(): string;

	/**
	 * Get description.
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return '';
	}
}
