<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection\Utils as ProductCollectionUtils;
use Automattic\WooCommerce\Blocks\QueryFilters;
use Automattic\WooCommerce\Blocks\Package;


/**
 * Product Filter: Rating Block
 *
 * @package Automattic\WooCommerce\Blocks\BlockTypes
 */
final class ProductFilterRating extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-filter-rating';

	const RATING_FILTER_QUERY_VAR = 'rating_filter';

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
	 * Register the query param keys.
	 *
	 * @param array $filter_param_keys The active filters data.
	 * @param array $url_param_keys    The query param parsed from the URL.
	 *
	 * @return array Active filters param keys.
	 */
	public function get_filter_query_param_keys( $filter_param_keys, $url_param_keys ) {
		$rating_param_keys = array_filter(
			$url_param_keys,
			function ( $param ) {
				return self::RATING_FILTER_QUERY_VAR === $param;
			}
		);

		return array_merge(
			$filter_param_keys,
			$rating_param_keys
		);
	}

	/**
	 * Prepare the active filter items.
	 *
	 * @param array $items  The active filter items.
	 * @param array $params The query param parsed from the URL.
	 * @return array Active filters items.
	 */
	public function prepare_selected_filters( $items, $params ) {
		if ( empty( $params[ self::RATING_FILTER_QUERY_VAR ] ) ) {
			return $items;
		}

		$active_ratings = array_filter(
			explode( ',', $params[ self::RATING_FILTER_QUERY_VAR ] )
		);

		if ( empty( $active_ratings ) ) {
			return $items;
		}

		foreach ( $active_ratings as $rating ) {
			$items[] = array(
				'type'  => 'rating',
				'value' => $rating,
				/* translators: %s is referring to rating value. Example: Rated 4 out of 5. */
				'label' => sprintf( __( 'Rating: Rated %d out of 5', 'woocommerce' ), $rating ),
			);
		}

		return $items;
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
		// don't render if its admin, or ajax in progress.
		if ( is_admin() || wp_doing_ajax() ) {
			return '';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		$min_rating    = $attributes['minRating'] ?? 0;
		$rating_counts = $this->get_rating_counts( $block );
		// User selected minimum rating to display.
		$rating_counts_with_min = array_filter(
			$rating_counts,
			function ( $rating ) use ( $min_rating ) {
				return $rating['rating'] >= $min_rating;
			}
		);
		$filter_params          = $block->context['filterParams'] ?? array();
		$rating_query           = $filter_params[ self::RATING_FILTER_QUERY_VAR ] ?? '';
		$selected_rating        = array_filter( explode( ',', $rating_query ) );

		$filter_options = array_map(
			function ( $rating ) use ( $selected_rating, $attributes ) {
				$value       = (string) $rating['rating'];
				$count_label = $attributes['showCounts'] ? "({$rating['count']})" : '';

				$aria_label = sprintf(
					/* translators: %s is referring to rating value. Example: Rated 4 out of 5. */
					__( 'Rated %s out of 5', 'woocommerce' ),
					$value,
				);

				return array(
					'id'        => 'rating-' . $value,
					'selected'  => in_array( $value, $selected_rating, true ),
					'label'     => $this->render_rating_label( (int) $value, $count_label ),
					'ariaLabel' => $aria_label,
					'value'     => $value,
					'type'      => 'rating',
					'data'      => $rating,
				);
			},
			$rating_counts_with_min
		);

		$filter_context = array(
			'items'  => $filter_options,
			'parent' => $this->get_full_block_name(),
		);

		$wrapper_attributes = array(
			'data-wp-interactive'  => $this->get_full_block_name(),
			'data-wp-context'      => wp_json_encode(
				array(
					'hasFilterOptions'    => ! empty( $filter_options ),
					/* translators: {{labe}} is the rating filter item label. */
					'activeLabelTemplate' => __( 'Rating: {{label}}', 'woocommerce' ),
				),
				JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			),
			'data-wp-bind--hidden' => '!context.hasFilterOptions',
		);

		if ( empty( $filter_options ) ) {
			$wrapper_attributes['hidden'] = true;
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
	 * Render the rating label.
	 *
	 * @param int    $rating The rating to render.
	 * @param string $count_label The count label to render.
	 * @return string|false
	 */
	private function render_rating_label( $rating, $count_label ) {
		$width = $rating * 20;

		$rating_label = sprintf(
			/* translators: %1$d is referring to rating value. Example: Rated 4 out of 5. */
			__( 'Rated %1$d out of 5', 'woocommerce' ),
			$rating,
		);

		ob_start();
		?>
		<div class="wc-block-components-product-rating">
			<div class="wc-block-components-product-rating__stars" role="img" aria-label="<?php echo esc_attr( $rating_label ); ?>">
				<span style="width: <?php echo esc_attr( $width ); ?>%" aria-hidden="true">
				</span>
			</div>
			<span class="wc-block-components-product-rating-count">
				<?php echo esc_html( $count_label ); ?>
			</span>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Retrieve the rating filter data for current block.
	 *
	 * @param WP_Block $block Block instance.
	 */
	private function get_rating_counts( $block ) {
		$filters    = Package::container()->get( QueryFilters::class );
		$query_vars = ProductCollectionUtils::get_query_vars( $block, 1 );

		if ( ! empty( $query_vars['tax_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$query_vars['tax_query'] = ProductCollectionUtils::remove_query_array( $query_vars['tax_query'], 'rating_filter', true );
		}

		if ( isset( $query_vars['taxonomy'] ) && false !== strpos( $query_vars['taxonomy'], 'pa_' ) ) {
			unset(
				$query_vars['taxonomy'],
				$query_vars['term']
			);
		}

		$counts = $filters->get_rating_counts( $query_vars );
		$data   = array();

		foreach ( $counts as $key => $value ) {
			$data[] = array(
				'rating' => $key,
				'count'  => $value,
			);
		}

		return $data;
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
