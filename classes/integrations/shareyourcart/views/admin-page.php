<?php if ( ! class_exists( 'ShareYourCartBase', false ) ) die( 'Access Denied' );

$GLOBALS['hide_save_button'] = true;

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
<div class="wrap">

<?php if ( $show_header ) : ?>

	<?php echo $this->getUpdateNotification(); ?>

	<?php if ( ! $this->getClientId() && ! $this->getAppKey() ) : //show the get started message ?>

		<div id="wc_get_started">
			<span class="main"><?php _e('Setup your ShareYourCart account', 'woocommerce'); ?></span>
			<span><?php echo $wcIntegration->method_description; ?></span>
			<p><a href="<?php echo add_query_arg( 'syc-account', 'create', admin_url( 'admin.php?page=woocommerce&tab=integration&section=shareyourcart' ) ); ?>" class="button button-primary"><?php _e('Create an account', 'woocommerce'); ?></a> <a href="<?php echo add_query_arg( 'syc-account', 'recover', admin_url( 'admin.php?page=woocommerce&tab=integration&section=shareyourcart' ) ); ?>" class="button"><?php _e('Can\'t access your account?', 'woocommerce'); ?></a></p>
		</div>

	<?php else: ?>

		<h3>
			<a href="http://www.shareyourcart.com" target="_blank" title="Shareyourcart" class="shareyourcart-logo">
				<img src="<?php echo $this->createUrl(dirname(__FILE__).'/../sdk/img/shareyourcart-logo.png'); ?>"/>
			</a>
		</h3>
		<?php echo wpautop( $wcIntegration->method_description );?>

	<?php endif; ?>

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
		<table class="form-table">
        	<tr valign="top">
        		<th class="titledesc" scope="row">
        			<?php echo SyC::t('sdk','API Status:'); ?>
		            <?php if ( $this->isActive() ) echo SyC::t('sdk','Enabled'); else echo SyC::t('sdk','Disabled'); ?>
        		</th>
        		<td class="forminp">
        			<?php if($this->isActive()) : ?>
			            <input class="button" type="submit" value="<?php echo SyC::t('sdk','Disable'); ?>" name="disable-API" />
			        <?php else :?>
			            <input class="button" type="submit" value="<?php echo SyC::t('sdk','Enable'); ?>" name="enable-API" />
			        <?php endif;?>
        		</td>
        	</tr>
            <tr valign="top">
                <th class="titledesc" scope="row"><?php echo SyC::t('sdk','Client ID'); ?></th>
                <td class="forminp"><input type="text" name="client_id" id="client_id" class="regular-text" value="<?php echo $this->getClientId(); ?>"/></td>
            </tr>
            <tr valign="top">
                <th class="titledesc" scope="row"><?php echo SyC::t('sdk','App Key'); ?></th>
                <td class="forminp"><input type="text" name="app_key" id="app_key" class="regular-text" value="<?php echo $this->getAppKey(); ?>"/></td>
            </tr>
            <?php if($this->isActive()) : ?>
            <tr valign="top">
            	<th class="titledesc" scope="row"><?php echo SyC::t('sdk','Configuration'); ?></th>
            	<td>

	            	<a href="<?php echo $this->SHAREYOURCART_CONFIGURE; ?>?app_key=<?php echo $this->getAppKey(); ?>&amp;client_id=<?php echo $this->getClientId(); ?>&amp;email=<?php echo $this->getAdminEmail(); ?>" class="button" target="_blank"><?php _e('Configure', 'woocommerce'); ?></a>

	            	<p class="description"><?php echo SyC::t('sdk','You can choose how much of a discount to give (in fixed amount, percentage, or free shipping) and to which social media channels it should it be applied. You can also define what the advertisement should say, so that it fully benefits your sales.'); ?></p>

            	</td>
            </tr>
            <?php endif;?>
        </table>

        <div class="submit">
        	<input type="submit" class="button button-primary" name="syc-account-form" value="<?php _e('Save changes', 'woocommerce'); ?>" />
        </div>
    </div>

</div>