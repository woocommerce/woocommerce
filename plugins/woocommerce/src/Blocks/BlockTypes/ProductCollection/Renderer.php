<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use WP_HTML_Tag_Processor;

/**
 * Renderer class.
 * Handles rendering of the block and adds interactivity.
 */
class Renderer {

	/**
	 * The render state of the product collection block.
	 *
	 * @var array
	 */
	private $render_state = array(
		'has_results'          => false,
		'has_no_results_block' => false,
	);

	/**
	 * The Block with its attributes before it gets rendered
	 *
	 * @var array
	 */
	protected $parsed_block;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Interactivity API: Add navigation directives to the product collection block.
		add_filter( 'render_block_woocommerce/product-collection', array( $this, 'handle_rendering' ), 10, 2 );

		// Disable block render if the ProductTemplate block is empty.
		add_filter(
			'render_block_woocommerce/product-template',
			function ( $html ) {
				$this->render_state['has_results'] = ! empty( $html );
				return $html;
			},
			100,
			1
		);

		// Enable block render if the NoResults block is rendered.
		add_filter(
			'render_block_woocommerce/product-collection-no-results',
			function ( $html ) {
				$this->render_state['has_no_results_block'] = ! empty( $html );
				return $html;
			},
			100,
			1
		);
		add_filter( 'render_block_core/query-pagination', array( $this, 'add_navigation_link_directives' ), 10, 3 );

