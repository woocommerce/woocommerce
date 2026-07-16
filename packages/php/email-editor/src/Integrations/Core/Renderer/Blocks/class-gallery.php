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
		// The number of columns and the available layout width determine how wide each cropped image
		// is displayed. We pass that per-image width to the crop so an image CDN can serve an
		// appropriately sized (and cropped) file.
		$columns      = $this->get_columns_from_attributes( $parsed_block['attrs'] ?? array() );
		$layout_width = (int) Styles_Helper::parse_value( $rendering_context->get_layout_width_without_padding() );

		// Extract images directly from the block content (more efficient than re-rendering).
		$gallery_images = $this->extract_images_from_gallery_content( $block_content, $parsed_block, $columns, $layout_width );

		// If we don't have any images, return empty.
		if ( empty( $gallery_images ) ) {
			return '';
		}

		// Build the email-friendly layout.
		return $this->build_email_layout( $gallery_images, $parsed_block, $block_content, $rendering_context );
	}

	/**
	 * Estimate the rendered width (in px) of the gallery cell that holds a given image.
	 *
	 * The gallery packs images into rows of `$columns`. A complete row splits the layout width
	 * evenly, but an incomplete final row is distributed across only its remaining images — a lone
	 * trailing image spans the full width (see {@see build_gallery_row_table()}). Sizing the crop to
	 * the actual cell keeps an image CDN from serving an undersized file for such images.
	 *
	 * @param int $index Zero-based index of the image among the rendered images.
	 * @param int $image_count Total number of rendered images.
	 * @param int $columns Number of gallery columns.
	 * @param int $layout_width Available layout width in px.
	 * @return int Cell width in px (at least 1).
	 */
	private function get_cell_width( int $index, int $image_count, int $columns, int $layout_width ): int {
		$columns       = max( 1, $columns );
		$row_start     = intdiv( $index, $columns ) * $columns;
		$images_in_row = max( 1, min( $columns, $image_count - $row_start ) );

		return (int) max( 1, floor( $layout_width / $images_in_row ) );
	}

	/**
	 * Extract all images from gallery content with their links and captions.
	 *
	 * @param string $block_content The rendered gallery block HTML.
	 * @param array  $parsed_block The parsed block data.
	 * @param int    $columns Number of gallery columns.
	 * @param int    $layout_width Available layout width in px.
	 * @return array Array of sanitized image HTML strings.
	 */
	private function extract_images_from_gallery_content( string $block_content, array $parsed_block, int $columns, int $layout_width ): array {
		$gallery_images = array();
		$inner_blocks   = $parsed_block['innerBlocks'] ?? array();

		// The gallery can request a crop (aspect ratio) for all of its images. Individual images
		// may override it with their own aspectRatio attribute. Guard against malformed input so a
		// non-string ratio can't throw a TypeError and abort rendering.
		$gallery_attrs        = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : array();
		$gallery_aspect_ratio = isset( $gallery_attrs['aspectRatio'] ) && is_string( $gallery_attrs['aspectRatio'] ) ? $gallery_attrs['aspectRatio'] : null;

		// Collect the image blocks first so we know how many land in each rendered row and can size
		// each crop to its actual cell (incomplete final rows are wider — see get_cell_width()).
		$image_blocks = array();
		foreach ( $inner_blocks as $block ) {
			if ( 'core/image' === $block['blockName'] && isset( $block['innerHTML'] ) ) {
				$image_blocks[] = $block;
			}
		}
		$image_count = count( $image_blocks );

		foreach ( $image_blocks as $index => $block ) {
			$image_attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

			// Use the per-image override only when it is a valid ratio; a malformed override falls
			// back to the gallery ratio instead of silently disabling the crop for that image.
			$image_ratio  = isset( $image_attrs['aspectRatio'] ) && is_string( $image_attrs['aspectRatio'] ) ? $image_attrs['aspectRatio'] : null;
			$aspect_ratio = ( null !== $image_ratio && null !== $this->parse_aspect_ratio( $image_ratio ) ) ? $image_ratio : $gallery_aspect_ratio;

			$cell_width      = $this->get_cell_width( $index, $image_count, $columns, $layout_width );
			$extracted_image = $this->extract_image_from_html( $block['innerHTML'], $aspect_ratio, $cell_width, $image_attrs );
			if ( ! empty( $extracted_image ) ) {
				$gallery_images[] = $extracted_image;
			}
		}

		return $gallery_images;
	}

	/**
	 * Extract and sanitize image with optional link and caption from HTML content.
	 * This is the unified method that handles all image extraction scenarios.
	 *
	 * @param string      $html_content HTML content containing the image.
	 * @param string|null $aspect_ratio Optional aspect ratio (e.g. "1" or "4/3") to crop the image to.
	 * @param int         $cell_width Estimated display width of the gallery cell in px.
	 * @param array       $image_attrs Parsed attributes of the core/image block (id, sizeSlug, ...).
	 * @return string Sanitized image HTML with proper link and caption handling.
	 */
	private function extract_image_from_html( string $html_content, ?string $aspect_ratio = null, int $cell_width = 0, array $image_attrs = array() ): string {
		$result = '';

		// First, try to find a linked image (most common case).
		if ( preg_match( '/<a[^>]*href=(["\'])(.*?)\1[^>]*>(\s*<img[^>]*>)\s*<\/a>/s', $html_content, $link_matches ) ) {
			// Validate and sanitize the link URL.
			$sanitized_url = esc_url( $link_matches[2] );
			if ( ! empty( $sanitized_url ) ) {
				$sanitized_img = $this->apply_aspect_ratio_crop( Html_Processing_Helper::sanitize_image_html( $link_matches[3] ), $aspect_ratio, $cell_width, $image_attrs );
				if ( '' !== $sanitized_img ) {
					$result .= '<a href="' . $sanitized_url . '">' . $sanitized_img . '</a>';
				}
			} else {
				// If URL is invalid, extract just the image without link.
				$sanitized_img = $this->apply_aspect_ratio_crop( Html_Processing_Helper::sanitize_image_html( $link_matches[3] ), $aspect_ratio, $cell_width, $image_attrs );
				if ( '' !== $sanitized_img ) {
					$result .= $sanitized_img;
				}
			}
		} elseif ( preg_match( '/<img[^>]*>/', $html_content, $img_matches ) ) {
			// Image is not linked - just extract the img element with sanitization.
			$sanitized_img = $this->apply_aspect_ratio_crop( Html_Processing_Helper::sanitize_image_html( $img_matches[0] ), $aspect_ratio, $cell_width, $image_attrs );
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
	 * Apply an aspect-ratio crop to a sanitized <img> tag.
	 *
	 * Email clients can't crop client-side reliably (`object-fit`/`aspect-ratio` are unsupported in
	 * Gmail), so the only way to truly honor the crop everywhere is to serve an already-cropped image
	 * file. This method exposes the {@see 'woocommerce_email_editor_gallery_cropped_image_url'} filter
	 * so integrations (e.g. Jetpack/Photon on WordPress.com) can rewrite the image URL to a
	 * server-cropped version. When that happens, the image is given concrete `width`/`height`
	 * dimensions so it renders correctly even in clients without CSS crop support.
	 *
	 * When no integration crops the URL (e.g. self-hosted sites with no image CDN), the method falls
	 * back to inline `aspect-ratio` + `object-fit: cover` CSS. Clients that support it (Apple Mail,
	 * iOS Mail, modern webmail) render the crop; the rest fall back to the natural aspect ratio,
	 * matching the previous behavior with no regression.
	 *
	 * @param string      $img_html Sanitized <img> HTML.
	 * @param string|null $aspect_ratio Aspect ratio to apply (e.g. "1" or "4/3").
	 * @param int         $cell_width Estimated display width of the gallery cell in px.
	 * @param array       $image_attrs Parsed attributes of the core/image block (id, sizeSlug, ...).
	 * @return string Image HTML with the crop applied, or the input unchanged when no valid ratio.
	 */
	private function apply_aspect_ratio_crop( string $img_html, ?string $aspect_ratio, int $cell_width = 0, array $image_attrs = array() ): string {
		if ( null === $aspect_ratio || '' === $img_html ) {
			return $img_html;
		}

		// Only accept simple numeric ratios such as "1", "1.5" or "4/3" to avoid injecting anything unexpected.
		$aspect_ratio = trim( $aspect_ratio );
		$ratio_value  = $this->parse_aspect_ratio( $aspect_ratio );
		if ( null === $ratio_value ) {
			return $img_html;
		}

		$html = new \WP_HTML_Tag_Processor( $img_html );
		if ( ! $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $img_html;
		}

		// Derive the target display dimensions from the cell width and the requested ratio. Clamp the
		// height to at least 1px so a very wide ratio can't round down to a 0-height (unusable) crop.
		$width  = $cell_width > 0 ? $cell_width : 0;
		$height = $width > 0 ? max( 1, (int) round( $width / $ratio_value ) ) : 0;

		// get_attribute() can return a string, null (absent), or bool true (valueless attribute);
		// coerce anything that isn't a real URL string to an empty string.
		$src_attribute = $html->get_attribute( 'src' );
		$image_url     = is_string( $src_attribute ) ? $src_attribute : '';

		/**
		 * Filters the URL of an image inside an email gallery so integrations can serve a
		 * server-side-cropped file that honors the block's aspect ratio.
		 *
		 * Email can't crop client-side reliably, so returning an already-cropped URL (e.g. an image
		 * CDN URL with resize/crop parameters) is the only way to honor the crop in every client.
		 * Return the URL unchanged to leave the image uncropped (the renderer then falls back to
		 * best-effort CSS cropping).
		 *
		 * @param string $image_url    The original image URL.
		 * @param string $aspect_ratio The requested aspect ratio (e.g. "1", "4/3").
		 * @param int    $width        Target display width of the image in px (0 if unknown).
		 * @param int    $height       Target display height derived from the aspect ratio in px (0 if unknown).
		 * @param array  $image_attrs  Parsed attributes of the core/image block (id, sizeSlug, ...).
		 */
		$filtered_url = apply_filters( 'woocommerce_email_editor_gallery_cropped_image_url', $image_url, $aspect_ratio, $width, $height, $image_attrs );

		// Extensions can return anything (arrays, WP_Error, objects). Only accept a string, and
		// sanitize it before we compare or use it so an invalid value can't emit a warning, be
		// misclassified as a server crop, or become an empty src.
		$cropped_url = is_string( $filtered_url ) ? esc_url( $filtered_url ) : '';

		$is_server_cropped = '' !== $image_url && '' !== $cropped_url && $cropped_url !== $image_url;

		// These crop styles are appended after Html_Processing_Helper::sanitize_image_html() has run
		// (its style allowlist would otherwise strip object-fit), so they intentionally bypass that
		// sanitizer. Only the regex-validated $aspect_ratio may be interpolated here — every other
		// token is a literal. Do not add dynamic values to $crop_styles without validating them.
		if ( $is_server_cropped ) {
			// The file is already cropped to the requested ratio, so we can give it concrete
			// dimensions. This renders the crop correctly even in clients without CSS crop support.
			$html->set_attribute( 'src', $cropped_url );
			if ( $width > 0 && $height > 0 ) {
				$html->set_attribute( 'width', esc_attr( (string) $width ) );
				$html->set_attribute( 'height', esc_attr( (string) $height ) );
			}
			$crop_styles = sprintf( 'aspect-ratio: %s; object-fit: cover; width: 100%%; height: auto; max-width: 100%%; display: block;', $aspect_ratio );
		} else {
			// No server-side crop available: fall back to best-effort CSS cropping. We avoid concrete
			// dimensions here so the natural image isn't distorted in clients that ignore object-fit.
			$crop_styles = sprintf( 'aspect-ratio: %s; object-fit: cover; width: 100%%; height: auto; display: block;', $aspect_ratio );
		}

		/** @var string $existing_style */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
		$existing_style = $html->get_attribute( 'style' ) ?? '';
		$existing_style = '' !== $existing_style ? ( rtrim( $existing_style, ';' ) . '; ' ) : '';
		$html->set_attribute( 'style', esc_attr( $existing_style . $crop_styles ) );

		return $html->get_updated_html();
	}

	/**
	 * Parse an aspect ratio attribute value (e.g. "1", "1.5", "4/3") into a numeric width/height ratio.
	 *
	 * @param string $aspect_ratio Aspect ratio value.
	 * @return float|null The ratio (width divided by height), or null when the value is invalid.
	 */
	private function parse_aspect_ratio( string $aspect_ratio ): ?float {
		// Only accept simple numeric ratios to avoid injecting anything unexpected into the markup.
		if ( ! preg_match( '/^(\d+(?:\.\d+)?)(?:\s*\/\s*(\d+(?:\.\d+)?))?$/', trim( $aspect_ratio ), $matches ) ) {
			return null;
		}

		$numerator   = (float) $matches[1];
		$denominator = isset( $matches[2] ) ? (float) $matches[2] : 1.0;

		if ( $numerator <= 0 || $denominator <= 0 ) {
			return null;
		}

		return $numerator / $denominator;
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
				'text-align'      => $rendering_context->get_default_text_align(),
			)
		);

		// Apply class and style attributes to the wrapper table.
		$table_attrs = array(
			'class' => 'email-block-gallery ' . Html_Processing_Helper::clean_css_classes( $original_wrapper_classname ),
			'style' => $block_styles['css'],
			'align' => $rendering_context->get_default_text_align(),
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
