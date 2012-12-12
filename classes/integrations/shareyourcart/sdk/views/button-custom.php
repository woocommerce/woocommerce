<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>
<a href="<?php echo $this->SHAREYOURCART_BUTTON_URL ?>" class="shareyourcart-button <?php if (isset($is_product_page) && $is_product_page) echo "product_button"; ?>" <?php echo (isset($callback_url) && !empty($callback_url)) ? "data-syc-callback_url=$callback_url" : ''; ?> data-syc-layout="custom" <?php if(!empty($position_after)): echo "data-syc-position-after=\"$position_after\""; elseif(!empty($position_before)): echo "data-syc-position-before=\"$position_before\""; endif; ?> <?php if(!empty($language)): echo "data-syc-language=\"$language\""; endif; ?>>
<?php echo $button_html;?>
</a>

<!--
<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script> -->