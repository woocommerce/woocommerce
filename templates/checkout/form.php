<?php do_action('before_checkout_form');
// filter hook for include new pages inside the payment method
$get_checkout_url = apply_filters( 'jigoshop_get_checkout_url', jigoshop_cart::get_checkout_url() ); ?>

<form name="checkout" method="post" class="checkout" action="<?php echo $get_checkout_url; ?>">
	
	<div class="col2-set" id="customer_details">
		<div class="col-1">

			<?php do_action('jigoshop_checkout_billing'); ?>
						
		</div>
		<div class="col-2">
		
			<?php do_action('jigoshop_checkout_shipping'); ?>
					
		</div>
	</div>
	
	<h3 id="order_review_heading"><?php _e('Your order', 'jigoshop'); ?></h3>
	
	<?php jigoshop_get_template('checkout/review_order.php', false); ?>
	
</form>

<?php do_action('after_checkout_form'); ?>