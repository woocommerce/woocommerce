<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;

/**
 * Gallery block renderer.
 * This renderer handles core/gallery blocks with proper email-friendly HTML layout.
 */
class Gallery extends Abstract_Block_Renderer {
	/**
	 * Renders the gallery block content using a table-based layout.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		// Process inner blocks to get gallery images.
		$gallery_images = array();

		foreach ( $inner_blocks as $block ) {
			if ( 'core/image' === $block['blockName'] ) {
				$rendered_image = render_block( $block );
				// Extract image with link information from the original block data.
				$gallery_images[] = $this->extract_image_with_link( $rendered_image, $block );
			}
		}

		// If we don't have any images, return empty.
		if ( empty( $gallery_images ) ) {
			return '';
		}

		// Build the email-friendly layout.
		return $this->build_email_layout( $gallery_images, $parsed_block, $block_content, $rendering_context );
	}

	/**
	 * Extract image with link information from block data.
	 *
	 * @param string $rendered_image The rendered image block HTML.
	 * @param array  $block The original block data.
	 * @return string Image HTML with proper link handling.
	 */
	private function extract_image_with_link( string $rendered_image, array $block ): string {
		// Check if there's a link in the original innerHTML.
		if ( isset( $block['innerHTML'] ) ) {
			$inner_html = $block['innerHTML'];

			// Look for a link around the image in the original HTML.
			if ( preg_match( '/<a[^>]*href=(["\'])(.*?)\1[^>]*>(\s*<img[^>]*>)\s*<\/a>/s', $inner_html, $link_matches ) ) {
				// Validate and sanitize the link URL.
				$sanitized_url = esc_url_raw( $link_matches[2] );
				if ( empty( $sanitized_url ) ) {
					// If URL is invalid, fall back to image without link.
					return $this->remove_figure_wrapper( $rendered_image );
				}

				// Extract the linked image and caption separately.
				$linked_image = '<a href="' . $sanitized_url . '">' . $link_matches[3] . '</a>';

				// Extract caption if it exists.
				$caption = '';
				if ( preg_match( '/<figcaption[^>]*>(.*?)<\/figcaption>/s', $inner_html, $caption_matches ) ) {
					$sanitized_caption = Html_Processing_Helper::sanitize_caption_html( $caption_matches[1] );
					$caption           = '<br><div class="wp-element-caption" style="font-size: 13px; line-height: 1.0;">' . $sanitized_caption . '</div>';
				}

				return $linked_image . $caption;
			}
		}

		// Fallback to the original method if no link is found.
		return $this->remove_figure_wrapper( $rendered_image );
	}

	/**
	 * Remove figure wrapper from rendered image block for email compatibility.
	 *
	 * @param string $rendered_image Rendered image HTML.
	 * @return string Image HTML without figure wrapper.
	 */
	private function remove_figure_wrapper( string $rendered_image ): string {
		// Extract the img element and caption, preserving any links around the image.
		$result = '';

		// Check if the image is wrapped in a link.
		if ( preg_match( '/<a[^>]*href="([^"]*)"[^>]*>(<img[^>]*>)<\/a>/', $rendered_image, $link_matches ) ) {
			// Image is linked - validate and sanitize the link URL.
			$sanitized_href = esc_url_raw( $link_matches[1] );
			if ( ! empty( $sanitized_href ) ) {
				$sanitized_img = wp_kses_post( $link_matches[2] );
				$result       .= '<a href="' . $sanitized_href . '">' . $sanitized_img . '</a>';
			} else {
				// If URL is invalid, extract just the image without link.
				$result .= wp_kses_post( $link_matches[2] );
			}
		} elseif ( preg_match( '/<img[^>]*>/', $rendered_image, $img_matches ) ) {
			// Image is not linked - just extract the img element with sanitization.
			$result .= wp_kses_post( $img_matches[0] );
		}

		// Extract the caption if it exists (handle both figcaption and span formats).
		if ( preg_match( '/<figcaption[^>]*>(.*?)<\/figcaption>/s', $rendered_image, $caption_matches ) ) {
			$sanitized_caption = Html_Processing_Helper::sanitize_caption_html( $caption_matches[1] );
			$result           .= '<br><div class="wp-element-caption" style="font-size: 13px; line-height: 1.0;">' . $sanitized_caption . '</div>';
		} elseif ( preg_match( '/<span class="wp-element-caption"[^>]*>(.*?)<\/span>/s', $rendered_image, $caption_matches ) ) {
			$sanitized_caption = Html_Processing_Helper::sanitize_caption_html( $caption_matches[1] );
			$result           .= '<br><div class="wp-element-caption" style="font-size: 13px; line-height: 1.0;">' . $sanitized_caption . '</div>';
		}

		return $result;
	}

	/**
	 * Extract gallery-level caption from the original block content.
	 *
	 * @param string $block_content Original block content.
	 * @return string Gallery caption or empty string if not found.
	 */
	private function extract_gallery_caption( string $block_content ): string {
		// Look for gallery-level caption: <figcaption class="blocks-gallery-caption wp-element-caption">.
		if ( preg_match( '/<figcaption class="blocks-gallery-caption[^"]*"[^>]*>(.*?)<\/figcaption>/s', $block_content, $matches ) ) {
			return Html_Processing_Helper::sanitize_caption_html( trim( $matches[1] ) );
		}

		return '';
	}

