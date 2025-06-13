<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Dom_Document_Helper;

/**
 * Renders an image block.
 */
class Image extends Abstract_Block_Renderer {
	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	protected function render_content( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$parsed_html = $this->parse_block_content( $block_content );

		if ( ! $parsed_html ) {
			return '';
		}

		$image_url = $parsed_html['imageUrl'];
		$image     = $parsed_html['image'];
		$caption   = $parsed_html['caption'];
		$class     = $parsed_html['class'];

		$parsed_block = $this->add_image_size_when_missing( $parsed_block, $image_url, $settings_controller );
		$image        = $this->addImageDimensions( $image, $parsed_block, $settings_controller );
		$image        = $this->apply_image_border_style( $image, $parsed_block, $caption );
		$image        = $this->apply_rounded_style( $image, $parsed_block );

		$image_with_wrapper = str_replace(
			array( '{image_content}', '{caption_content}' ),
			array( $image, $caption ),
			$this->get_block_wrapper( $parsed_block, $settings_controller, $caption )
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
			// This style is applied to both wrapper and the image.
			$block_content = $this->remove_style_attribute_from_element(
				$block_content,
				array(
					'tag_name'   => 'td',
					'class_name' => 'email-image-cell',
				),
				'border-radius'
			);
			$block_content = $this->add_style_to_element(
				$block_content,
				array(
					'tag_name'   => 'td',
					'class_name' => 'email-image-cell',
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
	 * @param array               $parsed_block Parsed block.
	 * @param string              $image_url Image URL.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	private function add_image_size_when_missing( array $parsed_block, string $image_url, Settings_Controller $settings_controller ): array {
		if ( isset( $parsed_block['attrs']['width'] ) ) {
			return $parsed_block;
		}
		// Can't determine any width let's go with 100%.
		if ( ! isset( $parsed_block['email_attrs']['width'] ) ) {
			$parsed_block['attrs']['width'] = '100%';
		}
		$max_width                      = $settings_controller->parse_number_from_string_with_pixels( $parsed_block['email_attrs']['width'] );
		$image_size                     = wp_getimagesize( $image_url );
		$image_size                     = $image_size ? $image_size[0] : $max_width;
		$width                          = min( $image_size, $max_width );
		$parsed_block['attrs']['width'] = "{$width}px";
		return $parsed_block;
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
		$border_element_tag         = array(
			'tag_name'   => 'td',
			'class_name' => 'email-image-cell',
		);
		$content_with_border_styles = $this->add_style_to_element( $block_content, $border_element_tag, \WP_Style_Engine::compile_css( $border_styles, '' ) );
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
	 * Settings width and height attributes for images is important for MS Outlook.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 */
	private function addImageDimensions( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );
		if ( $html->next_tag( array( 'tag_name' => 'img' ) ) ) {
			// Getting height from styles and if it's set, we set the height attribute.
			/** @var string $styles */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$styles = $html->get_attribute( 'style' ) ?? '';
			$styles = $settings_controller->parse_styles_to_array( $styles );
			$height = $styles['height'] ?? null;
			if ( $height && 'auto' !== $height ) {
				$height = $settings_controller->parse_number_from_string_with_pixels( $height );
				/* @phpstan-ignore-next-line Wrong annotation for parameter in WP. */
				$html->set_attribute( 'height', esc_attr( $height ) );
			}

			if ( isset( $parsed_block['attrs']['width'] ) ) {
				$width = $settings_controller->parse_number_from_string_with_pixels( $parsed_block['attrs']['width'] );
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
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @param array               $parsed_block Parsed block.
	 */
	private function get_caption_styles( Settings_Controller $settings_controller, array $parsed_block ): string {
		$theme_data = $settings_controller->get_theme()->get_data();

		$styles = array(
			'text-align' => isset( $parsed_block['attrs']['align'] ) ? 'center' : 'left',
		);

		$styles['font-size'] = $parsed_block['email_attrs']['font-size'] ?? $theme_data['styles']['typography']['fontSize'];
		return \WP_Style_Engine::compile_css( $styles, '' );
	}

	/**
	 * Based on MJML <mj-image> but because MJML doesn't support captions, our solution is a bit different
	 *
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @param string|null         $caption Caption.
	 */
	private function get_block_wrapper( array $parsed_block, Settings_Controller $settings_controller, ?string $caption ): string {
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
			$caption_styles                  = $this->get_caption_styles( $settings_controller, $parsed_block );
			$caption_html                    = '
      <table
        role="presentation"
        class="email-table-with-width"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . esc_attr( \WP_Style_Engine::compile_css( $caption_wrapper_styles, '' ) ) . '"
        width="' . esc_attr( $caption_width ) . '"
          >
        <tr>
            <td style="' . esc_attr( $caption_styles ) . '">{caption_content}</td>
         </tr>
      </table>';
		}

		$styles['width'] = '100%';
		$align           = $parsed_block['attrs']['align'] ?? 'left';

		return '
      <table
        role="presentation"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . esc_attr( \WP_Style_Engine::compile_css( $styles, '' ) ) . '"
        width="100%"
      >
        <tr>
          <td align="' . esc_attr( $align ) . '">
            <table
              role="presentation"
              class="email-table-with-width"
              border="0"
              cellpadding="0"
              cellspacing="0"
              style="' . esc_attr( \WP_Style_Engine::compile_css( $wrapper_styles, '' ) ) . '"
              width="' . esc_attr( $wrapper_width ) . '"
            >
              <tr>
                <td class="email-image-cell">{image_content}</td>
              </tr>
            </table>' . $caption_html . '
          </td>
        </tr>
      </table>
    ';
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
			$element_style = preg_replace( '/' . $style_name . ':(.?[0-9]+px)+;?/', '', $element_style );
			$html->set_attribute( 'style', esc_attr( strval( $element_style ) ) );
			$block_content = $html->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Parse block content to get image URL, image HTML and caption HTML.
	 *
	 * @param string $block_content Block content.
	 * @return array{imageUrl: string, image: string, caption: string, class: string}|null
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

		return array(
			'imageUrl' => $image_src ? $image_src : '',
			'image'    => $this->cleanup_image_html( $image_html ),
			'caption'  => $figcaption_html ? $figcaption_html : '',
			'class'    => $image_class ? $image_class : '',
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
