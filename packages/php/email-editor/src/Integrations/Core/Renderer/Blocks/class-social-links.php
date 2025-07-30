<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Social_Links_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
/**
 * Renders the social links block.
 */
class Social_Links extends Abstract_Block_Renderer {

	/**
	 * Cache of the core social link services.
	 *
	 * @var array<string, array>
	 */
	private $core_social_link_services_cache = array();

	/**
	 * Supported image types.
	 *
	 * @var array<string>
	 */
	private $supported_image_types = array( 'white', 'brand' );

	/**
	 * Renders the block content.
	 *
	 * @param string            $block_content Block content.
	 * @param array             $parsed_block Parsed block.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Rendering_Context $rendering_context ): string {
		$attrs = $parsed_block['attrs'] ?? array();

		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		$content = '';
		foreach ( $inner_blocks as $block ) {
			$content .= $this->generate_social_link_content( $block, $attrs );
		}

		return str_replace(
			'{social_links_content}',
			$content,
			$this->get_block_wrapper( $block_content, $parsed_block )
		);
	}

	/**
	 * Generates the social link content.
	 *
	 * @param array $block The block data.
	 * @param array $parent_block_attrs The parent block attributes.
	 * @return string The generated content.
	 */
	private function generate_social_link_content( $block, $parent_block_attrs ) {
		$service_name = $block['attrs']['service'] ?? '';
		$service_url  = $block['attrs']['url'] ?? '';
		$label        = $block['attrs']['label'] ?? '';

		if ( empty( $service_name ) || empty( $service_url ) ) {
			return '';
		}

		/**
		 * Prepend emails with `mailto:` if not set.
		 * The `is_email` returns false for emails with schema.
		 */
		if ( is_email( $service_url ) ) {
			$service_url = 'mailto:' . antispambot( $service_url );
		}

		/**
		 * Prepend URL with https:// if it doesn't appear to contain a scheme
		 * and it's not a relative link or a fragment.
		 */
		if ( ! wp_parse_url( $service_url, PHP_URL_SCHEME ) && ! str_starts_with( $service_url, '//' ) && ! str_starts_with( $service_url, '#' ) ) {
			$service_url = 'https://' . $service_url;
		}

		$open_in_new_tab = $parent_block_attrs['openInNewTab'] ?? false;
		$show_labels     = $parent_block_attrs['showLabels'] ?? false;
		$size            = $parent_block_attrs['size'] ?? Social_Links_Helper::get_default_social_link_size();

		$service_brand_color = Social_Links_Helper::get_service_brand_color( $service_name );

		$icon_color_value            = $parent_block_attrs['iconColorValue'] ?? '#ffffff'; // use white as default icon color.
		$icon_background_color_value = $parent_block_attrs['iconBackgroundColorValue'] ?? '';

		$is_logos_only = strpos( $parent_block_attrs['className'] ?? '', 'is-style-logos-only' ) !== false;
		$is_pill_shape = strpos( $parent_block_attrs['className'] ?? '', 'is-style-pill-shape' ) !== false;

		if ( ! $is_logos_only && Social_Links_Helper::detect_whiteish_color( $icon_color_value ) && ( Social_Links_Helper::detect_whiteish_color( $icon_background_color_value ) || empty( $icon_background_color_value ) ) ) {
			// If the icon color is white and the background color is white or empty, use the service brand color for the icon background color.
			$icon_background_color_value = ! empty( $service_brand_color ) ? $service_brand_color : '#000';
		}

		if ( $is_logos_only ) {
			// logos only mode does not need background color. We also don't really need the icon color (we can't change png image color anyways).
			// We set it so that the label text color will reflect the service brand color.
			$icon_color_value = ! empty( $service_brand_color ) ? $service_brand_color : '#000';
		}

		$icon_size = Social_Links_Helper::get_social_link_size_option_value( $size );

		$service_icon_url = $this->get_service_icon_url( $service_name, $is_logos_only ? 'brand' : 'white' );

		$service_label = '';
		if ( $show_labels ) {
			$text          = ! empty( $label ) ? trim( $label ) : '';
			$service_label = $text ? $text : block_core_social_link_get_name( $service_name );
		}

		$main_table_styles = $this->compile_css(
			array(
				'background-color' => $icon_background_color_value,
				'border-radius'    => '9999px',
				'display'          => 'inline-table',
				'float'            => 'none',
			)
		);

		// divide the icon value by 2 to get the font size.
		$font_size_value = (int) rtrim( $icon_size, 'px' );
		$font_size       = ( $font_size_value / 2 ) + 1; // inline with core styles.
		$text_font_size  = "{$font_size}px";
		$anchor_styles   = $this->compile_css(
			array(
				'color'           => $icon_color_value,
				'text-decoration' => 'none',
				'text-transform'  => 'none',
				'font-size'       => $text_font_size,
			)
		);

		$anchor_html = sprintf( ' style="%s" ', esc_attr( $anchor_styles ) );
		if ( $open_in_new_tab ) {
			$anchor_html .= ' rel="noopener nofollow" target="_blank" ';
		}

		$row_container_styles = array(
			'display' => 'block',
			'padding' => '0.25em',
		);

		if ( $is_pill_shape ) {
			$row_container_styles['padding-left']  = '17px';
			$row_container_styles['padding-right'] = '17px';
		}
		$row_container_styles = $this->compile_css( $row_container_styles );

		// Generate the icon content.
		$icon_content = sprintf(
			'<a href="%1$s" %2$s class="wp-block-social-link-anchor">
				<img height="%3$s" src="%4$s" style="display:block;margin-right:0;" width="%3$s" alt="%5$s">
			</a>',
			esc_url( $service_url ),
			$anchor_html,
			esc_attr( $icon_size ),
			esc_url( $service_icon_url ),
			// translators: %s is the social service name.
			sprintf( __( '%s icon', 'woocommerce' ), $service_name )
		);
		$icon_content = Table_Wrapper_Helper::render_table_wrapper( $icon_content, array(), array( 'style' => 'vertical-align:middle;' ) );
		$icon_content = Table_Wrapper_Helper::render_table_cell( $icon_content, array( 'style' => sprintf( 'vertical-align:middle;font-size:%s;', $text_font_size ) ) );

		// Generate the label content if needed.
		$label_content = '';
		if ( $service_label ) {
			$label_content    = sprintf(
				'<a href="%1$s" %2$s class="wp-block-social-link-anchor">
					<span style="margin-left:.5em;margin-right:.5em"> %3$s </span>
				</a>',
				esc_url( $service_url ),
				$anchor_html,
				esc_html( $service_label )
			);
			$label_cell_style = sprintf(
				'vertical-align:middle;padding-left:6px;padding-right:6px;font-size:%s;',
				$text_font_size
			);
			$label_content    = Table_Wrapper_Helper::render_table_cell( $label_content, array( 'style' => $label_cell_style ) );
		}

		// Combine icon and label tables.
		$social_link_content = $icon_content . $label_content;

		// Create the main social link table.
		$main_table_attrs = array(
			'align' => 'center',
			'style' => $main_table_styles,
		);

		$main_row_attrs = array(
			'style' => $row_container_styles,
		);

		$main_table = Table_Wrapper_Helper::render_table_wrapper( $social_link_content, $main_table_attrs, array(), $main_row_attrs, false );

		return Table_Wrapper_Helper::render_outlook_table_cell( $main_table );
	}

