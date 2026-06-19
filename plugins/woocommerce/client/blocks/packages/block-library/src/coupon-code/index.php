<?php
/**
 * Server-side rendering of the `woocommerce/coupon-code` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Table_Wrapper_Helper;

/**
 * Default displayed when coupon code is generated while sending the email.
 */
const BLOCK_WOOCOMMERCE_COUPON_CODE_PLACEHOLDER = 'XXXX-XXXXXX-XXXX';

/**
 * Default styles for the coupon code element.
 */
const BLOCK_WOOCOMMERCE_COUPON_CODE_DEFAULT_STYLES = array(
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
 * Renders the `woocommerce/coupon-code` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered coupon code block.
 */
function render_block_woocommerce_coupon_code( $attributes, $content, $block ): string {
	$parsed_block = $block instanceof WP_Block ? $block->parsed_block : array();
	$attributes   = get_block_woocommerce_coupon_code_attributes( $parsed_block, $attributes );
	$source       = $attributes['source'] ?? 'createNew';
	$coupon_code  = 'createNew' === $source
		? BLOCK_WOOCOMMERCE_COUPON_CODE_PLACEHOLDER
		: get_block_woocommerce_coupon_code_value( $attributes );

	if ( empty( $coupon_code ) ) {
		return '';
	}

	$rendering_context = get_block_woocommerce_coupon_code_rendering_context( $block );
	$coupon_html       = get_block_woocommerce_coupon_code_html( $coupon_code, $attributes, $rendering_context );

	return get_block_woocommerce_coupon_code_email_wrapper( $coupon_html, $parsed_block );
}

/**
 * Gets block attributes from parsed block data or fallback attributes.
 *
 * @since 11.0.0
 *
 * @param array $parsed_block Parsed block data.
 * @param mixed $fallback     Fallback attributes.
 * @return array Block attributes.
 */
function get_block_woocommerce_coupon_code_attributes( array $parsed_block, $fallback ): array {
	$attributes = $parsed_block['attrs'] ?? $fallback ?? array();
	return is_array( $attributes ) ? $attributes : array();
}

/**
 * Gets the saved coupon code value from block attributes.
 *
 * @since 11.0.0
 *
 * @param array $attributes Block attributes.
 * @return string Coupon code.
 */
function get_block_woocommerce_coupon_code_value( array $attributes ): string {
	$coupon_code = $attributes['couponCode'] ?? '';
	return is_string( $coupon_code ) ? $coupon_code : '';
}

/**
 * Gets the rendering context for resolving email styles.
 *
 * @since 11.0.0
 *
 * @param WP_Block|null $block Block instance.
 * @return Rendering_Context Rendering context.
 */
function get_block_woocommerce_coupon_code_rendering_context( $block ): Rendering_Context {
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
 * Builds the coupon code HTML element.
 *
 * @since 11.0.0
 *
 * @param string            $coupon_code       Coupon code text.
 * @param array             $attributes        Block attributes.
 * @param Rendering_Context $rendering_context Rendering context.
 * @return string Coupon code HTML.
 */
function get_block_woocommerce_coupon_code_html( string $coupon_code, array $attributes, Rendering_Context $rendering_context ): string {
	$block_styles = Styles_Helper::get_block_styles(
		$attributes,
		$rendering_context,
		array( 'border', 'background-color', 'color', 'typography', 'spacing' )
	);

	$declarations = $block_styles['declarations'] ?? array();

	if ( ! has_block_woocommerce_coupon_code_valid_background_color( $declarations ) ) {
		$declarations['background-color'] = get_block_woocommerce_coupon_code_background_color( $attributes, $rendering_context );
	}

	$merged_styles = array_merge( BLOCK_WOOCOMMERCE_COUPON_CODE_DEFAULT_STYLES, $declarations );
	$css           = WP_Style_Engine::compile_css( $merged_styles, '' );

	return sprintf(
		'<span class="woocommerce-coupon-code" style="%s">%s</span>',
		esc_attr( $css ),
		esc_html( $coupon_code )
	);
}

/**
 * Checks if the style declarations include a valid background color.
 *
 * @since 11.0.0
 *
 * @param array $declarations CSS declarations.
 * @return bool Whether a valid background color exists.
 */
function has_block_woocommerce_coupon_code_valid_background_color( array $declarations ): bool {
	if ( empty( $declarations['background-color'] ) ) {
		return false;
	}

	return is_block_woocommerce_coupon_code_css_color_value( $declarations['background-color'] );
}

/**
 * Resolves the background color from attributes.
 *
 * @since 11.0.0
 *
 * @param array             $attributes        Block attributes.
 * @param Rendering_Context $rendering_context Rendering context.
 * @return string Resolved color value.
 */
function get_block_woocommerce_coupon_code_background_color( array $attributes, Rendering_Context $rendering_context ): string {
	if ( empty( $attributes['backgroundColor'] ) ) {
		return BLOCK_WOOCOMMERCE_COUPON_CODE_DEFAULT_STYLES['background-color'];
	}

	$color_slug = $attributes['backgroundColor'];

	if ( ! is_string( $color_slug ) ) {
		return BLOCK_WOOCOMMERCE_COUPON_CODE_DEFAULT_STYLES['background-color'];
	}

	$normalized = Styles_Helper::get_normalized_block_styles( $attributes, $rendering_context );
	$color      = $normalized['color']['background'] ?? '';

	if ( is_block_woocommerce_coupon_code_css_color_value( $color ) ) {
		return $color;
	}

	$translated = $rendering_context->translate_slug_to_color( $color_slug );
	if ( is_block_woocommerce_coupon_code_css_color_value( $translated ) ) {
		return $translated;
	}

	return BLOCK_WOOCOMMERCE_COUPON_CODE_DEFAULT_STYLES['background-color'];
}

/**
 * Checks if a string is a supported CSS color value.
 *
 * @since 11.0.0
 *
 * @param string $value Value to check.
 * @return bool Whether the value looks like a CSS color.
 */
function is_block_woocommerce_coupon_code_css_color_value( string $value ): bool {
	return str_starts_with( $value, '#' )
		|| str_starts_with( $value, 'rgb' )
		|| str_starts_with( $value, 'hsl' );
}

/**
 * Wraps coupon HTML in an email-compatible table structure.
 *
 * @since 11.0.0
 *
 * @param string $coupon_html Coupon HTML content.
 * @param array  $parsed_block Parsed block data.
 * @return string Wrapped coupon HTML.
 */
function get_block_woocommerce_coupon_code_email_wrapper( string $coupon_html, array $parsed_block ): string {
	$align = get_block_woocommerce_coupon_code_alignment( $parsed_block );

	$table_attrs = array(
		'style' => WP_Style_Engine::compile_css(
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
		'style' => WP_Style_Engine::compile_css(
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
 * Gets the coupon code alignment.
 *
 * @since 11.0.0
 *
 * @param array $parsed_block Parsed block data.
 * @return string Alignment.
 */
function get_block_woocommerce_coupon_code_alignment( array $parsed_block ): string {
	$allowed = array( 'left', 'center', 'right' );
	$align   = $parsed_block['attrs']['align'] ?? 'center';

	if ( ! is_string( $align ) || ! in_array( $align, $allowed, true ) ) {
		return 'center';
	}

	return $align;
}

/**
 * Adds coupon types to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_coupon_code_asset_data(): void {
	if ( ! function_exists( 'wc_get_coupon_types' ) ) {
		return;
	}

	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

	if ( ! $asset_data_registry->exists( 'couponTypes' ) ) {
		$asset_data_registry->add( 'couponTypes', wc_get_coupon_types() );
	}
}

/**
 * Registers the `woocommerce/coupon-code` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_coupon_code(): void {
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_coupon_code',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_coupon_code' );
add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_coupon_code_asset_data' );
