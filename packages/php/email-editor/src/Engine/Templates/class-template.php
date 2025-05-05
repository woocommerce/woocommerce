<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\Templates;

/**
 * The class represents a template
 */
class Template {
	/**
	 * Plugin uri used in the template name.
	 *
	 * @var string $plugin_uri
	 */
	private string $plugin_uri;
	/**
	 * The template slug used in the template name.
	 *
	 * @var string $slug
	 */
	private string $slug;
	/**
	 * The template name used for block template registration.
	 *
	 * @var string $name
	 */
	private string $name;
	/**
	 * The template title.
	 *
	 * @var string $title
	 */
	private string $title;
	/**
	 * The template description.
	 *
	 * @var string $description
	 */
	private string $description;
	/**
	 * The template content.
	 *
	 * @var string $content
	 */
	private string $content;
	/**
	 * The list of supoorted post types.
	 *
	 * @var string[]
	 */
	private array $post_types;

	/**
	 * Constructor of the class.
	 *
	 * @param string   $plugin_uri The plugin uri.
	 * @param string   $slug The template slug.
	 * @param string   $title The template title.
	 * @param string   $description The template description.
	 * @param string   $content The template content.
	 * @param string[] $post_types The list of post types supported by the template.
	 */
	public function __construct(
		string $plugin_uri,
		string $slug,
		string $title,
		string $description,
		string $content,
		array $post_types = array()
	) {
		$this->plugin_uri  = $plugin_uri;
		$this->slug        = $slug;
		$this->name        = "{$plugin_uri}//{$slug}"; // The template name is composed from the namespace and the slug.
		$this->title       = $title;
		$this->description = $description;
		$this->content     = $content;
		$this->post_types  = $post_types;
	}
	/**
	 * Get the plugin uri.
	 *
	 * @return string
	 */
	public function get_pluginuri(): string {
		return $this->plugin_uri;
	}
	/**
	 * Get the template slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * Get the template name composed from the plugin_uri and the slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}
	/**
	 * Get the template title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}
	/**
	 * Get the template description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}
	/**
	 * Get the template content.
	 *
	 * @return string
	 */
	public function get_content(): string {
		return $this->content;
	}
	/**
	 * Get the list of supported post types.
	 *
	 * @return string[]
	 */
	public function get_post_types(): array {
		return $this->post_types;
	}
}
