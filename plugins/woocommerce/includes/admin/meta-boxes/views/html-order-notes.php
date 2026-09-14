<?php
/**
 * Order notes HTML for meta box.
 *
 * @package WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

?>
<ul class="order_notes">
	<?php
	if ( $notes ) {
		foreach ( $notes as $note ) {
			$css_class   = array( 'note' );
			$css_class[] = $note->customer_note ? 'customer-note' : '';
			$css_class[] = 'system' === $note->added_by ? 'system-note' : '';
			$css_class   = apply_filters( 'woocommerce_order_note_class', array_filter( $css_class ), $note );
			?>
			<li rel="<?php echo absint( $note->id ); ?>" class="<?php echo esc_attr( implode( ' ', $css_class ) ); ?>">
				<div class="note_content">
					<?php if ( $note->customer_note ) : ?>
						<div class="note_header"><?php esc_html_e( 'Sent to customer', 'woocommerce' ); ?></div>
					<?php endif; ?>
					<div class="note_body">
						<?php
						$content = wp_kses_post( $note->content );
						$content = wc_wptexturize_order_note( $content );
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- the content goes through wp_kses_post above.
						echo wpautop( $content );
						?>
					</div>
				</div>
				<p class="meta">
					<abbr class="exact-date" title="<?php echo esc_attr( $note->date_created->date( 'Y-m-d H:i:s' ) ); ?>">
						<?php
						/* translators: %1$s: order note date, %2$s: order note time */
						echo esc_html( sprintf( __( '%1$s at %2$s', 'woocommerce' ), $note->date_created->date_i18n( wc_date_format() ), $note->date_created->date_i18n( wc_time_format() ) ) );
						?>
					</abbr>
					<?php
					if ( 'system' !== $note->added_by ) :
						/* translators: %s: order note author */
						echo esc_html( sprintf( ' ' . __( 'by %s', 'woocommerce' ), $note->added_by ) );
					endif;
					?>
					<?php
					$note_date_label   = $note->date_created->date_i18n( wc_date_format() );
					$delete_aria_label = 'system' === $note->added_by
						/* translators: %s: order note date */
						? sprintf( __( 'Delete system note from %s', 'woocommerce' ), $note_date_label )
						/* translators: %1$s: order note author, %2$s: order note date */
						: sprintf( __( 'Delete note from %1$s on %2$s', 'woocommerce' ), $note->added_by, $note_date_label );
					?>
					<a href="#" class="delete_note" role="button" aria-label="<?php echo esc_attr( $delete_aria_label ); ?>"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a>
				</p>
			</li>
			<?php
		}
	} else {
		?>
		<li class="no-items"><?php esc_html_e( 'There are no notes yet.', 'woocommerce' ); ?></li>
		<?php
	}
	?>
</ul>
