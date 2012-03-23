<?php
/**
 * Debug/Status page
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

function woocommerce_status() {
	global $woocommerce;
	
    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-status" id="icon-woocommerce"><br></div>
		<h2><?php _e( 'WooCommerce Status', 'woocommerce' ); ?></h2>
		
		<?php
			if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {
				switch ( $_GET['action'] ) {
					case "clear_transients" :
						$woocommerce->clear_product_transients();
						
						echo '<div class="updated"><p>' . __('Product Transients Cleared', 'woocommerce') . '</p></div>';
					break;
					case "reset_roles" :
						global $wp_roles;

						// Roles
						remove_role( 'customer' );  
						remove_role( 'shop_manager' );

						// Capabilities
						$wp_roles->remove_cap( 'administrator', 'manage_woocommerce' );

						$woocommerce->init_user_roles();
						
						echo '<div class="updated"><p>' . __('Roles successfully reset', 'woocommerce') . '</p></div>';
					break;
				}
			}
		?>
		<br/>
		<table class="wc_status_table widefat" cellspacing="0">
			
			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Versions', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			
			<tbody>
                <tr class="alternate">
                    <td><?php _e('WooCommerce version','woocommerce')?></td>
                    <td><?php echo $woocommerce->version; ?></td>
                </tr>
                <tr>
                    <td><?php _e('WordPress version','woocommerce')?></td>
                    <td><?php if ( is_multisite() ) echo 'WPMU'; else echo 'WP'; ?> <?php echo bloginfo('version'); ?></td>
                </tr>
             	<tr class="alternate">
             		<td><?php _e('Installed WooCommerce plugins','woocommerce')?></td>
             		<td><?php
             			$active_plugins = (array) get_option( 'active_plugins', array() );
             			
             			if ( is_multisite() )
							$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
						
						$active_plugins = array_map( 'strtolower', $active_plugins );
						
						$wc_plugins = array();
						
						foreach ( $active_plugins as $plugin ) {
							if ( strstr( $plugin, 'woocommerce' ) ) {
							
								$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
	    						
	    						if ( ! empty( $plugin_data['Name'] ) ) {
	    							
	    							$wc_plugins[] = $plugin_data['Name'] . ' ' . __('by', 'woocommerce') . ' ' . $plugin_data['Author'] . ' ' . __('version', 'woocommerce') . ' ' . $plugin_data['Version'];
	    							
	    						}
    						}
						}
						
						echo implode( '</br>', $wc_plugins )
	
             		?></td>
             	</tr>
			</tbody>
			
			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Server Environment', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			
			<tbody>
                <tr class="alternate">
                    <td><?php _e('PHP Version','woocommerce')?></td>
                    <td><?php 
                    	if ( function_exists( 'phpversion' ) ) echo phpversion(); 
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('Server Software','woocommerce')?></td>
                    <td><?php 
                    	echo $_SERVER['SERVER_SOFTWARE']; 
                    ?></td>
                </tr>
				<tr class="alternate">
                    <td><?php _e('WP Max Upload Size','woocommerce'); ?></td>
                    <td><?php 
                    	echo wp_convert_bytes_to_hr( wp_max_upload_size() );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('Server upload_max_filesize','woocommerce')?></td>
                    <td><?php 
                    	if(function_exists('phpversion'))
                    		echo wp_convert_bytes_to_hr( woocommerce_let_to_num( ini_get('upload_max_filesize') ) );
                    ?></td>
                </tr>
                <tr class="alternate">
                    <td><?php _e('Server post_max_size','woocommerce')?></td>
                    <td><?php 
                    	if(function_exists('phpversion')) 
                    		echo wp_convert_bytes_to_hr( woocommerce_let_to_num( ini_get('post_max_size') ) );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('WP Memory Limit','woocommerce')?></td>
                    <td><?php 
                    	echo wp_convert_bytes_to_hr( woocommerce_let_to_num( WP_MEMORY_LIMIT ) ); 
                    ?></td>
                </tr>
                <tr class="alternate">
                    <td><?php _e('WP Debug Mode','woocommerce')?></td>
                    <td><?php if ( defined('WP_DEBUG') && WP_DEBUG ) echo '<mark class="yes">' . __('Yes', 'woocommerce') . '</mark>'; else echo '<mark class="no">' . __('No', 'woocommerce') . '</mark>'; ?></td>
                </tr>
                <tr>
                    <td><?php _e('WC Logging','woocommerce')?></td>
                    <td><?php 
                    	if ( @fopen( $woocommerce->plugin_path() . '/logs/paypal.txt', 'a' ) )
                    		echo '<mark class="yes">' . __('Log directory is writable.', 'woocommerce') . '</mark>';
                    	else
                    		echo '<mark class="error">' . __('Log directory (<code>woocommerce/logs/</code>) is not writable. Logging will not be possible.', 'woocommerce') . '</mark>';
                    ?></td>
                </tr>
            </tbody>
            
            <thead>
				<tr>
					<th colspan="2"><?php _e( 'Remote Posting/IPN', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			
			<tbody>
            	<tr class="alternate">
                    <td><?php _e('fsockopen/Curl','woocommerce')?></td>
                    <td><?php 
                    	if( function_exists('fsockopen') || function_exists('curl_init') ) 
                    		echo '<mark class="yes">' . __('Your server has fsockopen or Curl enabled.', 'woocommerce'). '</mark>'; 
                    	else 
                    		echo '<mark class="error">' . __('Your server does not have fsockopen or Curl enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce'). '</mark>'; ?></td>
                </tr>
                <tr>
                    <td><?php _e('WP Remote Post Check','woocommerce')?></td>
                    <td><?php
						$params = array( 
							'sslverify' 	=> false,
				        	'timeout' 		=> 30,
				        	'user-agent'	=> 'WooCommerce/'.$woocommerce->version
						);	
						$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );
                    	 
                    	if ( ! is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) 
                    		echo '<mark class="yes">' . __('wp_remote_post() was successful - PayPal IPN is working.', 'woocommerce'). '</mark>'; 
                    	else 
                    		echo '<mark class="error">' . __('wp_remote_post() failed. PayPal IPN won\'t work with your server. Contact your hosting provider. Error: ', 'woocommerce') . $response->get_error_message() . '</mark>';
                    ?></td>
                </tr>
            </tbody>
            
            <thead>
				<tr>
					<th colspan="2"><?php _e( 'Tools', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			
			<tbody>
				<tr class="alternate">
                    <td><?php _e('Transients','woocommerce')?></td>
                    <td>
                    	<p>
	                    	<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=woocommerce_status&action=clear_transients'), 'debug_action' ); ?>" class="button"><?php _e('Clear Transients','woocommerce')?></a>
	                    	<span class="description"><?php _e( 'This tool will clear the product/shop transients cache.', 'woocommerce' ); ?></span>
                    	</p>
                    </td>
                </tr>
				<tr>
                    <td><?php _e('Capabilities','woocommerce')?></td>
                    <td>
                    	<p>
	                    	<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=woocommerce_status&action=reset_roles'), 'debug_action' ); ?>" class="button"><?php _e('Reset Capabilities','woocommerce')?></a>
	                    	<span class="description"><?php _e( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ); ?></span>
                    	</p>
                    </td>
                </tr>
			</tbody>
		</table>

	</div>
	<?php
}