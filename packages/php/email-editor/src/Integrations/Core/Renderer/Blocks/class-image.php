<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Renders an image block.
 */
class Image extends Abstract_Block_Renderer {
	/**
	 * Renders the block content
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( string $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$parsed_html = $this->parse_block_content( $block_content );

		if ( ! $parsed_html ) {
			return '';
		}

		$image_url             = $parsed_html['imageUrl'];
		$image                 = $parsed_html['image'];
		$caption               = $parsed_html['caption'];
		$class                 = $parsed_html['class'];
		$anchor_tag_href       = $parsed_html['anchor_tag_href'];
		$anchor_data_link_href = $parsed_html['anchor_data_link_href'];

		$parsed_block = $this->add_image_size_when_missing( $parsed_block, $image_url );
		$image        = $this->add_image_dimensions( $image, $parsed_block );

		$image_with_wrapper = str_replace(
			array( '{image_content}', '{caption_content}' ),
			array( $image, $caption ),
			$this->get_block_wrapper( $parsed_block, $rendering_context, $caption, $anchor_tag_href, $anchor_data_link_href )
		);

		$image_with_wrapper = $this->apply_rounded_style( $image_with_wrapper, $parsed_block );
		$image_with_wrapper = $this->apply_image_border_style( $image_with_wrapper, $parsed_block, $class );
		return $image_with_wrapper;
	}

	/**
	 * Apply rounded style to the image.
	 *
	 * @param string $block_content Block content.
	 * @param array  $parsed_block Parsed block.
	 */
	private function apply_rounded_style( string $block_content, array $parsed_block ): string {
		// Because the isn't an attribute for definition of rounded style, we have to check the class name.
		if ( isset( $parsed_block['attrs']['className'] ) && strpos( $parsed_block['attrs']['className'], 'is-style-rounded' ) !== false ) {
			// If the image should be in a circle, we need to set the border-radius to 9999px to make it the same as is in the editor
			// This style is applied to both the border cell wrapper and the image.
			$block_content = $this->remove_style_attribute_from_element(
				$block_content,
				array(
					'tag_name'   => 'td',
					'class_name' => 'email-image-border-cell',
				),
				'border-radius'
			);
			$block_content = $this->add_style_to_element(
				$block_content,
				array(
					'tag_name'   => 'td',
					'class_name' => 'email-image-border-cell',
				),
				'border-radius: 9999px;'
			);
			$block_content = $this->remove_style_attribute_from_element( $block_content, array( 'tag_name' => 'img' ), 'border-radius' );
			$block_content = $this->add_style_to_element( $block_content, array( 'tag_name' => 'img' ), 'border-radius: 9999px;' );
		}
		return $block_content;
	}

	/**
	 * When the width is not set, it's important to get it for the image to be displayed correctly
	 *
	 * @param array  $parsed_block Parsed block.
	 * @param string $image_url Image URL.
	 */
	private function add_image_size_when_missing( array $parsed_block, string $image_url ): array {
		if ( isset( $parsed_block['attrs']['width'] ) ) {
			return $parsed_block;
		}
		// Can't determine any width let's go with 100%.
		if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
			$parsed_block['attrs']['width'] = '100%';
			return $parsed_block;
		}
		$max_width = Styles_Helper::parse_value( $parsed_block['email_attrs']['width'] );

		$image_size = null;

