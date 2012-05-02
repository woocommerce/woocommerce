<?php 
	// If only the hover is uploaded
    if((!$button_img or !$button_img_width or !$button_img_height) and ($button_img_hover and $button_img_hover_width and $button_img_hover_height)) {
        $button_img = $button_img_hover;
        $button_img_width = $button_img_hover_width;
        $button_img_height = $button_img_hover_height;
        $button_img_hover = null;
        $button_img_hover_width = null;
        $button_img_hover_height = null;
    }
	
	$callbackDataAttr = '';
	
	if(isset($callback_url) && !empty($callback_url)) {
		$callbackDataAttr = 'data-syc-callback_url="' . $callback_url .'"';
	}
?>

<a href="<?php echo $this->SHAREYOURCART_BUTTON_URL ?>" class="shareyourcart-button" <?php echo $callbackDataAttr; ?> data-syc-layout="custom">
	ShareYourCart Discount
</a>

<style>
	.shareyourcart-button {
		display: block;
		background: url('<?php echo $button_img;?>') left top;
		width: <?php echo $button_img_width; ?>px;
		height: <?php echo $button_img_height; ?>px;
		text-indent: -9999px;
	}
	
	<?php
		if($button_img_hover and $button_img_hover_width and $button_img_hover_height) {
	?>
	.shareyourcart-button:hover {
		background-image: url('<?php echo $button_img_hover; ?>');
		width: <?php echo $button_img_hover_width; ?>px;
		height: <?php echo $button_img_hover_height; ?>px;
	}
	body:after {
		content: url(<?php echo $button_img_hover; ?>);
		background-image: url(<?php echo $button_img_hover; ?>);
		visibility: hidden;
		position: absolute;
		left: -999em;
	}
	<?php
		}
	?>
</style>

<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script>