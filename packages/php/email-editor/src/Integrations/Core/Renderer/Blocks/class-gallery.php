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
		// Extract images directly from the block content (more efficient than re-rendering).
		$gallery_images = $this->extract_images_from_gallery_content( $block_content, $parsed_block );

		// If we don't have any images, return empty.
		if ( empty( $gallery_images ) ) {
			return '';
		}

		// Build the email-friendly layout.
		return $this->build_email_layout( $gallery_images, $parsed_block, $block_content, $rendering_context );
	}

	/**
	 * Extract all images from gallery content with their links and captions.
	 *
	 * @param string $block_content The rendered gallery block HTML.
	 * @param array  $parsed_block The parsed block data.
	 * @return array Array of sanitized image HTML strings.
	 */
	private function extract_images_from_gallery_content( string $block_content, array $parsed_block ): array {
		$gallery_images = array();
		$inner_blocks   = $parsed_block['innerBlocks'] ?? array();

		// Extract images from inner blocks data where the actual image HTML is stored.
		foreach ( $inner_blocks as $block ) {
			if ( 'core/image' === $block['blockName'] && isset( $block['innerHTML'] ) ) {
				$extracted_image = $this->extract_image_from_html( $block['innerHTML'] );
				if ( ! empty( $extracted_image ) ) {
					$gallery_images[] = $extracted_image;
				}
			}
		}

		return $gallery_images;
	}

	/**
	 * Extract and sanitize image with optional link and caption from HTML content.
	 * This is the unified method that handles all image extraction scenarios.
	 *
	 * @param string $html_content HTML content containing the image.
	 * @return string Sanitized image HTML with proper link and caption handling.
	 */
	private function extract_image_from_html( string $html_content ): string {
		$result = '';

		// First, try to find a linked image (most common case).
		if ( preg_match( '/<a[^>]*href=(["\'])(.*?)\1[^>]*>(\s*<img[^>]*>)\s*<\/a>/s', $html_content, $link_matches ) ) {
			// Validate and sanitize the link URL.
			$sanitized_url = esc_url( $link_matches[2] );
			if ( ! empty( $sanitized_url ) ) {
				$sanitized_img = Html_Processing_Helper::sanitize_image_html( $link_matches[3] );
				if ( '' !== $sanitized_img ) {
					$result .= '<a href="' . $sanitized_url . '">' . $sanitized_img . '</a>';
				}
			} else {
				// If URL is invalid, extract just the image without link.
				$sanitized_img = Html_Processing_Helper::sanitize_image_html( $link_matches[3] );
				if ( '' !== $sanitized_img ) {
					$result .= $sanitized_img;
				}
			}
		} elseif ( preg_match( '/<img[^>]*>/', $html_content, $img_matches ) ) {
			// Image is not linked - just extract the img element with sanitization.
			$sanitized_img = Html_Processing_Helper::sanitize_image_html( $img_matches[0] );
			if ( '' !== $sanitized_img ) {
				$result .= $sanitized_img;
			}
		}

		// Extract the caption if it exists (handle both figcaption and span formats).
		// Enhanced security: validate container attributes before extracting content.
		if ( preg_match( '/(<figcaption[^>]*>)(.*?)(<\/figcaption>)/s', $html_content, $caption_matches ) ) {
			// Validate the figcaption container attributes for security.
			if ( Html_Processing_Helper::validate_container_attributes( $caption_matches[1] . $caption_matches[3] ) ) {
				$sanitized_caption = Html_Processing_Helper::sanitize_caption_html( $caption_matches[2] );
				$result           .= '<br><div class="wp-element-caption" style="font-size: 13px; line-height: 1.0;">' . $sanitized_caption . '</div>';
			}
		} elseif ( preg_match( '/(<span class="wp-element-caption"[^>]*>)(.*?)(<\/span>)/s', $html_content, $caption_matches ) ) {
			// Validate the span container attributes for security.
			if ( Html_Processing_Helper::validate_container_attributes( $caption_matches[1] . $caption_matches[3] ) ) {
				$sanitized_caption = Html_Processing_Helper::sanitize_caption_html( $caption_matches[2] );
				$result           .= '<br><div class="wp-element-caption" style="font-size: 13px; line-height: 1.0;">' . $sanitized_caption . '</div>';
			}
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
		// Enhanced security: validate container attributes before extracting content.
		if ( preg_match( '/(<figcaption class="blocks-gallery-caption[^"]*"[^>]*>)(.*?)(<\/figcaption>)/s', $block_content, $matches ) ) {
			// Validate the figcaption container attributes for security.
			if ( Html_Processing_Helper::validate_container_attributes( $matches[1] . $matches[3] ) ) {
				return Html_Processing_Helper::sanitize_caption_html( trim( $matches[2] ) );
			}
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
