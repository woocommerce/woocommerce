<?php
/**
 * Server render for the Gift Note Badge (Demo) block — the `findItem({ filter })`
 * escape-hatch demo.
 *
 * The badge's OWN store context (`wc-gift-note-demo/badge`) carries the two
 * values its getter needs: the `productId` to scope the lookup, and the
 * `giftNoteMarker` the local predicate reads. The `hasMarkedLine` getter calls
 * `cartState.findItem({ id, filter })` directly — no `woocommerce/cart` context,
 * no serialized `cartItemFilter` reference, no public filter store.
 *
 * The local predicate REPLACES the generic narrowing: it selects the note-split
 * cart line whose note starts with the configured marker — a line the generic
 * rules would exclude (the badge surface carries no draft note, so the presence
 * heuristic would drop every note-carrying line). The badge only shows when the
 * predicate paired exactly one line, which is the visible proof that the escape
 * hatch overrode pairing.
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

// The badge's own store context carries everything the getter reads: the product
// id to scope `findItem`, and the marker the local predicate matches by. No
// domain (`woocommerce/*`) context is needed — custom matching is a caller-
// supplied predicate, not a context reference.
$badge_context = wp_interactivity_data_wp_context(
	array(
		'productId'      => $product_id,
		'giftNoteMarker' => $note_marker,
	)
);

// IMPORTANT: `data-wp-interactive` and the context must sit on, or inside, the
// interactive island root. iAPI ignores a `data-wp-context` on a bare ancestor of
// the island (T8 finding). Both go on the same element here; `wp_interactivity_
// data_wp_context` defaults to the island's own namespace.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'data-wp-interactive' => 'wc-gift-note-demo/badge',
	)
);
?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $badge_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
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
