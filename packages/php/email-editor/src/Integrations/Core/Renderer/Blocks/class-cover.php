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
 * Cover block renderer.
 * This renderer handles core/cover blocks with proper email-friendly HTML layout.
 */
class Cover extends Abstract_Block_Renderer {
	/**
	 * Renders the cover block content using a table-based layout for email compatibility.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$block_attrs  = $parsed_block['attrs'] ?? array();
		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		// Render all inner blocks content.
		$inner_content = '';
		foreach ( $inner_blocks as $block ) {
			$inner_content .= render_block( $block );
		}

		// If we don't have inner content, return empty.
		if ( empty( $inner_content ) ) {
			return '';
		}

		// Build the email-friendly layout.
		$background_image = $this->extract_background_image( $block_attrs, $parsed_block['innerHTML'] ?? $block_content );
		return $this->build_email_layout( $inner_content, $block_attrs, $block_content, $background_image, $rendering_context );
	}

	/**
	 * Build the email-friendly layout for cover blocks.
	 *
	 * @param string            $inner_content Inner content.
	 * @param array             $block_attrs Block attributes.
	 * @param string            $block_content Original block content.
	 * @param string            $background_image Background image URL.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Rendered HTML.
	 */
	private function build_email_layout( string $inner_content, array $block_attrs, string $block_content, string $background_image, Rendering_Context $rendering_context ): string {
		// Get original wrapper classes from block content.
		$original_wrapper_classname = ( new Dom_Document_Helper( $block_content ) )->get_attribute_value_by_tag_name( 'div', 'class' ) ?? '';

		// Get background color information.
		$background_color = $this->get_background_color( $block_attrs, $rendering_context );

		// Get block styles using the Styles_Helper.
		$block_styles   = Styles_Helper::get_block_styles( $block_attrs, $rendering_context, array( 'padding', 'border', 'background-color' ) );
		$default_styles = array(
			'width'           => '100%',
			'border-collapse' => 'collapse',
			'text-align'      => 'center',
		);

		// Add minimum height (use specified value or default).
		$min_height                   = $this->get_minimum_height( $block_attrs );
		$default_styles['min-height'] = ! empty( $min_height ) ? $min_height : '430px';

		$block_styles = Styles_Helper::extend_block_styles(
			$block_styles,
			$default_styles
		);

		// Add background image to table styles if present.
		if ( ! empty( $background_image ) ) {
			$block_styles = Styles_Helper::extend_block_styles(
				$block_styles,
				array(
					'background-image'    => 'url("' . esc_url( $background_image ) . '")',
					'background-size'     => 'cover',
					'background-position' => 'center',
					'background-repeat'   => 'no-repeat',
				)
			);
		} elseif ( ! empty( $background_color ) ) {
			// If no background image but there's a background color, use it.
			$block_styles = Styles_Helper::extend_block_styles(
				$block_styles,
				array(
					'background-color' => $background_color,
				)
			);
		}

		// Apply class and style attributes to the wrapper table.
		$table_attrs = array(
			'class' => 'email-block-cover ' . esc_attr( $original_wrapper_classname ),
			'style' => $block_styles['css'],
			'align' => 'center',
			'width' => '100%',
		);

		// Build the cover content without background (background is now on the table).
		$cover_content = $this->build_cover_content( $inner_content );

		// Build individual table cell.
		$cell_attrs = array(
			'valign' => 'middle',
			'align'  => 'center',
		);

		$cell = Table_Wrapper_Helper::render_table_cell( $cover_content, $cell_attrs );

		// Use render_cell = false to avoid wrapping in an extra <td>.
		return Table_Wrapper_Helper::render_table_wrapper( $cell, $table_attrs, array(), array(), false );
	}

