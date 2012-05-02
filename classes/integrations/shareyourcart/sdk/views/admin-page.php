<script type="text/javascript">
  if(_gaq) _gaq.push(['_trackPageview', '/admin-view']);
</script>
<div class="wrap">
<?php if($show_header):?>
	
	<?php echo $this->getUpdateNotification(); ?>
	
    <h2>
        <a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/logo-click']);">
            <img src="<?php echo $this->createUrl(dirname(__FILE__).'/../img/shareyourcart-logo.png'); ?>"/>
        </a>
		<div class="syc-slogan"><?php echo SyC::t('sdk','Increase your social media exposure by 10%!'); ?></div>
		
		<?php
			if(isset($this->adminFix)) echo "<br /><br /><br /><br /><br />";
			else echo "<br class=\"clr\" /> ";
		?>
    </h2>
	<?php endif; ?>

    <?php if(!empty($status_message)): ?>
	<div class="updated settings-error"><p><strong>
		<?php echo $status_message; ?>
	</strong></p></div>
	<?php endif; ?>
	
    <p><?php echo SyC::t('sdk','{brand} helps you get more customers by motivating satisfied customers to talk with their friends about your products. Each customer that promotes your products, via social media, will receive a coupon that they can apply to their shopping cart in order to get a small discount.',array('{brand}' => '<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart&trade;" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin-view/logo-click\']);">ShareYourCart&trade;</a>')); ?></p>
    
    <br />
    <div id="acount-options">      	
        <form method="POST" name="account-form">       
		<fieldset>	   		
		<div class="api-status" align="right">
                    <?php echo SyC::t('sdk','API Status:'); ?>
                    <?php if($this->isActive()) : ?>
                        <span class="green"><?php echo SyC::t('sdk','Enabled'); ?></span>
                    <?php else :?>
                        <span class="red"><?php echo SyC::t('sdk','Disabled'); ?></span>
                    <?php endif;?>
                        <br />
                    <?php if($this->isActive()) : ?>
                        <input type="submit" value="<?php echo SyC::t('sdk','Disable'); ?>" name="disable-API" class="api-button" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/disable-click']);" />
                    <?php else :?>
                        <input type="submit" value="<?php echo SyC::t('sdk','Enable'); ?>" name="enable-API" class="api-button" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/enable-click']);" />
                    <?php endif;?>
                </div>                
        <table class="form-table-api" name="shareyourcart_settings">
            <tr>
                <th scope="row"><?php echo SyC::t('sdk','Client ID'); ?></th>
                <td><input type="text" name="client_id" id="client_id" class="regular-text" value="<?php echo $this->getClientId(); ?>"/></td>
            </tr>
            <tr>
                <th scope="row"><?php echo SyC::t('sdk','App Key'); ?></th>
                <td><input type="text" name="app_key" id="app_key" class="regular-text" value="<?php echo $this->getAppKey(); ?>"/></td>
            </tr>
            <tr>
                <td></td>
                <td><a href="?<?php echo http_build_query(array_merge($_GET,array('syc-account'=>'recover')),'','&')?>" class="api-link" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/recover-click']);"><?php echo SyC::t('sdk',"Can't access your account?"); ?></a> <strong><?php echo SyC::t('sdk','or'); ?></strong> <?php echo SyC::t('sdk','New to ShareYourCart&trade;?'); ?> <a href="?<?php echo http_build_query(array_merge($_GET,array('syc-account'=>'create')),'','&')?>" id="account-recovery" class="api-link" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/create-account-click']);"><?php echo SyC::t('sdk','Create an account'); ?></a></td>
            </tr>
        </table>
       <?php echo $html;?>
        <div class="submit"><input type="submit" name="syc-account-form" class="button" value="<?php echo SyC::t('sdk','Save'); ?>" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/save-click']);"></div>        
		</fieldset>			 
    </form>  
    </div>
    
	<?php if($this->isActive()): //show the configure part only if it is active ?>
    <br/>
    <fieldset>
    <p><?php echo SyC::t('sdk','You can choose how much of a discount to give (in fixed amount, percentage, or free shipping) and to which social media channels it should it be applied. You can also define what the advertisement should say, so that it fully benefits your sales.'); ?></p>
    <br />
 	 <form action="<?php echo $this->SHAREYOURCART_CONFIGURE; ?>" method="POST" id="configure-form" target="_blank">
       
        <div class="configure-button-container" align="center">
            <input type="submit" value="<?php echo SyC::t('sdk','Configure'); ?>" id="configure-button" class="shareyourcart-button-orange" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/configure-click']);" />
            <input type="hidden" name="app_key" value="<?php echo $this->getAppKey(); ?>" />
            <input type="hidden" name="client_id" value="<?php echo $this->getClientId(); ?>" />
			<input type="hidden" name="email" value="<?php echo $this->getAdminEmail(); ?>" />
        </div>   
       
    </form>
    </fieldset>
	
	<?php if($show_footer):?>	
	<br />
	<h2><?php echo SyC::t('sdk','Contact'); ?></h2>
	<p><?php echo SyC::t('sdk',"If you've got 30 seconds, we'd {link-1} love to know what ideal outcome you'd like ShareYourCart to help bring to your business</a>, or if you have a private question, you can {link-2} contact us directly</a>", array('{link-1}' => '<a href="http://shareyourcart.uservoice.com" target="_blank" title="forum" class="api-link" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/forum-click\']);">', '{link-2}' => '<a href="http://www.shareyourcart.com/contact" target="_blank" class="api-link" onclick=" if(_gaq) _gaq.push([\'_trackPageview\', \'/admin/documentation/contact-click\']);">')); ?></p>
	<br />
	<?php endif; ?>
	
	<?php endif; //show only if the cart is active ?>
</div>