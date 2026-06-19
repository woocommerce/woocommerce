<?php
/**
 * Server-side rendering of the `woocommerce/add-to-wishlist-button` block.
 *
 * @package WooCommerce\Blocks
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListRenderer;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListsController;

/**
 * Server renderer for the `woocommerce/add-to-wishlist-button` block.
 *
 * @since 11.0.0
 */
final class WooCommerce_Block_Library_Add_To_Wishlist_Button {
	/**
	 * The list slug this block writes to.
	 */
	private const LIST_SLUG = 'wishlist';

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	public function render( array $attributes, string $content, $block ): string {
		unset( $attributes, $content );

		if ( ! $block instanceof WP_Block || ! is_user_logged_in() ) {
			return '';
		}

		$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );

		$items = $this->prefetch_items();

		wp_interactivity_state(
			'woocommerce/shopper-lists',
			array(
				'restUrl' => get_rest_url(),
				'nonce'   => wp_create_nonce( 'wc_store_api' ),
				'lists'   => array(
					self::LIST_SLUG => array(
						'items'     => $items,
						'isLoading' => false,
					),
				),
			)
		);

		wp_interactivity_config(
			'woocommerce/add-to-wishlist-button',
			array(
				'addLabel'           => $this->get_add_label(),
				'savedLabel'         => $this->get_saved_label(),
				'selectOptionsLabel' => $this->get_select_options_label(),
			)
		);

		$is_variable            = $product->is_type( 'variable' );
		$initial_is_in_wishlist = $this->is_initial_in_wishlist( $items, $product );
		$initial_disabled       = $is_variable;
		$initial_label          = $is_variable
			? $this->get_select_options_label()
			: ( $initial_is_in_wishlist ? $this->get_saved_label() : $this->get_add_label() );

		$context_json = wp_json_encode(
			array(
				'productId'      => $product->get_id(),
				'isVariableType' => $is_variable,
				'isPending'      => false,
			)
		);

		$wrapper_attributes = array(
			'class'               => 'wc-block-add-to-wishlist-button',
			'data-wp-interactive' => 'woocommerce/add-to-wishlist-button',
			'data-wp-context'     => false === $context_json ? '{}' : $context_json,
		);

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( $wrapper_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes returns escaped attribute markup. ?>>
			<button
				type="button"
				class="wc-block-add-to-wishlist-button__toggle"
				data-wp-on--click="actions.onClickToggle"
				data-wp-bind--aria-pressed="state.isInWishlist"
				data-wp-bind--disabled="state.isDisabled"
				<?php echo $initial_is_in_wishlist ? 'aria-pressed="true"' : 'aria-pressed="false"'; ?>
				<?php
				if ( $initial_disabled ) {
					echo 'disabled';
				}
				?>
			>
				<span class="wc-block-add-to-wishlist-button__icon wc-block-add-to-wishlist-button__icon--empty" data-wp-bind--hidden="state.isInWishlist"
				<?php
				if ( $initial_is_in_wishlist ) {
					echo ' hidden';
				}
				?>
				>
					<?php echo ShopperListRenderer::get_star_empty_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
				</span>
				<span class="wc-block-add-to-wishlist-button__icon wc-block-add-to-wishlist-button__icon--filled" data-wp-bind--hidden="!state.isInWishlist"
				<?php
				if ( ! $initial_is_in_wishlist ) {
					echo ' hidden';
				}
				?>
				>
					<?php echo ShopperListRenderer::get_star_filled_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?>
				</span>
				<span class="wc-block-add-to-wishlist-button__label" data-wp-text="state.currentLabel"><?php echo esc_html( $initial_label ); ?></span>
			</button>
		</div>
		<?php
		$output = ob_get_clean();
		return is_string( $output ) ? $output : '';
	}

	/**
	 * Get the full block name.
	 *
	 * @return string Full block name.
	 */
	private function get_full_block_name(): string {
		return 'woocommerce/add-to-wishlist-button';
	}

	/**
	 * Prefetch wishlist items via `rest_do_request()`.
	 *
	 * @return array<int, array<string, mixed>> Items in the schema response shape.
	 */
	private function prefetch_items(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$request  = new WP_REST_Request( 'GET', '/wc/store/v1/shopper-lists/' . self::LIST_SLUG . '/items' );
		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			$error   = $response->as_error();
			$message = $error instanceof WP_Error ? $error->get_error_message() : 'Unknown error';
			wc_get_logger()->debug(
				sprintf( 'Add to Wishlist button prefetch failed: %s', $message ),
				array(
					'source' => 'add-to-wishlist-button',
					'data'   => array( 'slug' => self::LIST_SLUG ),
				)
			);
			return array();
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) && ! is_object( $data ) ) {
			return array();
		}

		$decoded = json_decode( (string) wp_json_encode( $data ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Whether the current product is already in the prefetched wishlist.
	 *
	 * @param array<int, array<string, mixed>> $items   Schema-shape items.
	 * @param WC_Product                       $product The product being viewed.
	 */
	private function is_initial_in_wishlist( array $items, WC_Product $product ): bool {
		if ( $product->is_type( 'variable' ) ) {
			return false;
		}

		$product_id = $product->get_id();
		foreach ( $items as $item ) {
			if ( isset( $item['id'] ) && (int) $item['id'] === $product_id ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Visible label when the product is not in the wishlist.
	 */
	private function get_add_label(): string {
		return __( 'Add to wishlist', 'woocommerce' );
	}

	/**
	 * Visible label when the product is already in the wishlist.
	 */
	private function get_saved_label(): string {
		return __( 'Saved to wishlist', 'woocommerce' );
	}

	/**
	 * Visible label when the shopper still needs to pick variation attributes.
	 */
	private function get_select_options_label(): string {
		return __( 'Select options first', 'woocommerce' );
	}
}

/**
 * Registers the `woocommerce/add-to-wishlist-button` block on the server.
 *
 * @since 11.0.0
 */
function register_block_woocommerce_add_to_wishlist_button(): void {
	if ( WP_Block_Type_Registry::get_instance()->is_registered( 'woocommerce/add-to-wishlist-button' ) ) {
		return;
	}

	if ( ! wc_get_container()->get( ShopperListsController::class )->is_enabled( 'wishlist' ) ) {
		return;
	}

	$renderer = new WooCommerce_Block_Library_Add_To_Wishlist_Button();
	register_block_type_from_metadata(
		__DIR__,
		array(
			'render_callback' => array( $renderer, 'render' ),
		)
	);
}

add_action( 'init', 'register_block_woocommerce_add_to_wishlist_button' );