		if ( $image_url ) {
			// Try to extract width from URL query parameter if it exists.
			$parsed_url = wp_parse_url( $image_url );
			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $query_params );
				if ( isset( $query_params['w'] ) && is_numeric( $query_params['w'] ) && $query_params['w'] > 0 ) {
					$image_size = (int) $query_params['w'];
				}
			}

			// Next we check the attachment data if it has an ID.
			if ( ! isset( $image_size ) ) {
				$attachment_id = $parsed_block['attrs']['id'] ?? null;
				if ( $attachment_id ) {
					$size_slug = $parsed_block['attrs']['sizeSlug'] ?? 'large';

					// Check the metadata first.
					$metadata = wp_get_attachment_metadata( $attachment_id );
					if ( $metadata ) {
						if ( isset( $metadata['sizes'][ $size_slug ]['width'] ) ) {
							$image_size = (int) $metadata['sizes'][ $size_slug ]['width'];
						} elseif ( 'full' === $size_slug && isset( $metadata['width'] ) ) {
							$image_size = (int) $metadata['width'];
						}
					}

					// Try to get dimensions from wp_get_attachment_image_src if metadata didn't have it.
					if ( ! isset( $image_size ) ) {
						$image_src = wp_get_attachment_image_src( $attachment_id, $size_slug );
						if ( $image_src && isset( $image_src[1] ) ) {
							$image_size = (int) $image_src[1];
						}
					}
				}
			}

			// Fall back to reading the local image file if we still don't have a size.
			if ( ! isset( $image_size ) ) {
				$image_size = $this->get_local_image_width( $image_url );
			}
		}

		// Use the found image size or fall back to max_width.
		$width = isset( $image_size ) ? min( $image_size, $max_width ) : $max_width;

		$parsed_block['attrs']['width'] = "{$width}px";
		return $parsed_block;
	}

	/**
	 * Get the width of an image stored locally in the uploads directory.
	 *
	 * Only local files are measured. External URLs return null so the caller
	 * can fall back to the max width.
	 *
	 * @param string $image_url Image URL.
	 * @return int|null Image width in pixels, or null if it can't be determined locally.
	 */
	private function get_local_image_width( string $image_url ): ?int {
		$upload_dir = wp_upload_dir();
		$base_url   = trailingslashit( $upload_dir['baseurl'] );

		// Only measure images served from the uploads directory.
		if ( ! str_starts_with( $image_url, $base_url ) ) {
			return null;
		}

		$base_dir   = realpath( $upload_dir['basedir'] );
		$image_path = realpath( $upload_dir['basedir'] . '/' . substr( $image_url, strlen( $base_url ) ) );

		if ( false === $base_dir || false === $image_path ) {
			return null;
		}

		// Make sure the resolved file stays inside the uploads directory.
		$base_dir   = trailingslashit( wp_normalize_path( $base_dir ) );
		$image_path = wp_normalize_path( $image_path );
		if ( ! str_starts_with( $image_path, $base_dir ) ) {
			return null;
		}

		$result = wp_getimagesize( $image_path );

		return $result ? (int) $result[0] : null;
	}

	/**
	 * Apply border style to the image.
	 *
	 * @param string $block_content Block content.
	 * @param array  $parsed_block Parsed block.
	 * @param string $class_name Class name.
	 */
	private function apply_image_border_style( string $block_content, array $parsed_block, string $class_name ): string {
		// Getting individual border properties.
		$border_styles = wp_style_engine_get_styles( array( 'border' => $parsed_block['attrs']['style']['border'] ?? array() ) );
		$border_styles = $border_styles['declarations'] ?? array();
		if ( ! empty( $border_styles ) ) {
			$border_styles['border-style'] = 'solid';
			$border_styles['box-sizing']   = 'border-box';
		}
		// Apply border to the dedicated border cell wrapper, not the outer image cell.
		// This ensures borders stay tight around the image on mobile when the outer wrapper becomes 100% width.
		$border_element_tag         = array(
			'tag_name'   => 'td',
			'class_name' => 'email-image-border-cell',
		);
		$content_with_border_styles = $this->add_style_to_element( $block_content, $border_element_tag, \WP_Style_Engine::compile_css( $border_styles, '' ) );
		// Remove border styles from the image HTML tag. The border is applied on the wrapper cell.
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-style' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-width' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-color' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-radius' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-top-left-radius' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-top-right-radius' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-bottom-left-radius' );
		$content_with_border_styles = $this->remove_style_attribute_from_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-bottom-right-radius' );

		// The border sits on the wrapper cell, so the image needs a smaller radius to stay flush
		// inside it. With equal radii the border width would leave a gap in the corners, so we
		// subtract the border width from the image radius (clamped to 0).
		$inner_radius = $this->get_inner_image_border_radius( $parsed_block );
		if ( '' !== $inner_radius ) {
			$content_with_border_styles = $this->add_style_to_element( $content_with_border_styles, array( 'tag_name' => 'img' ), 'border-radius:' . $inner_radius . ';' );
		}
		// Add Border related classes to proper element. This is required for inlined border-color styles when defined via class.
		$border_classes = array_filter(
			explode( ' ', $class_name ),
			function ( $class_name ) {
				return strpos( $class_name, 'border' ) !== false;
			}
		);
		$html           = new \WP_HTML_Tag_Processor( $content_with_border_styles );
		if ( $html->next_tag( $border_element_tag ) ) {
			$class_name       = $html->get_attribute( 'class' ) ?? '';
			$border_classes[] = $class_name;
			$html->set_attribute( 'class', implode( ' ', $border_classes ) );
		}
		return $html->get_updated_html();
	}

	/**
	 * Get the border radius to apply to the image so it sits flush inside the bordered wrapper cell.
	 *
	 * The border is rendered on the wrapper cell, so the image radius is reduced by the border
	 * width (clamped to 0). Returns a CSS value (single radius or a four-value shorthand), or an
	 * empty string when there's no radius to apply.
	 *
	 * @param array $parsed_block Parsed block.
	 */
	private function get_inner_image_border_radius( array $parsed_block ): string {
		$border     = $parsed_block['attrs']['style']['border'] ?? array();
		$class_name = $parsed_block['attrs']['className'] ?? '';
		$is_rounded = strpos( $class_name, 'is-style-rounded' ) !== false;

		$radius = $border['radius'] ?? null;
		if ( $is_rounded ) {
			$radius = '9999px'; // Matches the circle radius applied by apply_rounded_style().
		}

		if ( empty( $radius ) ) {
			return '';
		}

		// Use the widest border side so the image never pokes out past the border on any corner.
		$border_width = $this->get_max_border_width( $border );

		if ( is_array( $radius ) ) {
			$top_left     = $this->reduce_radius_by_border_width( $radius['topLeft'] ?? null, $border_width );
			$top_right    = $this->reduce_radius_by_border_width( $radius['topRight'] ?? null, $border_width );
			$bottom_right = $this->reduce_radius_by_border_width( $radius['bottomRight'] ?? null, $border_width );
			$bottom_left  = $this->reduce_radius_by_border_width( $radius['bottomLeft'] ?? null, $border_width );

			if ( $top_left === $top_right && $top_right === $bottom_right && $bottom_right === $bottom_left ) {
				return $top_left;
			}
			return "{$top_left} {$top_right} {$bottom_right} {$bottom_left}";
		}

		return $this->reduce_radius_by_border_width( $radius, $border_width );
	}

	/**
	 * Get the largest border width (in px) across all sides.
	 *
	 * @param array $border Border style attributes.
	 */
	private function get_max_border_width( array $border ): float {
		$max = isset( $border['width'] ) ? Styles_Helper::parse_value( (string) $border['width'] ) : 0.0;
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( isset( $border[ $side ]['width'] ) ) {
				$max = max( $max, Styles_Helper::parse_value( (string) $border[ $side ]['width'] ) );
			}
		}
		return $max;
	}

	/**
	 * Reduce a single border radius value by the border width, clamped to 0.
	 *
	 * Only px (or unitless) values can be combined with the px border width; other units are
	 * returned unchanged.
	 *
	 * @param mixed $radius Border radius value (e.g. "20px"). Non-scalar values are treated as 0.
	 * @param float $border_width Border width in px.
	 */
	private function reduce_radius_by_border_width( $radius, float $border_width ): string {
		$radius = is_scalar( $radius ) ? trim( (string) $radius ) : '';
		// Non-px units can't be combined with a px border width, so keep them unchanged.
		if ( '' !== $radius && ! str_ends_with( $radius, 'px' ) && ! is_numeric( $radius ) ) {
			return $radius;
		}
		$inner = max( 0.0, Styles_Helper::parse_value( $radius ) - $border_width );
		return $inner . 'px';
	}

	/**
	 * Settings width and height attributes for images is important for MS Outlook.
	 *
	 * @param string $block_content Block content.
	 * @param array  $parsed_block Parsed block.
	 */
	private function add_image_dimensions( string $block_content, array $parsed_block ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );
		if ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			// Getting height from styles and if it's set, we set the height attribute.
			/** @var string $styles */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$styles = $html->get_attribute( 'style' ) ?? '';
			$styles = Styles_Helper::parse_styles_to_array( $styles );
			$height = $styles['height'] ?? null;
			if ( $height && 'auto' !== $height ) {
				$height = Styles_Helper::parse_value( $height );
				/* @phpstan-ignore-next-line Wrong annotation for parameter in WP. */
				$html->set_attribute( 'height', esc_attr( $height ) );
			}

			if ( isset( $parsed_block['attrs']['width'] ) ) {
				$width = Styles_Helper::parse_value( $parsed_block['attrs']['width'] );
				/* @phpstan-ignore-next-line Wrong annotation for parameter in WP. */
				$html->set_attribute( 'width', esc_attr( $width ) );
			}
			$block_content = $html->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * This method configure the font size of the caption because it's set to 0 for the parent element to avoid unexpected white spaces
	 * We try to use font-size passed down from the parent element $parsedBlock['email_attrs']['font-size'], but if it's not set, we use the default font-size from the email theme.
	 *
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @param array             $parsed_block Parsed block.
	 */
	private function get_caption_styles( Rendering_Context $rendering_context, array $parsed_block ): string {
		$theme_data = $rendering_context->get_theme_json()->get_data();

		$align  = $parsed_block['attrs']['align'] ?? '';
		$styles = array(
			'text-align' => $align ? 'center' : $rendering_context->get_default_text_align(),
		);

		$styles['font-size'] = $parsed_block['email_attrs']['font-size'] ?? $theme_data['styles']['typography']['fontSize'];
		return \WP_Style_Engine::compile_css( $styles, '' );
	}

	/**
	 * Based on MJML <mj-image> but because MJML doesn't support captions, our solution is a bit different
	 *
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @param string|null       $caption Caption.
	 * @param string|null       $anchor_tag_href Anchor tag href.
	 * @param string|null       $anchor_data_link_href Anchor data-link-href attribute for personalization tags.
	 */
	private function get_block_wrapper( array $parsed_block, Rendering_Context $rendering_context, ?string $caption, ?string $anchor_tag_href, ?string $anchor_data_link_href = null ): string {
		$styles = array(
			'border-collapse' => 'collapse',
			'border-spacing'  => '0px',
			'font-size'       => '0px',
			'vertical-align'  => 'top',
			'width'           => '100%',
		);

		$width                             = $parsed_block['attrs']['width'] ?? '100%';
		$wrapper_width                     = ( $width && '100%' !== $width ) ? $width : 'auto';
		$wrapper_styles                    = $styles;
		$wrapper_styles['width']           = $wrapper_width;
		$wrapper_styles['border-collapse'] = 'separate'; // Needed because of border radius.

		$caption_html = '';
		if ( $caption ) {
			// When the image is not aligned, the wrapper is set to 100% width due to caption that can be longer than the image.
			$caption_width                   = isset( $parsed_block['attrs']['align'] ) ? ( $parsed_block['attrs']['width'] ?? '100%' ) : '100%';
			$caption_wrapper_styles          = $styles;
			$caption_wrapper_styles['width'] = $caption_width;
			$caption_styles                  = $this->get_caption_styles( $rendering_context, $parsed_block );

			$caption_table_attrs = array(
				'class' => 'email-table-with-width',
				'style' => \WP_Style_Engine::compile_css( $caption_wrapper_styles, '' ),
				'width' => $caption_width,
			);

			$caption_cell_attrs = array(
				'style' => $caption_styles,
			);

			$caption_html = Table_Wrapper_Helper::render_table_wrapper( '{caption_content}', $caption_table_attrs, $caption_cell_attrs );
		}

		$styles['width'] = '100%';
		$align           = $parsed_block['attrs']['align'] ?? $rendering_context->get_default_text_align();

		// Map block alignment to valid HTML/CSS alignment values.
		// "full" and "wide" are not valid text-align or table align values.
		$css_align = in_array( $align, array( 'full', 'wide' ), true ) ? 'center' : $rendering_context->resolve_text_align( $align );

		$table_attrs = array(
			'style' => \WP_Style_Engine::compile_css( $styles, '' ),
			'width' => '100%',
		);

		$cell_attrs = array(
			'align' => $css_align,
		);

		$image_table_attrs = array(
			'class' => 'email-table-with-width',
			'style' => \WP_Style_Engine::compile_css( $wrapper_styles, '' ),
			'width' => $wrapper_width,
		);

		$image_cell_attrs = array(
			'class' => 'email-image-cell',
			'style' => 'overflow: hidden;',
			'align' => $css_align,
		);

		$image_content = '{image_content}';
		if ( $anchor_tag_href ) {
			$data_link_attr = $anchor_data_link_href
				? sprintf( ' data-link-href="%s"', esc_attr( $anchor_data_link_href ) )
				: '';
			$image_content  = sprintf(
				'<a href="%s"%s rel="noopener nofollow" target="_blank">%s</a>',
				esc_url( $anchor_tag_href ),
				$data_link_attr,
				'{image_content}'
			);
		}

		// Wrap image in a border wrapper table that won't expand to 100% on mobile.
		// This ensures borders stay tight around the image regardless of screen size.
		$border_wrapper_styles = array(
			'border-collapse' => 'separate',
			'border-spacing'  => '0px',
		);
		$border_wrapper_attrs  = array(
			'class' => 'email-image-border-wrapper',
			'style' => \WP_Style_Engine::compile_css( $border_wrapper_styles, '' ),
		);
		$border_cell_attrs     = array(
			'class' => 'email-image-border-cell',
		);
		$image_content         = Table_Wrapper_Helper::render_table_wrapper( $image_content, $border_wrapper_attrs, $border_cell_attrs );

		$image_html    = Table_Wrapper_Helper::render_table_wrapper( $image_content, $image_table_attrs, $image_cell_attrs );
		$inner_content = $image_html . $caption_html;

		return Table_Wrapper_Helper::render_table_wrapper( $inner_content, $table_attrs, $cell_attrs );
	}

	/**
	 * Add style to the element.
	 *
	 * @param string                                       $block_content Block content.
	 * @param array{tag_name: string, class_name?: string} $tag Tag to add style to.
	 * @param string                                       $style Style to add.
	 */
	private function add_style_to_element( $block_content, array $tag, string $style ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );
		if ( $html->next_tag( $tag ) ) {
			/** @var string $element_style */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$element_style  = $html->get_attribute( 'style' ) ?? '';
			$element_style  = ! empty( $element_style ) ? ( rtrim( $element_style, ';' ) . ';' ) : ''; // Adding semicolon if it's missing.
			$element_style .= $style;
			$html->set_attribute( 'style', esc_attr( $element_style ) );
			$block_content = $html->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Remove style attribute from the element.
	 *
	 * @param string                                       $block_content Block content.
	 * @param array{tag_name: string, class_name?: string} $tag Tag to remove style from.
	 * @param string                                       $style_name Name of the style to remove.
	 */
	private function remove_style_attribute_from_element( $block_content, array $tag, string $style_name ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );
		if ( $html->next_tag( $tag ) ) {
			/** @var string $element_style */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$element_style = $html->get_attribute( 'style' ) ?? '';
			$element_style = preg_replace( '/' . preg_quote( $style_name, '/' ) . '\s*:\s*[^;]+;?/', '', $element_style );
			$html->set_attribute( 'style', esc_attr( strval( $element_style ) ) );
			$block_content = $html->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Parse block content to get image URL, image HTML and caption HTML.
	 *
	 * @param string $block_content Block content.
	 * @return array{imageUrl: string, image: string, caption: string, class: string, anchor_tag_href: string, anchor_data_link_href: string}|null
	 */
	private function parse_block_content( string $block_content ): ?array {
		// If block's image is not set, we don't need to parse the content.
		if ( empty( $block_content ) ) {
			return null;
		}

		$dom_helper = new Dom_Document_Helper( $block_content );

		$figure_tag = $dom_helper->find_element( 'figure' );
		if ( ! $figure_tag ) {
			return null;
		}

		$img_tag = $dom_helper->find_element( 'img' );
		if ( ! $img_tag ) {
			return null;
		}

		$image_src       = $dom_helper->get_attribute_value( $img_tag, 'src' );
		$image_class     = $dom_helper->get_attribute_value( $img_tag, 'class' );
		$image_html      = $dom_helper->get_outer_html( $img_tag );
		$figcaption      = $dom_helper->find_element( 'figcaption' );
		$figcaption_html = $figcaption ? $dom_helper->get_outer_html( $figcaption ) : '';
		$figcaption_html = str_replace( array( '<figcaption', '</figcaption>' ), array( '<span', '</span>' ), $figcaption_html );

		$anchor_tag            = $dom_helper->find_element( 'a' );
		$anchor_tag_href       = $anchor_tag ? $dom_helper->get_attribute_value( $anchor_tag, 'href' ) : '';
		$anchor_data_link_href = $anchor_tag ? $dom_helper->get_attribute_value( $anchor_tag, 'data-link-href' ) : '';

		return array(
			'imageUrl'              => $image_src ? $image_src : '',
			'image'                 => $this->cleanup_image_html( $image_html ),
			'caption'               => $figcaption_html ? $figcaption_html : '',
			'class'                 => $image_class ? $image_class : '',
			'anchor_tag_href'       => $anchor_tag_href ? $anchor_tag_href : '',
			'anchor_data_link_href' => $anchor_data_link_href ? $anchor_data_link_href : '',
		);
	}

	/**
	 * Cleanup image HTML.
	 *
	 * @param string $content_html Content HTML.
	 */
	private function cleanup_image_html( string $content_html ): string {
		$html = new \WP_HTML_Tag_Processor( $content_html );
		if ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			$html->remove_attribute( 'srcset' );
			$html->remove_attribute( 'class' );
		}
		return $html->get_updated_html();
	}
}
