<?php
namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
/**
 * ProductRating class.
 */
class ProductRating extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-rating';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'color'                  =>
			array(
				'text'                            => true,
				'background'                      => false,
				'link'                            => false,
				'__experimentalSkipSerialization' => true,
			),
			'typography'             =>
			array(
				'fontSize'                        => true,
				'__experimentalSkipSerialization' => true,
			),
			'spacing'                =>
			array(
				'margin'                          => true,
				'padding'                         => true,
				'__experimentalSkipSerialization' => true,
			),
			'__experimentalSelector' => '.wc-block-components-product-rating',
		);
	}

	/**
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	private function parse_attributes( $attributes ) {
		// These should match what's set in JS `registerBlockType`.
		$defaults = array(
			'productId'                           => 0,
			'isDescendentOfQueryLoop'             => false,
			'textAlign'                           => '',
			'isDescendentOfSingleProductBlock'    => false,
			'isDescendentOfSingleProductTemplate' => false,
		);

		return wp_parse_args( $attributes, $defaults );
	}

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return array_merge( parent::get_block_type_style(), [ 'wc-blocks-packages-style' ] );
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return [ 'query', 'queryId', 'postId' ];
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
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( [ $this->block_name ] );
			return $content;
		}

		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_get_product( $post_id );

		if ( $product && $product->get_review_count() > 0
			&& $product->get_reviews_allowed()
			&& wc_reviews_enabled() ) {
			$product_reviews_count                    = $product->get_review_count();
			$product_rating                           = $product->get_average_rating();
			$parsed_attributes                        = $this->parse_attributes( $attributes );
			$is_descendent_of_single_product_block    = $parsed_attributes['isDescendentOfSingleProductBlock'];
			$is_descendent_of_single_product_template = $parsed_attributes['isDescendentOfSingleProductTemplate'];

			$styles_and_classes            = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
			$text_align_styles_and_classes = StyleAttributesUtils::get_text_align_class_and_style( $attributes );

			/**
			 * Filter the output from wc_get_rating_html.
			 *
			 * @param string $html   Star rating markup. Default empty string.
			 * @param float  $rating Rating being shown.
			 * @param int    $count  Total number of ratings.
			 * @return string
			 */
			$filter_rating_html = function( $html, $rating, $count ) use ( $post_id, $product_rating, $product_reviews_count, $is_descendent_of_single_product_block, $is_descendent_of_single_product_template ) {
				$product_permalink = get_permalink( $post_id );
				$reviews_count     = $count;
				$average_rating    = $rating;

				if ( $product_rating ) {
					$average_rating = $product_rating;
				}

				if ( $product_reviews_count ) {
					$reviews_count = $product_reviews_count;
				}

				if ( 0 < $average_rating || false === $product_permalink ) {
					/* translators: %s: rating */
					$label                  = sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $average_rating );
					$customer_reviews_count = sprintf(
						/* translators: %s is referring to the total of reviews for a product */
						_n(
							'(%s customer review)',
							'(%s customer reviews)',
							$reviews_count,
							'woocommerce'
						),
						esc_html( $reviews_count )
					);

					if ( $is_descendent_of_single_product_block ) {
						$customer_reviews_count = '<a href="' . esc_url( $product_permalink ) . '#reviews">' . $customer_reviews_count . '</a>';
					} elseif ( $is_descendent_of_single_product_template ) {
						$customer_reviews_count = '<a class="woocommerce-review-link" rel="nofollow" href="#reviews">' . $customer_reviews_count . '</a>';
					}

					$reviews_count_html = sprintf( '<span class="wc-block-components-product-rating__reviews_count">%1$s</span>', $customer_reviews_count );
					$html               = sprintf(
						'<div class="wc-block-components-product-rating__container">
							<div class="wc-block-components-product-rating__stars wc-block-grid__product-rating__stars" role="img" aria-label="%1$s">
								%2$s
							</div>
							%3$s
						</div>
						',
						esc_attr( $label ),
						wc_get_star_rating_html( $average_rating, $reviews_count ),
						$is_descendent_of_single_product_block || $is_descendent_of_single_product_template ? $reviews_count_html : ''
					);
				} else {
					$html = '';
				}

				return $html;
			};

			add_filter(
				'woocommerce_product_get_rating_html',
				$filter_rating_html,
				10,
				3
			);

			$rating_html = wc_get_rating_html( $product->get_average_rating() );

			remove_filter(
				'woocommerce_product_get_rating_html',
				$filter_rating_html,
				10
			);

			$classes = implode(
				' ',
				array_filter(
					array(
						'wc-block-components-product-rating wc-block-grid__product-rating',
						esc_attr( $text_align_styles_and_classes['class'] ?? '' ),
						esc_attr( $styles_and_classes['classes'] ),
					)
				)
			);

			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'class' => $classes,
					'style' => esc_attr( $styles_and_classes['styles'] ?? '' ),
				)
			);

			return sprintf(
				'<div %1$s>
					%2$s
				</div>',
				$wrapper_attributes,
				$rating_html
			);
		}
		return '';
	}
}
