<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * Shopper Collection block (e.g. Saved for Later).
 */
final class ShopperCollection extends AbstractBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'shopper-collection';

	/**
	 * Render the block.
	 *
	 * @param array     $attributes Block attributes.
	 * @param string    $content    Block content.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		$column_count = max( 1, (int) ( $attributes['layout']['columnCount'] ?? 3 ) );
		$list_name    = (string) ( $attributes['listName'] ?? 'saved-for-later' );

		wp_interactivity_config(
			'woocommerce/shopper-collections',
			array(
				'restUrl'           => esc_url_raw( rest_url() ),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'placeholderImgSrc' => self::placeholder_image(),
			)
		);

		wp_interactivity_state(
			'woocommerce/shopper-collections',
			array(
				'lists' => array(
					$list_name => $this->prefetch_list_state( $list_name ),
				),
			)
		);

		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class'               => 'wc-block-shopper-collection',
				'data-wp-interactive' => 'woocommerce/shopper-collections',
				'data-wp-key'         => wp_unique_prefixed_id( $this->get_full_block_name() ),
				'style'               => sprintf( '--wc-shopper-collection-columns:%d;', $column_count ),
			)
		);

		// Bind the empty-state to `.items.0.key` (string), not `.items.0` (array):
		// the HTML tag processor's set_attribute() calls strtr() and crashes on arrays.
		$items_path          = sprintf( 'state.lists.%s.items', $list_name );
		$first_item_key_path = $items_path . '.0.key';
		$context             = array( 'listName' => $list_name );

		ob_start();
		?>
		<ul
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core helper output. ?>
			<?php echo $wrapper_attributes; ?>
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core helper output. ?>
			<?php echo wp_interactivity_data_wp_context( $context ); ?>
		>
			<template data-wp-each--list-item="<?php echo esc_attr( $items_path ); ?>" data-wp-each-key="context.listItem.key">
				<li class="wc-block-shopper-collection-item">
					<div class="wc-block-components-product-image wc-block-components-product-image--aspect-ratio-auto">
						<a data-wp-bind--href="context.listItem.permalink">
							<img data-wp-bind--src="context.listItem.thumbnail" data-wp-bind--alt="context.listItem.alt" data-wp-bind--hidden="!context.listItem.hasImage">
						</a>
						<div class="wc-block-components-product-image__inner-container">
							<button
								type="button"
								class="wc-block-shopper-collection-item__remove"
								aria-label="<?php esc_attr_e( 'Remove from list', 'woocommerce' ); ?>"
								data-wp-on--click="actions.removeCurrentItem"
							>
								<?php esc_html_e( 'Remove', 'woocommerce' ); ?>
							</button>
						</div>
					</div>
					<h2 class="wp-block-post-title has-text-align-center has-medium-font-size">
						<a data-wp-bind--href="context.listItem.permalink" data-wp-text="context.listItem.name"></a>
					</h2>
					<div class="price wc-block-components-product-price has-text-align-center has-small-font-size" data-wp-bind--hidden="!context.listItem.product_exists">
						<span class="wc-block-components-product-price__value" data-wp-text="context.listItem.priceLabel"></span>
					</div>
					<span class="wc-block-shopper-collection-item__quantity" data-wp-text="context.listItem.quantityLabel" data-wp-bind--hidden="!context.listItem.product_exists"></span>
					<div class="wp-block-button wc-block-components-product-button" data-wp-bind--hidden="!context.listItem.product_exists">
						<button
							type="button"
							class="wp-block-button__link wp-element-button add_to_cart_button wc-block-components-product-button__button"
							data-wp-on--click="actions.moveCurrentItemToCart"
						>
							<?php esc_html_e( 'Move to cart', 'woocommerce' ); ?>
						</button>
					</div>
				</li>
			</template>
			<li class="wc-block-shopper-collection__empty" data-wp-bind--hidden="<?php echo esc_attr( $first_item_key_path ); ?>">
				<?php esc_html_e( 'Nothing saved yet — items you save from the cart will appear here.', 'woocommerce' ); ?>
			</li>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Pre-fetch the active list via the Store API for SSR seeding of the iAPI store.
	 *
	 * @param string $list_name Slug of the list to prefetch.
	 * @return array{items: array<int, array<string, mixed>>, isLoading: bool, error: ?string}
	 */
	private function prefetch_list_state( string $list_name ): array {
		$state = array(
			'items'     => array(),
			'isLoading' => false,
			'error'     => null,
		);

		if ( ! is_user_logged_in() ) {
			return $state;
		}

		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/shopper-lists/' . $list_name . '/items' );
		$response = rest_do_request( $request );
		$error    = $response->as_error();

		if ( $error instanceof \WP_Error ) {
			$state['error'] = $error->get_error_message();
			return $state;
		}

		// JSON round-trip flattens nested stdClass (images[], prices); the iAPI
		// server-side path resolver only traverses associative arrays.
		$decoded = json_decode( (string) wp_json_encode( $response->get_data() ), true );

		if ( is_array( $decoded ) ) {
			$state['items'] = array_map(
				static fn( $item ) => self::enrich_item_for_display( $item ),
				$decoded
			);
		}

		return $state;
	}

	/**
	 * Add display fields to a raw item. Mirrored on the JS side so SSR and CSR match.
	 *
	 * @param array $item Raw item array from the Store API.
	 * @return array
	 */
	private static function enrich_item_for_display( array $item ): array {
		$placeholder = self::placeholder_image();
		$thumbnail   = (string) ( $item['images'][0]['thumbnail'] ?? '' );
		$alt         = (string) ( $item['images'][0]['alt'] ?? '' );
		$name        = (string) $item['name'];

		$item['thumbnail']     = '' !== $thumbnail ? $thumbnail : $placeholder;
		$item['alt']           = '' !== $alt ? $alt : $name;
		$item['hasImage']      = '' !== $thumbnail || '' !== $placeholder;
		$item['quantityLabel'] = sprintf( 'Qty: %d', absint( $item['quantity'] ) );
		$item['priceLabel']    = is_array( $item['prices'] )
			? self::format_currency_amount( $item['prices'] )
			: '';

		return $item;
	}

	/**
	 * Memoized thumbnail placeholder URL.
	 *
	 * @return string
	 */
	private static function placeholder_image(): string {
		static $cached = null;
		if ( null === $cached ) {
			$cached = (string) wc_placeholder_img_src( 'woocommerce_thumbnail' );
		}
		return $cached;
	}

	/**
	 * Format a Store API `prices` payload. Mirrors JS `formatPriceWithCurrency`.
	 *
	 * @param array $prices Money response (`price`, `currency_*` fields).
	 * @return string
	 */
	private static function format_currency_amount( array $prices ): string {
		$price = (string) $prices['price'];
		if ( '' === $price ) {
			return '';
		}

		$minor_unit = (int) $prices['currency_minor_unit'];
		$amount     = (int) $price / ( 10 ** $minor_unit );
		$formatted  = number_format(
			$amount,
			$minor_unit,
			(string) $prices['currency_decimal_separator'],
			(string) $prices['currency_thousand_separator']
		);

		return html_entity_decode(
			(string) $prices['currency_prefix'] . $formatted . (string) $prices['currency_suffix'],
			ENT_QUOTES | ENT_HTML5
		);
	}

	/**
	 * Get the frontend script handle for this block type.
	 *
	 * Scripts are loaded via `viewScriptModule` in block.json.
	 *
	 * @param string|null $key The key of the script to get.
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

	/**
	 * Disable the editor style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_editor_style() {
		return null;
	}
}
