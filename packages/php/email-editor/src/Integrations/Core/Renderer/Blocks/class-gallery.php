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
		$gallery_rows = $this->extract_gallery_rows( $parsed_block, $columns, $layout_width );

		// If we don't have any images, return empty.
		if ( empty( $gallery_rows ) ) {
			return '';
		}

		// Build the email-friendly layout.
		return $this->build_email_layout( $gallery_rows, $columns, $parsed_block, $block_content, $rendering_context );
	}

	/**
	 * Extract and crop the images using the same rows that the email will render.
	 *
	 * @param array $parsed_block The parsed block data.
	 * @param int   $columns Number of gallery columns.
	 * @param int   $layout_width Available layout width in px.
	 * @phpstan-param int<1, 8> $columns
	 * @return array Rows of sanitized image HTML strings.
	 */
	private function extract_gallery_rows( array $parsed_block, int $columns, int $layout_width ): array {
		$gallery_attrs        = $parsed_block['attrs'] ?? array();
		$gallery_aspect_ratio = $gallery_attrs['aspectRatio'] ?? null;
		// The block's default crop setting can be omitted from the saved attributes.
		$auto_crop = $columns > 1 && false !== ( $gallery_attrs['imageCrop'] ?? true ) && in_array( $gallery_aspect_ratio, array( null, '', 'auto' ), true );

		$images         = array();
		$attachment_ids = array();
		foreach ( $parsed_block['innerBlocks'] ?? array() as $block ) {
			if ( 'core/image' !== ( $block['blockName'] ?? null ) || ! isset( $block['innerHTML'] ) ) {
				continue;
			}

			$image_html = $this->extract_image_from_html( $block['innerHTML'] );
			if ( '' === $image_html ) {
				continue;
			}

			$image_attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
			$image_ratio = $image_attrs['aspectRatio'] ?? null;
			if ( ! is_string( $image_ratio ) || null === Html_Processing_Helper::parse_aspect_ratio( $image_ratio ) ) {
				$image_ratio = null;
			}

			$images[] = array(
				'html'  => $image_html,
				'attrs' => $image_attrs,
				'ratio' => $image_ratio,
			);
			if ( $auto_crop && null === $image_ratio && is_numeric( $image_attrs['id'] ?? null ) && (int) $image_attrs['id'] > 0 ) {
				$attachment_ids[ count( $images ) - 1 ] = (int) $image_attrs['id'];
			}
		}

		// A trailing single image never needs a size lookup for automatic cropping.
		if ( 1 === count( $images ) % $columns ) {
			unset( $attachment_ids[ count( $images ) - 1 ] );
		}
		if ( ! empty( $attachment_ids ) ) {
			// Prime attachment posts and metadata before looking up image sizes.
			_prime_post_caches( array_unique( $attachment_ids ), false );
		}

		$gallery_ratio = is_string( $gallery_aspect_ratio ) ? $gallery_aspect_ratio : null;
		$gallery_rows  = array();
		foreach ( array_chunk( $images, $columns ) as $row ) {
			// An incomplete row divides the full layout width among the remaining images.
			$cell_width = max( 1, intdiv( $layout_width, count( $row ) ) );
			$row_ratio  = $auto_crop && count( $row ) > 1 ? $this->get_auto_crop_ratio( $row ) : $gallery_ratio;
			$row_html   = array();
			foreach ( $row as $image ) {
				$image_html = $this->apply_aspect_ratio_crop( $image['html'], $image['ratio'] ?? $row_ratio, $cell_width, $image['attrs'] );
				$row_html[] = $this->normalize_image_for_email( $image_html, $cell_width );
			}
			$gallery_rows[] = $row_html;
		}

		return $gallery_rows;
	}

	/**
	 * Match the tallest image in the row at an equal cell width.
	 *
	 * @param array $row Images sharing an email row.
	 * @return string|null Crop ratio, or null when the row does not need an automatic crop.
	 */
	private function get_auto_crop_ratio( array $row ): ?string {
		$ratios = array();
		foreach ( $row as $image ) {
			$html = new \WP_HTML_Tag_Processor( $image['html'] );
			if ( ! $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
				// A caption can survive when its image was rejected.
				continue;
			}

			if ( null !== $image['ratio'] ) {
				$ratio = Html_Processing_Helper::parse_aspect_ratio( $image['ratio'] );
				if ( null !== $ratio && $ratio > 0 && is_finite( $ratio ) ) {
					$ratios[ $image['ratio'] ] = $ratio;
				}
				continue;
			}

			$size = $this->get_gallery_image_size( $image['attrs'], $html );
			if ( null === $size ) {
				return null;
			}

			list( $width, $height )     = $size;
			$ratios[ "$width/$height" ] = $width / $height;
		}

		// Allow for rounding in resized attachments that are effectively the same shape.
		if ( count( $ratios ) < 2 || max( $ratios ) <= min( $ratios ) * 1.01 ) {
			return null;
		}

		return (string) array_search( min( $ratios ), $ratios, true );
	}

	/**
	 * Get the dimensions of the selected attachment size or the saved image tag.
	 *
	 * @param array                  $attrs Parsed image block attributes.
	 * @param \WP_HTML_Tag_Processor $html Processor positioned on the sanitized image tag.
	 * @return array{int, int}|null Image width and height if known.
	 */
	private function get_gallery_image_size( array $attrs, \WP_HTML_Tag_Processor $html ): ?array {
		$src = $html->get_attribute( 'src' );
		if ( is_string( $src ) && isset( $attrs['id'] ) && is_numeric( $attrs['id'] ) && (int) $attrs['id'] > 0 ) {
			$slug = isset( $attrs['sizeSlug'] ) && is_string( $attrs['sizeSlug'] ) ? $attrs['sizeSlug'] : 'full';
			$size = $this->get_attachment_size_for_src( (int) $attrs['id'], $slug, $src );
			if ( null !== $size ) {
				return $size;
			}
		}

		$width  = $this->get_positive_dimension( $html->get_attribute( 'width' ) );
		$height = $this->get_positive_dimension( $html->get_attribute( 'height' ) );
		return $width > 0 && $height > 0 ? array( $width, $height ) : null;
	}

	/**
	 * Get attachment dimensions that describe the image URL saved in the gallery.
	 *
	 * @param int    $id Attachment ID.
	 * @param string $slug Selected image size.
	 * @param string $src Saved image URL.
	 * @return array{int, int}|null Image width and height if known.
	 */
	private function get_attachment_size_for_src( int $id, string $slug, string $src ): ?array {
		// Require stored dimensions so resize limits cannot stand in for unknown image dimensions.
		$metadata = wp_get_attachment_metadata( $id, true );
		$sizes    = $metadata['sizes'] ?? array();
		if ( ! is_array( $sizes ) ) {
			return null;
		}
		$stored = $sizes[ $slug ] ?? array();
		if ( ! is_array( $stored ) ) {
			return null;
		}

		$full_width      = $this->get_positive_dimension( $metadata['width'] ?? null );
		$full_height     = $this->get_positive_dimension( $metadata['height'] ?? null );
		$stored_width    = $this->get_positive_dimension( $stored['width'] ?? null );
		$stored_height   = $this->get_positive_dimension( $stored['height'] ?? null );
		$stored_file     = $stored['file'] ?? null;
		$has_full_size   = $full_width > 0 && $full_height > 0;
		$has_stored_size = is_string( $stored_file ) && '' !== $stored_file && $stored_width > 0 && $stored_height > 0;
		// A partial intermediate record can make WordPress read missing fields during downsizing.
		if ( ! $has_stored_size && ( ! empty( $stored ) || ! $has_full_size ) ) {
			return null;
		}

		$size = wp_get_attachment_image_src( $id, $slug );
		if ( is_array( $size ) && ( $size[0] ?? null ) === $src ) {
			$width  = $this->get_positive_dimension( $size[1] ?? null );
			$height = $this->get_positive_dimension( $size[2] ?? null );
			return $width > 0 && $height > 0 ? array( $width, $height ) : null;
		}

		// The saved URL can refer to the same file before a CDN rewrite or a width-only resize.
		if ( ! preg_match( '/^([^?#]+)(?:\?w=[1-9][0-9]*)?\z/', $src, $matches ) ) {
			return null;
		}
		$saved_url = $matches[1];
		$original  = wp_get_attachment_url( $id );
		if ( ! is_string( $original ) ) {
			return null;
		}
		if ( $has_stored_size && path_join( dirname( $original ), $stored_file ) === $saved_url ) {
			return array( $stored_width, $stored_height );
		}
		return $has_full_size && $original === $saved_url ? array( $full_width, $full_height ) : null;
	}

	/**
	 * Read an integer pixel dimension from image metadata, filters, or markup.
	 *
	 * @param mixed $value Candidate dimension.
	 * @return int Positive dimension, or zero when unknown.
	 */
	private function get_positive_dimension( $value ): int {
		if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
			return max( 0, (int) $value );
		}
		return 0;
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
			$sanitized_img = Html_Processing_Helper::sanitize_image_html( $link_matches[3] );
			if ( '' !== $sanitized_img ) {
				if ( ! empty( $sanitized_url ) ) {
					$result .= '<a href="' . $sanitized_url . '">' . $sanitized_img . '</a>';
				} else {
					// If the URL is invalid, extract just the image without the link.
					$result .= $sanitized_img;
				}
			}
		} elseif ( preg_match( '/<img[^>]*>/', $html_content, $img_matches ) ) {
			// Image is not linked - just extract the img element with sanitization.
			$result = Html_Processing_Helper::sanitize_image_html( $img_matches[0] );
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
	 * Normalize a gallery <img> for email: drop the web-only class and rein in an oversized raw width.
	 *
	 * The block editor stores the intrinsic `width`/`height` of the original file (e.g. `width="2560"`).
	 * Outlook honors that raw width literally — blowing a thumbnail-sized cell wide open. The
	 * core/image renderer avoids this with add_image_dimensions(); the gallery path (which sizes to a
	 * per-cell width rather than the block width) needs the equivalent tailored to its cell model. We
	 * clamp the width down to the cell it renders in, but only when the stored width exceeds it,
	 * scaling any height to keep the aspect ratio. An image with no explicit width is left responsive
	 * (no attribute added), and a width already at or below the cell width is untouched — so the
	 * concrete dimensions the aspect-ratio crop sets for a server-cropped file, and the deliberately
	 * dimensionless CSS-crop fallback, are both preserved.
	 *
	 * The other web-only attributes core emits (`srcset`, `sizes`, `loading`, `decoding`) are already
	 * stripped upstream by {@see Html_Processing_Helper::sanitize_image_html()}, whose allowlist keeps
	 * only src/alt/width/height/class/style; the `class` is the one web-only attribute it preserves, so
	 * that is all we remove here (matching the core/image renderer, which also drops it).
	 *
	 * @param string $img_html Sanitized <img> HTML.
	 * @param int    $cell_width Estimated display width of the gallery cell in px.
	 * @return string The normalized <img> HTML.
	 */
	private function normalize_image_for_email( string $img_html, int $cell_width ): string {
		if ( '' === $img_html ) {
			return $img_html;
		}

		$html = new \WP_HTML_Tag_Processor( $img_html );
		if ( ! $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $img_html;
		}

		// Drop the web-only class the sanitizer preserves (harmless in email, and the core/image
		// renderer strips it too). remove_attribute() is a no-op when the attribute is absent.
		$html->remove_attribute( 'class' );

		// Rein in a raw width wider than the cell it renders in (e.g. a 2560px original in a 20%-wide
		// cell). We only shrink an explicit, oversized width — never add one to an otherwise responsive
		// image, and never touch a width already sized to (or below) the cell — so the aspect-ratio
		// crop's concrete server-crop dimensions are left intact.
		if ( $cell_width > 0 ) {
			$raw_width = $html->get_attribute( 'width' );
			$width     = is_string( $raw_width ) && is_numeric( $raw_width ) ? (int) $raw_width : 0;

			if ( $width > $cell_width ) {
				$raw_height = $html->get_attribute( 'height' );
				$height     = is_string( $raw_height ) && is_numeric( $raw_height ) ? (int) $raw_height : 0;
				if ( $height > 0 ) {
					// Scale the height by the same factor so the image keeps its aspect ratio.
					$scaled_height = max( 1, (int) round( $height * ( $cell_width / $width ) ) );
					$html->set_attribute( 'height', esc_attr( (string) $scaled_height ) );
				}
				$html->set_attribute( 'width', esc_attr( (string) $cell_width ) );
			}
		}

		return $html->get_updated_html();
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
		$ratio_value  = Html_Processing_Helper::parse_aspect_ratio( $aspect_ratio );
		if ( null === $ratio_value ) {
			return $img_html;
		}

		$html = new \WP_HTML_Tag_Processor( $img_html );
		if ( ! $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			return $img_html;
		}

		$width  = $cell_width > 0 ? $cell_width : 0;
		$height = Html_Processing_Helper::get_crop_height( $width, $ratio_value );

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

		// If esc_url() reduces a hostile crop URL to an empty string, this falls through to the CSS branch.
		$cropped_url = Html_Processing_Helper::get_server_cropped_url( $filtered_url, $image_url );

		// These crop styles are appended after Html_Processing_Helper::sanitize_image_html() has run
		// (its style allowlist would otherwise strip object-fit), so they intentionally bypass that
		// sanitizer. Only the regex-validated $aspect_ratio may be interpolated here — every other
		// token is a literal. Do not add dynamic values to $crop_styles without validating them.
		if ( '' !== $cropped_url ) {
			// The file is already cropped to the requested ratio, so we can give it concrete
			// dimensions. This renders the crop correctly even in clients without CSS crop support.
			$html->set_attribute( 'src', $cropped_url );
			if ( $width > 0 && $height > 0 ) {
				$html->set_attribute( 'width', esc_attr( (string) $width ) );
				$html->set_attribute( 'height', esc_attr( (string) $height ) );
			}
			$crop_styles = sprintf( 'aspect-ratio: %s; object-fit: cover; width: 100%%; height: auto; max-width: 100%%; display: block;', $aspect_ratio );
		} else {
			// No server-side crop available: fall back to best-effort CSS cropping. We don't stamp
			// crop dimensions here so the natural image isn't distorted in clients that ignore
			// object-fit. normalize_image_for_email() may still clamp an oversized width downstream,
			// but it scales the height with it, preserving the natural ratio rather than the crop.
			$crop_styles = sprintf( 'aspect-ratio: %s; object-fit: cover; width: 100%%; height: auto; display: block;', $aspect_ratio );
		}

		/** @var string $existing_style */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
		$existing_style = $html->get_attribute( 'style' ) ?? '';
		$existing_style = '' !== $existing_style ? ( rtrim( $existing_style, ';' ) . '; ' ) : '';
		$html->set_attribute( 'style', esc_attr( $existing_style . $crop_styles ) );

		return $html->get_updated_html();
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
	 * @param array             $gallery_rows Rows of image HTML strings.
	 * @param int               $columns Number of gallery columns.
	 * @param array             $parsed_block Full parsed block data.
	 * @param string            $block_content Original block content.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered HTML.
	 */
	private function build_email_layout( array $gallery_rows, int $columns, array $parsed_block, string $block_content, Rendering_Context $rendering_context ): string {
		// Get original wrapper classes from block content.
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'figure', 'class' ) ?? '';

		// Get gallery attributes.
		$block_attrs = $parsed_block['attrs'] ?? array();

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
		//
		// Intentionally omit the `align` attribute. `align="left"` (or "right") on a table renders as
		// `float: left` in email clients, taking the gallery out of normal flow — the block that follows
		// (e.g. a heading) then fails to clear it and butts up against the gallery with no vertical gap.
		// Horizontal alignment is already carried by the `text-align` declaration in $block_styles['css'],
		// so dropping the attribute preserves alignment while keeping the gallery in normal flow.
		$table_attrs = array(
			'class' => 'email-block-gallery ' . Html_Processing_Helper::clean_css_classes( $original_wrapper_classname ),
			'style' => $block_styles['css'],
			'width' => '100%',
		);

		// Add email width to cell attributes if available.
		$cell_attrs = array();
		if ( isset( $parsed_block['email_attrs']['width'] ) ) {
			$cell_attrs['width'] = $parsed_block['email_attrs']['width'];
		}

		// Each row is a separate table with half-em cell padding.
		$gallery_content = '';
		foreach ( $gallery_rows as $row_images ) {
			$gallery_content .= $this->build_gallery_row_table( $row_images, $columns, 8 );
		}

		// Add gallery caption if it exists.
		if ( ! empty( $gallery_caption ) ) {
			$gallery_content .= '<br><div class="blocks-gallery-caption wp-element-caption" style="font-size: 13px; line-height: 1.0; text-align: center;">' . $gallery_caption . '</div>';
		}

		// Use Table_Wrapper_Helper for the main container (following tiled gallery pattern).
		return Table_Wrapper_Helper::render_table_wrapper( $gallery_content, $table_attrs, $cell_attrs );
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
	 * @return int Number of columns (1-8).
	 * @phpstan-return int<1, 8>
	 */
	private function get_columns_from_attributes( array $block_attrs ): int {
		$columns = $block_attrs['columns'] ?? 3;

		// Clamp to the same 1-8 range the core gallery block allows, so the email doesn't
		// silently render fewer columns than the author chose. A lower cap forced wide galleries
		// into extra partial rows whose images then stretched to a size the author never set
		// (e.g. a 6-column gallery clamped to 5 rendered 5 + 5 + 2, ballooning the trailing pair).
		$columns = max( 1, min( 8, (int) $columns ) );

		return $columns;
	}
}
