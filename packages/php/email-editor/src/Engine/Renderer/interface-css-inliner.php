<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

interface Css_Inliner {
	/**
	 * Builds a new instance from the given HTML.
	 *
	 * @param string $unprocessed_html raw HTML, must be UTF-encoded, must not be empty.
	 *
	 * @return static
	 */
	public function from_html( string $unprocessed_html ): self;

	/**
	 * Inlines the given CSS into the existing HTML.
	 *
	 * @param string $css the CSS to inline, must be UTF-8-encoded.
	 *
	 * @return $this
	 */
	public function inline_css( string $css = '' ): self;

	/**
	 * Renders the normalized and processed HTML.
	 *
	 * @return string
	 */
	public function render(): string;
}
