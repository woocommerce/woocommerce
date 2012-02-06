<?php
/**
 * Single Product Share
 */
?>

<?php if (get_option('woocommerce_sharethis')) : ?>
	
	<div class="social">
		<iframe src="https://www.facebook.com/plugins/like.php?href=<?php echo urlencode(get_permalink($post->ID)); ?>&amp;layout=button_count&amp;show_faces=false&amp;width=100&amp;action=like&amp;colorscheme=light&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;" allowTransparency="true"></iframe>
		<span class="st_twitter"></span><span class="st_email"></span><span class="st_sharethis"></span><span class="st_plusone_button"></span>
	</div>

	<?php $sharethis = (is_ssl()) ? 'https://ws.sharethis.com/button/buttons.js' : 'http://w.sharethis.com/button/buttons.js'; ?>

	<script type="text/javascript">var switchTo5x=true;</script><script type="text/javascript" src="<?php echo $sharethis; ?>"></script>
	<script type="text/javascript">stLight.options({publisher:"<?php echo get_option('woocommerce_sharethis'); ?>"});</script>

<?php endif; ?>

<?php if (get_option('woocommerce_sharedaddy') && function_exists('sharing_display')) : ?>

	<div class="social"><?php echo sharing_display(); ?></div>

<?php endif; ?>