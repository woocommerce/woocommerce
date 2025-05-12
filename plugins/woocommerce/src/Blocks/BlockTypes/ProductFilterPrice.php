<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Internal\ProductFilters\FilterDataProvider;
use Automattic\WooCommerce\Internal\ProductFilters\QueryClauses;

/**
 * Product Filter: Price Block.
 */
final class ProductFilterPrice extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-price';

	const MIN_PRICE_QUERY_VAR = 'min_price';
	const MAX_PRICE_QUERY_VAR = 'max_price';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();

		add_filter( 'woocommerce_blocks_product_filters_param_keys', array( $this, 'get_filter_query_param_keys' ), 10, 2 );
		add_filter( 'woocommerce_blocks_product_filters_selected_items', array( $this, 'prepare_selected_filters' ), 10, 2 );
	}

	/**
	 * Prepare the active filter items.
	 *
	 * @param array $items  The active filter items.
	 * @param array $params The query param parsed from the URL.
	 * @return array Active filters items.
	 */
	public function prepare_selected_filters( $items, $params ) {
		$min_price           = intval( $params[ self::MIN_PRICE_QUERY_VAR ] ?? 0 );
		$max_price           = intval( $params[ self::MAX_PRICE_QUERY_VAR ] ?? 0 );
		$formatted_min_price = $min_price ? html_entity_decode( wp_strip_all_tags( wc_price( $min_price, array( 'decimals' => 0 ) ) ) ) : null;
		$formatted_max_price = $max_price ? html_entity_decode( wp_strip_all_tags( wc_price( $max_price, array( 'decimals' => 0 ) ) ) ) : null;

		if ( ! $formatted_min_price && ! $formatted_max_price ) {
			return $items;
		}

		$item = array(
			'type' => 'price',
		);

		if ( $formatted_min_price && $formatted_max_price ) {
			$item['activeLabel'] = sprintf(
				/* translators: %1$s and %2$s are the formatted minimum and maximum prices respectively. */
				__( 'Price: %1$s - %2$s', 'woocommerce' ),
				$formatted_min_price,
				$formatted_max_price
			);
			$item['value'] = "{$min_price}|{$max_price}";
		}

		if ( ! $formatted_min_price ) {
			/* translators: %s is the formatted maximum price. */
			$item['activeLabel'] = sprintf( __( 'Price: Up to %s', 'woocommerce' ), $formatted_max_price );
			$item['value']       = "|{$max_price}";
		}

		if ( ! $formatted_max_price ) {
			/* translators: %s is the formatted minimum price. */
			$item['activeLabel'] = sprintf( __( 'Price: From %s', 'woocommerce' ), $formatted_min_price );
			$item['value']       = "{$min_price}|";
		}

		$items[] = $item;

		return $items;
	}

	/**
	 * Register the query param keys.
	 *
	 * @param array $filter_param_keys The active filters data.
	 * @param array $url_param_keys    The query param parsed from the URL.
	 *
	 * @return array Active filters param keys.
	 */
	public function get_filter_query_param_keys( $filter_param_keys, $url_param_keys ) {
		$price_param_keys = array_filter(
			$url_param_keys,
			function ( $param ) {
				return self::MIN_PRICE_QUERY_VAR === $param || self::MAX_PRICE_QUERY_VAR === $param;
			}
		);

		return array_merge(
			$filter_param_keys,
			$price_param_keys
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}

		$price_range   = $this->get_filtered_price( $block );
		$min_range     = $price_range['min_price'] ?? 0;
		$max_range     = $price_range['max_price'] ?? 0;
		$filter_params = $block->context['filterParams'] ?? array();
		$min_price     = intval( $filter_params[ self::MIN_PRICE_QUERY_VAR ] ?? $min_range );
		$max_price     = intval( $filter_params[ self::MAX_PRICE_QUERY_VAR ] ?? $max_range );

		$formatted_min_price = html_entity_decode( wp_strip_all_tags( wc_price( $min_price, array( 'decimals' => 0 ) ) ) );
		$formatted_max_price = html_entity_decode( wp_strip_all_tags( wc_price( $max_price, array( 'decimals' => 0 ) ) ) );

		$filter_context = array(
			'price' => array(
				'minPrice' => $min_price,
				'maxPrice' => $max_price,
				'minRange' => $min_range,
				'maxRange' => $max_range,
			),
		);

		$wrapper_attributes = array(
			'data-wp-interactive' => 'woocommerce/product-filters',
			'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
			'data-wp-context'     => wp_json_encode(
				array(
					'filterType' => 'price',
					'minRange'   => $min_range,
					'maxRange'   => $max_range,
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
			),
		);

		wp_interactivity_config(
			'woocommerce/product-filters',
			array(
				'activePriceLabelTemplates' => array(
					/* translators: {{min}} and {{max}} are the formatted minimum and maximum prices respectively. */
					'minAndMax' => __( 'Price: {{min}} - {{max}}', 'woocommerce' ),
					/* translators: {{max}} is the formatted maximum price. */
					'maxOnly'   => __( 'Price: Up to {{max}}', 'woocommerce' ),
					/* translators: {{min}} is the formatted minimum price. */
					'minOnly'   => __( 'Price: From {{min}}', 'woocommerce' ),
				),
			)
		);

		wp_interactivity_state(
			'woocommerce/product-filters',
			array(
				'formattedMinPrice' => $formatted_min_price,
				'formattedMaxPrice' => $formatted_max_price,
				'minPrice'          => $min_price,
				'maxPrice'          => $max_price,
			)
		);

		if ( $min_range === $max_range || ! $max_range ) {
			$wrapper_attributes['hidden'] = true;
			$wrapper_attributes['class']  = 'wc-block-product-filter--hidden';
			return sprintf(
				'<div %1$s>%2$s</div>',
				get_block_wrapper_attributes( $wrapper_attributes ),
				array_reduce(
					$block->parsed_block['innerBlocks'],
					function ( $carry, $parsed_block ) {
						$carry .= render_block( $parsed_block );
						return $carry;
					},
					''
				)
			);
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( $wrapper_attributes ),
			array_reduce(
				$block->parsed_block['innerBlocks'],
				function ( $carry, $parsed_block ) use ( $filter_context ) {
					$carry .= ( new \WP_Block( $parsed_block, array( 'filterData' => $filter_context ) ) )->render();
					return $carry;
				},
				''
			)
		);
	}

	/**
	 * Retrieve the price filter data for current block.
	 *
	 * @param WP_Block $block Block instance.
	 */
	private function get_filtered_price( $block ) {
		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		unset( $query_vars['min_price'], $query_vars['max_price'] );

		if ( ! empty( $query_vars['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query_vars['meta_query'] = ProductCollectionUtils::remove_query_array( $query_vars['meta_query'], 'key', '_price' );
		}

		if ( isset( $query_vars['taxonomy'] ) && false !== strpos( $query_vars['taxonomy'], 'pa_' ) ) {
			unset(
				$query_vars['taxonomy'],
				$query_vars['term']
			);
		}

		$container     = wc_get_container();
		$price_results = $container->get( FilterDataProvider::class )->with( $container->get( QueryClauses::class ) )->get_filtered_price( $query_vars );

		return array(
			'min_price' => intval( floor( floatval( $price_results['min_price'] ?? 0 ) ) ),
			'max_price' => intval( ceil( floatval( $price_results['max_price'] ?? 0 ) ) ),
		);
	}
}
