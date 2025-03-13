<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Product Filter: Price Block.
 */
final class ProductFilterPrice extends AbstractBlock {

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
			'type'  => 'price',
			'value' => "{$min_price}-{$max_price}",
			'price' => array(),
		);

		if ( $min_price ) {
			$item['price']['min'] = $min_price;
		}

		if ( $max_price ) {
			$item['price']['max'] = $max_price;
		}

		if ( $formatted_min_price && $formatted_max_price ) {
			$item['label'] = sprintf(
				/* translators: %1$s and %2$s are the formatted minimum and maximum prices respectively. */
				__( 'Price: %1$s - %2$s', 'woocommerce' ),
				$formatted_min_price,
				$formatted_max_price
			);
		}

		if ( ! $formatted_min_price ) {
			/* translators: %s is the formatted maximum price. */
			$item['label'] = sprintf( __( 'Price: Up to %s', 'woocommerce' ), $formatted_max_price );
		}

		if ( ! $formatted_max_price ) {
			/* translators: %s is the formatted minimum price. */
			$item['label'] = sprintf( __( 'Price: From %s', 'woocommerce' ), $formatted_min_price );
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

		wp_enqueue_script_module( $this->get_full_block_name() );

		$price_range   = $this->get_filtered_price( $block );
		$min_range     = $price_range['min_price'] ?? 0;
		$max_range     = $price_range['max_price'] ?? 0;
		$filter_params = $block->context['filterParams'] ?? array();
		$min_price     = intval( $filter_params[ self::MIN_PRICE_QUERY_VAR ] ?? $min_range );
		$max_price     = intval( $filter_params[ self::MAX_PRICE_QUERY_VAR ] ?? $max_range );

		$filter_context = array(
			'price'  => array(
				'minPrice' => $min_price,
				'maxPrice' => $max_price,
				'minRange' => $min_range,
				'maxRange' => $max_range,
			),
			'parent' => $this->get_full_block_name(),
		);

		$wrapper_attributes = array(
			'data-wp-interactive'  => $this->get_full_block_name(),
			'data-wp-context'      => wp_json_encode(
				array(
					'minRange'             => $min_range,
					'maxRange'             => $max_range,
					'hasFilterOptions'     => $min_range < $max_range && $min_price < $max_price,
					'activeLabelTemplates' => array(
						/* translators: {{min}} and {{max}} are the formatted minimum and maximum prices respectively. */
						'minAndMax' => __( 'Price: {{min}} - {{max}}', 'woocommerce' ),
						/* translators: {{max}} is the formatted maximum price. */
						'maxOnly'   => __( 'Price: Up to {{max}}', 'woocommerce' ),
						/* translators: {{min}} is the formatted minimum price. */
						'minOnly'   => __( 'Price: From {{min}}', 'woocommerce' ),
					),
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP,
			),
			'data-wp-key'          => 'product-filter-price-' . md5( wp_json_encode( $attributes ) ),
			'data-wp-bind--hidden' => '!context.hasFilterOptions',
		);

		if ( $min_range === $max_range || ! $max_range ) {
			return sprintf(
				'<div %1$s hidden>%2$s</div>',
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
		$filters    = Package::container()->get( QueryFilters::class );
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

		$price_results = $filters->get_filtered_price( $query_vars );

		return array(
			'min_price' => intval( floor( $price_results->min_price ?? 0 ) ),
			'max_price' => intval( ceil( $price_results->max_price ?? 0 ) ),
		);
	}

	/**
	 * Disable the block type script, this uses script modules.
	 *
	 * @param string|null $key The key.
	 *
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}
}
