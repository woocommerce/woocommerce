<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div id="message" class="updated woocommerce-message wc-connect">
	<div class="squeezer">
		<h4><?php _e( '<strong>Your theme does not declare WooCommerce support</strong> &#8211; if you encounter layout issues please read our integration guide or choose a WooCommerce theme :)', 'woocommerce' ); ?></h4>
		<p class="submit"><a href="http://docs.woothemes.com/document/third-party-custom-theme-compatibility/" class="button-primary"><?php _e( 'Theme Integration Guide', 'woocommerce' ); ?></a> <a class="skip button-primary" href="<?php echo add_query_arg( 'hide_woocommerce_theme_support_check', 'true' ); ?>"><?php _e( 'Hide this notice', 'woocommerce' ); ?></a></p>
	</div>
</div>