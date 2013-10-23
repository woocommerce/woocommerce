<table class="wc_status_table widefat" cellspacing="0">
	<thead class="tools">
		<tr>
			<th colspan="2"><?php _e( 'Tools', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody class="tools">
	<?php foreach( $tools as $action => $tool ) { ?>
		<tr>
			<td><?php echo esc_html( $tool['name'] ); ?></td>
			<td>
				<p>
					<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=wc-status&tab=tools&action=' . $action ), 'debug_action' ); ?>" class="button"><?php echo esc_html( $tool['button'] ); ?></a>
					<span class="description"><?php echo wp_kses_post( $tool['desc'] ); ?></span>
				</p>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>