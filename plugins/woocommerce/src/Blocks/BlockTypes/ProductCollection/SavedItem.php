<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes\ProductCollection;

use Automattic\WooCommerce\Blocks\BlockTypes\AbstractBlock;
use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\SavedListsStore;

/**
 * SavedItem inner block.
 *
 * Renders the variation attributes, saved quantity, and a "Move to cart" button for a single
 * product row inside the save-for-later Product Collection variation. The button is wired to
 * the Interactivity API store under the `woocommerce/save-for-later` namespace; the local
 * context carries everything the Store API cart/add-item endpoint needs (id, quantity,
 * variation), so the click handler can add the product with a single request.
 */
class SavedItem extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-collection-saved-item';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered HTML.
	 */
	protected function render( $attributes, $content, $block ) {
		$post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
		if ( 0 === $post_id || ! is_user_logged_in() ) {
			return '';
		}

		$item = $this->get_saved_item_for_product( $post_id );
		if ( null === $item ) {
			return '';
		}

		$variation       = (array) ( $item['variation'] ?? array() );
		$variation_line  = $this->format_variation_line( $variation );
		$quantity        = (int) ( $item['quantity'] ?? 0 );
		$item_key        = (string) ( $item['key'] ?? '' );
		$variation_id    = (int) ( $item['variation_id'] ?? 0 );
		$add_to_cart_id  = $variation_id > 0 ? $variation_id : (int) ( $item['product_id'] ?? $post_id );
		$product         = wc_get_product( $add_to_cart_id );
		$product_type    = $product ? $product->get_type() : 'simple';

		$wrapper_attributes = get_block_wrapper_attributes(
			array( 'class' => 'wc-block-product-collection-saved-item' )
		);

		$context = wp_json_encode(
			array(
				'key'         => $item_key,
				'id'          => $add_to_cart_id,
				'quantity'    => $quantity > 0 ? $quantity : 1,
				'variation'   => $this->format_variation_for_store_api( $variation ),
				'productType' => $product_type,
			)
		);

		wp_enqueue_script_module( 'woocommerce/product-collection-saved-item' );

		// Seed the shared `woocommerce` iAPI state (restUrl, cart snapshot). The shared
		// save-for-later store delegates to cart.ts's `addCartItem`, which reads `restUrl`
		// from that namespace to build Store API URLs. Without this, cart.ts would fire
		// `GET undefinedwc/store/v1/cart`, 404, and enter an exponential retry loop.
		BlocksSharedState::load_cart_state(
			'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce'
		);

		ob_start();
		?>
		<div
			<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-wp-interactive="woocommerce/save-for-later"
			data-wp-context="<?php echo esc_attr( (string) $context ); ?>"
		>
			<?php if ( ! empty( $variation_line ) ) : ?>
				<p class="wc-block-product-collection-saved-item__variation">
					<?php echo esc_html( $variation_line ); ?>
				</p>
			<?php endif; ?>

			<p class="wc-block-product-collection-saved-item__quantity">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: Saved quantity. */
						_n( 'Qty: %d', 'Qty: %d', $quantity, 'woocommerce' ),
						$quantity
					)
				);
				?>
			</p>

			<button
				type="button"
				class="wc-block-product-collection-saved-item__move-to-cart wp-element-button"
				data-wp-on--click="actions.moveToCart"
			>
				<?php esc_html_e( 'Move to cart', 'woocommerce' ); ?>
			</button>

			<button
				type="button"
				class="wc-block-product-collection-saved-item__remove"
				data-wp-on--click="actions.removeFromList"
			>
				<?php esc_html_e( 'Remove', 'woocommerce' ); ?>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Look up the saved-for-later record for a given product ID, matching on product_id
	 * (not variation_id) because the Product Template iterates over parent products.
	 *
	 * @param int $product_id Product ID currently being rendered by Product Template.
	 * @return array|null
	 */
	private function get_saved_item_for_product( int $product_id ): ?array {
		try {
			$store = new SavedListsStore();
			$items = $store->get_items( SavedListsStore::SAVE_FOR_LATER );
		} catch ( RouteException $e ) {
			return null;
		}

		foreach ( $items as $item ) {
			if ( (int) ( $item['product_id'] ?? 0 ) === $product_id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Turn a variation array (attribute => value) into a readable line such as "Size: S, Color: Red".
	 *
	 * @param array<string, string> $variation Variation attributes keyed by raw attribute key.
	 * @return string
	 */
	private function format_variation_line( array $variation ): string {
		if ( empty( $variation ) ) {
			return '';
		}

		$parts = array();
		foreach ( $variation as $attribute => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$label   = wc_attribute_label( str_replace( 'attribute_', '', (string) $attribute ) );
			$parts[] = sprintf( '%s: %s', $label, (string) $value );
		}

		return implode( ', ', $parts );
	}

	/**
	 * Reshape the stored variation (attribute => value) into the list of
	 * { attribute, value } objects the Store API cart/add-item endpoint expects.
	 *
	 * @param array<string, string> $variation Stored variation attributes keyed by raw attribute key.
	 * @return array<int, array<string, string>>
	 */
	private function format_variation_for_store_api( array $variation ): array {
		$result = array();
		foreach ( $variation as $attribute => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$result[] = array(
				'attribute' => (string) $attribute,
				'value'     => (string) $value,
			);
		}
		return $result;
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}
}
