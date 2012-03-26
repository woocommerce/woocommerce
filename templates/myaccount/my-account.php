<?php
/**
 * My Account
 */
 
global $woocommerce;
?>

<?php $woocommerce->show_messages(); ?>

<p><?php echo sprintf( __('Hello, <strong>%s</strong>. From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">change your password</a>.', 'woocommerce'), $current_user->display_name, get_permalink(woocommerce_get_page_id('change_password'))); ?></p>

<?php do_action('woocommerce_before_my_account'); ?>

<?php if ($downloads = $woocommerce->customer->get_downloadable_products()) : ?>
<h2><?php _e('Available downloads', 'woocommerce'); ?></h2>
<ul class="digital-downloads">
	<?php foreach ($downloads as $download) : ?>
		<li><?php if (is_numeric($download['downloads_remaining'])) : ?><span class="count"><?php echo $download['downloads_remaining'] . _n(' download Remaining', ' downloads remaining', $download['downloads_remaining'], 'woocommerce'); ?></span><?php endif; ?> <a href="<?php echo esc_url( $download['download_url'] ); ?>"><?php echo $download['download_name']; ?></a></li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>	

<h2><?php _e('Recent Orders', 'woocommerce'); ?></h2>
<?php
$customer_id = get_current_user_id();

$args = array(
    'numberposts'     => $recent_orders,
    'meta_key'        => '_customer_user',
    'meta_value'	  => $customer_id,
    'post_type'       => 'shop_order',
    'post_status'     => 'publish' 
);
$customer_orders = get_posts($args);
if ($customer_orders) :
?>
	<table class="shop_table my_account_orders">
	
		<thead>
			<tr>
				<th class="order-number"><span class="nobr"><?php _e('#', 'woocommerce'); ?></span></th>
				<th class="order-date"><span class="nobr"><?php _e('Date', 'woocommerce'); ?></span></th>
				<th class="order-shipto"><span class="nobr"><?php _e('Ship to', 'woocommerce'); ?></span></th>
				<th class="order-total"><span class="nobr"><?php _e('Total', 'woocommerce'); ?></span></th>
				<th class="order-status" colspan="2"><span class="nobr"><?php _e('Status', 'woocommerce'); ?></span></th>
			</tr>
		</thead>
		
		<tbody><?php
			foreach ($customer_orders as $customer_order) :
				$order = new WC_Order();
				$order->populate($customer_order);
				?><tr class="order">
					<td class="order-number"><?php echo $order->get_order_number(); ?></td>
					<td class="order-date"><time title="<?php echo esc_attr( strtotime($order->order_date) ); ?>"><?php echo date_i18n(get_option('date_format'), strtotime($order->order_date)); ?></time></td>
					<td class="order-shipto"><address><?php if ($order->get_formatted_shipping_address()) echo $order->get_formatted_shipping_address(); else echo '&ndash;'; ?></address></td>
					<td class="order-total"><?php echo woocommerce_price($order->order_total); ?></td>
					<td class="order-status"><?php
						$status = get_term_by('slug', $order->status, 'shop_order_status');
						echo __($status->name, 'woocommerce'); 
					?></td>
					<td class="order-actions" style="text-align:right; white-space:nowrap;">
						<?php if (in_array($order->status, array('pending', 'failed'))) : ?>
							<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e('Pay', 'woocommerce'); ?></a>
							<a href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>" class="button cancel"><?php _e('Cancel', 'woocommerce'); ?></a>
						<?php endif; ?>
						<a href="<?php echo esc_url( add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('view_order'))) ); ?>" class="button"><?php _e('View', 'woocommerce'); ?></a>
					</td>
				</tr><?php
			endforeach;
		?></tbody>
	
	</table>
<?php
else :
?>
	<p><?php _e('You have no recent orders.', 'woocommerce'); ?></p>
<?php
endif;
?>

<h2><?php _e('My Address', 'woocommerce'); ?></h2>	
<p><?php _e('The following addresses will be used on the checkout page by default.', 'woocommerce'); ?></p>
<?php woocommerce_get_template('myaccount/my-address.php'); ?>

<?php
do_action('woocommerce_after_my_account');