	/**
	 * Gets the block wrapper.
	 *
	 * @param string $block_content The block content.
	 * @param array  $parsed_block The parsed block.
	 * @return string The block wrapper HTML.
	 */
	private function get_block_wrapper( $block_content, $parsed_block ) {
		$content = $this->adjust_block_content( $block_content, $parsed_block );

		$table_styles    = $content['table_styles'];
		$classes         = $content['classes'];
		$compiled_styles = $content['compiled_styles'];
		$align           = $content['align'];

		$table_attrs = array(
			'class' => 'wp-block-social-links',
			'style' => $table_styles . ' vertical-align:top;',
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => $classes,
			'style' => $compiled_styles,
			'align' => $align,
		);

		$row_attrs = array(
			'role' => 'presentation',
		);

		$inner_content = Table_Wrapper_Helper::render_outlook_table_wrapper( '{social_links_content}', array( 'align' => 'center' ), array(), array(), false );
		$main_wrapper  = Table_Wrapper_Helper::render_table_wrapper( $inner_content, $table_attrs, $cell_attrs, $row_attrs );

		return Table_Wrapper_Helper::render_outlook_table_wrapper( $main_wrapper, array( 'align' => 'center' ) );
	}

	/**
	 * Adjusts the block content.
	 * Returns css classes and styles compatible with email clients.
	 *
	 * @param string $block_content The block content.
	 * @param array  $parsed_block The parsed block.
	 * @return array The adjusted block content.
	 */
	private function adjust_block_content( $block_content, $parsed_block ) {
		$block_content    = $this->adjust_style_attribute( $block_content );
		$block_attributes = wp_parse_args(
			$parsed_block['attrs'] ?? array(),
			array(
				'textAlign' => 'left',
				'style'     => array(),
			)
		);
		$html             = new \WP_HTML_Tag_Processor( $block_content );
		$classes          = 'wp-block-social-links';
		if ( $html->next_tag() ) {
			/** @var string $block_classes */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$block_classes = $html->get_attribute( 'class' ) ?? '';
			$classes      .= ' ' . $block_classes;
			// remove has-background to prevent double padding applied for wrapper and inner element.
			$block_classes = str_replace( 'has-background', '', $block_classes );
			// remove border related classes because we handle border on wrapping table cell.
			$block_classes = preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $block_classes );
			/** @var string $block_classes */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$html->set_attribute( 'class', trim( $block_classes ) );
			$block_content = $html->get_updated_html();
		}

		$block_styles = $this->get_styles_from_block(
			array(
				'color'      => $block_attributes['style']['color'] ?? array(),
				'spacing'    => $block_attributes['style']['spacing'] ?? array(),
				'typography' => $block_attributes['style']['typography'] ?? array(),
				'border'     => $block_attributes['style']['border'] ?? array(),
			)
		);

