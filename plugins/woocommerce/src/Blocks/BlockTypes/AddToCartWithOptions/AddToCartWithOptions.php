<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes\AddToCartWithOptions;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\BlockTypes\EnableBlockJsonAssetsTrait;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListsController;

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
	 * Get the template part path for a product type.
	 *
	 * @param string $product_type The product type.
	 * @return string|bool The template part path if it exists, false otherwise.
	 */
	protected function get_template_part_path( $product_type ) {
		if ( in_array( $product_type, array( ProductType::SIMPLE, ProductType::EXTERNAL, ProductType::VARIABLE, ProductType::GROUPED ), true ) ) {
			return Package::get_path() . 'templates/' . BlockTemplateUtils::DIRECTORY_NAMES['TEMPLATE_PARTS'] . '/' . $product_type . '-product-add-to-cart-with-options.html';
		}

		/**
		 * Experimental filter for extensions to register a block template part
		 * for a product type.
		 *
		 * @since 9.9.0
		 * @param string|boolean $template_part_path The template part path if it exists
		 * @param string $product_type The product type
		 */
		return apply_filters( '__experimental_woocommerce_' . $product_type . '_add_to_cart_with_options_block_template_part', false, $product_type );
	}

	/**
	 * Product type string used to resolve add-to-cart template parts.
	 * It returns the product type in most cases, except for variations,
	 * which use the simple product template.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return string Product type slug.
	 */
	private function get_product_type_for_add_to_cart_template( \WC_Product $product ): string {
		return ProductType::VARIATION === $product->get_type() ? ProductType::SIMPLE : $product->get_type();
	}

	/**
	 * Enqueue assets specific to this block.
	 * We enqueue frontend scripts only if the product type has a block template
	 * part (that's WC core product types and extensions that migrated to block
	 * templates).
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 *
	 * @return void
	 */
	protected function enqueue_assets( $attributes, $content, $block ) {
		$product_id = ( is_object( $block ) && property_exists( $block, 'context' ) && is_array( $block->context ) && array_key_exists( 'postId', $block->context ) ) ? $block->context['postId'] : null;

		if ( isset( $product_id ) ) {
			$rendered_product = wc_get_product( $product_id );

			if ( $rendered_product instanceof \WC_Product ) {
				$product_type       = $this->get_product_type_for_add_to_cart_template( $rendered_product );
				$template_part_path = $this->get_template_part_path( $product_type );

				if ( is_string( $template_part_path ) && '' !== $template_part_path && file_exists( $template_part_path ) ) {
					wp_enqueue_script_module( 'woocommerce/add-to-cart-with-options' );
				}
			}
		}

		parent::enqueue_assets( $attributes, $content, $block );
	}

	/**
	 * Extra data passed through from server to client for block.
	 *
	 * @param array $attributes  Any attributes that currently are available from the block.
	 *                           Note, this will be empty in the editor context when the block is
	 *                           not in the post content on editor load.
	 * @return void
	 */
	protected function enqueue_data( array $attributes = array() ): void {
		parent::enqueue_data( $attributes );

		if ( is_admin() ) {
			$this->asset_data_registry->add( 'productTypes', wc_get_product_types() );
			$this->asset_data_registry->add( 'addToCartWithOptionsTemplatePartIds', $this->get_template_part_ids() );
			$this->asset_data_registry->add( 'wishlistFeatureEnabled', $this->is_wishlist_enabled() );
		}
	}

	/**
	 * Whether the wishlist feature is enabled.
	 *
	 * Gates the render-time injection of the Add to Wishlist Button block (and
	 * its editor preview), so the button only appears while the (experimental,
	 * feature-flagged) wishlist feature is on.
	 *
	 * @return bool True if the wishlist feature flag is enabled, false otherwise.
	 */
	private function is_wishlist_enabled(): bool {
		return wc_get_container()->get( ShopperListsController::class )->is_enabled( 'wishlist' );
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
	 * Check if HTML content has form elements.
	 *
	 * @param string $html_content The HTML content.
	 * @return bool True if the HTML content has form elements, false otherwise.
	 */
	public function has_form_elements( $html_content ) {
		$processor     = new \WP_HTML_Tag_Processor( $html_content );
		$form_elements = array( 'INPUT', 'TEXTAREA', 'SELECT', 'BUTTON', 'FORM' );
		while ( $processor->next_tag() ) {
			if ( in_array( $processor->get_tag(), $form_elements, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content Block content.
	 * @param \WP_Block $block Block instance.
	 *
	 * @return string|void Rendered block output.
	 */
	protected function render( $attributes, $content, $block ) {
		global $product;

		$product_id = ( is_object( $block ) && property_exists( $block, 'context' ) && is_array( $block->context ) && array_key_exists( 'postId', $block->context ) ) ? $block->context['postId'] : null;

		if ( ! isset( $product_id ) ) {
			return '';
		}

		$previous_product = $product;
		$product          = wc_get_product( $product_id );
		if ( ! $product instanceof \WC_Product ) {
			$product = $previous_product;

			return '';
		}

		$product_type = $this->get_product_type_for_add_to_cart_template( $product );

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

		$template_part_path = $this->get_template_part_path( $product_type );

		if ( is_string( $template_part_path ) && '' !== $template_part_path && file_exists( $template_part_path ) ) {
			$slug                   = $product_type . '-product-add-to-cart-with-options';
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

			$product_id = $product->get_id();

			// SSR fallback for `state.isFormValid` before hydration. It must
			// resolve per-element, not per-page: `isFormValid` is a
			// namespace-global state slot, so a closure capturing a single
			// product id would let the last-rendered form's value win for every
			// form on the page. Deriving the product from the ambient
			// `woocommerce/products` context (the same source the client getter
			// uses) keeps each form's SSR value correct on multi-product pages.
			// This SSR closure is a DELIBERATELY CONSERVATIVE approximation of the
			// client `isFormValid` predicate, not a faithful mirror: it only knows
			// the product type and whether options exist (grouped / has-options →
			// invalid until the shopper interacts), because at first paint there is
			// no draft or selection to validate against. The client predicate is
			// the source of truth once hydrated (it validates the actual draft
			// quantity, variation selection, and grouped child quantities). Keep
			// this approximation as-is — do NOT try to reproduce the full client
			// logic server-side; its only job is a sensible pre-hydration default
			// for the add button's disabled state.
			wp_interactivity_state(
				'woocommerce/add-to-cart-with-options',
				array(
					'isFormValid' => function () {
						$product_context    = wp_interactivity_get_context( 'woocommerce/products' );
						$context_product_id = $product_context['productId'] ?? null;

						if ( null === $context_product_id ) {
							$products_state     = wp_interactivity_state( 'woocommerce/products' );
							$context_product_id = $products_state['productId'] ?? null;
						}

						if ( null === $context_product_id ) {
							return true;
						}

						$product = wc_get_product( $context_product_id );

						if ( $product instanceof \WC_Product && ( $product->is_type( ProductType::GROUPED ) || $product->has_options() ) ) {
							return false;
						}
						return true;
					},
				)
			);

			wp_interactivity_config(
				'woocommerce/add-to-cart-with-options',
				array(
					'errorMessages' => array(
						'invalidQuantities'                => esc_html__(
							'Please select a valid quantity to add to the cart.',
							'woocommerce'
						),
						'groupedProductAddToCartMissingItems' => esc_html__(
							'Please select some products to add to the cart.',
							'woocommerce'
						),
						'variableProductMissingAttributes' => esc_html__(
							'Please select product attributes before adding to cart.',
							'woocommerce'
						),
						'variableProductOutOfStock'        => sprintf(
							/* translators: %s: product name */
							esc_html__(
								'You cannot add &quot;%s&quot; to the cart because the product is out of stock.',
								'woocommerce'
							),
							$product->get_name()
						),
					),
				)
			);

			// Load product into the shared store with full REST API data.
			wc_interactivity_api_load_product(
				'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
				$product->get_id()
			);

			// Shopper input for this form (quantity + selected variation) lives in
			// the shared `woocommerce/cart` store as a draft, born client-side on
			// first interaction via `upsertDraftItem` — never seeded server-side.
			// The form context therefore carries only its own validation state and,
			// for grouped products, the child ids the validation/batch-add loop over.
			$context = array(
				'validationErrors' => array(),
			);

			if ( $product->is_type( ProductType::VARIABLE ) ) {
				// Load all variations into the shared store with full REST API data.
				wc_interactivity_api_load_variations(
					'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
					$product->get_id()
				);
			} elseif ( $product->is_type( ProductType::GROUPED ) ) {
				// Load purchasable child products into the shared store with full REST API data.
				$child_products = wc_interactivity_api_load_purchasable_child_products(
					'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
					$product->get_id()
				);

				$context['groupedProductIds'] = array_keys( $child_products );
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

			// Add to Wishlist Button: when the wishlist feature is enabled,
			// inject the button as the last child of the template part so it
			// renders inside the form's iAPI scope (where it can read the
			// selected variation/attributes). The markup is injected at render
			// time only, never persisted to the shipped template parts.
			if ( $this->is_wishlist_enabled() ) {
				$template_part_contents .= "\n<!-- wp:woocommerce/add-to-wishlist-button /-->";
			}

			// Because we are printing the template part using do_blocks, context from the outside is lost.
			// This filter is used to add the isDescendantOfAddToCartWithOptions context back.
			add_filter( 'render_block_context', array( $this, 'set_is_descendant_of_add_to_cart_with_options_context' ), 10, 2 );
			$template_part_blocks = do_blocks( $template_part_contents );
			remove_filter( 'render_block_context', array( $this, 'set_is_descendant_of_add_to_cart_with_options_context' ) );

			$wrapper_attributes = array(
				'class'                            => $classes,
				'style'                            => esc_attr( $classes_and_styles['styles'] ),
				'data-wp-interactive'              => 'woocommerce/add-to-cart-with-options',
				'data-wp-class--is-invalid'        => '!state.isFormValid',
				// Re-run quantity validation whenever the context draft's quantity
				// changes. This replaces the side effect the removed `setQuantity`
				// write path used to perform inline.
				'data-wp-watch--validate-quantity' => 'callbacks.validateQuantityConstraints',
			);
			$context_directive  = wp_interactivity_data_wp_context( $context );

			$cart_redirect_after_add = get_option( 'woocommerce_cart_redirect_after_add' );
			$form_attributes         = '';
			$legacy_mode             = 'yes' === $cart_redirect_after_add || $this->has_form_elements( $hooks_before ) || $this->has_form_elements( $hooks_after );
			if ( $legacy_mode ) {
				$action_url = home_url( add_query_arg( null, null ) );

				// If an extension is hooking into the form or we need to redirect to the cart,
				// we fall back to a regular HTML form.
				$form_attributes = array(
					'action'  => esc_url(
						/**
						 * Filter the add to cart form action.
						 *
						 * @since 10.0.0
						 * @param string $action_url The add to cart form action URL, defaulting to the current page.
						 * @return string The add to cart form action URL.
						 */
						apply_filters( 'woocommerce_add_to_cart_form_action', $action_url )
					),
					'method'  => 'post',
					'enctype' => 'multipart/form-data',
					'class'   => 'cart',
				);
			} else {
				// Otherwise, we use the Interactivity API.
				$form_attributes = array(
					'data-wp-on--submit' => 'actions.addToCart',
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
						data-wp-bind--value="woocommerce/products::state.productVariationInContext.id"
					/>
				</div>';
			}

			$form_html = sprintf(
				'<form %1$s %2$s>%3$s%4$s%5$s%6$s</form>',
				get_block_wrapper_attributes(
					array_merge(
						$wrapper_attributes,
						$form_attributes,
						array(
							'class' => implode(
								' ',
								array_filter(
									array(
										$wrapper_attributes['class'],
										isset( $form_attributes['class'] ) ? $form_attributes['class'] : '',
										// Add the `is-layout-flow` class so inner elements automatically get the
										// default vertical margin from the theme. That's especially useful for
										// elements added by extensions like express payment method buttons.
										// In the future, we want to use `supports.layout` in block.json instead
										// of hardcoding the class here. However, right now that wouldn't work
										// because the wrapper element of the block is the notices `<div>`, so the
										// `is-layout-flow` class would be applied to the notices container instead
										// of the `<form>` as we want.
										'is-layout-flow',
									)
								)
							),
						)
					)
				),
				$context_directive,
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
				do_action( 'woocommerce_' . $product_type . '_add_to_cart' );
				add_action( 'woocommerce_' . $product_type . '_add_to_cart', $add_to_cart_fn, 30 );
			}

			$form_html = $form_html . ob_get_clean();

			// No shared-context wrapper here: the form's product identity is
			// resolved through the products store's `mainProductInContext` derived
			// state, which reads the `woocommerce/products` context (a per-element
			// context in query loops / SingleProduct cards) or the products store's
			// global `state.productId` (seeded by `SingleProductTemplate` on the
			// single product page). Everything inside the form — the submit handler
			// (`actions.addToCart` → `addItem()`), the variation selector, the
			// quantity selector — keys its draft off that derived product id.
			// Deliberately NOT wrapping the form in its own `woocommerce/products`
			// context keeps the variation selector's `variationId` write GLOBAL on
			// the single product page, which the Product Gallery reads for
			// variation-image switching.

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
			do_action( 'woocommerce_' . $product_type . '_add_to_cart' );

			$wrapper_attributes = array(
				'class' => $classes,
				'style' => esc_attr( $classes_and_styles['styles'] ),
			);

			$form_html = ob_get_clean();

			$has_visible_quantity_input = $form_html ? Utils::has_visible_quantity_input( $form_html ) : false;
			if ( $has_visible_quantity_input ) {
				$product_name                              = $product->get_name();
				$form_html                                 = Utils::add_quantity_steppers( $form_html, $product_name );
				$form_html                                 = Utils::add_quantity_stepper_classes( $form_html );
				$wrapper_attributes['data-wp-interactive'] = 'woocommerce/add-to-cart-form';
				wp_enqueue_script_module( 'woocommerce/add-to-cart-form' );
			}

			$form_html = sprintf(
				'<div %1$s>%2$s</div>',
				get_block_wrapper_attributes( $wrapper_attributes ),
				$form_html
			);
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
		$context_directive = wp_interactivity_data_wp_context(
			array(
				'notices' => array(),
			)
		);

		ob_start();
		?>
		<div data-wp-interactive="woocommerce/store-notices" class="wc-block-components-notices alignwide" <?php echo $context_directive; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<template data-wp-each--notice="context.notices" data-wp-each-key="context.notice.id">
				<div
					class="wc-block-components-notice-banner"
					data-wp-class--is-error="state.isError"
					data-wp-class--is-success="state.isSuccess"
					data-wp-class--is-info="state.isInfo"
					data-wp-class--is-dismissible="context.notice.dismissible"
					data-wp-bind--role="state.role"
					data-wp-watch="callbacks.injectIcon"
				>
					<div class="wc-block-components-notice-banner__content">
						<span data-wp-init="callbacks.renderNoticeContent" aria-live="assertive" aria-atomic="true"></span>
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
