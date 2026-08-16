<?php
/**
 * Admin View: Page - Status Tools
 *
 * @package WooCommerce
 */

use Automattic\WooCommerce\Utilities\ArrayUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $tools as $action_name => $tool ) {
	?>
	<form id="<?php echo esc_attr( 'form_' . $action_name ); ?>" method="GET" action="<?php echo esc_attr( esc_url( admin_url( 'admin.php?foo=bar' ) ) ); ?>">
		<?php wp_nonce_field( 'debug_action', '_wpnonce', false ); ?>
		<input type="hidden" name="page" value="wc-status"/>
		<input type="hidden" name="tab" value="tools"/>
		<input type="hidden" name="action" value="<?php echo esc_attr( $action_name ); ?>"/>
	</form>
	<?php
}
?>

<table class="wc_status_table wc_status_table--tools widefat" cellspacing="0">
	<tbody class="tools">
		<?php foreach ( $tools as $action_name => $tool ) : ?>
			<?php
			$row_classes = array( sanitize_html_class( $action_name ) );
			if ( ArrayUtil::is_truthy( $tool, 'requires_refresh' ) ) {
				$row_classes[] = 'requires-refresh';
			}
			?>
			<tr class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>" data-tool-action="<?php echo esc_attr( $action_name ); ?>">
				<th>
					<strong class="name"><?php echo esc_html( $tool['name'] ); ?></strong>
					<p class="description">
						<?php
						echo wp_kses_post( $tool['desc'] );
						if ( ! is_null( ArrayUtil::get_value_or_default( $tool, 'selector' ) ) ) {
							$selector = $tool['selector'];
							if ( isset( $selector['description'] ) ) {
								echo '</p><p class="description">';
								echo wp_kses_post( $selector['description'] );
							}
							printf(
								'&nbsp;&nbsp;<select style="width: 300px;" form="%1$s" id="%2$s" data-allow_clear="true" class="%3$s" name="%4$s" data-placeholder="%5$s" data-action="%6$s"></select>',
								esc_attr( 'form_' . $action_name ),
								esc_attr( 'selector_' . $action_name ),
								esc_attr( $selector['class'] ),
								esc_attr( $selector['name'] ),
								esc_attr( $selector['placeholder'] ),
								esc_attr( $selector['search_action'] )
							);
						}
						?>
					</p>
				</th>
				<td class="run-tool">
					<span class="run-tool-actions">
					<input <?php disabled( ArrayUtil::is_truthy( $tool, 'disabled' ) ); ?> type="submit" form="<?php echo esc_attr( 'form_' . $action_name ); ?>" class="button button-large" value="<?php echo esc_attr( $tool['button'] ); ?>" />

					<?php if ( ! empty( $tool['status_text'] ) ) : ?>
					<span class="run-tool-status"><?php echo wp_kses_post( $tool['status_text'] ); ?></span>
					<?php endif; ?>
					</span>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
