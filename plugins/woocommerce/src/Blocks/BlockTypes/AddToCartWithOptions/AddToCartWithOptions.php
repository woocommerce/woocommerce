<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;

/**
 * AddToCartWithOptions class.
 */
class AddToCartWithOptions extends AbstractBlock {

	use EnableBlockJsonAssetsTrait;

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-with-options';

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 */
	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( is_admin() && ! WC()->is_rest_api_request() ) {
			$this->asset_data_registry->add( 'productTypes', wc_get_product_types() );
			$this->asset_data_registry->add( 'addToCartWithOptionsTemplatePartIds', $this->get_template_part_ids() );
		}
	}

	/**
	 * Get template part IDs for each product type.
	 *
	 * @return array Array of product types with their corresponding template part IDs.
	 */
	protected function get_template_part_ids() {
		$product_types = array_keys( wc_get_product_types() );
		$current_theme = wp_get_theme()->get_stylesheet();

		$template_part_ids = array();
		foreach ( $product_types as $product_type ) {
			$slug = $product_type . '-product-add-to-cart-with-options';

			// Check if theme template exists.
			$theme_has_template = BlockTemplateUtils::theme_has_template_part( $slug );

			if ( $theme_has_template ) {
				$template_part_ids[ $product_type ] = "{$current_theme}//{$slug}";
			} else {
				$template_part_ids[ $product_type ] = "woocommerce/woocommerce//{$slug}";
			}
		}

		return $template_part_ids;
	}

	/**
	 * Modifies the block context for product button blocks when inside the Add to Cart + Options block.
	 *
	 * @param array $context The block context.
	 * @param array $block   The parsed block.
	 * @return array Modified block context.
	 */
	public function set_is_descendant_of_add_to_cart_with_options_context( $context, $block ) {
		if ( 'woocommerce/product-button' === $block['blockName'] ) {
			$context['woocommerce/isDescendantOfAddToCartWithOptions'] = true;
		}

		return $context;
	}

	/**
	 * Check if a child product is purchasable.
	 *
	 * @param \WC_Product $product The product to check.
	 * @return bool True if the product is purchasable, false otherwise.
	 */
	private function is_child_product_purchasable( \WC_Product $product ) {
		// Skip variable products.
		if ( $product->is_type( 'variable' ) ) {
			return false;
		}

		// Skip grouped products.
		if ( $product->is_type( 'grouped' ) ) {
			return false;
		}

		return $product->is_purchasable() && $product->is_in_stock();
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 *
	 * @return string | void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		global $product;

		$product_id = $block->context['postId'];

		if ( ! isset( $product_id ) ) {
			return '';
		}

		$previous_product = $product;
		$product          = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			$product = $previous_product;

			return '';
		}

		$product_type = $product->get_type();

		$slug = $product_type . '-product-add-to-cart-with-options';

		if ( in_array( $product_type, array( ProductType::SIMPLE, ProductType::EXTERNAL, ProductType::VARIABLE, ProductType::GROUPED ), true ) ) {
			$template_part_path = Package::get_path() . 'templates/' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/' . $slug . '.html';
		} else {
			/**
			 * Filter to declare product type's cart block template is supported.
			 *
			 * @since 9.9.0
			 * @param mixed string|boolean The template part path if it exists
			 * @param string $product_type The product type
			 */
			$template_part_path = apply_filters( '__experimental_woocommerce_' . $product_type . '_add_to_cart_with_options_block_template_part', false, $product_type );
		}

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );
		$classes            = implode(
			' ',
			array_filter(
				array(
					'wp-block-add-to-cart-with-options wc-block-add-to-cart-with-options',
					esc_attr( $classes_and_styles['classes'] ),
				)
			)
		);

		if ( is_string( $template_part_path ) && file_exists( $template_part_path ) ) {

			$template_part_contents = '';
			// Determine if we need to load the template part from the DB, the theme or WooCommerce in that order.
			$templates_from_db = BlockTemplateUtils::get_block_templates_from_db( array( $slug ), 'wp_template_part' );

			if ( is_countable( $templates_from_db ) && count( $templates_from_db ) > 0 ) {
				$template_slug_to_load = $templates_from_db[0]->theme;
			} else {
				$theme_has_template_part = BlockTemplateUtils::theme_has_template_part( $slug );
				$template_slug_to_load   = $theme_has_template_part ? get_stylesheet() : BlockTemplateUtils::PLUGIN_SLUG;
			}
			$template_part = get_block_template( $template_slug_to_load . '//' . $slug, 'wp_template_part' );

			if ( $template_part && ! empty( $template_part->content ) ) {
				$template_part_contents = $template_part->content;
			}

			if ( '' === $template_part_contents ) {
				$template_part_contents = file_get_contents( $template_part_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}

			/**
			 * Filter the default quantity to add to cart.
			 *
			 * @since 10.0.0
			 * @param number $default_quantity The default quantity.
			 * @param \WC_Product $product The product object.
			 */
			$default_quantity = apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product );

			wp_interactivity_state(
				'woocommerce/add-to-cart-with-options',
				array(
					'isFormValid' => function () {
						$context = wp_interactivity_get_context();
						$product = wc_get_product( $context['productId'] );
						if ( $product instanceof \WC_Product && $product->is_type( 'variable' ) ) {
							return false;
						}
						return true;
					},
					'variationId' => null,
				)
			);

			wp_interactivity_state(
				'woocommerce',
				array(
					// Use camelCase for error messages generated from the frontend,
					// and snake_case for error messages generated from the backend.
					'errorMessages' => array(
						'groupedProductAddToCartMissingItems' => __(
							'Please select some products to add to the cart.',
							'woocommerce'
						),
						'woocommerce_rest_missing_attributes' => __(
							'Please select product attributes before adding to cart.',
							'woocommerce'
						),
					),
				)
			);

			$context = array(
				'productId'           => $product->get_id(),
				'productType'         => $product->get_type(),
				'quantity'            => array( $product->get_id() => $default_quantity ),
				'quantityConstraints' => array(),
			);

			if ( $product->is_type( 'variable' ) ) {
				$context['selectedAttributes'] = array();
				$available_variations          = $product->get_available_variations( 'objects' );
				foreach ( $available_variations as $variation ) {
					/**
					 * Filter the default quantity to add to cart.
					 *
					 * @since 10.1.0
					 * @param number $default_variation_quantity The default quantity.
					 * @param WC_Variation_Product $variation The variation object.
					 */
					$default_variation_quantity                  = apply_filters( 'woocommerce_quantity_input_min', $variation->get_min_purchase_quantity(), $variation );
					$context['quantity'][ $variation->get_id() ] = $default_variation_quantity;
					$context['availableVariations'][]            = array(
						'variation_id' => $variation->get_id(),
						'attributes'   => $variation->get_variation_attributes(),
						'is_in_stock'  => $variation->is_in_stock(),
					);
				}
			}

			if ( $product->is_type( 'grouped' ) ) {
				// Add context for purchasable child products.
				$context['groupedProductIds'] = array();
				foreach ( $product->get_children() as $child_product_id ) {
					$child_product = wc_get_product( $child_product_id );
					if ( $child_product && $this->is_child_product_purchasable( $child_product ) ) {
						$context['groupedProductIds'][] = $child_product_id;

						$args = Utils::get_quantity_input_args( $child_product );
						$min  = isset( $args['min_value'] ) ? (int) $args['min_value'] : 0;
						// For grouped children, if min is 1 (the default), set to 0 unless a filter sets otherwise.
						if ( 1 === $min ) {
							$min = 0;
						}
						$max  = ( isset( $args['max_value'] ) && '' !== $args['max_value'] && -1 !== $args['max_value'] )
							? (int) $args['max_value']
							: null;
						$step = isset( $args['step'] ) ? (int) $args['step'] : 1;
						$context['quantityConstraints'][ $child_product_id ] = array(
							'min'  => $min,
							'max'  => $max,
							'step' => $step,
						);
					}
				}

				// Add quantity context for purchasable child products.
				$context['quantity'] = array_fill_keys(
					$context['groupedProductIds'],
					$default_quantity
				);

				// Set default quantity for each child product.
				foreach ( $context['groupedProductIds'] as $child_product_id ) {
					$child_product = wc_get_product( $child_product_id );
					if ( $child_product ) {

						$default_child_quantity = isset( $_POST['quantity'][ $child_product->get_id() ] ) ? wc_stock_amount( wc_clean( wp_unslash( $_POST['quantity'][ $child_product->get_id() ] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

						$context['quantity'][ $child_product_id ] = $default_child_quantity;

						// Check for any "sold individually" products and set their default quantity to 0.
						if ( $child_product->is_sold_individually() ) {
							$context['quantity'][ $child_product_id ] = 0;
						}
					}
				}
			} else {
				// Not grouped: just add constraints for the main product.
				$args = Utils::get_quantity_input_args( $product );
				$min  = isset( $args['min_value'] ) ? (int) $args['min_value'] : 1;
				$max  = ( isset( $args['max_value'] ) && '' !== $args['max_value'] && -1 !== $args['max_value'] )
				? (int) $args['max_value']
				: null;
				$step = isset( $args['step'] ) ? (int) $args['step'] : 1;

				$context['quantityConstraints'][ $product->get_id() ] = array(
					'min'  => $min,
					'max'  => $max,
					'step' => $step,
				);
			}

			$hooks_before = '';
			$hooks_after  = '';

			/**
			* Filter to disable the compatibility layer for the blockified templates.
			*
			* This hook allows to disable the compatibility layer for the blockified.
			*
			* @since 7.6.0
			* @param boolean $is_disabled_compatibility_layer Whether the compatibility layer should be disabled.
			*/
			$is_disabled_compatibility_layer = apply_filters( 'woocommerce_disable_compatibility_layer', false );

			if ( ! $is_disabled_compatibility_layer && ! Utils::is_not_purchasable_product( $product ) ) {
				ob_start();
				/**
				 * Hook: woocommerce_before_add_to_cart_form.
				 *
				 * @since 10.1.0
				 */
				do_action( 'woocommerce_before_add_to_cart_form' );

				if ( ProductType::SIMPLE === $product_type ) {
					/**
					 * Hook: woocommerce_before_add_to_cart_quantity.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_add_to_cart_quantity' );
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
				} elseif ( ProductType::EXTERNAL === $product_type ) {
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
				} elseif ( ProductType::GROUPED === $product_type ) {
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
				} elseif ( ProductType::VARIABLE === $product_type ) {
					/**
					 * Hook: woocommerce_before_variations_form.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_variations_form' );
					/**
					 * Hook: woocommerce_after_variations_table.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_variations_table' );
					/**
					 * Hook: woocommerce_before_single_variation.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_single_variation' );

					// WooCommerce uses `woocommerce_single_variation` to render
					// some UI elements like the Add to Cart button for
					// variations. We need to remove them to avoid those UI
					// elements being duplicate with the blocks.
					// We later add these actions back to avoid affecting other
					// blocks or templates.
					remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
					remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
					/**
					 * Hook: woocommerce_single_variation.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_single_variation' );
					if ( function_exists( 'woocommerce_single_variation' ) ) {
						add_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
					}
					if ( function_exists( 'woocommerce_single_variation_add_to_cart_button' ) ) {
						add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
					}
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
					/**
					 * Hook: woocommerce_before_add_to_cart_quantity.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_before_add_to_cart_quantity' );
				}
				$hooks_before = ob_get_clean();

				ob_start();
				if ( ProductType::SIMPLE === $product_type ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_quantity.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_add_to_cart_quantity' );
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
				} elseif ( ProductType::EXTERNAL === $product_type ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
				} elseif ( ProductType::GROUPED === $product_type ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
				} elseif ( ProductType::VARIABLE === $product_type ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_quantity.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_add_to_cart_quantity' );
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
					/**
					 * Hook: woocommerce_after_single_variation.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_single_variation' );
					/**
					 * Hook: woocommerce_after_variations_form.
					 *
					 * @since 10.0.0
					 */
					do_action( 'woocommerce_after_variations_form' );
				}

				/**
				 * Hook: woocommerce_after_add_to_cart_form.
				 *
				 * @since 10.1.0
				 */
				do_action( 'woocommerce_after_add_to_cart_form' );

				$hooks_after = ob_get_clean();
			}

			// Because we are printing the template part using do_blocks, context from the outside is lost.
			// This filter is used to add the isDescendantOfAddToCartWithOptions context back.
			add_filter( 'render_block_context', array( $this, 'set_is_descendant_of_add_to_cart_with_options_context' ), 10, 2 );
			$template_part_blocks = do_blocks( $template_part_contents );
			remove_filter( 'render_block_context', array( $this, 'set_is_descendant_of_add_to_cart_with_options_context' ) );

			$wrapper_attributes = array(
				'class'                     => $classes,
				'style'                     => esc_attr( $classes_and_styles['styles'] ),
				'data-wp-interactive'       => 'woocommerce/add-to-cart-with-options',
				'data-wp-class--is-invalid' => '!state.isFormValid',
				'data-wp-watch'             => 'callbacks.setSelectedVariationId',
				'data-wp-context'           => wp_json_encode(
					$context,
					JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
				),
			);

			$cart_redirect_after_add = get_option( 'woocommerce_cart_redirect_after_add' );
			$form_attributes         = '';
			$legacy_mode             = $hooks_before || $hooks_after || 'yes' === $cart_redirect_after_add;
			if ( $legacy_mode ) {
				// If an extension is hoooking into the form or we need to redirect to the cart,
				// we fall back to a regular HTML form.
				$form_attributes = array(
					'action'  => esc_url(
						/**
						 * Filter the add to cart form action.
						 *
						 * @since 10.0.0
						 * @param string $permalink The product permalink.
						 * @return string The add to cart form action.
						 */
						apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() )
					),
					'method'  => 'post',
					'enctype' => 'multipart/form-data',
					'class'   => 'cart',
				);
			} else {
				// Otherwise, we use the Interactivity API.
				$form_attributes = array(
					'data-wp-on--submit' => 'actions.handleSubmit',
				);
			}

			// These hidden inputs are used by extensions or Express Payment methods to gather information of the form state.
			$hidden_input = '';
			if ( ProductType::SIMPLE === $product_type ) {
				$hidden_input = '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product_id ) . '" />';
			} elseif ( ProductType::GROUPED === $product_type ) {
				$hidden_input = '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product_id ) . '" />';
			} elseif ( ProductType::VARIABLE === $product_type ) {
				$hidden_input = '<div class="single_variation_wrap">
					<input type="hidden" name="add-to-cart" value="' . esc_attr( $product_id ) . '" />
					<input type="hidden" name="product_id" value="' . esc_attr( $product_id ) . '" />
					<input type="hidden"
						name="variation_id"
						data-wp-bind--value="state.variationId"
					/>
				</div>';
			}

			$form_html = sprintf(
				'<form %1$s>%2$s%3$s%4$s%5$s</form>',
				get_block_wrapper_attributes(
					array_merge(
						$wrapper_attributes,
						$form_attributes,
						array(
							'class' => implode(
								' ',
								array_filter(
									array(
										isset( $wrapper_attributes['class'] ) ? $wrapper_attributes['class'] : '',
										isset( $form_attributes['class'] ) ? $form_attributes['class'] : '',
									)
								)
							),
						)
					)
				),
				$hooks_before,
				$template_part_blocks,
				$hooks_after,
				$hidden_input
			);

			ob_start();

			if ( in_array( $product_type, array( ProductType::SIMPLE, ProductType::EXTERNAL, ProductType::VARIABLE, ProductType::GROUPED ), true ) ) {

				$add_to_cart_fn = 'woocommerce_' . $product_type . '_add_to_cart';
				remove_action( 'woocommerce_' . $product_type . '_add_to_cart', $add_to_cart_fn, 30 );

				/**
				 * Trigger the single product add to cart action that prints the markup.
				 *
				 * @since 9.9.0
				 */
				do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );
				add_action( 'woocommerce_' . $product_type . '_add_to_cart', $add_to_cart_fn, 30 );
			}

			$form_html = $form_html . ob_get_clean();

			if ( ! $legacy_mode ) {
				$form_html = $this->render_interactivity_notices_region( $form_html );
			}
		} else {
			ob_start();

			/**
			 * Trigger the single product add to cart action that prints the markup.
			 *
			 * @since 9.7.0
			 */
			do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );

			$wrapper_attributes = array(
				'class' => $classes,
				'style' => esc_attr( $classes_and_styles['styles'] ),
			);

			$form_html = ob_get_clean();
			$form_html = sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( $wrapper_attributes ), $form_html );
		}

		$product = $previous_product;

		return $form_html;
	}

	/**
	 * Render interactivity API powered notices that can be added client-side. This reuses classes
	 * from the woocommerce/store-notices block to ensure style consistency.
	 *
	 * @param string $form_html The form HTML.
	 * @return string The rendered store notices HTML.
	 */
	protected function render_interactivity_notices_region( $form_html ) {
		$context = array(
			'notices' => array(),
		);

		ob_start();
		?>
		<div data-wp-interactive="woocommerce/store-notices" class="wc-block-components-notices alignwide" data-wp-context='<?php echo wp_json_encode( $context, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ); ?>'>
			<template data-wp-each--notice="context.notices" data-wp-each-key="context.notice.id">
				<div
					class="wc-block-components-notice-banner"
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
			<?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
