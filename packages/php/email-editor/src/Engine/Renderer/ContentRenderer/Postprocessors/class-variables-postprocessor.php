<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;

use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * In some case the blocks HTML contains CSS variables.
 * For example when spacing is set from a preset the inline styles contain var(--wp--preset--spacing--10), var(--wp--preset--spacing--20) etc.
 * This postprocessor uses variables from theme.json and replaces the CSS variables with their values in final email HTML.
 */
class Variables_Postprocessor implements Postprocessor {
	/**
	 * Instance of Theme_Controller.
	 *
	 * @var Theme_Controller Theme controller.
	 */
	private Theme_Controller $theme_controller;

	/**
	 * Constructor.
	 *
	 * @param Theme_Controller $theme_controller Theme controller.
	 */
	public function __construct(
		Theme_Controller $theme_controller
	) {
		$this->theme_controller = $theme_controller;
	}

	/**
	 * Postprocess the HTML.
	 *
	 * @param string $html HTML to postprocess.
	 * @return string
	 */
	public function postprocess( string $html ): string {
		$variables    = $this->theme_controller->get_variables_values_map();
		$replacements = array();

		foreach ( $variables as $name => $value ) {
			$var_pattern                  = '/' . preg_quote( 'var(' . $name . ')', '/' ) . '/i';
			$replacements[ $var_pattern ] = $value;
		}

		// Pattern to match style attributes and their values.
		$callback = function ( $matches ) use ( $replacements ) {
			// For each match, replace CSS variables with their values.
			$style = $matches[1];
			$style = preg_replace( array_keys( $replacements ), array_values( $replacements ), $style );
			return 'style="' . esc_attr( $style ) . '"';
		};

		// We want to replace the CSS variables only in the style attributes to avoid replacing the actual content.
		$style_pattern     = '/style="(.*?)"/i';
		$style_pattern_alt = "/style='(.*?)'/i";
		$html              = (string) preg_replace_callback( $style_pattern, $callback, $html );
		$html              = (string) preg_replace_callback( $style_pattern_alt, $callback, $html );

		return $html;
	}
}
