<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;

/**
 * This postprocessor replaces <mark> tags with <span> tags because mark tags are not supported across all email clients
 */
class Highlighting_Postprocessor implements Postprocessor {
	/**
	 * Postprocess the HTML.
	 *
	 * @param string $html HTML to postprocess.
	 * @return string
	 */
	public function postprocess( string $html ): string {
		return str_replace(
			array( '<mark', '</mark>' ),
			array( '<span', '</span>' ),
			$html
		);
	}
}
