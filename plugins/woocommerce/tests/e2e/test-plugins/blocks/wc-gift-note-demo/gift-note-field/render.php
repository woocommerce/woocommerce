<?php
/**
 * Server render for the Gift Note Field (Demo) block.
 *
 * A minimal text input hooked into the Add to Cart + Options form (via Block
 * Hooks, before the quantity selector). It is a DOM descendant of the form, which
 * resolves its product identity through the `woocommerce/products` context /
 * global state (T12), so the field's iAPI module resolves the correct context
 * draft with no extra wiring.
 *
 * The field owns NO submission code: it only writes its value into the context
 * draft (`upsertDraftItem`). The core Add to Cart button POSTs the draft, and
 * the namespaced prop rides along.
 *
 * @package wc-gift-note-demo
 *
 * @var array    $attributes Block attributes (unused).
 * @var string   $content    Block content (unused).
 * @var WP_Block $block      Block instance (carries the `postId` context).
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		// Own interactivity namespace: UI-only wiring for the field. The shared
		// draft lives in `woocommerce/cart`; this store just bridges the input.
		'data-wp-interactive' => 'wc-gift-note-demo',
	)
);

$input_id = 'wc-gift-note-demo-field-' . wp_unique_id();
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<label
		class="wc-gift-note-demo__label"
		for="<?php echo esc_attr( $input_id ); ?>"
	>
		<?php esc_html_e( 'Gift note', 'wc-gift-note-demo' ); ?>
	</label>
	<input
		id="<?php echo esc_attr( $input_id ); ?>"
		class="wc-gift-note-demo__input"
		type="text"
		name="wc-gift-note-demo-gift-note"
		autocomplete="off"
		placeholder="<?php esc_attr_e( 'Add a note for this gift', 'wc-gift-note-demo' ); ?>"
		data-wp-bind--value="state.giftNote"
		data-wp-on--input="actions.setGiftNote"
	/>
</div>
