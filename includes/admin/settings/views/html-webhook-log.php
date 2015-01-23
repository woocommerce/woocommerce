<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<tr>
	<td><?php echo date_i18n( __( 'M j, Y @ G:i', 'woocommerce' ), strtotime( $log['comment']->comment_date_gmt ), true ); ?></td>
	<td><?php echo esc_attr( $log['duration'] ); ?></td>
	<td>
		<p><strong><?php _e( 'Method', 'woocommerce' ); ?>: </strong><?php echo esc_html( $log['request_method'] ); ?></p>
		<p><strong><?php _e( 'Duration', 'woocommerce' ); ?>: </strong><?php echo esc_html( $log['request_url'] ); ?></p>
		<p><strong><?php _e( 'Headers', 'woocommerce' ); ?>:</strong></p>
		<ul>
			<?php foreach ( ( array ) $log['request_headers'] as $key => $value ) : ?>
				<li><strong><em><?php echo strtolower( esc_html( $key ) ); ?>: </em></strong><code><?php echo esc_html( $value ); ?></code></li>
			<?php endforeach ?>
		</ul>
		<p><strong><?php _e( 'Content', 'woocommerce' ); ?>: </strong></p>
		<pre><code><?php echo esc_html( $log['request_body'] ); ?></code></pre>
	</td>
	<td>
		<p><strong><?php _e( 'Status', 'woocommerce' ); ?>: </strong><?php echo esc_html( $log['summary'] ); ?></p>
		<p><strong><?php _e( 'Headers', 'woocommerce' ); ?>:</strong></p>
		<ul>
			<?php foreach ( (array) $log['response_headers'] as $key => $value ) : ?>
				<li><strong><em><?php echo strtolower( esc_html( $key ) ); ?>: </em></strong><code><?php echo esc_html( $value ); ?></code></li>
			<?php endforeach ?>
		</ul>
		<?php if ( ! empty( $log['response_body'] ) ) : ?>
			<h4><?php _e( 'Content', 'woocommerce' ); ?>:</h4>
			<p><?php echo esc_html( $log['response_body'] ); ?></p>
		<?php endif; ?>
	</td>
</tr>