		// Provide location context into block's context.
		add_filter( 'render_block_context', array( $this, 'provide_location_context_for_inner_blocks' ), 11, 1 );
	}

	/**
	 * Set the parsed block.
	 *
	 * @param array $block The block to be parsed.
	 */
	public function set_parsed_block( $block ) {
		$this->parsed_block = $block;
	}

	/**
	 * Handle the rendering of the block.
	 *
	 * @param string $block_content The block content about to be rendered.
	 * @param array  $block The block being rendered.
	 *
	 * @return string
	 */
	public function handle_rendering( $block_content, $block ) {
		if ( $this->should_prevent_render() ) {
			return ''; // Prevent rendering.
		}

		// Reset the render state for the next render.
		$this->reset_render_state();

		return $this->enhance_product_collection_with_interactivity( $block_content, $block );
	}

	/**
	 * Check if the block should be prevented from rendering.
	 *
	 * @return bool
	 */
	private function should_prevent_render() {
		return ! $this->render_state['has_results'] && ! $this->render_state['has_no_results_block'];
	}

	/**
	 * Reset the render state.
	 */
	private function reset_render_state() {
		$this->render_state = array(
			'has_results'          => false,
			'has_no_results_block' => false,
		);
	}

	/**
	 * Enhances the Product Collection block with client-side pagination.
	 *
	 * This function identifies Product Collection blocks and adds necessary data attributes
	 * to enable client-side navigation. It also enqueues the Interactivity API runtime.
	 *
	 * @param string $block_content The HTML content of the block.
	 * @param array  $block         Block details, including its attributes.
	 *
	 * @return string Updated block content with added interactivity attributes.
	 */
	public function enhance_product_collection_with_interactivity( $block_content, $block ) {
		$is_product_collection_block = $block['attrs']['query']['isProductCollectionBlock'] ?? false;

		if ( $is_product_collection_block ) {
			wp_enqueue_script_module( 'woocommerce/product-collection' );

			$collection                     = $block['attrs']['collection'] ?? '';
			$is_enhanced_pagination_enabled = ! ( $block['attrs']['forcePageReload'] ?? false );

			$p = new \WP_HTML_Tag_Processor( $block_content );
			if ( $p->next_tag( array( 'class_name' => 'wp-block-woocommerce-product-collection' ) ) ) {
				$p->set_attribute( 'data-wp-interactive', 'woocommerce/product-collection' );
				$p->set_attribute( 'data-wp-init', 'callbacks.onRender' );
				$p->set_attribute(
					'data-wp-context',
					$collection ? wp_json_encode(
						array( 'collection' => $collection ),
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
					) : '{}'
				);

				if ( $is_enhanced_pagination_enabled && isset( $this->parsed_block ) ) {
					$p->set_attribute(
						'data-wp-router-region',
						'wc-product-collection-' . $this->parsed_block['attrs']['queryId']
					);
				}
			}

			// Check if dimensions need to be set and handle accordingly.
			$this->handle_block_dimensions( $p, $block );

			$block_content = $p->get_updated_html();
			$block_content = $this->add_store_notices_fallback( $block_content );
		}

		return $block_content;
	}

	/**
	 * Add a fallback store notices div to the block content.
	 *
	 * @param string $block_content The block content.
	 * @return string The updated block content.
	 */
	private function add_store_notices_fallback( $block_content ) {
		return preg_replace( '/(<div[^>]+>)/', '$1' . $this->render_interactivity_notices_region(), $block_content, 1 ) . '</div>';
	}

	/**
	 * Render interactivity API powered notices that can be added client-side. This reuses classes
	 * from the woocommerce/store-notices block to ensure style consistency.
	 *
	 * @return string The rendered store notices HTML.
	 */
	protected function render_interactivity_notices_region() {
		// Remove this extra div wrapper once we can use two context
		// directives in the top level div (https://github.com/WordPress/gutenberg/discussions/62720).
		ob_start();
		?>
		<div data-wp-context='woocommerce/store-notices::{"notices":[]}' style="display: contents;">
			<div data-wp-interactive="woocommerce/store-notices" class="wc-block-components-notices alignwide">
				<template
					data-wp-each--notice="context.notices"
					data-wp-each-key="context.notice.id"
				>
					<div
						class="wc-block-components-notice-banner"
						data-wp-init="callbacks.scrollIntoView"
						data-wp-class--is-error="state.isError"
						data-wp-class--is-success ="state.isSuccess"
						data-wp-class--is-info="state.isInfo"
						data-wp-class--is-dismissible="context.notice.dismissible"
						data-wp-bind--role="state.role"
					>
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
							<path data-wp-bind--d="state.iconPath"></path>
						</svg>
						<div class="wc-block-components-notice-banner__content">
							<span data-wp-init="callbacks.renderNoticeContent"></span>
						</div>
						<button
							data-wp-bind--hidden="!context.notice.dismissible"
							class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained"
							aria-label="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce' ); ?>"
							data-wp-on--click="actions.removeNotice"
						>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z" />
							</svg>
						</button>
					</div>
				</template>
			</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the styles for the list element (fixed width).
	 *
	 * @param string $fixed_width Fixed width value.
	 * @return string
	 */
	protected function get_list_styles( $fixed_width ) {
		$style = '';

		if ( isset( $fixed_width ) ) {
			$style .= sprintf( 'width:%s;', esc_attr( $fixed_width ) );
			$style .= 'margin: 0 auto;';
		}
		return $style;
	}

	/**
	 * Set the style attribute for fixed width.
	 *
	 * @param WP_HTML_Tag_Processor $p          The HTML tag processor.
	 * @param string                $fixed_width The fixed width value.
	 */
	private function set_fixed_width_style( $p, $fixed_width ) {
		$p->set_attribute( 'style', $this->get_list_styles( $fixed_width ) );
	}

	/**
	 * Handle block dimensions if width type is set to 'fixed'.
	 *
	 * @param WP_HTML_Tag_Processor $p     The HTML tag processor.
	 * @param array                 $block The block details.
	 */
	private function handle_block_dimensions( $p, $block ) {
		if ( isset( $block['attrs']['dimensions'] ) && isset( $block['attrs']['dimensions']['widthType'] ) ) {
			if ( 'fixed' === $block['attrs']['dimensions']['widthType'] ) {
				$this->set_fixed_width_style( $p, $block['attrs']['dimensions']['fixedWidth'] );
			}
		}
	}

	/**
	 * Add interactive links to all anchors inside the Query Pagination block.
	 * This enabled client-side navigation for the product collection block.
	 *
	 * @param string    $block_content The block content.
	 * @param array     $block         The full block, including name and attributes.
	 * @param \WP_Block $instance      The block instance.
	 */
	public function add_navigation_link_directives( $block_content, $block, $instance ) {
		$query_context                  = $instance->context['query'] ?? array();
		$is_product_collection_block    = $query_context['isProductCollectionBlock'] ?? false;
		$query_id                       = $instance->context['queryId'] ?? null;
		$parsed_query_id                = $this->parsed_block['attrs']['queryId'] ?? null;
		$is_enhanced_pagination_enabled = ! ( $this->parsed_block['attrs']['forcePageReload'] ?? false );

		// Only proceed if the block is a product collection block,
		// enhanced pagination is enabled and query IDs match.
		if ( $is_product_collection_block && $is_enhanced_pagination_enabled && $query_id === $parsed_query_id ) {
			$p = new \WP_HTML_Tag_Processor( $block_content );
			$p->next_tag( array( 'class_name' => 'wp-block-query-pagination' ) );

			while ( $p->next_tag( 'A' ) ) {
				if ( $p->has_class( 'wp-block-query-pagination-next' ) || $p->has_class( 'wp-block-query-pagination-previous' ) ) {
					$p->set_attribute( 'data-wp-on--click', 'woocommerce/product-collection::actions.navigate' );
					$p->set_attribute(
						'data-wp-key',
						$p->has_class( 'wp-block-query-pagination-next' )
							? 'product-collection-pagination--next'
							: 'product-collection-pagination--previous'
					);
					$p->set_attribute( 'data-wp-watch', 'woocommerce/product-collection::callbacks.prefetch' );
					$p->set_attribute( 'data-wp-on--mouseenter', 'woocommerce/product-collection::actions.prefetchOnHover' );
				} elseif ( $p->has_class( 'page-numbers' ) ) {
					$p->set_attribute( 'data-wp-on--click', 'woocommerce/product-collection::actions.navigate' );
					$p->set_attribute( 'data-wp-key', 'product-collection-pagination-numbers--' . $p->get_attribute( 'aria-label' ) );
				}
			}

			return $p->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Provides the location context to each inner block of the product collection block.
	 * Hint: Only blocks using the 'query' context will be affected.
	 *
	 * The sourceData structure depends on the context type as follows:
	 * - site:    [ ]
	 * - order:   [ 'orderId'    => int ]
	 * - cart:    [ 'productIds' => int[] ]
	 * - archive: [ 'taxonomy'   => string, 'termId' => int ]
	 * - product: [ 'productId'  => int ]
	 *
	 * @example array(
	 *   'type'       => 'product',
	 *   'sourceData' => array( 'productId' => 123 ),
	 * )
	 *
	 * @param array $context  The block context.
	 * @return array $context {
	 *     The block context including the product collection location context.
	 *
	 *     @type array $productCollectionLocation {
	 *         @type string  $type        The context type. Possible values are 'site', 'order', 'cart', 'archive', 'product'.
	 *         @type array   $sourceData  The context source data. Can be the product ID of the viewed product, the order ID of the current order viewed, etc. See structure above for more details.
	 *     }
	 * }
	 */
	public function provide_location_context_for_inner_blocks( $context ) {
		// Run only on frontend.
		// This is needed to avoid SSR renders while in editor. @see https://github.com/woocommerce/woocommerce/issues/45181.
		if ( is_admin() || \WC()->is_rest_api_request() ) {
			return $context;
		}

		// Target only product collection's inner blocks that use the 'query' context.
		if ( ! isset( $context['query'] ) || ! isset( $context['query']['isProductCollectionBlock'] ) || ! $context['query']['isProductCollectionBlock'] ) {
			return $context;
		}

		$is_in_single_product                 = isset( $context['singleProduct'] ) && ! empty( $context['postId'] );
		$context['productCollectionLocation'] = $is_in_single_product ? array(
			'type'       => 'product',
			'sourceData' => array(
				'productId' => absint( $context['postId'] ),
			),
		) : $this->get_location_context();

		return $context;
	}

	/**
	 * Get the global location context.
	 * Serve as a runtime cache for the location context.
	 *
	 * @see ProductCollectionUtils::parse_frontend_location_context()
	 *
	 * @return array The location context.
	 */
	private function get_location_context() {
		static $location_context = null;
		if ( null === $location_context ) {
			$location_context = ProductCollectionUtils::parse_frontend_location_context();
		}
		return $location_context;
	}
}
