<link rel="stylesheet" type="text/css" href="<?php echo $this->createUrl(dirname(__FILE__).'/../css/style.css'); ?>" />
<!--[if lt IE 9]>
<link rel="stylesheet" href="<?php echo $this->createUrl(dirname(__FILE__).'/../css/ie.css'); ?>" type="text/css"/>
<![endif]-->

<meta property="syc:client_id" content="<?php echo $this->getClientId(); ?>" />
<?php if($this->isActive() & is_array($data)): ?>

<meta property="og:title" content="<?php echo htmlspecialchars(@$data['item_name']); ?>" />
<meta property="og:url" content="<?php echo @$data['item_url'] ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars(@$data['item_description']); ?>" />
<meta property="og:image" content="<?php echo @$data['item_picture_url'] ?>" />
<meta property="syc:price" content="<?php echo @$data['item_price'] ?>" />

<?php endif; ?>