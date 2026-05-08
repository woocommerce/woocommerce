<?php
/**
 * Star-rating control partial.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/star-rating.php`.
 *
 * Renders five native `<input type="radio">` elements wrapped in a
 * `role="radiogroup"` container, with SVG icons added as decorative siblings
 * for the visual stars and a caption span the JS module updates as the
 * selection changes. Without JavaScript the customer still has a working
 * (if visually plain) radio group.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.8.0
 *
 * @var string             $name      Form field name (e.g. `reviews[123][rating]`).
 * @var string             $id_prefix Prefix used for unique radio ids.
 * @var string             $label_id  Existing label id; bound via aria-labelledby.
 * @var int                $selected  Pre-selected value (0 = none).
 * @var array<int, string> $labels    Map of value (1-5) to caption text.
 */

defined( 'ABSPATH' ) || exit;

$caption_id      = $id_prefix . '-caption';
$initial_caption = $selected > 0 && isset( $labels[ $selected ] ) ? $labels[ $selected ] : '';

// Render in reverse (5 first, 1 last) so the visual layout — driven by
// `flex-direction: row-reverse` — can use ~ sibling selectors for the
// "fill stars 1..N" effect without depending on `:has()`.
$reversed = array_reverse( $labels, true );
?>
<div
	class="woocommerce-star-rating"
	role="radiogroup"
	aria-labelledby="<?php echo esc_attr( $label_id ); ?>"
	aria-describedby="<?php echo esc_attr( $caption_id ); ?>"
>
	<?php foreach ( $reversed as $value => $label ) : ?>
		<?php
		$input_id = $id_prefix . '-' . $value;
		$checked  = $value === $selected;
		?>
		<input
			class="woocommerce-star-rating__input"
			type="radio"
			id="<?php echo esc_attr( $input_id ); ?>"
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			data-label="<?php echo esc_attr( $label ); ?>"
			<?php checked( $checked ); ?>
		/>
		<label class="woocommerce-star-rating__star" for="<?php echo esc_attr( $input_id ); ?>">
			<span class="screen-reader-text">
				<?php
				printf(
					/* translators: 1: numeric star rating 2: label text e.g. "Good" */
					esc_html__( '%1$d out of 5 stars: %2$s', 'woocommerce' ),
					(int) $value,
					esc_html( $label )
				);
				?>
			</span>
			<svg
				class="woocommerce-star-rating__icon"
				width="24"
				height="24"
				viewBox="0 0 24 24"
				aria-hidden="true"
				focusable="false"
			>
				<path d="M12 2.5l2.92 6.36 6.99.74-5.21 4.74 1.46 6.86L12 17.77l-6.16 3.43 1.46-6.86L2.09 9.6l6.99-.74L12 2.5z" />
			</svg>
		</label>
	<?php endforeach; ?>

	<span
		id="<?php echo esc_attr( $caption_id ); ?>"
		class="woocommerce-star-rating__caption"
		aria-live="polite"
	><?php echo esc_html( $initial_caption ); ?></span>
</div>
