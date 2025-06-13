<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;

/**
 * Interface for postprocessors.
 */
interface Postprocessor {
	/**
	 * Postprocess the HTML.
	 *
	 * @param string $html HTML to postprocess.
	 * @return string
	 */
	public function postprocess( string $html ): string;
}
