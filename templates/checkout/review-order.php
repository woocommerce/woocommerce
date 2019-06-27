<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @package 	WooCommerce/Templates
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="review-order-card">
	<div class="order">Seu pedido</div>
	<?php
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        $produtRegularPrice = $_product->get_regular_price();
        $produtPrice = $_product->get_price();
        $productName = $_product->get_name();
	};
	?>
	<div class="title"><?php echo $productName ?></div>
	<div class="from-price">de <span>R$<?php echo str_replace(".", ",", $produtRegularPrice) ?></span> por</div>
	<div class="current-price">R$ <span><?php echo str_replace(".", ",", $produtPrice) ?></span></div>
	<div class="subtext">No boleto ou cartão em até 18x</div>
</div>
<div class='security-check'>
	<img src="https://s3.amazonaws.com/store.newlaw/imgs/lock.svg" alt="security">
	Seus pagamentos são efetuados com criptografia SSL de 128 bits, o que significa que são 100% seguros.
</div>