<?php
/**
 * Server render for the Gift Note Badge (Demo) block — the cartItemFilter demo.
 *
 * This block renders TWO domain-scoped contexts (T12): a `woocommerce/products`
 * context carrying the `productId` (so the envelope has a product to scope to),
 * and a nested `woocommerce/cart` context carrying the `cartItemFilter`
 * serialized action reference plus the marker the predicate reads. The reference
 * points at a PUBLIC iAPI store (`wc-gift-note-demo/filter`) whose `matchByMarker`
 * action is a pure predicate.
 *
 * When core derives `itemInContext` inside this context, the predicate REPLACES
 * the generic narrowing: it selects the note-split cart line whose note starts
 * with a configured marker — a line the generic rules would exclude (the badge
 * surface carries no draft note, so the presence heuristic would drop every
 * note-carrying line). The badge only shows when the filter paired exactly one
 * line, which is the visible proof that the escape hatch overrode pairing.
 *
 * @package wc-gift-note-demo
 *
 * @var array    $attributes Block attributes (`noteMarker`).
 * @var string   $content    Block content (unused).
 * @var WP_Block $block      Block instance (carries the `postId` context).
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id  = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$note_marker = isset( $attributes['noteMarker'] ) && is_string( $attributes['noteMarker'] )
	? $attributes['noteMarker']
	: 'VIP';

if ( ! $product_id ) {
	return '';
}

// Domain-scoped contexts (T12). The product to scope to lives in the
// `woocommerce/products` context (the cart store resolves it via derived state —
// `mainProductInContext`). The `cartItemFilter` reference and the marker live in
// the `woocommerce/cart` context — the cart store's own namespace. `marker`
// travels in the cart context so the predicate (which receives `context`) can
// read it, staying pure and configuration-free.
$products_context = wp_interactivity_data_wp_context(
	array( 'productId' => $product_id ),
	'woocommerce/products'
);
$cart_context     = wp_interactivity_data_wp_context(
	array(
		'cartItemFilter' => array(
			'namespace' => 'wc-gift-note-demo/filter',
			'action'    => 'matchByMarker',
		),
		'giftNoteMarker' => $note_marker,
	),
	'woocommerce/cart'
);

// IMPORTANT: `data-wp-interactive` and the contexts must sit on, or inside, the
// interactive island root. iAPI ignores a `data-wp-context` on a bare ancestor of
// the island (T8 finding). The two domain contexts go on SEPARATE elements — the
// products context on the island root, the cart context on a nested wrapper —
// because WordPress 6.8 does not support two `data-wp-context` namespaces on the
// same element (WP 6.9 does). The `hasMarkedLine` getter runs in the inner span's
// scope, which inherits BOTH contexts.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'data-wp-interactive' => 'wc-gift-note-demo/badge',
	)
);
?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $products_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<div <?php echo $cart_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<span
			class="wc-gift-note-demo__badge"
			data-wp-bind--hidden="!state.hasMarkedLine"
			data-testid="gift-note-badge"
		>
			<?php
			printf(
				/* translators: %s: the note marker, e.g. "VIP". */
				esc_html__( 'This %s gift note is in your cart', 'wc-gift-note-demo' ),
				esc_html( $note_marker )
			);
			?>
		</span>
	</div>
</div>
