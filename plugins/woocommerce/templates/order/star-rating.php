<?php
/**
 * Star-rating control partial.
 *
 * Theme-overridable. Copy to `yourtheme/woocommerce/order/star-rating.php`.
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

// Reverse so row-reverse + `~` selectors can fill stars 1..N without `:has()`.
$reversed = array_reverse( $labels, true );
?>
<div
	class="woocommerce-star-rating"
	role="radiogroup"
	aria-labelledby="<?php echo esc_attr( $label_id ); ?>"
	aria-describedby="<?php echo esc_attr( $caption_id ); ?>"
>
	<div class="woocommerce-star-rating__stars">
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
	</div>

	<span
		id="<?php echo esc_attr( $caption_id ); ?>"
		class="woocommerce-star-rating__caption"
		aria-live="polite"
	><?php echo esc_html( $initial_caption ); ?></span>
</div>
