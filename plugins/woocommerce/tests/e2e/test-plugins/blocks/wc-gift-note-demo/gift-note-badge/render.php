<?php
/**
 * Server render for the Gift Note Badge (Demo) block — the cartItemFilter demo.
 *
 * This block renders its own shared `woocommerce` context carrying BOTH a
 * `productId` (so the envelope has a product to scope to) AND a `cartItemFilter`
 * serialized action reference. The reference points at a PUBLIC iAPI store
 * (`wc-gift-note-demo/filter`) whose `matchByMarker` action is a pure predicate.
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

// The shared `woocommerce` context for this surface: the product to scope to
// PLUS the serialized cartItemFilter reference. `marker` travels in the context
// so the predicate (which receives `context`) can read it — the predicate stays
// pure and configuration-free.
$shared_context = wp_interactivity_data_wp_context(
	array(
		'productId'      => $product_id,
		'cartItemFilter' => array(
			'namespace' => 'wc-gift-note-demo/filter',
			'action'    => 'matchByMarker',
		),
		'giftNoteMarker' => $note_marker,
	),
	'woocommerce'
);

// IMPORTANT: `data-wp-interactive` and the shared `woocommerce::` context must
// be on the SAME element (the interactive island root). iAPI only processes a
// `data-wp-context` that sits on, or inside, an interactive region; a context on
// a bare ancestor of the island is ignored, so `getContext('woocommerce')` would
// return nothing. (T8 finding.)
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'data-wp-interactive' => 'wc-gift-note-demo/badge',
	)
);
?>
<div
	<?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $shared_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
