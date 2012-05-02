<?php if(!$this->isActive()) return; //if the plugin is not active, do not show this page ?>
<script type="text/javascript">
  if(_gaq) _gaq.push(['_trackPageview', '/admin/documentation']);
</script>
<div class="wrap">
	<?php if($show_header):?>
	
	<?php echo $this->getUpdateNotification(); ?>
	
    <h2>
        <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin/documentation/logo-click']);">
            <img src="<?php echo $this->createUrl(dirname(__FILE__).'/../img/shareyourcart-logo.png'); ?>"/>
        </a>
		<div class="syc-slogan"><?php echo SyC::t('sdk','Increase your social media exposure by 10%!'); ?></div>
		<br clear="all" /> 
    </h2>
    <?php endif; ?>
    
    <div id="doc-content">
        <h2><?php echo SyC::t('sdk','Standard Button'); ?></h2>
        <p><?php echo SyC::t('sdk','In order to see the {brand} button on a <strong>post</strong> or <strong>page</strong> you can use the following shortcode:', array( '{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/logo-click\']);">ShareYourCart&trade;</a>')); ?></p>
        <pre><code>[shareyourcart]</code></pre>
        
        <p><?php echo SyC::t('sdk','If you want to use the {brand} button directly in a <strong>theme</strong> or <strong>page template</strong> you have to use the following function call:', array('{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/logo-click\']);">ShareYourCart&trade;</a>')); ?></p>
        <pre><code>echo do_shortcode('[shareyourcart]');</code></pre>
		<h3><?php echo SyC::t('sdk','Remarks'); ?></h3>
		<ol>
	
<?php if (!(isset($action_url) && !empty($action_url))): //if no shopping cart is active ?>
		<li><p><?php echo SyC::t('sdk','For the button to work, you need to specify the following properties in the meta description area'); ?></p>
<pre><code><?php echo SyC::htmlIndent(nl2br(htmlspecialchars('<html xmlns="http://www.w3.org/1999/xhtml"
      xmlns:og="http://ogp.me/ns#"
      xmlns:syc="http://www.shareyourcart.com">
  <head>
      <meta property="og:image" content="http://www.example.com/product-image.jpg"/>
      <meta property="syc:price" content="$10" />
      <meta property="og:description"
          content="
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
            Proin feugiat nunc quis nibh congue luctus. 
            Maecenas ac est nec turpis fermentum imperdiet.
          "/>
    ...
  </head>
  ...
</html>'))); ?></code></pre></li>
		<li><?php echo SyC::t('sdk','This plugin allows you to easilly set the above meta properties directly in the post or page edit form'); ?></li>
<?php endif; ?>

		<li><p><?php echo SyC::t('sdk','To position the {brand} button, you need to override the following CSS classes', array('{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/logo-click\']);">ShareYourCart&trade;</a>')); ?></p>
                    <ul>
			<li><?php echo SyC::t('sdk','{css_class} for the horrizontal button', array('{css_class}' => '<code>button_iframe-normal</code>')); ?></li>
			<li><?php echo SyC::t('sdk','{css_class} for the vertical button', array('{css_class}' => '<code>button_iframe</code>')); ?></li>
                    </ul>
                </li>
		</ol>
		
		
		<h2><?php echo SyC::t('sdk','Custom Button'); ?></h2>
		<p><?php echo SyC::t('sdk','If you want to fully style the {brand} button, use instead the following HTML code', array('{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/logo-click\']);">ShareYourCart&trade;</a>')); ?></p>

<?php $custom_button = '<button class="shareyourcart-button" data-syc-layout="custom"';
if (isset($action_url) && !empty($action_url)){	
	//if there is no action url, it means none of the supported shopping carts are active,
	//so there would be no need for the callback attribute
	$custom_button .= ' data-syc-callback_url="'.$action_url.'" ';
 }
 
 $custom_button .= '>
     Get a <div class="shareyourcart-discount" ></div> discount
</button>'; ?>
		<pre><code><?php echo  SyC::htmlIndent(nl2br(htmlspecialchars($custom_button))); ?></code></pre>
		
<?php if (isset($action_url) && !empty($action_url)): //only show if a known shopping cart is active ?>
		<h3><?php echo SyC::t('sdk','Remarks'); ?></h3>
		<p><?php echo SyC::t('sdk','If you want to use the {brand} button on a product\'s page, you need to <strong>append</strong> {product-property} to the {callback-url} value, where {product-property} is the product\'s id', array('{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/logo-click\']);">ShareYourCart&trade;</a>', '{product-property}' => '<code>&p=&lt;product_id&gt;</code>', '{callback-url}' => '<strong>data-syc-callback_url</strong>')); ?></p>
<?php endif; ?>

<?php if($show_footer):?>
		<h2><?php echo SyC::t('sdk','Contact'); ?></h2>
		<p><?php echo SyC::t('sdk',"If you've got 30 seconds, we'd {link-1} love to know what ideal outcome you'd like ShareYourCart to help bring to your business</a>, or if you have a private question, you can {link-2} contact us directly</a>", array('{link-1}' => '<a href="http://shareyourcart.uservoice.com" target="_blank" title="forum" class="api-link" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/forum-click\']);">', '{link-2}' => '<a href="http://www.shareyourcart.com/contact" target="_blank" class="api-link" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/contact-click\']);">')); ?></p>
		<br />
		<?php endif; ?>
	</div>
</div>