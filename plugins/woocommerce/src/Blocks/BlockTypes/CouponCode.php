<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;
use WP_Block;

/**
 * CouponCode block for displaying coupon codes in emails.
 *
 * @since 10.5.0
 */
class CouponCode extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'coupon-code';

	/**
	 * Default styles for the coupon code element.
	 */
	private const DEFAULT_STYLES = array(
		'font-size'        => '1.2em',
		'padding'          => '12px 20px',
		'display'          => 'inline-block',
		'border'           => '2px dashed #cccccc',
		'border-radius'    => '4px',
		'box-sizing'       => 'border-box',
		'color'            => '#000000',
		'background-color' => '#f5f5f5',
		'text-align'       => 'center',
		'font-weight'      => 'bold',
		'letter-spacing'   => '1px',
	);

	/**
	 * Get the editor script handle for this block type.
	 *
	 * @param string|null $key Data to get. Valid keys: "handle", "path", "dependencies".
	 * @return array|string|null
	 */
	protected function get_block_type_editor_script( $key = null ) {
		$script = array(
			'handle'       => 'wc-' . $this->block_name . '-block',
			'path'         => $this->asset_api->get_block_asset_build_path( $this->block_name ),
			'dependencies' => array( 'wc-blocks' ),
		);
		return null === $key ? $script : ( $script[ $key ] ?? null );
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Render the coupon code block.
	 *
	 * @param array         $attributes Block attributes.
	 * @param string        $content Block content.
	 * @param WP_Block|null $block Block instance.
	 * @return string
	 */
	protected function render( $attributes, $content, $block ) {
		$parsed_block = $block instanceof WP_Block ? $block->parsed_block : array();
		$attributes   = $this->get_block_attributes( $parsed_block, $attributes );
		$coupon_code  = $this->get_coupon_code( $attributes );

		if ( empty( $coupon_code ) ) {
			return '';
		}

		$rendering_context = $this->get_rendering_context( $block );
		$coupon_html       = $this->build_coupon_html( $coupon_code, $attributes, $rendering_context );

		return $this->wrap_for_email( $coupon_html, $parsed_block );
	}

	/**
	 * Get block attributes from parsed block or fallback.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @param array $fallback Fallback attributes.
	 * @return array
	 */
	private function get_block_attributes( array $parsed_block, $fallback ): array {
		$attributes = $parsed_block['attrs'] ?? $fallback ?? array();
		return is_array( $attributes ) ? $attributes : array();
	}

	/**
	 * Extract coupon code from attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	private function get_coupon_code( array $attributes ): string {
		$coupon_code = $attributes['couponCode'] ?? '';
		return is_string( $coupon_code ) ? $coupon_code : '';
	}

	/**
	 * Get rendering context from block or create a new one.
	 *
	 * @param WP_Block|null $block Block instance.
	 * @return Rendering_Context
	 */
	private function get_rendering_context( $block ): Rendering_Context {
		if ( $block instanceof WP_Block
			&& isset( $block->context['renderingContext'] )
			&& $block->context['renderingContext'] instanceof Rendering_Context
		) {
			return $block->context['renderingContext'];
		}

		$theme_controller = Email_Editor_Container::container()->get( Theme_Controller::class );
		return new Rendering_Context( $theme_controller->get_theme(), array() );
	}

	/**
	 * Build the coupon code HTML element with styles.
	 *
	 * @param string            $coupon_code Coupon code text.
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context for style resolution.
	 * @return string
	 */
	private function build_coupon_html( string $coupon_code, array $attributes, Rendering_Context $rendering_context ): string {
		$block_styles = Styles_Helper::get_block_styles(
			$attributes,
			$rendering_context,
			array( 'border', 'background-color', 'color', 'typography', 'spacing' )
		);

		$declarations = $block_styles['declarations'] ?? array();

		if ( ! $this->has_valid_background_color( $declarations ) ) {
			$declarations['background-color'] = $this->resolve_background_color( $attributes, $rendering_context );
		}

		$merged_styles = array_merge( self::DEFAULT_STYLES, $declarations );
		$css           = \WP_Style_Engine::compile_css( $merged_styles, '' );

		return sprintf(
			'<span class="woocommerce-coupon-code" style="%s">%s</span>',
			esc_attr( $css ),
			esc_html( $coupon_code )
		);
	}

	/**
	 * Check if declarations contain a valid CSS background color.
	 *
	 * @param array $declarations CSS declarations.
	 * @return bool
	 */
	private function has_valid_background_color( array $declarations ): bool {
		if ( empty( $declarations['background-color'] ) ) {
			return false;
		}
		return $this->is_css_color_value( $declarations['background-color'] );
	}

	/**
	 * Resolve background color from attributes, translating color slugs if needed.
	 *
	 * @param array             $attributes Block attributes.
	 * @param Rendering_Context $rendering_context Rendering context.
	 * @return string Resolved color value or default.
	 */
	private function resolve_background_color( array $attributes, Rendering_Context $rendering_context ): string {
		if ( empty( $attributes['backgroundColor'] ) ) {
			return self::DEFAULT_STYLES['background-color'];
		}

		$color_slug = $attributes['backgroundColor'];

		// Try to get color from normalized styles (handles slug translation).
		$normalized = Styles_Helper::get_normalized_block_styles( $attributes, $rendering_context );
		$color      = $normalized['color']['background'] ?? '';

		if ( $this->is_css_color_value( $color ) ) {
			return $color;
		}

		// Fallback: try direct translation if normalization returned the slug unchanged.
		$translated = $rendering_context->translate_slug_to_color( $color_slug );
		if ( $this->is_css_color_value( $translated ) ) {
			return $translated;
		}

		return self::DEFAULT_STYLES['background-color'];
	}

	/**
	 * Check if a string is a valid CSS color value (hex, rgb, or hsl).
	 *
	 * @param string $value Value to check.
	 * @return bool
	 */
	private function is_css_color_value( string $value ): bool {
		return str_starts_with( $value, '#' )
			|| str_starts_with( $value, 'rgb' )
			|| str_starts_with( $value, 'hsl' );
	}

	/**
	 * Wrap coupon HTML in an email-compatible table structure.
	 *
	 * @param string $coupon_html Coupon HTML content.
	 * @param array  $parsed_block Parsed block data.
	 * @return string
	 */
	private function wrap_for_email( string $coupon_html, array $parsed_block ): string {
		$align = $this->get_alignment( $parsed_block );

		$table_attrs = array(
			'style' => \WP_Style_Engine::compile_css(
				array(
					'border-collapse' => 'collapse',
					'width'           => '100%',
				),
				''
			),
			'width' => '100%',
		);

		$cell_attrs = array(
			'class' => 'email-coupon-code-cell',
			'style' => \WP_Style_Engine::compile_css(
				array(
					'padding'    => '10px 0',
					'text-align' => $align,
				),
				''
			),
			'align' => $align,
		);

		return Table_Wrapper_Helper::render_table_wrapper( $coupon_html, $table_attrs, $cell_attrs );
	}

	/**
	 * Get alignment from parsed block attributes.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @return string
	 */
	private function get_alignment( array $parsed_block ): string {
		$allowed = array( 'left', 'center', 'right' );
		$align   = $parsed_block['attrs']['align'] ?? 'center';

		if ( ! is_string( $align ) || ! in_array( $align, $allowed, true ) ) {
			return 'center';
		}

		return $align;
	}
}
