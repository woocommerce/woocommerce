<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\InteractivityComponents\CheckboxList;
use Automattic\WooCommerce\Blocks\InteractivityComponents\Dropdown;

/**
 * Collection Rating Filter Block
 *
 * @package Automattic\WooCommerce\Blocks\BlockTypes
 */
final class CollectionRatingFilter extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'collection-rating-filter';

	const RATING_FILTER_QUERY_VAR = 'rating_filter';

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
			function( $param ) {
				return self::RATING_FILTER_QUERY_VAR === $param;
			}
		);

		return array_merge(
			$filter_param_keys,
			$price_param_keys
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
		if ( empty( $params[ self::RATING_FILTER_QUERY_VAR ] ) ) {
			return $data;
		}

		$active_ratings = array_filter(
			explode( ',', $params[ self::RATING_FILTER_QUERY_VAR ] )
		);

		if ( empty( $active_ratings ) ) {
			return $data;
		}

		$active_ratings = array_map(
			function( $rating ) {
				return array(
					/* translators: %d is the rating value. */
					'title'      => sprintf( __( 'Rated %d out of 5', 'woocommerce' ), $rating ),
					'attributes' => array(
						'data-wc-on--click' => 'woocommerce/collection-rating-filter::actions.removeFilter',
						'data-wc-context'   => 'woocommerce/collection-rating-filter::' . wp_json_encode( array( 'value' => $rating ) ),
					),
				);
			},
			$active_ratings
		);

		$data['rating'] = array(
			'type'  => __( 'Rating', 'woocommerce' ),
			'items' => $active_ratings,
		);

		return $data;
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

		$rating_counts = $block->context['collectionData']['rating_counts'] ?? array();
		$display_style = $attributes['displayStyle'] ?? 'list';
		$show_counts   = $attributes['showCounts'] ?? false;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
		$selected_ratings_query_param = isset( $_GET[ self::RATING_FILTER_QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::RATING_FILTER_QUERY_VAR ] ) ) : '';

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-wc-interactive' => 'woocommerce/collection-rating-filter',
				'class'               => 'wc-block-rating-filter',
			)
		);

		$input = 'list' === $display_style ? CheckboxList::render(
			array(
				'items'     => $this->get_checkbox_list_items( $rating_counts, $selected_ratings_query_param, $show_counts ),
				'on_change' => 'woocommerce/collection-rating-filter::actions.onCheckboxChange',
			)
		) : Dropdown::render(
			$this->get_dropdown_props( $rating_counts, $selected_ratings_query_param, $show_counts )
		);

		return sprintf(
			'<div %1$s>
				<div class="wc-block-rating-filter__controls">%2$s</div>
				<div class="wc-block-rating-filter__actions"></div>
			</div>',
			$wrapper_attributes,
			$input
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
	 * Get the checkbox list items.
	 *
	 * @param array  $rating_counts    The rating counts.
	 * @param string $selected_ratings_query The url query param for selected ratings.
	 * @param bool   $show_counts      Whether to show the counts.
	 * @return array
	 */
	private function get_checkbox_list_items( $rating_counts, $selected_ratings_query, $show_counts ) {
		$ratings_array = explode( ',', $selected_ratings_query );

		return array_map(
			function( $rating ) use ( $ratings_array, $show_counts ) {
				$rating_str  = (string) $rating['rating'];
				$count       = $rating['count'];
				$count_label = $show_counts ? "($count)" : '';

				return array(
					'id'      => 'rating-' . $rating_str,
					'checked' => in_array( $rating_str, $ratings_array, true ),
					'label'   => $this->render_rating_label( (int) $rating_str, $count_label ),
					'value'   => $rating_str,
				);
			},
			$rating_counts
		);
	}

	/**
	 * Get the dropdown props.
	 *
	 * @param mixed $rating_counts The rating counts.
	 * @param mixed $selected_ratings_query The url query param for selected ratings.
	 * @param mixed $show_counts Whether to show the counts.
	 * @return array<array-key, array>
	 */
	private function get_dropdown_props( $rating_counts, $selected_ratings_query, $show_counts ) {
		$ratings_array = explode( ',', $selected_ratings_query );

		$selected_item = array_reduce(
			$rating_counts,
			function( $carry, $rating ) use ( $ratings_array, $show_counts ) {
				if ( in_array( (string) $rating['rating'], $ratings_array, true ) ) {
					$count       = $rating['count'];
					$count_label = $show_counts ? "($count)" : '';
					$rating_str  = (string) $rating['rating'];
					return array(
						/* translators: %d is referring to the average rating value. Example: Rated 4 out of 5. */
						'label' => sprintf( __( 'Rated %d out of 5', 'woocommerce' ), $rating_str ) . ' ' . $count_label,
						'value' => $rating['rating'],
					);
				}
				return $carry;
			},
			array()
		);

		return array(
			'items'         => array_map(
				function ( $rating ) use ( $show_counts ) {
					$count = $rating['count'];
					$count_label = $show_counts ? "($count)" : '';
					$rating_str = (string) $rating['rating'];
					return array(
						/* translators: %d is referring to the average rating value. Example: Rated 4 out of 5. */
						'label' => sprintf( __( 'Rated %d out of 5', 'woocommerce' ), $rating_str ) . ' ' . $count_label,
						'value' => $rating['rating'],
					);
				},
				$rating_counts
			),
			'selected_item' => $selected_item,
			'action'        => 'woocommerce/collection-rating-filter::actions.onDropdownChange',
		);
	}
}