	/**
	 * Build the email-friendly layout for gallery blocks.
	 *
	 * @param array             $gallery_images Array of image HTML strings.
	 * @param array             $parsed_block Full parsed block data.
	 * @param string            $block_content Original block content.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered HTML.
	 */
	private function build_email_layout( array $gallery_images, array $parsed_block, string $block_content, Rendering_Context $rendering_context ): string {
		// Get original wrapper classes from block content.
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'figure', 'class' ) ?? '';

		// Get gallery attributes.
		$block_attrs = $parsed_block['attrs'] ?? array();
		$columns     = $this->get_columns_from_attributes( $block_attrs );

		// Extract gallery-level caption from the original block content.
		$gallery_caption = $this->extract_gallery_caption( $block_content );

		// Get block styles using the Styles_Helper.
		$block_styles = Styles_Helper::get_block_styles( $block_attrs, $rendering_context, array( 'padding', 'border', 'background', 'background-color', 'color' ) );
		$block_styles = Styles_Helper::extend_block_styles(
			$block_styles,
			array(
				'width'           => '100%',
				'border-collapse' => 'collapse',
				'text-align'      => 'left',
			)
		);

		// Apply class and style attributes to the wrapper table.
		$table_attrs = array(
			'class' => 'email-block-gallery ' . Html_Processing_Helper::clean_css_classes( $original_wrapper_classname ),
			'style' => $block_styles['css'],
			'align' => 'left',
			'width' => '100%',
		);

		// Add email width to cell attributes if available.
		$cell_attrs = array();
		if ( isset( $parsed_block['email_attrs']['width'] ) ) {
			$cell_attrs['width'] = $parsed_block['email_attrs']['width'];
		}

		// Build the gallery rows with proper table structure.
		$gallery_content = $this->build_gallery_table( $gallery_images, $columns );

		// Add gallery caption if it exists.
		if ( ! empty( $gallery_caption ) ) {
			$gallery_content .= '<br><div class="blocks-gallery-caption wp-element-caption" style="font-size: 13px; line-height: 1.0; text-align: center;">' . $gallery_caption . '</div>';
		}

		// Use Table_Wrapper_Helper for the main container (following tiled gallery pattern).
		return Table_Wrapper_Helper::render_table_wrapper( $gallery_content, $table_attrs, $cell_attrs );
	}

	/**
	 * Build the gallery table structure with proper rows and cells.
	 * Uses the tiled gallery pattern: separate tables for each row, then wrap in main table.
	 *
	 * @param array $gallery_images Array of image HTML strings.
	 * @param int   $columns Number of columns.
	 * @return string Gallery table HTML.
	 */
	private function build_gallery_table( array $gallery_images, int $columns ): string {
		$content_parts = array();
		$image_count   = count( $gallery_images );
		$cell_padding  = 8; // 0.5em equivalent (approximately 8px)

		// Process images in chunks based on columns to create rows.
		for ( $i = 0; $i < $image_count; $i += $columns ) {
			$row_images      = array_slice( $gallery_images, $i, $columns );
			$content_parts[] = $this->build_gallery_row_table( $row_images, $columns, $cell_padding );
		}

		return implode( '', $content_parts );
	}

	/**
	 * Build a single gallery row as a separate table (following tiled gallery pattern).
	 *
	 * @param array $row_images Images for this row.
	 * @param int   $total_columns Total number of columns.
	 * @param int   $cell_padding Cell padding.
	 * @return string Row table HTML.
	 */
	private function build_gallery_row_table( array $row_images, int $total_columns, int $cell_padding ): string {
		$images_in_row = count( $row_images );
		$row_cells     = '';

		// If there is exactly one image, span full width; otherwise distribute width evenly across the images in this row.
		if ( 1 === $images_in_row ) {
			$cell_attrs = array(
				'style'   => sprintf( 'width: %s; padding: %dpx; vertical-align: top; text-align: center;', Html_Processing_Helper::sanitize_css_value( '100%' ), $cell_padding ),
				'valign'  => 'top',
				'colspan' => $total_columns,
			);
			$row_cells .= Table_Wrapper_Helper::render_table_cell( $row_images[0], $cell_attrs );
		} else {
			// Evenly distribute available width among the images in this row.
			$cell_width_percent = 100 / $images_in_row;

			foreach ( $row_images as $image_html ) {
				$cell_attrs = array(
					'style'  => sprintf(
						'width: %s; padding: %dpx; vertical-align: top; text-align: center;',
						Html_Processing_Helper::sanitize_css_value( sprintf( '%.2f%%', $cell_width_percent ) ),
						$cell_padding
					),
					'valign' => 'top',
				);
				$row_cells .= Table_Wrapper_Helper::render_table_cell( $image_html, $cell_attrs );
			}
		}

		// Create a separate table for this row (following tiled gallery pattern).
		return sprintf(
			'<table role="presentation" style="width: %s; border-collapse: collapse; table-layout: fixed;"><tr>%s</tr></table>',
			Html_Processing_Helper::sanitize_css_value( '100%' ),
			$row_cells
		);
	}

	/**
	 * Get the columns value from block attributes.
	 *
	 * @param array $block_attrs Block attributes.
	 * @return int Number of columns (1-5).
	 */
	private function get_columns_from_attributes( array $block_attrs ): int {
		$columns = $block_attrs['columns'] ?? 3;

		// Ensure the columns are within reasonable bounds.
		$columns = max( 1, min( 5, (int) $columns ) );

		return $columns;
	}
}
