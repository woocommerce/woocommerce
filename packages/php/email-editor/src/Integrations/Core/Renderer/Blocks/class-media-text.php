<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preset_Variable_Resolver;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;

/**
 * Media-text block renderer.
 * This renderer handles core/media-text blocks with proper email-friendly HTML layout.
 */
class Media_Text extends Abstract_Block_Renderer {
	/**
	 * Padding in pixels on each side of the media cell.
	 */
	private const MEDIA_CELL_PADDING = 10;

	/**
	 * Renders the media-text block content using a direct table-based layout.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$block_attrs  = $parsed_block['attrs'] ?? array();
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		// Extract media content from innerHTML.
		$media_content = $this->extract_media_from_html( $parsed_block['innerHTML'] ?? $block_content );

		// Render all inner blocks content.
		$text_content = '';
		foreach ( $inner_blocks as $block ) {
			$text_content .= render_block( $block );
		}

		// If we don't have both media and text content, return empty.
		if ( empty( $media_content ) || empty( $text_content ) ) {
			return '';
		}

		$media_content = $this->constrain_media_image_width( $media_content, $parsed_block, $rendering_context );

		// Build the email-friendly layout.
		return $this->build_email_layout( $media_content, $text_content, $block_attrs, $block_content, $rendering_context );
	}

	/**
	 * Extract media content from the HTML block content.
	 *
	 * @param string $block_content Raw block content.
	 * @return string Media HTML content or empty string if not found.
	 */
	private function extract_media_from_html( string $block_content ): string {
		// Extract inner content from figure element (removing figure wrapper for email compatibility).
		$media_content = '';
		if ( preg_match( '/<figure[^>]*class="[^"]*\bwp-block-media-text__media\b[^"]*"[^>]*>(.*?)<\/figure>/s', $block_content, $matches ) ) {
			$media_content = trim( $matches[1] );
		}

		return $media_content;
	}

	/**
	 * Build the email-friendly layout for media-text blocks.
	 *
	 * @param string            $media_content Media HTML content.
	 * @param string            $text_content Text content.
	 * @param array             $block_attrs Block attributes.
	 * @param string            $block_content Original block content.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered HTML.
	 */
	private function build_email_layout( string $media_content, string $text_content, array $block_attrs, string $block_content, Rendering_Context $rendering_context ): string {
		// Get original wrapper classes from block content.
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';

		// Get layout attributes.
		$media_position     = $block_attrs['mediaPosition'] ?? $rendering_context->get_start_side();
		$vertical_alignment = $this->get_vertical_alignment_from_attributes( $block_attrs );
		$media_width        = $this->get_media_width_from_attributes( $block_attrs );
		$text_width         = 100 - $media_width; // Text takes the remaining width.

		// Handle image linking for any linkDestination type that has an href.
		if ( ! empty( $block_attrs['href'] ) ) {
			$media_content = $this->wrap_media_with_link( $media_content, $block_attrs['href'] );
		}

		// Get block styles using the Styles_Helper.
		$block_styles = Styles_Helper::get_block_styles( $block_attrs, $rendering_context, array( 'padding', 'border', 'background', 'background-color', 'color' ) );
		$block_styles = Styles_Helper::extend_block_styles(
			$block_styles,
			array(
				'width'           => '100%',
				'border-collapse' => 'collapse',
				'text-align'      => $rendering_context->get_default_text_align(),
			)
		);

		// Apply class and style attributes to the wrapper table.
		//
		// Intentionally omit the `align` attribute. `align="left"` (or "right") on a table renders as
		// `float: left` in email clients, taking the block out of normal flow — the block that follows
		// then fails to clear it and overlaps it, so a following button paints its background across
		// the media & text block above. Horizontal alignment is already carried by the `text-align`
		// declaration in $block_styles['css'], so dropping the attribute preserves alignment while
		// keeping the block in normal flow. Same fix as the gallery wrapper.
		$table_attrs = array(
			'class' => 'email-block-media-text ' . $original_wrapper_classname,
			'style' => $block_styles['css'],
			'width' => '100%',
		);

		// Build individual table cells.
		$media_cell_attrs = array(
			'style'  => sprintf( 'width: %d%%; padding: %dpx; vertical-align: %s;', $media_width, self::MEDIA_CELL_PADDING, $vertical_alignment ),
			'valign' => $vertical_alignment,
		);
		$text_cell_attrs  = array(
			'style'  => sprintf( 'width: %d%%; padding: 0 8%%; vertical-align: %s;', $text_width, $vertical_alignment ),
			'valign' => $vertical_alignment,
		);

		$media_cell = Table_Wrapper_Helper::render_table_cell( $media_content, $media_cell_attrs );
		$text_cell  = Table_Wrapper_Helper::render_table_cell( $text_content, $text_cell_attrs );

		// Order cells based on media position.
		if ( 'right' === $media_position ) {
			// Text first, then media.
			$cells = $text_cell . $media_cell;
		} else {
			// Media first, then text (default left position).
			$cells = $media_cell . $text_cell;
		}

		// Use render_cell = false to avoid wrapping in an extra <td>.
		return Table_Wrapper_Helper::render_table_wrapper( $cells, $table_attrs, array(), array(), false );
	}

