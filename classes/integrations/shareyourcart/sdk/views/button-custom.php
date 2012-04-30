<a href="<?php echo $this->SHAREYOURCART_BUTTON_URL ?>" class="shareyourcart-button" <?php echo (isset($callback_url) && !empty($callback_url)) ? "data-syc-callback_url=$callback_url" : ''; ?> data-syc-layout="custom">
<?php echo $button_html;?>
</a>

<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script>