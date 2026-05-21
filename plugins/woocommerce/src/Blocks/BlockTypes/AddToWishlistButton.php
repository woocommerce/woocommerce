<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\BlocksSharedState;
use Automattic\WooCommerce\Internal\ShopperLists\ShopperListRenderer;

/**
 * Add to Wishlist Button block.
 *
 * Single-product trigger UI for the wishlist. Auto-injected by the Block
 * Hooks API after `woocommerce/add-to-cart-with-options` on the single-
 * product template (block themes only). Hidden for guests and gated by the
 * `product_wishlist` feature flag.
 *
 * On click, toggles the currently configured product (parent or selected
 * variation) in the shopper's wishlist via the shared
 * `woocommerce/shopper-lists` iAPI store. Errors are surfaced through the
 * page's existing `woocommerce/store-notices` region — no inline notices
 * area of its own.
 */
final class AddToWishlistButton extends AbstractBlock {
	/**
	 * The list slug this block writes to.
	 */
	private const LIST_SLUG = 'wishlist';

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'add-to-wishlist-button';

	/**
	 * Initialize this block type.
	 */
	protected function initialize(): void {
		parent::initialize();

		// We do not use `BlockHooksTrait` currently as it has issues with PHPStan.
		add_filter( 'hooked_block_types', array( $this, 'register_hooked_block' ), 9, 4 );
	}

	/**
	 * Auto-inject this block after `woocommerce/add-to-cart-with-options`,
	 * scoped to single-product templates on block themes. The iAPI hookups
	 * the JS frontend relies on (the products store's `productId` /
	 * `variationId` state) are seeded by the block-theme single-product
	 * pipeline — classic themes don't get them, so we never inject there.
	 *
	 * @param array                                  $hooked_block_types Block names hooked at this position.
	 * @param string                                 $relative_position  Position of the insertion point.
	 * @param string                                 $anchor_block_type  Anchor block name.
	 * @param array|\WP_Post|\WP_Block_Template|null $context            Where the block is being embedded.
	 * @return array
	 */
	public function register_hooked_block( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
		if ( 'after' !== $relative_position || 'woocommerce/add-to-cart-with-options' !== $anchor_block_type ) {
			return $hooked_block_types;
		}

		if ( ! wp_is_block_theme() ) {
			return $hooked_block_types;
		}

		// Only inject into block templates — and only the single-product
		// ones. The slug is either the canonical `single-product` or a
		// product-specific variant `single-product-{post_name}` (see
		// `SingleProductTemplate::SLUG`); both should get the button.
		if ( ! ( $context instanceof \WP_Block_Template ) ) {
			return $hooked_block_types;
		}
		if ( 'single-product' !== $context->slug && 0 !== strpos( $context->slug, 'single-product-' ) ) {
			return $hooked_block_types;
		}

		// `has_block()` accepts post/string content but not `WP_Block_Template`
		// — pass the template's `content` string so the parser can scan it.
		if ( has_block( $this->get_full_block_name(), $context->content ) ) {
			return $hooked_block_types;
		}

		$hooked_block_types[] = $this->get_full_block_name();
		return $hooked_block_types;
	}

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Guests can't have a wishlist — bail before enqueuing assets or
		// seeding state.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$post_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;
		if ( ! $post_id ) {
			return '';
		}

		$product = wc_get_product( $post_id );
		if ( ! $product instanceof \WC_Product ) {
			return '';
		}

		wp_enqueue_script_module( $this->get_full_block_name() );

		$consent = 'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WooCommerce';
		BlocksSharedState::load_store_config( $consent );

		$items = $this->prefetch_items();

		// Seed the shared shopper-lists store the same way the Wishlist
		// block does — restUrl + starter nonce + prefetched items. The
		// two blocks may both render on the same page (e.g. the merchant
		// drops the Wishlist block into a sidebar of single-product); iAPI's
		// deep-merge keeps the first writer's payload, so seeding identical
		// values here is a no-op when Wishlist already ran.
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

		// Visible labels flow through `wp_interactivity_config` so the
		// JS-side getter can pick the right one based on
		// `state.isInWishlist`. PHP renders the matching one as the
		// initial server-side label.
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

		$wrapper_attributes = array(
			'class'               => 'wc-block-add-to-wishlist-button',
			'data-wp-interactive' => 'woocommerce/add-to-wishlist-button',
			'data-wp-context'     => (string) wp_json_encode(
				array(
					'productId'      => $product->get_id(),
					'isVariableType' => $is_variable,
					'isPending'      => false,
				)
			),
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
		return (string) ob_get_clean();
	}

	/**
	 * Prefetch the wishlist items via `rest_do_request()`. Logged-out
	 * users short-circuit to an empty list — the route requires
	 * authentication and we don't want to fire an API call that's only
	 * going to 401.
	 *
	 * @return array<int, array<string, mixed>> Items in the schema response shape.
	 */
	private function prefetch_items(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/shopper-lists/' . self::LIST_SLUG . '/items' );
		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			$error   = $response->as_error();
			$message = $error instanceof \WP_Error ? $error->get_error_message() : 'Unknown error';
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
	 * Whether the current product (or its parent, for a variable parent
	 * with no selection yet) is already in the prefetched wishlist. For
	 * variable products the SSR star is always empty — we can't know which
	 * variation the shopper will pick before JS hydrates.
	 *
	 * @param array<int, array<string, mixed>> $items   Schema-shape items.
	 * @param \WC_Product                      $product The product being viewed.
	 */
	private function is_initial_in_wishlist( array $items, \WC_Product $product ): bool {
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
	 * Visible label when the shopper still needs to pick variation
	 * attributes before the wishlist toggle can resolve to a specific
	 * variation.
	 */
	private function get_select_options_label(): string {
		return __( 'Select options first', 'woocommerce' );
	}

	/**
	 * Get the frontend script handle for this block type. Scripts are
	 * loaded via `viewScriptModule` in block.json.
	 *
	 * @param string|null $key The key of the script to get.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type. Returning null
	 * lets WP use the `style` array from block.json.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Disable the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
