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
	protected function enqueue_data( array $attributes = [] ) {
		parent::enqueue_data( $attributes );
		$this->asset_data_registry->add( 'isBlockifiedAddToCart', Features::is_enabled( 'blockified-add-to-cart' ) );
		$this->asset_data_registry->add( 'productTypes', wc_get_product_types() );
		$this->asset_data_registry->add( 'addToCartWithOptionsTemplatePartIds', $this->get_template_part_ids() );
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
	 * Modifies the block context for product button blocks when inside the Add to Cart with Options block.
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

		$post_id = $block->context['postId'];

		if ( ! isset( $post_id ) ) {
			return '';
		}

		$previous_product = $product;
		$product          = wc_get_product( $post_id );
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

			$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes, array(), array( 'extra_classes' ) );

			$classes = implode(
				' ',
				array_filter(
					array(
						'wp-block-add-to-cart-with-options wc-block-add-to-cart-with-options',
						esc_attr( $classes_and_styles['classes'] ),
					)
				)
			);

			/**
			 * Filters the change the quantity to add to cart.
			 *
			 * @since 10.9.0
			 * @param number $default_quantity The default quantity.
			 * @param number $product_id The product id.
			 */
			$default_quantity = apply_filters( 'woocommerce_add_to_cart_quantity', 1, $product->get_id() );

			$context = array(
				'productId'   => $product->get_id(),
				'productType' => $product->get_type(),
				'quantity'    => $default_quantity,
			);

			if ( $product->is_type( 'variable' ) ) {
				$context['selectedAttributes']  = array();
				$context['availableVariations'] = $product->get_available_variations();
			}

			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'data-wp-interactive'       => 'woocommerce/add-to-cart-with-options',
					'data-wp-context'           => wp_json_encode(
						$context,
						JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
					),
					'data-wp-on--submit'        => 'actions.handleSubmit',
					'data-wp-class--is-invalid' => '!state.isFormValid',
					'class'                     => $classes,
					'style'                     => esc_attr( $classes_and_styles['styles'] ),
				)
			);

			$hooks_before = '';
			$hooks_after  = '';

			/**
			* Filter to disable the compatibility layer for the blockified templates.
			*
			* This hook allows to disable the compatibility layer for the blockified.
			*
			* @since 7.6.0
			* @param boolean.
			*/
			$is_disabled_compatibility_layer = apply_filters( 'woocommerce_disable_compatibility_layer', false );

			if ( ! $is_disabled_compatibility_layer ) {
				ob_start();
				if ( ProductType::SIMPLE === $product_type && $product->is_in_stock() && $product->is_purchasable() ) {
					/**
					 * Hook: woocommerce_before_add_to_cart_quantity.
					 *
					 * @since 2.7.0
					 */
					do_action( 'woocommerce_before_add_to_cart_quantity' );
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 1.5.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
				} elseif ( ProductType::EXTERNAL === $product_type ) {
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 1.5.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
				} elseif ( ProductType::GROUPED === $product_type ) {
					/**
					 * Hook: woocommerce_before_add_to_cart_button.
					 *
					 * @since 1.5.0
					 */
					do_action( 'woocommerce_before_add_to_cart_button' );
				} elseif ( ProductType::VARIABLE === $product_type ) {
					/**
					 * Hook: woocommerce_before_variations_form.
					 *
					 * @since 2.4.0
					 */
					do_action( 'woocommerce_before_variations_form' );
				}
				$hooks_before = ob_get_clean();

				ob_start();
				if ( ProductType::SIMPLE === $product_type && $product->is_in_stock() && $product->is_purchasable() ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_quantity.
					 *
					 * @since 2.7.0
					 */
					do_action( 'woocommerce_after_add_to_cart_quantity' );
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 1.5.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
				} elseif ( ProductType::EXTERNAL === $product_type ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 1.5.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
				} elseif ( ProductType::GROUPED === $product_type ) {
					/**
					 * Hook: woocommerce_after_add_to_cart_button.
					 *
					 * @since 1.5.0
					 */
					do_action( 'woocommerce_after_add_to_cart_button' );
				} elseif ( ProductType::VARIABLE === $product_type ) {
					/**
					 * Hook: woocommerce_after_variations_form.
					 *
					 * @since 2.4.0
					 */
					do_action( 'woocommerce_after_variations_form' );
				}
				$hooks_after = ob_get_clean();
			}

			// Because we are printing the template part using do_blocks, context from the outside is lost.
			// This filter is used to add the isDescendantOfAddToCartWithOptions context back.
			add_filter( 'render_block_context', array( $this, 'set_is_descendant_of_add_to_cart_with_options_context' ), 10, 2 );
			$template_part_blocks = do_blocks( $template_part_contents );
			remove_filter( 'render_block_context', array( $this, 'set_is_descendant_of_add_to_cart_with_options_context' ) );

			$form_html = sprintf(
				'<form %1$s>%2$s%3$s%4$s</form>',
				$wrapper_attributes,
				$hooks_before,
				$template_part_blocks,
				$hooks_after,
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
		} else {
			ob_start();

			/**
			 * Trigger the single product add to cart action that prints the markup.
			 *
			 * @since 9.7.0
			 */
			do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );

			$form_html = ob_get_clean();
		}

		$product = $previous_product;

		return $form_html;
	}
}
