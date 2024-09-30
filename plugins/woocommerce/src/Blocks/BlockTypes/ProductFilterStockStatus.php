<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;

/**
 * Product Filter: Stock Status Block.
 */
final class ProductFilterStockStatus extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-stock-status';

	const STOCK_STATUS_QUERY_VAR = 'filter_stock_status';

	/**
	 * Initialize this block type.
	 *
	 * - Hook into WP lifecycle.
	 * - Register the block with WordPress.
	 */
	protected function initialize() {
		parent::initialize();

		add_filter( 'collection_filter_query_param_keys', array( $this, 'get_filter_query_param_keys' ), 10, 2 );
		add_filter( 'collection_active_filters_data', array( $this, 'register_active_filters_data' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'register_block_patterns' ) );
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
		$stock_param_keys = array_filter(
			$url_param_keys,
			function ( $param ) {
				return self::STOCK_STATUS_QUERY_VAR === $param;
			}
		);

		return array_merge(
			$filter_param_keys,
			$stock_param_keys
		);
	}

	/**
	 * Register the active filters data.
	 *
	 * @param array $data   The active filters data.
	 * @param array $params The query param parsed from the URL.
	 * @return array Active filters data.
	 */
	public function register_active_filters_data( $data, $params ) {
		$stock_status_options = wc_get_product_stock_status_options();

		if ( empty( $params[ self::STOCK_STATUS_QUERY_VAR ] ) ) {
			return $data;
		}

		$active_stock_statuses = array_filter(
			explode( ',', $params[ self::STOCK_STATUS_QUERY_VAR ] )
		);

		if ( empty( $active_stock_statuses ) ) {
			return $data;
		}

		$action_namespace = $this->get_full_block_name();

		$active_stock_statuses = array_map(
			function ( $status ) use ( $stock_status_options, $action_namespace ) {
				return array(
					'title'      => $stock_status_options[ $status ],
					'attributes' => array(
						'value'             => $status,
						'data-wc-on--click' => "$action_namespace::actions.toggleFilter",
					),
				);
			},
			$active_stock_statuses
		);

		$data['stock'] = array(
			'type'  => __( 'Stock Status', 'woocommerce' ),
			'items' => $active_stock_statuses,
		);

		return $data;
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $stock_statuses  Any stock statuses that currently are available from the block.
	 *                               Note, this will be empty in the editor context when the block is
	 *                               not in the post content on editor load.
	 */
	protected function enqueue_data( array $stock_statuses = [] ) {
		parent::enqueue_data( $stock_statuses );
		$this->asset_data_registry->add( 'stockStatusOptions', wc_get_product_stock_status_options() );
		$this->asset_data_registry->add( 'hideOutOfStockItems', 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) );
	}

	/**
	 * Get the block context.
	 *
	 * @param array $stock_count The block attributes.
	 * @return array
	 */
	private function create_context_by_stock_counts( $stock_count ) {
		$stock_statuses          = wc_get_product_stock_status_options();
		$query                   = isset( $_GET[ self::STOCK_STATUS_QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::STOCK_STATUS_QUERY_VAR ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected_stock_statuses = explode( ',', $query );

		$stock = array_map(
			function ( $item ) use ( $stock_statuses, $selected_stock_statuses ) {
				$label = $stock_statuses[ $item['status'] ] . ' (' . $item['count'] . ')';
				return array(
					'label'    => $label,
					'value'    => $item['status'],
					'selected' => in_array( $item['status'], $selected_stock_statuses, true ),
				);
			},
			$stock_count
		);

		return array(
			'filterData'         => array(
				'items'   => $stock,
				'actions' => array(
					'toggleFilter' => "{$this->get_full_block_name()}::actions.toggleFilter",
				),
			),
			'hasSelectedFilters' => ! empty( $selected_stock_statuses ),
		);
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {

		$stock_counts   = $this->get_stock_status_counts( $block );
		$filter_context = $this->create_context_by_stock_counts( $stock_counts );

		$wrapper_attributes = array(
			'data-wc-interactive' => wp_json_encode( array( 'namespace' => $this->get_full_block_name() ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
			'data-wc-context'     => wp_json_encode( $filter_context['hasSelectedFilters'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ),
		);

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes(
				$wrapper_attributes
			),
			array_reduce(
				$block->parsed_block['innerBlocks'],
				function ( $carry, $parsed_block ) use ( $filter_context ) {
					$carry .= ( new \WP_Block( $parsed_block, array( 'filterData' => $filter_context['filterData'] ) ) )->render();
					return $carry;
				},
				''
			)
		);
	}

	/**
	 * Retrieve the stock status filter data for current block.
	 *
	 * @param WP_Block $block Block instance.
	 */
	private function get_stock_status_counts( $block ) {
		$filters    = Package::container()->get( QueryFilters::class );
		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		unset( $query_vars['filter_stock_status'] );

		if ( ! empty( $query_vars['meta_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query_vars['meta_query'] = ProductCollectionUtils::remove_query_array( $query_vars['meta_query'], 'key', '_stock_status' );
		}

		$counts = $filters->get_stock_status_counts( $query_vars );
		$data   = array();

		foreach ( $counts as $key => $value ) {
			$data[] = array(
				'status' => $key,
				'count'  => $value,
			);
		}

		return array_filter(
			$data,
			function ( $stock_count ) {
				return $stock_count['count'] > 0;
			}
		);
	}

	/**
	 * Register pattern for default product attribute.
	 */
	public function register_block_patterns() {
		register_block_pattern(
			'woocommerce/default-stock-filter',
			array(
				'title'    => '',
				'inserter' => false,
				'content'  => '
<!-- wp:woocommerce/product-filter-stock-status -->
<div class="wp-block-woocommerce-product-filter-stock-status">
  <!-- wp:group {"metadata":{"name":"Header"},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
  <div class="wp-block-group">
    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">Status</h3>
    <!-- /wp:heading -->
    <!-- wp:woocommerce/product-filter-clear-button {"lock":{"remove":true,"move":false}} -->
    <!-- wp:buttons {"layout":{"type":"flex"}} -->
    <div class="wp-block-buttons">
      <!-- wp:button {"className":"wc-block-product-filter-clear-button is-style-outline","style":{"border":{"width":"0px","style":"none"},"typography":{"textDecoration":"underline"},"outline":"none","fontSize":"medium"}} -->
      <div class="wp-block-button wc-block-product-filter-clear-button is-style-outline" style="text-decoration:underline"><a class="wp-block-button__link wp-element-button" style="border-style:none;border-width:0px">Clear</a></div>
      <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
    <!-- /wp:woocommerce/product-filter-clear-button -->
  </div>
  <!-- /wp:group -->
  <!-- wp:woocommerce/product-filter-chips {"lock":{"remove":true}} -->
  <div class="wp-block-woocommerce-product-filter-chips wc-block-product-filter-chips"></div>
  <!-- /wp:woocommerce/product-filter-chips -->
</div>
<!-- /wp:woocommerce/product-filter-stock-status -->
				',
			)
		);
	}
}
