<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 	
?>
<div id="message" class="updated woocommerce-message wc-connect">
	<div class="squeezer">
		<h4><?php _e( '<strong>WooCommerce has been updated</strong> &#8211; You\'re ready to continue selling :)', 'woocommerce' ); ?></h4>

		<p class="submit"><a href="<?php echo admin_url('admin.php?page=woocommerce_settings'); ?>" class="button-primary"><?php _e( 'Settings', 'woocommerce' ); ?></a> <a class="docs button-primary" href="http://www.woothemes.com/woocommerce-docs/"><?php _e( 'Docs', 'woocommerce' ); ?></a></p>

		<p><a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.woothemes.com/woocommerce/" data-text="A open-source (free) #ecommerce plugin for #WordPress that helps you sell anything. Beautifully." data-via="WooThemes" data-size="large" data-hashtags="WooCommerce">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script></p>
	</div>
</div>