	/**
	 * Cap the media image at the width of the media cell.
	 *
	 * WordPress gives the image its full `width` (often 1024px). Outlook lays the image out at
	 * that width and ignores `max-width`, and other clients size the table by it, so the email grows
	 * wider than its layout and Gmail on Android shrinks the whole message to fit the screen.
	 *
	 * @param string            $media_content Media HTML content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Media HTML content.
	 */
	private function constrain_media_image_width( string $media_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
			return $media_content;
		}

		$block_attrs = $parsed_block['attrs'] ?? array();
		$email_width = Styles_Helper::parse_value( $parsed_block['email_attrs']['width'] );
		$block_width = $email_width - $this->get_horizontal_box_width( $block_attrs, $email_width, $rendering_context );
		$max_width   = (int) floor( $block_width * $this->get_media_width_from_attributes( $block_attrs ) / 100 ) - 2 * self::MEDIA_CELL_PADDING;
		if ( $max_width <= 0 ) {
			return $media_content;
		}

		$html = new \WP_HTML_Tag_Processor( $media_content );
		if ( ! $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $media_content;
		}

		$saved_width  = $html->get_attribute( 'width' );
		$saved_height = $html->get_attribute( 'height' );
		if ( null === $saved_width && null === $saved_height ) {
			list( $saved_width, $saved_height ) = $this->get_unsaved_image_dimensions( $html, $block_attrs );
		}
		if ( is_string( $saved_width ) && is_numeric( $saved_width ) ) {
			if ( (float) $saved_width <= $max_width ) {
				return $media_content;
			}
			$html->set_attribute( 'width', $saved_width );
			if ( is_string( $saved_height ) ) {
				$html->set_attribute( 'height', $saved_height );
			}
			Html_Processing_Helper::scale_height_attribute( $html, $max_width );
		} else {
			// The size is unknown, as for an image hosted on another site. Fill the cell as the editor
			// does, and drop the height since there's no width to scale it against.
			$html->remove_attribute( 'height' );
		}
		$html->set_attribute( 'width', (string) $max_width );

		$style = $html->get_attribute( 'style' );
		$style = is_string( $style ) ? rtrim( trim( $style ), ';' ) . ';' : '';
		$html->set_attribute( 'style', $style . sprintf( 'max-width:%dpx;width:100%%;height:auto;', $max_width ) );