	/**
	 * Extract background image from block attributes or HTML content.
	 *
	 * @param array  $block_attrs Block attributes.
	 * @param string $block_content Original block content.
	 * @return string Background image URL or empty string.
	 */
	private function extract_background_image( array $block_attrs, string $block_content ): string {
		// First check block attributes for URL.
		if ( ! empty( $block_attrs['url'] ) ) {
			return esc_url( $block_attrs['url'] );
		}

		// Fallback: use HTML API to find background image src.
		$html = new \WP_HTML_Tag_Processor( $block_content );

		while ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			$class_attr = $html->get_attribute( 'class' );
			// Check if this img tag has the wp-block-cover__image-background class.
			if ( is_string( $class_attr ) && false !== strpos( $class_attr, 'wp-block-cover__image-background' ) ) {
				$src = $html->get_attribute( 'src' );
				if ( is_string( $src ) ) {
					return esc_url( $src );
				}
			}
		}

		return '';
	}

	/**
	 * Get minimum height from block attributes.
	 *
	 * @param array $block_attrs Block attributes.
	 * @return string Minimum height value or empty string.
	 */
	private function get_minimum_height( array $block_attrs ): string {
		// Check for minHeight attribute (legacy format).
		if ( ! empty( $block_attrs['minHeight'] ) ) {
			return Html_Processing_Helper::sanitize_dimension_value( $block_attrs['minHeight'] );
		}

		// Check for style.dimensions.minHeight (WordPress 6.2+ format).
		if ( ! empty( $block_attrs['style']['dimensions']['minHeight'] ) ) {
			return Html_Processing_Helper::sanitize_dimension_value( $block_attrs['style']['dimensions']['minHeight'] );
		}

		return '';
	}

	/**
	 * Get background color from block attributes.
	 *
	 * @param array             $block_attrs Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Background color or empty string.
	 */
	private function get_background_color( array $block_attrs, Rendering_Context $rendering_context ): string {
		// Check for custom overlay color first (used as background color when no image).
		if ( ! empty( $block_attrs['customOverlayColor'] ) ) {
			$color           = $block_attrs['customOverlayColor'];
			$sanitized_color = $this->validate_and_sanitize_color( $color );
			if ( ! empty( $sanitized_color ) ) {
				return $sanitized_color;
			}
		}

		// Check for overlay color slug (used as background color when no image).
		if ( ! empty( $block_attrs['overlayColor'] ) ) {
			$translated_color = $rendering_context->translate_slug_to_color( $block_attrs['overlayColor'] );
			$sanitized_color  = $this->validate_and_sanitize_color( $translated_color );
			if ( ! empty( $sanitized_color ) ) {
				return $sanitized_color;
			}
		}

		return '';
	}

	/**
	 * Validate and sanitize a color value, returning empty string for invalid colors.
	 *
	 * @param string $color The color value to validate and sanitize.
	 * @return string Sanitized color or empty string if invalid.
	 */
	private function validate_and_sanitize_color( string $color ): string {
		$sanitized_color = Html_Processing_Helper::sanitize_color( $color );

		// If sanitize_color returned the default fallback, check if the original was actually valid.
		if ( '#000000' === $sanitized_color && '#000000' !== $color ) {
			// The original color was invalid, so return empty string.
			return '';
		}

		// The color is valid (either it was sanitized to something other than the default,
		// or it was specifically #000000 which is a valid color).
		return $sanitized_color;
	}

	/**
	 * Build the cover content with background image or color.
	 *
	 * @param string $inner_content Inner content.
	 * @return string Cover content HTML.
	 */
	private function build_cover_content( string $inner_content ): string {
		$cover_style = 'position: relative; display: inline-block; width: 100%; max-width: 100%;';

		// Wrap inner content with padding.
		// Note: $inner_content is already rendered HTML from other blocks via render_block(),
		// so it should be properly escaped by the individual block renderers.
		$inner_wrapper_style = 'padding: 20px;';
		$inner_wrapper_html  = sprintf(
			'<div class="wp-block-cover__inner-container" style="%s">%s</div>',
			$inner_wrapper_style,
			$inner_content
		);

		return sprintf(
			'<div class="wp-block-cover" style="%s">%s</div>',
			$cover_style,
			$inner_wrapper_html
		);
	}
}
