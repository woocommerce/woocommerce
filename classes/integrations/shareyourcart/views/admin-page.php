<?php if(!class_exists('ShareYourCartBase',false)) die('Access Denied'); 

$wcIntegration = $html; //the object is sent under the $html parameter

//since this is a post, and is related to recovering or creating a new account, perform some special operations
if ($refresh || ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty( $_REQUEST['syc-account'] ))){

	$redirect = remove_query_arg( 'saved' ); 
	$redirect = remove_query_arg( 'wc_error', $redirect ); 
	$redirect = remove_query_arg( 'wc_message', $redirect ); 
	
	//remove the syc-account argument only if the SDK commanded as such
	if($refresh) $redirect = remove_query_arg( 'syc-account', $redirect );
	
	if ( !empty($error_message) ) $redirect = add_query_arg( 'wc_error', urlencode( esc_attr( $error_message ) ), $redirect );

	wp_safe_redirect( $redirect );
	exit;
}

?>
<script type="text/javascript">
  if(_gaq) _gaq.push(['_trackPageview', '/admin-view']);
</script>
<div class="wrap">
<?php if($show_header):?>
	
	<?php echo $this->getUpdateNotification(); ?>
	
	<?php if(true || !$this->getClientId() && !$this->getAppKey()): //show the get started message ?>
	
		<div id="wc_get_started">
			<span class="main"><?php _e('Setup your ShareYourCart account', 'woocommerce'); ?></span>
			<span><?php echo $wcIntegration->method_description; ?></span>
			<p><a href="<?php echo add_query_arg( 'syc-account', 'create', admin_url( 'admin.php?page=woocommerce&tab=integration&section=shareyourcart' ) ); ?>" class="button button-primary api-link"><?php _e('Create an account', 'woocommerce'); ?></a> <a href="<?php echo add_query_arg( 'syc-account', 'recover', admin_url( 'admin.php?page=woocommerce&tab=integration&section=shareyourcart' ) ); ?>" class="button api-link"><?php _e('Can\'t access your account?', 'woocommerce'); ?></a></p>
		</div>
	
	<?php else: ?>
	
		<h3>
			<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/logo-click']);">
				<img src="<?php echo $this->createUrl(dirname(__FILE__).'/../sdk/img/shareyourcart-logo.png'); ?>"/>
			</a>
		</h3>
		<br class="clr" />
		<?php echo wpautop( $wcIntegration->method_description );?>

	<?php endif;?>
	
	<?php endif; ?>

	<?php if(!empty($status_message) || !empty($error_message)): ?>
	<div class="updated settings-error"><p><strong>
		<?php 
			$message = @$error_message; 
		
			//is there a status message?
			if(!empty($status_message))
			{
				//put the status message on a new line
				if(!empty($message)) $message .= "<br /><br />";
				
				$message .= $status_message;
			}
		
			echo $message; 
		?>
	</strong></p></div>
	<?php endif; ?>
	 
    
    <h3>Account Options</h3>
    <div id="acount-options">      	     
		<fieldset>	   		
		<table class="form-table">
        	<tr valign="top">
        		<th class="titledesc" scope="row">
        			<?php echo SyC::t('sdk','API Status:'); ?>
		            <?php if($this->isActive()) : ?>
		                <span class="green"><?php echo SyC::t('sdk','Enabled'); ?></span>
		            <?php else :?>
		                <span class="red"><?php echo SyC::t('sdk','Disabled'); ?></span>
		            <?php endif;?>		
        		</th>
        		<td class="forminp">
        			<?php if($this->isActive()) : ?>
			            <input class="button" type="submit" value="<?php echo SyC::t('sdk','Disable'); ?>" name="disable-API" class="api-button" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/disable-click']);" />
			        <?php else :?>
			            <input class="button" type="submit" value="<?php echo SyC::t('sdk','Enable'); ?>" name="enable-API" class="api-button" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/enable-click']);" />
			        <?php endif;?>			
        		</td>
        	</tr>
        </table>     
        <table class="form-table form-table-api" name="shareyourcart_settings">
            <tr valign="top">
                <th class="titledesc" scope="row"><?php echo SyC::t('sdk','Client ID'); ?></th>
                <td class="forminp"><input type="text" name="client_id" id="client_id" class="regular-text" value="<?php echo $this->getClientId(); ?>"/></td>
            </tr>
            <tr valign="top">
                <th class="titledesc" scope="row"><?php echo SyC::t('sdk','App Key'); ?></th>
                <td class="forminp"><input type="text" name="app_key" id="app_key" class="regular-text" value="<?php echo $this->getAppKey(); ?>"/></td>
            </tr>
            <tr valign="top">
                <td class="titledesc"></td>
                <td class="forminp">
                	<span class="description">
	                	<a href="?<?php echo http_build_query(array_merge($_GET,array('syc-account'=>'recover')),'','&')?>" class="api-link" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/recover-click']);">
	                		<?php echo SyC::t('sdk',"Can't access your account?"); ?></a> <strong><?php echo SyC::t('sdk','or'); ?></strong> <?php echo SyC::t('sdk','New to ShareYourCart&trade;?'); ?> <a href="?<?php echo http_build_query(array_merge($_GET,array('syc-account'=>'create')),'','&')?>" id="account-recovery" class="api-link" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/create-account-click']);"><?php echo SyC::t('sdk','Create an account'); ?>
	                	</a>
                	</span>
                </td>
            </tr>
        </table>

        
        
        <br class="clr">

        <div class="submit">
        	<input type="submit" name="syc-account-form" class="button-primary" value="<?php echo SyC::t('sdk','Save changes'); ?>" onclick=" if(_gaq) _gaq.push(['_trackPageview', '/admin-view/save-click']);">
        </div>        
		</fieldset>
    </div>
    
	<?php if($this->isActive()): //show the configure part only if it is active ?>
    <h3>Configuration</h3>
    
    <fieldset class="syc-configuration">
	    <p><?php echo SyC::t('sdk','You can choose how much of a discount to give (in fixed amount, percentage, or free shipping) and to which social media channels it should it be applied. You can also define what the advertisement should say, so that it fully benefits your sales.'); ?></p>
	    
		<div class="submit">
	 		<a href="<?php echo $this->SHAREYOURCART_CONFIGURE; ?>?app_key=<?php echo $this->getAppKey(); ?>&amp;client_id=<?php echo $this->getClientId(); ?>&amp;email=<?php echo $this->getAdminEmail(); ?>" class="button-primary" target="_blank">
	 			<?php _e('Configure', 'woocommerce'); ?>
	 		</a>
	    </div>
	</fieldset>
	
	<?php endif; //show only if the cart is active ?>
</div>