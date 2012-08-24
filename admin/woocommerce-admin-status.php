<?php
/**
 * Debug/Status page
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/System Status
 * @version     1.6.4
 */

/**
 * Output the content of the debugging page.
 *
 * @access public
 * @return void
 */
function woocommerce_status() {
	global $woocommerce;

	$tools = apply_filters( 'wc_debug_tools', array(
		'clear_transients' => array(
			'name'		=> __('Transients','woocommerce'),
			'button'	=> __('Clear Transients','woocommerce'),
			'desc'		=> __( 'This tool will clear the product/shop transients cache.', 'woocommerce' ),
		),
		'reset_roles' => array(
			'name'		=> __('Capabilities','woocommerce'),
			'button'	=> __('Reset Capabilities','woocommerce'),
			'desc'		=> __( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ),
		),
	) );

    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-status" id="icon-woocommerce"><br /></div>
		<h2><?php _e( 'System Status', 'woocommerce' ); ?> <a href="#" class="add-new-h2 debug-report"><?php _e('Generate report', 'woocommerce'); ?></a></h2>

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
					default:
						$action = esc_attr( $_GET['action'] );
						if( isset( $tools[ $action ]['callback'] ) ) {
							$callback = $tools[ $action ]['callback'];
							$return = call_user_func( $callback );
							if( $return === false ) {
								if( is_array( $callback ) ) {
									echo '<div class="error"><p>' . sprintf( __('There was an error calling %s::%s', 'woocommerce'), get_class( $callback[0] ), $callback[1] ) . '</p></div>';

								} else {
									echo '<div class="error"><p>' . sprintf( __('There was an error calling %s', 'woocommerce'), $callback ) . '</p></div>';
								}
							}
						}
				}
			}
		?>
		<br/>
		<textarea id="debug-report" readonly="readonly"></textarea>
		<table class="wc_status_table widefat" cellspacing="0">

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Versions', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('WooCommerce version','woocommerce')?></td>
                    <td><?php echo $woocommerce->version; ?></td>
                </tr>
                <tr>
                    <td><?php _e('WordPress version','woocommerce')?></td>
                    <td><?php if ( is_multisite() ) echo 'WPMU'; else echo 'WP'; ?> <?php echo bloginfo('version'); ?></td>
                </tr>
             	<tr>
             		<td><?php _e('Installed plugins','woocommerce')?></td>
             		<td><?php
             			$active_plugins = (array) get_option( 'active_plugins', array() );

             			if ( is_multisite() )
							$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

						$active_plugins = array_map( 'strtolower', $active_plugins );

						$wc_plugins = array();

						foreach ( $active_plugins as $plugin ) {
							//if ( strstr( $plugin, 'woocommerce' ) ) {

								$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

	    						if ( ! empty( $plugin_data['Name'] ) ) {

	    							$wc_plugins[] = $plugin_data['Name'] . ' ' . __('by', 'woocommerce') . ' ' . $plugin_data['Author'] . ' ' . __('version', 'woocommerce') . ' ' . $plugin_data['Version'];

	    						}
    						//}
						}

						if ( sizeof( $wc_plugins ) == 0 ) echo '-'; else echo '<ul><li>' . implode( ', </li><li>', $wc_plugins ) . '</li></ul>';

             		?></td>
             	</tr>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Settings', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('Home URL','woocommerce')?></td>
                    <td><?php echo home_url(); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Site URL','woocommerce')?></td>
                    <td><?php echo site_url(); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Force SSL','woocommerce')?></td>
					<td><?php echo ( get_option( 'woocommerce_force_ssl_checkout' ) === 'yes' ) ? '<mark class="yes">'.__( 'Yes', 'woocommerce' ).'</mark>' : '<mark class="no">'.__( 'No', 'woocommerce' ).'</mark>'; ?></td>
                </tr>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Shop Pages', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php
					$check_pages = array(
						__('Shop base page', 'woocommerce') => array(
								'option' => 'woocommerce_shop_page_id',
								'shortcode' => ''
							),
						__('Cart Page', 'woocommerce') => array(
								'option' => 'woocommerce_cart_page_id',
								'shortcode' => '[woocommerce_cart]'
							),
						__('Checkout Page', 'woocommerce') => array(
								'option' => 'woocommerce_checkout_page_id',
								'shortcode' => '[woocommerce_checkout]'
							),
						__('Pay Page', 'woocommerce') => array(
								'option' => 'woocommerce_pay_page_id',
								'shortcode' => '[woocommerce_pay]'
							),
						__('Thanks Page', 'woocommerce') => array(
								'option' => 'woocommerce_thanks_page_id',
								'shortcode' => '[woocommerce_thankyou]'
							),
						__('My Account Page', 'woocommerce') => array(
								'option' => 'woocommerce_myaccount_page_id',
								'shortcode' => '[woocommerce_my_account]'
							),
						__('Edit Address Page', 'woocommerce') => array(
								'option' => 'woocommerce_edit_address_page_id',
								'shortcode' => '[woocommerce_edit_address]'
							),
						__('View Order Page', 'woocommerce') => array(
								'option' => 'woocommerce_view_order_page_id',
								'shortcode' => '[woocommerce_view_order]'
							),
						__('Change Password Page', 'woocommerce') => array(
								'option' => 'woocommerce_change_password_page_id',
								'shortcode' => '[woocommerce_change_password]'
							)
					);

					$alt = 1;

					foreach ( $check_pages as $page_name => $values ) {

						if ( $alt == 1 ) echo '<tr>'; else echo '<tr>';

						echo '<td>' . $page_name . '</td><td>';

						$error = false;

						$page_id = get_option($values['option']);

						// Page ID check
						if ( ! $page_id ) {
							echo '<mark class="error">' . __('Page not set', 'woocommerce') . '</mark>';
							$error = true;
						} else {

							// Shortcode check
							if ( $values['shortcode'] ) {
								$page = get_post( $page_id );

								if ( ! strstr( $page->post_content, $values['shortcode'] ) ) {

									echo '<mark class="error">' . sprintf(__('Page does not contain the shortcode: %s', 'woocommerce'), $values['shortcode'] ) . '</mark>';
									$error = true;

								}
							}

						}

						if ( ! $error ) echo '<mark class="yes">#' . $page_id . ' - ' . get_permalink( $page_id ) . '</mark>';

						echo '</td></tr>';

						$alt = $alt * -1;
					}
				?>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Core Taxonomies', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('Order Statuses','woocommerce')?></td>
                    <td><?php
                    	$order_statuses = get_terms( 'shop_order_status', array( 'fields' => 'names', 'hide_empty' => 0 ) );
                    	echo implode( ', ', $order_statuses );
                    ?></td>
                </tr>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Server Environment', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
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
				<tr>
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
                <tr>
                    <td><?php _e('Server post_max_size','woocommerce')?></td>
                    <td><?php
                    	if(function_exists('phpversion'))
                    		echo wp_convert_bytes_to_hr( woocommerce_let_to_num( ini_get('post_max_size') ) );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('WP Memory Limit','woocommerce')?></td>
                    <td><?php
                    	$memory = woocommerce_let_to_num( WP_MEMORY_LIMIT );

                    	if ( $memory < 67108864 ) {
                    		echo '<mark class="error">' . sprintf( __('%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'woocommerce'), wp_convert_bytes_to_hr( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</mark>';
                    	} else {
                    		echo '<mark class="yes">' . wp_convert_bytes_to_hr( $memory ) . '</mark>';
                    	}
                    ?></td>
                </tr>
                <tr>
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
					<th colspan="2"><?php _e( 'PHP Sessions', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
            	<tr>
                    <td><?php _e('Session save path','woocommerce')?></td>
					<td><?php
						$save_path = session_save_path();

						if ( ! is_dir( $save_path ) ) {
							echo '<mark class="error">' . sprintf( __('<code>%s</code> does not exist - contact your host to resolve the problem.', 'woocommerce'), $save_path ). '</mark>';
						} elseif ( ! is_writeable( $save_path ) ) {
							echo '<mark class="error">' . sprintf( __('<code>%s</code> is not writable - contact your host to resolve the problem.', 'woocommerce'), $save_path ). '</mark>';
						} else {
							echo '<mark class="yes">' . sprintf( __('<code>%s</code> is writable.', 'woocommerce'), $save_path ). '</mark>';
						}
                    ?></td>
                </tr>
                <tr>
                	<td><?php _e('Session name','woocommerce')?></td>
                	<td><?php echo session_name(); ?></td>
                </tr>
            </tbody>

            <thead>
				<tr>
					<th colspan="2"><?php _e( 'Remote Posting/IPN', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<?php
				$posting = array();

				// fsockopen/cURL
				$posting['fsockopen_curl']['name'] = __('fsockopen/cURL','woocommerce');
				if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
					if ( function_exists( 'fsockopen' ) && function_exists( 'curl_init' )) {
						$posting['fsockopen_curl']['note'] = __('Your server has fsockopen and cURL enabled.', 'woocommerce');
					} elseif ( function_exists( 'fsockopen' )) {
						$posting['fsockopen_curl']['note'] = __('Your server has fsockopen enabled, cURL is disabled.', 'woocommerce');
					} else {
						$posting['fsockopen_curl']['note'] = __('Your server has cURL enabled, fsockopen is disabled.', 'woocommerce');
					}
					$posting['fsockopen_curl']['success'] = true;
				} else {
            		$posting['fsockopen_curl']['note'] = __('Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce'). '</mark>';
            		$posting['fsockopen_curl']['success'] = false;
            	}

            	// WP Remote Post Check
				$posting['wp_remote_post']['name'] = __('WP Remote Post Check','woocommerce');
				$request['cmd'] = '_notify-validate';
				$params = array(
					'sslverify' 	=> false,
		        	'timeout' 		=> 60,
		        	'user-agent'	=> 'WooCommerce/' . $woocommerce->version,
		        	'body'			=> $request
				);
				$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

				if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
            		$posting['wp_remote_post']['note'] = __('wp_remote_post() was successful - PayPal IPN is working.', 'woocommerce');
            		$posting['wp_remote_post']['success'] = true;
            	} elseif ( is_wp_error( $response ) ) {
            		$posting['wp_remote_post']['note'] = __('wp_remote_post() failed. PayPal IPN won\'t work with your server. Contact your hosting provider. Error:', 'woocommerce') . ' ' . $response->get_error_message();
            		$posting['wp_remote_post']['success'] = false;
            	} else {
	            	$posting['wp_remote_post']['note'] = __('wp_remote_post() failed. PayPal IPN may not work with your server.', 'woocommerce');
            		$posting['wp_remote_post']['success'] = false;
            	}

            	$posting = apply_filters( 'wc_debug_posting', $posting );
            ?>

			<tbody>
			<?php foreach($posting as $post) { $mark = ( isset( $post['success'] ) && $post['success'] == true ) ? 'yes' : 'error'; ?>
				<tr>
                    <td><?php echo $post['name']; ?></td>
                    <td>
                    	<mark class="<?php echo $mark; ?>">
	                    	<?php echo $post['note']; ?>
                    	</mark>
                    </td>
                </tr>
			<?php } ?>
            </tbody>

            <thead class="tools">
				<tr>
					<th colspan="2"><?php _e( 'Tools', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody class="tools">
			<?php foreach($tools as $action => $tool) { ?>
				<tr>
                    <td><?php echo $tool['name']; ?></td>
                    <td>
                    	<p>
	                    	<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=woocommerce_status&action=' . $action ), 'debug_action' ); ?>" class="button"><?php echo $tool['button']; ?></a>
	                    	<span class="description"><?php echo $tool['desc']; ?></span>
                    	</p>
                    </td>
                </tr>
			<?php } ?>
			</tbody>
		</table>

	</div>
	<script type="text/javascript">

		jQuery('a.debug-report').click(function(){

			if ( ! jQuery('#debug-report').val() ) {

				// Generate report - user can paste into forum
				var report = '`';

				jQuery('thead:not(".tools"), tbody:not(".tools")', '.wc_status_table').each(function(){

					$this = jQuery( this );

					if ( $this.is('thead') ) {

						report = report + "\n=============================================================================================\n";
						report = report + " " + jQuery.trim( $this.text() ) + "\n";
						report = report + "=============================================================================================\n";

					} else {

						jQuery('tr', $this).each(function(){

							$this = jQuery( this );

							report = report + $this.find('td:eq(0)').text() + ": \t";
							report = report + $this.find('td:eq(1)').text() + "\n";

						});

					}

				});

				report = report + '`';

				jQuery('#debug-report').val( report );
			}

			jQuery('#debug-report').slideToggle('500', function() {
				jQuery(this).select();
			});

      		return false;

		});

	</script>
	<?php
}