		$styles = array(
			'min-width'      => '100%', // prevent Gmail App from shrinking the table on mobile devices.
			'vertical-align' => 'middle',
			'word-break'     => 'break-word',
		);

		$styles['text-align'] = 'left';
		if ( ! empty( $parsed_block['attrs']['textAlign'] ) ) { // in this case, textAlign needs to be one of 'left', 'center', 'right'.
			$styles['text-align'] = $parsed_block['attrs']['textAlign'];
		} elseif ( in_array( $parsed_block['attrs']['align'] ?? null, array( 'left', 'center', 'right' ), true ) ) {
			$styles['text-align'] = $parsed_block['attrs']['align'];
		}

		$compiled_styles = $this->compile_css( $block_styles['declarations'], $styles );
		$table_styles    = 'border-collapse: separate;'; // Needed because of border radius.

		return array(
			'table_styles'    => $table_styles,
			'classes'         => $classes,
			'compiled_styles' => $compiled_styles,
			'align'           => $styles['text-align'],
			'block_content'   => $block_content,
		);
	}

	/**
	 * 1) We need to remove padding because we render padding on wrapping table cell
	 * 2) We also need to replace font-size to avoid clamp() because clamp() is not supported in many email clients.
	 * The font size values is automatically converted to clamp() when WP site theme is configured to use fluid layouts.
	 * Currently (WP 6.5), there is no way to disable this behavior.
	 *
	 * @param string $block_content Block content.
	 */
	private function adjust_style_attribute( string $block_content ): string {
		$html = new \WP_HTML_Tag_Processor( $block_content );

		if ( $html->next_tag() ) {
			$element_style_value = $html->get_attribute( 'style' );
			$element_style       = isset( $element_style_value ) ? strval( $element_style_value ) : '';
			// Padding may contain value like 10px or variable like var(--spacing-10).
			$element_style = preg_replace( '/padding[^:]*:.?[0-9a-z-()]+;?/', '', $element_style );

			// Remove border styles. We apply border styles on the wrapping table cell.
			$element_style = preg_replace( '/border[^:]*:.?[0-9a-z-()#]+;?/', '', strval( $element_style ) );

			// We define the font-size on the wrapper element, but we need to keep font-size definition here
			// to prevent CSS Inliner from adding a default value and overriding the value set by user, which is on the wrapper element.
			// The value provided by WP uses clamp() function which is not supported in many email clients.
			$element_style = preg_replace( '/font-size:[^;]+;?/', 'font-size: inherit;', strval( $element_style ) );
			/** @var string $element_style */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
			$html->set_attribute( 'style', esc_attr( $element_style ) );
			$block_content = $html->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Gets the service icon URL.
	 *
	 * Default image type is 'white'.
	 *
	 * @param string $service The service name.
	 * @param string $image_type The image type. e.g 'white', 'brand'.
	 * @return string The service icon URL.
	 */
	public function get_service_icon_url( $service, $image_type = '' ) {
		$image_type = empty( $image_type ) ? 'white' : $image_type;
		$service    = empty( $service ) ? '' : strtolower( $service );

		if ( empty( $this->core_social_link_services_cache ) ) {
			$services                              = block_core_social_link_services();
			$this->core_social_link_services_cache = is_array( $services ) ? $services : array();
		}

		if ( ! isset( $this->core_social_link_services_cache[ $service ] ) ) {
			// not in the list of core services.
			return '';
		}

		if ( ! in_array( $image_type, $this->supported_image_types, true ) ) {
			return '';
		}

		// Get URL to icons/service.png.
		$service_icon_url = $this->get_service_png_url( $service, $image_type );

		if ( $service_icon_url && ! file_exists( $this->get_service_png_path( $service, $image_type ) ) ) {
			// The image file does not exist.
			return '';
		}

		return $service_icon_url;
	}

	/**
	 * Gets the service PNG URL.
	 *
	 * @param string $service The service name.
	 * @param string $image_type The image type. e.g 'white', 'brand'.
	 * @return string The service PNG URL.
	 */
	public function get_service_png_url( $service, $image_type = 'white' ) {
		if ( empty( $service ) ) {
			return '';
		}

		$image_type = empty( $image_type ) ? 'white' : $image_type;
		$file_name  = "/icons/{$service}/{$service}-{$image_type}.png";
		return plugins_url( $file_name, __FILE__ );
	}

	/**
	 * Gets the service PNG path.
	 *
	 * @param string $service The service name.
	 * @param string $image_type The image type. e.g 'white', 'brand'.
	 * @return string The service PNG path.
	 */
	public function get_service_png_path( $service, $image_type = 'white' ) {
		if ( empty( $service ) ) {
			return '';
		}

		$image_type = empty( $image_type ) ? 'white' : $image_type;
		$file_name  = "/icons/{$service}/{$service}-{$image_type}.png";
		return __DIR__ . $file_name;
	}
}