		return $html->get_updated_html();
	}

	/**
	 * Find the size of an image saved without `width` and `height`.
	 *
	 * Tries the attachment, then a `w` query parameter, then the file in the uploads directory.
	 * A `w` parameter gives no height.
	 *
	 * @param \WP_HTML_Tag_Processor $html Processor positioned on the image.
	 * @param array                  $block_attrs Block attributes.
	 * @return array{0: ?string, 1: ?string} Width and height, or nulls when unknown.
	 */
	private function get_unsaved_image_dimensions( \WP_HTML_Tag_Processor $html, array $block_attrs ): array {
		$dimensions = $this->get_attachment_image_dimensions( $html, $block_attrs );
		$src        = $html->get_attribute( 'src' );
		if ( null !== $dimensions[0] || ! is_string( $src ) ) {
			return $dimensions;
		}

		$query_width = Html_Processing_Helper::get_width_from_url_query( $src );
		if ( null !== $query_width ) {
			return array( (string) $query_width, null );
		}

		$local_size = Html_Processing_Helper::get_local_image_size( $src );

		return null === $local_size ? array( null, null ) : array( (string) $local_size[0], (string) $local_size[1] );
	}

	/**
	 * Get the dimensions of the attachment the image shows.
	 *
	 * Core saves the media image without `width` and `height`. `wp_filter_content_tags()` adds them
	 * from the attachment metadata, but only after this block has rendered, so look them up the same way.
	 *
	 * @param \WP_HTML_Tag_Processor $html Processor positioned on the image.
	 * @param array                  $block_attrs Block attributes.
	 * @return array{0: ?string, 1: ?string} Width and height, or nulls when unknown.
	 */
	private function get_attachment_image_dimensions( \WP_HTML_Tag_Processor $html, array $block_attrs ): array {
		$src           = $html->get_attribute( 'src' );
		$class         = $html->get_attribute( 'class' );
		$attachment_id = is_numeric( $block_attrs['mediaId'] ?? null ) ? (int) $block_attrs['mediaId'] : 0;
		if ( is_string( $class ) && preg_match( '/\bwp-image-(\d+)\b/', $class, $matches ) ) {
			$attachment_id = (int) $matches[1];
		}
		if ( ! is_string( $src ) || $attachment_id <= 0 ) {
			return array( null, null );
		}

		$metadata   = wp_get_attachment_metadata( $attachment_id );
		$dimensions = is_array( $metadata ) ? wp_image_src_get_dimensions( $src, $metadata, $attachment_id ) : false;
		if ( ! is_array( $dimensions ) ) {
			return array( null, null );
		}

		return array( (string) $dimensions[0], (string) $dimensions[1] );
	}

	/**
	 * Get the block's own left and right padding plus border width, in pixels.
	 *
	 * The block's email width includes these, but they're applied to the wrapper table, so the
	 * cells get what's left. Percentages resolve against the block's email width, as CSS padding does.
	 *
	 * @param array             $block_attrs Block attributes.
	 * @param float             $email_width Block's email width in pixels.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return float
	 */
	private function get_horizontal_box_width( array $block_attrs, float $email_width, Rendering_Context $rendering_context ): float {
		$padding = $block_attrs['style']['spacing']['padding'] ?? array();
		$border  = $block_attrs['style']['border'] ?? array();
		$padding = is_array( $padding ) ? $padding : array();
		$border  = is_array( $border ) ? $border : array();

		$values = array( $padding['left'] ?? '0px', $padding['right'] ?? '0px' );
		foreach ( array( 'left', 'right' ) as $side ) {
			$side_border = $border[ $side ] ?? array();
			$values[]    = ( is_array( $side_border ) ? $side_border['width'] ?? null : null ) ?? $border['width'] ?? '0px';
		}

		$variables_map = null;
		$total         = 0.0;
		foreach ( $values as $value ) {
			if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
				continue;
			}
			$value = (string) $value;
			if ( Preset_Variable_Resolver::is_preset_reference( $value ) ) {
				$variables_map = $variables_map ?? Preset_Variable_Resolver::get_variables_map( $rendering_context->get_theme_json() );
				$value         = Preset_Variable_Resolver::resolve( $value, $variables_map );
			}
			if ( '%' === substr( trim( $value ), -1 ) ) {
				$total += $email_width * Styles_Helper::parse_value( $value ) / 100;
				continue;
			}
			$px     = Styles_Helper::convert_to_px( $value, false );
			$total += Styles_Helper::parse_value( $px ?? $value );
		}

		return $total;
	}

	/**
	 * Get the vertical alignment value from block attributes.
	 *
	 * @param array $block_attrs Block attributes.
	 * @return string CSS vertical-align value.
	 */
	private function get_vertical_alignment_from_attributes( array $block_attrs ): string {
		$vertical_alignment = $block_attrs['verticalAlignment'] ?? 'middle';

		// Convert WordPress alignment values to CSS values.
		switch ( $vertical_alignment ) {
			case 'top':
				return 'top';
			case 'center':
				return 'middle';
			case 'bottom':
				return 'bottom';
			default:
				return 'middle';
		}
	}

	/**
	 * Get the media width value from block attributes.
	 *
	 * @param array $block_attrs Block attributes.
	 * @return int Media width percentage (1-99).
	 */
	private function get_media_width_from_attributes( array $block_attrs ): int {
		$media_width = $block_attrs['mediaWidth'] ?? 50;

		// Ensure the width is within reasonable bounds.
		$media_width = max( 1, min( 99, (int) $media_width ) );

		return $media_width;
	}

	/**
	 * Wrap media content with a link if it's not already wrapped.
	 *
	 * @param string $media_content The media content (inner content from figure element).
	 * @param string $href The URL to link to.
	 * @return string Media content wrapped with link.
	 */
	private function wrap_media_with_link( string $media_content, string $href ): string {
		// If media is already wrapped in a link, return as-is.
		if ( false !== strpos( $media_content, '<a ' ) ) {
			return $media_content;
		}

		// Wrap the media content with a link.
		return '<a href="' . esc_url( $href ) . '">' . $media_content . '</a>';
	}
}
