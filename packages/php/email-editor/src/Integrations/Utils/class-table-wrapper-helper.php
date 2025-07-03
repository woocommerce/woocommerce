<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * Helper class for generating table wrappers for email blocks.
 */
class Table_Wrapper_Helper {

	/**
	 * Default table attributes.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULT_TABLE_ATTRS = array(
		'border'      => '0',
		'cellpadding' => '0',
		'cellspacing' => '0',
		'role'        => 'presentation',
	);

	/**
	 * Render a table cell.
	 *
	 * @param string $content The content to wrap.
	 * @param array  $cell_attrs Cell attributes.
	 * @return string The generated table cell HTML.
	 */
	public static function render_table_cell(
		string $content,
		array $cell_attrs = array()
	): string {
		$cell_attr_string = self::build_attributes_string( $cell_attrs );

		return sprintf(
			'<td%1$s>%2$s</td>',
			$cell_attr_string ? ' ' . $cell_attr_string : '',
			$content
		);
	}

	/**
	 * Render an Outlook-specific table cell using conditional comments.
	 *
	 * @param string $content The content to wrap.
	 * @param array  $cell_attrs Cell attributes.
	 * @return string The generated table cell HTML with Outlook conditionals.
	 */
	public static function render_outlook_table_cell(
		string $content,
		array $cell_attrs = array()
	): string {
		$content_with_outlook_conditional = '<![endif]-->' . $content . '<!--[if mso | IE]>';
		return '<!--[if mso | IE]>' . self::render_table_cell( $content_with_outlook_conditional, $cell_attrs ) . '<![endif]-->';
	}

	/**
	 * Render a table wrapper for email blocks.
	 *
	 * @param string $content The content to wrap (e.g., '{block_content}').
	 * @param array  $table_attrs Table attributes to merge with defaults.
	 * @param array  $cell_attrs Cell attributes.
	 * @param array  $row_attrs Row attributes.
	 * @param bool   $render_cell Whether to render the td wrapper (default true).
	 * @return string The generated table wrapper HTML.
	 */
	public static function render_table_wrapper(
		string $content,
		array $table_attrs = array(),
		array $cell_attrs = array(),
		array $row_attrs = array(),
		bool $render_cell = true
	): string {
		$merged_table_attrs = array_merge( self::DEFAULT_TABLE_ATTRS, $table_attrs );
		$table_attr_string  = self::build_attributes_string( $merged_table_attrs );
		$row_attr_string    = self::build_attributes_string( $row_attrs );

		if ( $render_cell ) {
			$content = self::render_table_cell( $content, $cell_attrs );
		}

		return sprintf(
			'<table%2$s>
		<tbody>
			<tr%3$s>
				%1$s
			</tr>
		</tbody>
	</table>',
			$content,
			$table_attr_string ? ' ' . $table_attr_string : '',
			$row_attr_string ? ' ' . $row_attr_string : ''
		);
	}

	/**
	 * Render an Outlook-specific table wrapper using conditional comments.
	 *
	 * @param string $content The content to wrap (e.g., '{block_content}').
	 * @param array  $table_attrs Table attributes to merge with defaults.
	 * @param array  $cell_attrs Cell attributes.
	 * @param array  $row_attrs Row attributes.
	 * @param bool   $render_cell Whether to render the td wrapper (default true).
	 * @return string The generated table wrapper HTML.
	 */
	public static function render_outlook_table_wrapper(
		string $content,
		array $table_attrs = array(),
		array $cell_attrs = array(),
		array $row_attrs = array(),
		bool $render_cell = true
	): string {
		$content_with_outlook_conditional = '<![endif]-->' . $content . '<!--[if mso | IE]>';
		return '<!--[if mso | IE]>' . self::render_table_wrapper( $content_with_outlook_conditional, $table_attrs, $cell_attrs, $row_attrs, $render_cell ) . '<![endif]-->';
	}

	/**
	 * Build an HTML attributes string from an array.
	 *
	 * @param array<string, string> $attributes The attributes array.
	 * @return string The attributes string.
	 */
	private static function build_attributes_string( array $attributes ): string {
		$attr_parts = array();
		foreach ( $attributes as $key => $value ) {
			if ( '' !== $value ) {
				$attr_parts[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
			}
		}
		return implode( ' ', $attr_parts );
	}
}
