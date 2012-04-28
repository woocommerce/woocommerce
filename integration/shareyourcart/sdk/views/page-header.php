<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->createUrl(dirname(__FILE__).'/../css/style.css'); ?>" />
<!--[if lt IE 9]>
<link rel="stylesheet" href="<?php echo $this->createUrl(dirname(__FILE__).'/../css/ie.css'); ?>" type="text/css"/>
<![endif]-->

<meta property="syc:client_id" content="<?php echo $this->getClientId(); ?>" />
<meta property="syc:callback_url" content="<?php echo $this->getButtonCallbackURL(); ?>" />

<?php if($this->isActive() & is_array($data)): ?>

<meta property="og:title" content="<?php echo htmlspecialchars(@$data['item_name']); ?>" />
<meta property="og:url" content="<?php echo @$data['item_url'] ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars(@$data['item_description']); ?>" />
<meta property="og:image" content="<?php echo @$data['item_picture_url'] ?>" />
<meta property="syc:price" content="<?php echo @$data['item_price'] ?>" />

<?php endif; ?>

<script type="text/javascript">
   (function() {
     var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
     a.src = '<?php echo $this->SHAREYOURCART_BUTTON_JS; ?>';
     var b = document.getElementsByTagName('script')[0]; b.parentNode.insertBefore(a, b);
   })();
</script>

