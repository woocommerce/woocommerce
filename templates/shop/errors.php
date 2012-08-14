<?php
/**
 * Show error messages
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */
if ( ! $errors ) return;
?>
<ul class="woocommerce_error">
	<?php foreach ( $errors as $error ) : ?>
		<li><?php echo $error; ?></li>
	<?php endforeach; ?>
</ul>