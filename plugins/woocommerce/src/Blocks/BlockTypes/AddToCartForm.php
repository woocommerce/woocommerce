<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * CatalogSorting class.
 */
class AddToCartForm extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-cart-form';

	/**
	 * Initializes the AddToCartForm block and hooks into the `wc_add_to_cart_message_html` filter
	 * to prevent displaying the Cart Notice when the block is inside the Single Product block
	 * and the Add to Cart button is clicked.
	 *
	 * It also hooks into the `woocommerce_add_to_cart_redirect` filter to prevent redirecting
	 * to another page when the block is inside the Single Product block and the Add to Cart button
	 * is clicked.
	 *
	 * @return void
	 */
	protected function initialize() {
		parent::initialize();
		add_filter( 'wc_add_to_cart_message_html', array( $this, 'add_to_cart_message_html_filter' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'add_to_cart_redirect_filter' ), 10, 1 );
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
			'isDescendentOfSingleProductBlock' => false,
			'quantitySelectorStyle'            => 'input',
		);

		return wp_parse_args( $attributes, $defaults );
	}


	/**
	 * Enqueue assets specific to this block.
	 * We enqueue frontend scripts only if the quantitySelectorStyle is set to 'stepper'.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block Block instance.
	 */
	protected function enqueue_assets( $attributes, $content, $block ) {
		if ( 'stepper' !== $attributes['quantitySelectorStyle'] ) {
			return;
		}

		parent::enqueue_assets( $attributes, $content, $block );
	}

	/**
	 * Add increment and decrement buttons to the quantity input field.
	 *
	 * @param string $product add-to-cart form HTML.
	 * @return stringa add-to-cart form HTML with increment and decrement buttons.
	 */
	private function add_steppers( $product ) {
		// Regex pattern to match the <input> element with id starting with 'quantity_'.
		$pattern = '/(<input[^>]*id="quantity_[^"]*"[^>]*\/>)/';
		// Replacement string to add button BEFORE the matched <input> element.
		$minus_button = '<button type="button" data-wc-on--click="actions.removeQuantity" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">-</button>$1';
		// Replacement string to add button AFTER the matched <input> element.
		$plus_button = '$1<button type="button" data-wc-on--click="actions.addQuantity" class="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">+</button>';
		$new_html    = preg_replace( $pattern, $minus_button, $product );
		$new_html    = preg_replace( $pattern, $plus_button, $new_html );
		return $new_html;
	}

	/**
	 * Add classes to the Add to Cart form input.
	 *
	 * @param string $product The Add to Cart form HTML.
	 * @param array  $attributes Block attributes.
	 *
	 * @return string The Add to Cart form HTML with classes added.
	 */
	private function add_classes_to_add_to_cart_form_input( $product, $attributes ) {
		$is_stepper_style = 'stepper' === $attributes['quantitySelectorStyle'];

		$html = new \WP_HTML_Tag_Processor( $product );

		if ( $is_stepper_style ) {
			// Add classes to the form.
			while ( $html->next_tag( array( 'class_name' => 'quantity' ) ) ) {
				$html->add_class( 'wc-block-components-quantity-selector' );
			}

			$html = new \WP_HTML_Tag_Processor( $html->get_updated_html() );
			while ( $html->next_tag( array( 'class_name' => 'input-text' ) ) ) {
				$html->add_class( 'wc-block-components-quantity-selector__input' );
			}
		}

		return $html->get_updated_html();
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

		$is_external_product_with_url = $product instanceof \WC_Product_External && $product->get_product_url();

		ob_start();

		/**
		 * Trigger the single product add to cart action for each product type.
		 *
		 * @since 9.7.0
		 */
		do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );

		$product = ob_get_clean();

		if ( ! $product ) {
			$product = $previous_product;

			return '';
		}

		$is_stepper_style = 'stepper' === $attributes['quantitySelectorStyle'];

		$product = $is_stepper_style ? $this->add_steppers( $product ) : $product;

		$parsed_attributes                     = $this->parse_attributes( $attributes );
		$is_descendent_of_single_product_block = $parsed_attributes['isDescendentOfSingleProductBlock'];

		if ( ! $is_external_product_with_url ) {
			$product = $this->add_is_descendent_of_single_product_block_hidden_input_to_product_form( $product, $is_descendent_of_single_product_block );
		}

		$product = $this->add_classes_to_add_to_cart_form_input( $product, $attributes );

		$classes_and_styles = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
		$product_classname  = $is_descendent_of_single_product_block ? 'product' : '';

		$classes = implode(
			' ',
			array_filter(
				array(
					'wp-block-add-to-cart-form wc-block-add-to-cart-form',
					esc_attr( $classes_and_styles['classes'] ),
					esc_attr( $product_classname ),
					$is_stepper_style ? 'wc-block-add-to-cart-form--stepper' : 'wc-block-add-to-cart-form--input',
				)
			)
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => $classes,
				'style' => esc_attr( $classes_and_styles['styles'] ),
			)
		);

		$form = sprintf(
			'<div %1$s %2$s>%3$s</div>',
			$wrapper_attributes,
			$is_stepper_style ? 'data-wc-interactive=\'' . wp_json_encode(
				array(
					'namespace' => 'woocommerce/add-to-cart-form',
				),
				JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
			) . '\'' : '',
			$product
		);

		$product = $previous_product;

		return $form;
	}

	/**
	 * Add a hidden input to the Add to Cart form to indicate that it is a descendent of a Single Product block.
	 *
	 * @param string $product The Add to Cart Form HTML.
	 * @param string $is_descendent_of_single_product_block Indicates if block is descendent of Single Product block.
	 *
	 * @return string The Add to Cart Form HTML with the hidden input.
	 */
	protected function add_is_descendent_of_single_product_block_hidden_input_to_product_form( $product, $is_descendent_of_single_product_block ) {

		$hidden_is_descendent_of_single_product_block_input = sprintf(
			'<input type="hidden" name="is-descendent-of-single-product-block" value="%1$s">',
			$is_descendent_of_single_product_block ? 'true' : 'false'
		);
		$regex_pattern                                      = '/<button\s+type="submit"[^>]*>.*?<\/button>/i';

		preg_match( $regex_pattern, $product, $input_matches );

		if ( ! empty( $input_matches ) ) {
			$product = preg_replace( $regex_pattern, $hidden_is_descendent_of_single_product_block_input . $input_matches[0], $product );
		}

		return $product;
	}

	/**
	 * Filter the add to cart message to prevent the Notice from being displayed when the Add to Cart form is a descendent of a Single Product block
	 * and the Add to Cart button is clicked.
	 *
	 * @param string $message Message to be displayed when product is added to the cart.
	 */
	public function add_to_cart_message_html_filter( $message ) {
		// phpcs:ignore
		if ( isset( $_POST['is-descendent-of-single-product-block'] ) && 'true' === $_POST['is-descendent-of-single-product-block'] ) {
			return false;
		}
		return $message;
	}

	/**
	 * Hooks into the `woocommerce_add_to_cart_redirect` filter to prevent redirecting
	 * to another page when the block is inside the Single Product block and the Add to Cart button
	 * is clicked.
	 *
	 * @param string $url The URL to redirect to after the product is added to the cart.
	 * @return string The filtered redirect URL.
	 */
	public function add_to_cart_redirect_filter( $url ) {
		// phpcs:ignore
		if ( isset( $_POST['is-descendent-of-single-product-block'] ) && 'true' == $_POST['is-descendent-of-single-product-block'] ) {

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				return wc_get_cart_url();
			}

			return wp_validate_redirect( wp_get_referer(), $url );
		}

		return $url;
	}
}
