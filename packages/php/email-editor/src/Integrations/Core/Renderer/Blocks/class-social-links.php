<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Social_Links_Helper;
/**
 * Renders the social links block.
 */
class Social_Links extends Abstract_Block_Renderer {

	/**
	 * Cache of the core social link services.
	 *
	 * @var array
	 */
	private static $core_social_link_services_cache = array();

	/**
	 * Renders the block content.
	 *
	 * @param string              $block_content Block content.
	 * @param array               $parsed_block Parsed block.
	 * @param Settings_Controller $settings_controller Settings controller.
	 * @return string
	 */
	protected function render_content( $block_content, array $parsed_block, Settings_Controller $settings_controller ): string {
		$attrs = $parsed_block['attrs'] ?? array();

		$inner_blocks = $parsed_block['innerBlocks'] ?? array();

		$content = '';
		foreach ( $inner_blocks as $block ) {
			$content .= $this->generate_social_link_content( $block, $attrs );
		}

		return str_replace(
			'{social_links_content}',
			$content,
			$this->get_block_wrapper( $attrs )
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

		$open_in_new_tab = $parent_block_attrs['openInNewTab'] ?? false;
		$show_labels     = $parent_block_attrs['showLabels'] ?? false;

		$icon_color_value            = $parent_block_attrs['iconColorValue'] ?? '#ffffff'; // use white as default icon color.
		$icon_background_color_value = $parent_block_attrs['iconBackgroundColorValue'] ?? '';

		$is_logos_only = strpos( $parent_block_attrs['className'] ?? '', 'is-style-logos-only' ) !== false;
		$is_pill_shape = strpos( $parent_block_attrs['className'] ?? '', 'is-style-pill-shape' ) !== false;

		if ( !$is_logos_only && Social_Links_Helper::detect_whiteish_color( $icon_color_value ) && ( Social_Links_Helper::detect_whiteish_color( $icon_background_color_value ) || empty( $icon_background_color_value ) ) ) {
			$icon_background_color_value = '#000'; // using black as default background color for now. Aim to use service brand color.
		}

		if ( $is_logos_only ) {
			$icon_color_value = '#000'; // using black as default icon color for logos only. Will set to brand color in the future.
		}

		$service_icon_url = $this->get_service_icon_url( $service_name, $is_logos_only ? 'brand' : 'white' );

		$label_html = '';
		if ( $show_labels ) {
			$text       = ! empty( $label ) ? trim( $label ) : '';
			$text       = $text ? $text : block_core_social_link_get_name( $service_name );
			$label_html = sprintf( '<span class="wp-block-social-link-label">%s</span>', esc_html( $text ) );
		}

		$anchor_style = array(
			'color'            => $icon_color_value,
			'background-color' => $icon_background_color_value,
			'text-decoration'  => 'none',
			'text-transform'   => 'none',
			'padding'          => '10px',
			'border-radius'  => '9999px',
		);
		if ( $is_pill_shape ) {
			$anchor_style['padding-left']  = '17px';
			$anchor_style['padding-right'] = '17px';
		}
		$anchor_html  = sprintf( ' style="%s" ', esc_attr( $this->compile_css( $anchor_style ) ) );
		if ( $open_in_new_tab ) {
			$anchor_html .= ' rel="noopener nofollow" target="_blank"';
		}

		$td_styles = array(
			'vertical-align' => 'middle',
			'text-align'     => 'center',
			'padding'        => '10px',

		);

		$td_attributes = sprintf( 'class="wp-social-link wp-social-link-%1$s wp-block-social-link"', esc_attr( $service_name ) );
		if ( ! empty( $td_styles ) ) {
			$td_attributes .= sprintf( ' style="%s"', esc_attr( $this->compile_css( $td_styles ) ) );
		}

		return sprintf(
			'<td %1$s role="presentation" valign="middle">
				<a %2$s href="%3$s" class="wp-block-social-link-anchor">
					<img src="%4$s" alt="%6$s" width="17" height="17">
					%5$s
				</a>
			</td>',
			$td_attributes, // The td attributes.
			$anchor_html, // The a target and rel attributes.
			esc_url( $service_url ), // The a href link.
			esc_url( $service_icon_url ), // The Img src.
			$label_html, // The Label.
			// translators: %s is the social service name.
			sprintf( __( '%s icon', 'woocommerce' ), $service_name ) // The Img alt.
		);
	}

	/**
	 * Gets the block wrapper.
	 *
	 * @param array $attrs The block attributes.
	 * @return string The block wrapper HTML.
	 */
	private function get_block_wrapper( $attrs ) {
		$align      = $attrs['align'] ?? '';
		$class_name = $attrs['className'] ?? '';

		if ( ! in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
			$align = 'left';
		}

		return sprintf(
			'<table class="wp-block-social-links %1$s" width="%2$s" border="0" cellpadding="0" cellspacing="0" role="presentation">
				<tbody>
					<tr align="%3$s">
						%4$s
					</tr>
				</tbody>
        	</table>',
			esc_attr( $class_name ),
			'100%', // Width.
			esc_attr( $align ),
			'{social_links_content}'
		);
	}

	/**
	 * Gets the service icon URL.
	 *
	 * @param string $service The service name.
	 * @param string $image_type The image type. e.g 'white', 'brand', 'svg'.
	 * @return string The service icon URL.
	 */
	public function get_service_icon_url( $service, $image_type = '' ) {
		if ( empty( self::$core_social_link_services_cache ) ) {
			self::$core_social_link_services_cache = block_core_social_link_services();
		}

		if ( ! isset( self::$core_social_link_services_cache[ $service ] ) ) {
			// not in the list of core services.
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
	 * @param string $image_type The image type. e.g 'white', 'brand', 'black'.
	 * @return string The service PNG URL.
	 */
	public function get_service_png_url( $service, $image_type = 'white' ) {
		$file_name = "/icons/{$service}/{$service}-{$image_type}.png";
		return plugins_url( $file_name, __FILE__ );
	}

	/**
	 * Gets the service PNG path.
	 *
	 * @param string $service The service name.
	 * @param string $image_type The image type. e.g 'white', 'brand', 'black'.
	 * @return string The service PNG path.
	 */
	public function get_service_png_path( $service, $image_type = 'white' ) {
		$file_name = "/icons/{$service}/{$service}-{$image_type}.png";
		return __DIR__ . $file_name;
	}
}
