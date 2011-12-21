<?php
/**
 * Edit Address Form
 */
 
global $woocommerce, $load_address, $address;
?>

<?php $woocommerce->show_messages(); ?>

<form action="<?php echo esc_url( add_query_arg( 'address', $load_address, get_permalink( get_option( 'woocommerce_edit_address_page_id' ) ) ) ); ?>" method="post">
	
	<h3><?php if ($load_address=='billing') _e('Billing Address', 'woothemes'); else _e('Shipping Address', 'woothemes'); ?></h3>
	
	<?php 
	foreach ($address as $key => $field) :
		woocommerce_form_field( $key, $field, get_user_meta( get_current_user_id(), $key, true ) );
	endforeach;
	?>
	
	<input type="submit" class="button" name="save_address" value="<?php _e('Save Address', 'woothemes'); ?>" />
	
	<?php $woocommerce->nonce_field('edit_address') ?>
	<input type="hidden" name="action" value="edit_address" />

</form>