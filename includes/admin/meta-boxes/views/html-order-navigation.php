<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<ul class="order_navigation">
  <?php if ( array_filter( $order_navigation ) ) : ?>

	  <?php if ( ! is_null( $order_navigation['prev_order_id'] ) ) : $previous_order_id = absint( $order_navigation['prev_order_id'] ); ?>
			<li><?php echo sprintf( '<a href="%1$s" class="button button-secondary prev-order tips" data-tip=" ' . __('%2$s', 'woocommerce') . ' #%3$s"><span aria-hidden="true">&lsaquo;</span> ' . __('Previous %2$s', 'woocommerce') . '</a>', esc_url( sprintf( admin_url( 'post.php?post=%d&action=edit' ), $previous_order_id ) ), $order_type_object->labels->singular_name, $previous_order_id ); ?></li>
	  <?php endif; ?>

		<?php if ( ! is_null( $order_navigation['next_order_id'] ) ) : $next_order_id = absint( $order_navigation['next_order_id'] ); ?>
			<li><?php echo sprintf( '<a href="%1$s" class="button button-secondary prev-order tips" data-tip=" ' . __('%2$s', 'woocommerce') . ' #%3$s">' . __('Next %2$s', 'woocommerce') . ' <span aria-hidden="true">&rsaquo;</span></a>', esc_url( sprintf( admin_url( 'post.php?post=%d&action=edit' ), $next_order_id ) ), $order_type_object->labels->singular_name, $next_order_id ); ?></li>
	  <?php endif; ?>

	<?php else : ?>
		<li><?php _e('You need to have at least two orders, to use the navigation.', 'woocommerce'); ?></li>
	<?php endif; ?>

</ul>
