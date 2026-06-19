<?php
/**
 * Server-side rendering of the `woocommerce/product-filters` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Internal\ProductFilters\Params;

/**
 * Add Product Filters settings to the editor asset data registry.
 *
 * @since 11.0.0
 */
function add_block_woocommerce_product_filters_asset_data(): void {
	$asset_data_registry = Package::container()->get( AssetDataRegistry::class );

	if ( ! $asset_data_registry->exists( 'globalStylesColors' ) ) {
		$asset_data_registry->add( 'globalStylesColors', wp_get_global_styles( array( 'color' ) ) );
	}
}

add_action( 'enqueue_block_editor_assets', 'add_block_woocommerce_product_filters_asset_data' );

/**
 * Load frontend data needed by the Product Filters interactivity store.
 *
 * @since 11.0.0
 */
function block_woocommerce_product_filters_enqueue_data(): void {
	BlocksSharedState::load_store_config( 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce' );

	// Classic themes do not support client-side navigation on product archive pages.
	$is_product_archive = is_shop() || is_product_taxonomy() || ( is_search() && 'product' === get_post_type() );
	if ( ! wp_is_block_theme() && $is_product_archive ) {
		wp_interactivity_config( 'core/router', array( 'clientNavigationDisabled' => true ) );
	}
}

/**
 * Parse the filter parameters from the URL.
 * For now we only get the global query params from the URL. In the future,
 * we should get the query params based on $query_id.
 *
 * @since 11.0.0
 *
 * @param int $query_id Query ID.
 * @return array Parsed filter params.
 */
function block_woocommerce_product_filters_get_filter_params( int $query_id ): array {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$parsed_url  = wp_parse_url( esc_url_raw( $request_uri ) );

	if ( empty( $parsed_url['query'] ) ) {
		return array();
	}

	parse_str( $parsed_url['query'], $url_query_params );

	$filter_param_keys = wc_get_container()->get( Params::class )->get_param_keys();

	return array_filter(
		$url_query_params,
		function ( $key ) use ( $filter_param_keys ): bool {
			return in_array( $key, $filter_param_keys, true );
		},
		ARRAY_FILTER_USE_KEY
	);
}

/**
 * Get the canonical URL without pagination.
 *
 * @since 11.0.0
 *
 * @param array $filter_params Filter parameters.
 * @return string Canonical URL without pagination.
 */
function block_woocommerce_product_filters_get_canonical_url_no_pagination( array $filter_params ): string {
	$canonical_url_no_pagination = is_singular() ? get_permalink() : get_pagenum_link( 1 );
	$decoded_url                 = html_entity_decode( false === $canonical_url_no_pagination ? '' : $canonical_url_no_pagination, ENT_QUOTES, get_bloginfo( 'charset' ) );
	$parsed_url                  = wp_parse_url( $decoded_url );

	if ( empty( $filter_params ) || empty( $parsed_url['query'] ) ) {
		return $decoded_url;
	}

	foreach ( array_keys( $filter_params ) as $key ) {
		$parsed_url['query'] = remove_query_arg( $key, $parsed_url['query'] );
	}

	$url = '';

	if ( isset( $parsed_url['scheme'] ) ) {
		$url .= $parsed_url['scheme'] . '://';
	}

	if ( isset( $parsed_url['host'] ) ) {
		$url .= $parsed_url['host'];
	}

	if ( isset( $parsed_url['port'] ) ) {
		$url .= ':' . $parsed_url['port'];
	}

	if ( isset( $parsed_url['path'] ) ) {
		$url .= $parsed_url['path'];
	}

	if ( ! empty( $parsed_url['query'] ) ) {
		$url .= '?' . $parsed_url['query'];
	}

	if ( isset( $parsed_url['fragment'] ) ) {
		$url .= '#' . $parsed_url['fragment'];
	}

	return $url;
}

/**
 * Get SVG icon markup for a given icon name.
 *
 * @since 11.0.0
 *
 * @param string $name The icon name.
 * @return string SVG markup for the icon, or empty string if not found.
 */
function block_woocommerce_product_filters_get_svg_icon( string $name ): string {
	$icons = array(
		'close'         => '<path d="M12 13.0607L15.7123 16.773L16.773 15.7123L13.0607 12L16.773 8.28772L15.7123 7.22706L12 10.9394L8.28771 7.22705L7.22705 8.28771L10.9394 12L7.22706 15.7123L8.28772 16.773L12 13.0607Z" fill="currentColor"/>',
		'filter-icon-2' => '<path d="M10 17.5H14V16H10V17.5ZM6 6V7.5H18V6H6ZM8 12.5H16V11H8V12.5Z" fill="currentColor"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">%s</svg>',
		$icons[ $name ]
	);
}

/**
 * Get CSS custom properties from block attributes.
 *
 * @since 11.0.0
 *
 * @param array $attributes Block attributes.
 * @return string CSS custom properties string.
 */
function block_woocommerce_product_filters_get_css_variables( array $attributes ): string {
	$styles = array();

	$bg = StyleAttributesUtils::get_background_color_class_and_style( $attributes );
	if ( ! empty( $bg['value'] ) ) {
		$styles[] = sprintf( '--wc-product-filters-background-color: %s', $bg['value'] );
	}

	$text = StyleAttributesUtils::get_text_color_class_and_style( $attributes );
	if ( ! empty( $text['value'] ) ) {
		$styles[] = sprintf( '--wc-product-filters-text-color: %s', $text['value'] );
	}

	$block_gap = $attributes['style']['spacing']['blockGap'] ?? '';
	if ( $block_gap ) {
		$styles[] = sprintf( '--wc-product-filter-block-spacing: %s', StyleAttributesUtils::get_spacing_value( $block_gap ) );
	}

	return $styles ? implode( ';', $styles ) . ';' : '';
}

/**
 * Generate a unique navigation ID for the block.
 *
 * @since 11.0.0
 *
 * @param WP_Block $block Block instance.
 * @return string Unique navigation ID.
 */
function block_woocommerce_product_filters_generate_navigation_id( WP_Block $block ): string {
	$encoded_block = wp_json_encode(
		array(
			'attrs'       => $block->parsed_block['attrs'] ?? array(),
			'innerBlocks' => $block->parsed_block['innerBlocks'] ?? array(),
		)
	);

	return sprintf(
		'wc-product-filters-%s',
		md5( false === $encoded_block ? '' : $encoded_block )
	);
}

/**
 * Renders the `woocommerce/product-filters` block on the server.
 *
 * @since 11.0.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block output.
 */
function render_block_woocommerce_product_filters( array $attributes, string $content, $block ): string {
	if ( ! $block instanceof WP_Block ) {
		return $content;
	}

	block_woocommerce_product_filters_enqueue_data();
	wp_enqueue_script( 'wc-settings' );

	$query_id      = $block->context['queryId'] ?? 0;
	$filter_params = block_woocommerce_product_filters_get_filter_params( intval( $query_id ) );

	wp_interactivity_config(
		'woocommerce/product-filters',
		array(
			'canonicalUrl' => block_woocommerce_product_filters_get_canonical_url_no_pagination( $filter_params ),
		)
	);

	/**
	 * Filter hook to modify the selected filter items.
	 *
	 * @since 9.7.0
	 */
	$active_filters = apply_filters( 'woocommerce_blocks_product_filters_selected_items', array(), $filter_params );

	usort(
		$active_filters,
		function ( $a, $b ): int {
			return strnatcmp( $a['activeLabel'], $b['activeLabel'] );
		}
	);

	$block_context = array_merge(
		$block->context,
		array(
			'filterParams'  => $filter_params,
			'activeFilters' => $active_filters,
		)
	);
	$inner_blocks  = array_reduce(
		$block->parsed_block['innerBlocks'] ?? array(),
		function ( string $carry, array $parsed_block ) use ( $block_context ): string {
			$carry .= ( new WP_Block( $parsed_block, $block_context ) )->render();
			return $carry;
		},
		''
	);

	$interactivity_context = array(
		'params'          => $filter_params,
		'activeFilters'   => $active_filters,
		// Null when not a descendant of a Product Collection block, so the
		// frontend can fall back to the global interactivity config.
		'forcePageReload' => isset( $block->context['forcePageReload'] ) ? (bool) $block->context['forcePageReload'] : null,
	);

	$show_filter_drawer = ! isset( $attributes['showFilterDrawer'] ) || false !== $attributes['showFilterDrawer'];
	$wrapper_classes    = array( 'wc-block-product-filters' );
	if ( ! $show_filter_drawer ) {
		$wrapper_classes[] = 'is-filter-drawer-disabled';
	}

	$wrapper_attributes = array(
		'class'                         => implode( ' ', $wrapper_classes ),
		'data-wp-interactive'           => 'woocommerce/product-filters',
		'data-wp-init--colors'          => 'callbacks.initColors',
		'data-wp-watch--active-filters' => 'callbacks.syncActiveFiltersWithServer',
		'data-wp-context'               => (string) wp_json_encode( $interactivity_context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
		'style'                         => block_woocommerce_product_filters_get_css_variables( $attributes ),
	);

	if ( $show_filter_drawer ) {
		$wrapper_attributes['data-wp-watch--scrolling']         = 'callbacks.scrollLimit';
		$wrapper_attributes['data-wp-on--keyup']                = 'actions.closeOverlayOnEscape';
		$wrapper_attributes['data-wp-class--is-overlay-opened'] = 'context.isOverlayOpened';
	}

	// TODO: Remove this conditional once the fix is released in WP. https://github.com/woocommerce/gutenberg/pull/4.
	if ( ! isset( $block->context['productCollectionLocation'] ) ) {
		$wrapper_attributes['data-wp-router-region'] = block_woocommerce_product_filters_generate_navigation_id( $block );
	}

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $show_filter_drawer ) : ?>
			<button
				type="button"
				class="wc-block-product-filters__open-overlay"
				data-wp-on--click="actions.openOverlay"
			>
				<?php echo block_woocommerce_product_filters_get_svg_icon( 'filter-icon-2' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html__( 'Filter products', 'woocommerce' ); ?></span>
			</button>
			<div class="wc-block-product-filters__overlay">
				<div class="wc-block-product-filters__overlay-wrapper">
					<div
						class="wc-block-product-filters__overlay-dialog"
						role="dialog"
						aria-label="<?php echo esc_html__( 'Product Filters', 'woocommerce' ); ?>"
					>
						<header class="wc-block-product-filters__overlay-header">
							<button
								type="button"
								class="wc-block-product-filters__close-overlay"
								data-wp-on--click="actions.closeOverlay"
							>
								<span><?php echo esc_html__( 'Close', 'woocommerce' ); ?></span>
								<?php echo block_woocommerce_product_filters_get_svg_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</button>
						</header>
						<div class="wc-block-product-filters__overlay-content">
							<?php echo $inner_blocks; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
						<footer
							class="wc-block-product-filters__overlay-footer"
						>
							<button
								type="button"
								class="wc-block-product-filters__apply wp-element-button"
								data-wp-interactive="woocommerce/product-filters"
								data-wp-on--click="actions.closeOverlay"
							>
								<span><?php echo esc_html__( 'Apply', 'woocommerce' ); ?></span>
							</button>
						</footer>
					</div>
				</div>
			</div>
		<?php else : ?>
			<div class="wc-block-product-filters__content">
				<?php echo $inner_blocks; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	$output = ob_get_clean();
	return false === $output ? '' : $output;
}

/**
 * Registers the `woocommerce/product-filters` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_product_filters(): void {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/product-filters' ) ) {
		return;
	}

	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => 'render_block_woocommerce_product_filters',
		)
	);
}

add_action( 'init', 'register_block_woocommerce_product_filters' );
