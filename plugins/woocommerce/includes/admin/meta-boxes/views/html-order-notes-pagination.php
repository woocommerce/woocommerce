<?php
/**
 * Order notes pagination for the meta box.
 *
 * @package WooCommerce\Admin
 * @var array $pagination Pagination details.
 * @var array $notes      Notes on this page.
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<nav class="order-notes-pagination" aria-label="<?php esc_attr_e( 'Order notes pagination', 'woocommerce' ); ?>" data-page="<?php echo esc_attr( $pagination['page'] ); ?>" data-total-pages="<?php echo esc_attr( $pagination['total_pages'] ); ?>" <?php echo $pagination['total'] > 10 ? '' : 'hidden'; ?>>
	<span class="screen-reader-text order-notes-page-announcement">
		<?php
		printf(
			/* translators: %1$s: current page of notes, %2$s: total number of pages of notes. */
			esc_html__( 'Page %1$s of %2$s.', 'woocommerce' ),
			esc_html( number_format_i18n( $pagination['page'] ) ),
			esc_html( number_format_i18n( $pagination['total_pages'] ) )
		);
		?>
	</span>
	<span class="order-notes-count" aria-live="polite" aria-atomic="true">
		<?php
		printf(
			/* translators: %1$s: number of notes on this page, %2$s: total number of notes. */
			esc_html__( '%1$s of %2$s notes', 'woocommerce' ),
			esc_html( number_format_i18n( count( $notes ) ) ),
			esc_html( number_format_i18n( $pagination['total'] ) )
		);
		?>
	</span>
	<div class="order-notes-page-controls">
		<button type="button" data-page-action="first" aria-label="<?php esc_attr_e( 'First page of notes', 'woocommerce' ); ?>" <?php disabled( 1, $pagination['page'] ); ?>>
			<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m11 6-6 6 6 6m7-12-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.8" /></svg>
		</button>
		<button type="button" data-page-action="previous" aria-label="<?php esc_attr_e( 'Previous page of notes', 'woocommerce' ); ?>" <?php disabled( 1, $pagination['page'] ); ?>>
			<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
		</button>
		<label class="screen-reader-text" for="order-notes-page"><?php esc_html_e( 'Current page of notes', 'woocommerce' ); ?></label>
		<input type="number" id="order-notes-page" min="1" max="<?php echo esc_attr( $pagination['total_pages'] ); ?>" value="<?php echo esc_attr( $pagination['page'] ); ?>" inputmode="numeric" />
		<span class="order-notes-total-pages">
			<?php
			/* translators: %s: total number of pages of notes. */
			printf( esc_html__( 'of %s', 'woocommerce' ), esc_html( number_format_i18n( $pagination['total_pages'] ) ) );
			?>
		</span>
		<button type="button" data-page-action="next" aria-label="<?php esc_attr_e( 'Next page of notes', 'woocommerce' ); ?>" <?php disabled( $pagination['total_pages'], $pagination['page'] ); ?>>
			<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
		</button>
		<button type="button" data-page-action="last" aria-label="<?php esc_attr_e( 'Last page of notes', 'woocommerce' ); ?>" <?php disabled( $pagination['total_pages'], $pagination['page'] ); ?>>
			<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 6 6 6-6 6m7-12 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.8" /></svg>
		</button>
	</div>
</nav>
