<?php
/**
 * EmailFont class file
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Email;

/**
 * Helper class for getting fonts for emails.
 *
 * @internal Just for internal use.
 */
class EmailFont {

	/**
	 * Array of font families supported in email templates
	 *
	 * @var string[]
	 */
	public static $font = array(
		'Arial'           => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
		'Comic Sans MS'   => "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
		'Courier New'     => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
		'Georgia'         => "Georgia, Times, 'Times New Roman', serif",
		'Helvetica'       => "'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif",
		'Lucida'          => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
		'Tahoma'          => 'Tahoma, Verdana, Segoe, sans-serif',
		'Times New Roman' => "'Times New Roman', Times, Baskerville, Georgia, serif",
		'Trebuchet MS'    => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
		'Verdana'         => 'Verdana, Geneva, sans-serif',
	);
}
