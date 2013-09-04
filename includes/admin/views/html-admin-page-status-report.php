<div class="woocommerce-message">
	<div class="squeezer">
		<h4><?php _e( 'Please include this information when requesting support:', 'woocommerce' ); ?> </h4>
		<p class="submit"><a href="#" download="wc_report.txt" class="button-primary debug-report"><?php _e( 'Download System Report File', 'woocommerce' ); ?></a></p>
	</div>
</div>
<br/>
<table class="wc_status_table widefat" cellspacing="0">

	<thead>
		<tr>
			<th colspan="2"><?php _e( 'Environment', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td><?php _e( 'Home URL','woocommerce' ); ?>:</td>
			<td><?php echo home_url(); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Site URL','woocommerce' ); ?>:</td>
			<td><?php echo site_url(); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WC Version','woocommerce' ); ?>:</td>
			<td><?php echo esc_html( $woocommerce->version ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WC Database Version','woocommerce' ); ?>:</td>
			<td><?php echo esc_html( get_option( 'woocommerce_db_version' ) ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WP Version','woocommerce' ); ?>:</td>
			<td><?php bloginfo('version'); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WP Multisite Enabled','woocommerce' ); ?>:</td>
			<td><?php if ( is_multisite() ) echo __( 'Yes', 'woocommerce' ); else echo __( 'No', 'woocommerce' ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'Web Server Info','woocommerce' ); ?>:</td>
			<td><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'PHP Version','woocommerce' ); ?>:</td>
			<td><?php if ( function_exists( 'phpversion' ) ) echo esc_html( phpversion() ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'MySQL Version','woocommerce' ); ?>:</td>
			<td><?php if ( function_exists( 'mysql_get_server_info' ) ) echo esc_html( mysql_get_server_info() ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WP Memory Limit','woocommerce' ); ?>:</td>
			<td><?php
				$memory = woocommerce_let_to_num( WP_MEMORY_LIMIT );

				if ( $memory < 67108864 ) {
					echo '<mark class="error">' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'woocommerce' ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . size_format( $memory ) . '</mark>';
				}
			?></td>
		</tr>
		<tr>
			<td><?php _e( 'WP Debug Mode', 'woocommerce' ); ?>:</td>
			<td><?php if ( defined('WP_DEBUG') && WP_DEBUG ) echo '<mark class="yes">' . __( 'Yes', 'woocommerce' ) . '</mark>'; else echo '<mark class="no">' . __( 'No', 'woocommerce' ) . '</mark>'; ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WP Language', 'woocommerce' ); ?>:</td>
			<td><?php if ( defined( 'WPLANG' ) && WPLANG ) echo WPLANG; else  _e( 'Default', 'woocommerce' ); ?></td>
		</tr>
		<tr>
			<td><?php _e( 'WP Max Upload Size','woocommerce' ); ?>:</td>
			<td><?php echo size_format( wp_max_upload_size() ); ?></td>
		</tr>
		<?php if ( function_exists( 'ini_get' ) ) : ?>
			<tr>
				<td><?php _e('PHP Post Max Size','woocommerce' ); ?>:</td>
				<td><?php echo size_format( woocommerce_let_to_num( ini_get('post_max_size') ) ); ?></td>
			</tr>
			<tr>
				<td><?php _e('PHP Time Limit','woocommerce' ); ?>:</td>
				<td><?php echo ini_get('max_execution_time'); ?></td>
			</tr>
			<tr>
				<td><?php _e( 'PHP Max Input Vars','woocommerce' ); ?>:</td>
				<td><?php echo ini_get('max_input_vars'); ?></td>
			</tr>
			<tr>
				<td><?php _e( 'SUHOSIN Installed','woocommerce' ); ?>:</td>
				<td><?php echo extension_loaded( 'suhosin' ) ? __( 'Yes', 'woocommerce' ) : __( 'No', 'woocommerce' ); ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<td><?php _e( 'WC Logging','woocommerce' ); ?>:</td>
			<td><?php
				if ( @fopen( $woocommerce->plugin_path() . '/logs/paypal.txt', 'a' ) )
					echo '<mark class="yes">' . __( 'Log directory is writable.', 'woocommerce' ) . '</mark>';
				else
					echo '<mark class="error">' . __( 'Log directory (<code>woocommerce/logs/</code>) is not writable. Logging will not be possible.', 'woocommerce' ) . '</mark>';
			?></td>
		</tr>
		<tr>
			<td><?php _e( 'Default Timezone','woocommerce' ); ?>:</td>
			<td><?php
				$default_timezone = date_default_timezone_get();
				if ( 'UTC' !== $default_timezone ) {
					echo '<mark class="error">' . sprintf( __( 'Default timezone is %s - it should be UTC', 'woocommerce' ), $default_timezone ) . '</mark>';
				} else {
					echo '<mark class="yes">' . sprintf( __( 'Default timezone is %s', 'woocommerce' ), $default_timezone ) . '</mark>';
				} ?>
			</td>
		</tr>
		<?php
			$posting = array();

			// fsockopen/cURL
			$posting['fsockopen_curl']['name'] = __( 'fsockopen/cURL','woocommerce');
			if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
				if ( function_exists( 'fsockopen' ) && function_exists( 'curl_init' )) {
					$posting['fsockopen_curl']['note'] = __('Your server has fsockopen and cURL enabled.', 'woocommerce' );
				} elseif ( function_exists( 'fsockopen' )) {
					$posting['fsockopen_curl']['note'] = __( 'Your server has fsockopen enabled, cURL is disabled.', 'woocommerce' );
				} else {
					$posting['fsockopen_curl']['note'] = __( 'Your server has cURL enabled, fsockopen is disabled.', 'woocommerce' );
				}
				$posting['fsockopen_curl']['success'] = true;
			} else {
				$posting['fsockopen_curl']['note'] = __( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce' ). '</mark>';
				$posting['fsockopen_curl']['success'] = false;
			}

			// SOAP
			$posting['soap_client']['name'] = __( 'SOAP Client','woocommerce' );
			if ( class_exists( 'SoapClient' ) ) {
				$posting['soap_client']['note'] = __('Your server has the SOAP Client class enabled.', 'woocommerce' );
				$posting['soap_client']['success'] = true;
			} else {
				$posting['soap_client']['note'] = sprintf( __( 'Your server does not have the <a href="%s">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected.', 'woocommerce' ), 'http://php.net/manual/en/class.soapclient.php' ) . '</mark>';
				$posting['soap_client']['success'] = false;
			}

			// WP Remote Post Check
			$posting['wp_remote_post']['name'] = __( 'WP Remote Post','woocommerce');
			$request['cmd'] = '_notify-validate';
			$params = array(
				'sslverify' 	=> false,
				'timeout' 		=> 60,
				'user-agent'	=> 'WooCommerce/' . $woocommerce->version,
				'body'			=> $request
			);
			$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$posting['wp_remote_post']['note'] = __('wp_remote_post() was successful - PayPal IPN is working.', 'woocommerce' );
				$posting['wp_remote_post']['success'] = true;
			} elseif ( is_wp_error( $response ) ) {
				$posting['wp_remote_post']['note'] = __( 'wp_remote_post() failed. PayPal IPN won\'t work with your server. Contact your hosting provider. Error:', 'woocommerce' ) . ' ' . $response->get_error_message();
				$posting['wp_remote_post']['success'] = false;
			} else {
				$posting['wp_remote_post']['note'] = __( 'wp_remote_post() failed. PayPal IPN may not work with your server.', 'woocommerce' );
				$posting['wp_remote_post']['success'] = false;
			}

			$posting = apply_filters( 'woocommerce_debug_posting', $posting );

			foreach( $posting as $post ) { $mark = ( isset( $post['success'] ) && $post['success'] == true ) ? 'yes' : 'error';
				?>
				<tr>
					<td><?php echo esc_html( $post['name'] ); ?>:</td>
					<td>
						<mark class="<?php echo $mark; ?>">
							<?php echo wp_kses_data( $post['note'] ); ?>
						</mark>
					</td>
				</tr>
				<?php
			}
		?>
	</tbody>

	<thead>
		<tr>
			<th colspan="2"><?php _e( 'Plugins', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td><?php _e( 'Installed Plugins','woocommerce' ); ?>:</td>
			<td><?php
				$active_plugins = (array) get_option( 'active_plugins', array() );

				if ( is_multisite() )
					$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

				$wc_plugins = array();

				foreach ( $active_plugins as $plugin ) {

					$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
					$dirname        = dirname( $plugin );
					$version_string = '';

					if ( ! empty( $plugin_data['Name'] ) ) {

						// link the plugin name to the plugin url if available
						$plugin_name = $plugin_data['Name'];
						if ( ! empty( $plugin_data['PluginURI'] ) ) {
							$plugin_name = '<a href="' . $plugin_data['PluginURI'] . '" title="' . __( 'Visit plugin homepage' , 'woocommerce' ) . '">' . $plugin_name . '</a>';
						}

						if ( strstr( $dirname, 'woocommerce' ) ) {

							if ( false === ( $version_data = get_transient( $plugin . '_version_data' ) ) ) {
								$changelog = wp_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $dirname . '/changelog.txt' );
								$cl_lines  = explode( "\n", wp_remote_retrieve_body( $changelog ) );
								if ( ! empty( $cl_lines ) ) {
									foreach ( $cl_lines as $line_num => $cl_line ) {
										if ( preg_match( '/^[0-9]/', $cl_line ) ) {

											$date         = str_replace( '.' , '-' , trim( substr( $cl_line , 0 , strpos( $cl_line , '-' ) ) ) );
											$version      = preg_replace( '~[^0-9,.]~' , '' ,stristr( $cl_line , "version" ) );
											$update       = trim( str_replace( "*" , "" , $cl_lines[ $line_num + 1 ] ) );
											$version_data = array( 'date' => $date , 'version' => $version , 'update' => $update , 'changelog' => $changelog );
											set_transient( $plugin . '_version_data', $version_data , 60*60*12 );
											break;
										}
									}
								}
							}

							if ( ! empty( $version_data['version'] ) && version_compare( $version_data['version'], $plugin_data['Version'], '!=' ) )
								$version_string = ' &ndash; <strong style="color:red;">' . $version_data['version'] . ' ' . __( 'is available', 'woocommerce' ) . '</strong>';
						}

						$wc_plugins[] = $plugin_name . ' ' . __( 'by', 'woocommerce' ) . ' ' . $plugin_data['Author'] . ' ' . __( 'version', 'woocommerce' ) . ' ' . $plugin_data['Version'] . $version_string;

					}
				}

				if ( sizeof( $wc_plugins ) == 0 )
					echo '-';
				else
					echo implode( ', <br/>', $wc_plugins );

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
			<td><?php _e( 'Force SSL','woocommerce' ); ?>:</td>
			<td><?php echo get_option( 'woocommerce_force_ssl_checkout' ) === 'yes' ? '<mark class="yes">'.__( 'Yes', 'woocommerce' ).'</mark>' : '<mark class="no">'.__( 'No', 'woocommerce' ).'</mark>'; ?></td>
		</tr>
	</tbody>

	<thead>
		<tr>
			<th colspan="2"><?php _e( 'WC Pages', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php
			$check_pages = array(
				__( 'Shop Base', 'woocommerce' ) => array(
						'option' => 'woocommerce_shop_page_id',
						'shortcode' => ''
					),
				__( 'Cart', 'woocommerce' ) => array(
						'option' => 'woocommerce_cart_page_id',
						'shortcode' => '[woocommerce_cart]'
					),
				__( 'Checkout', 'woocommerce' ) => array(
						'option' => 'woocommerce_checkout_page_id',
						'shortcode' => '[woocommerce_checkout]'
					),
				__( 'My Account', 'woocommerce' ) => array(
						'option' => 'woocommerce_myaccount_page_id',
						'shortcode' => '[woocommerce_my_account]'
					)
			);

			$alt = 1;

			foreach ( $check_pages as $page_name => $values ) {

				if ( $alt == 1 ) echo '<tr>'; else echo '<tr>';

				echo '<td>' . esc_html( $page_name ) . ':</td><td>';

				$error = false;

				$page_id = get_option( $values['option'] );

				// Page ID check
				if ( ! $page_id ) {
					echo '<mark class="error">' . __( 'Page not set', 'woocommerce' ) . '</mark>';
					$error = true;
				} else {

					// Shortcode check
					if ( $values['shortcode'] ) {
						$page = get_post( $page_id );

						if ( empty( $page ) ) {

							echo '<mark class="error">' . sprintf( __( 'Page does not exist', 'woocommerce' ) ) . '</mark>';
							$error = true;

						} else if ( ! strstr( $page->post_content, $values['shortcode'] ) ) {

							echo '<mark class="error">' . sprintf( __( 'Page does not contain the shortcode: %s', 'woocommerce' ), $values['shortcode'] ) . '</mark>';
							$error = true;

						}
					}

				}

				if ( ! $error ) echo '<mark class="yes">#' . absint( $page_id ) . ' - ' . str_replace( home_url(), '', get_permalink( $page_id ) ) . '</mark>';

				echo '</td></tr>';

				$alt = $alt * -1;
			}
		?>
	</tbody>

	<thead>
		<tr>
			<th colspan="2"><?php _e( 'WC Taxonomies', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td><?php _e( 'Order Statuses', 'woocommerce' ); ?>:</td>
			<td><?php
				$display_terms = array();
				$terms = get_terms( 'shop_order_status', array( 'hide_empty' => 0 ) );
				foreach ( $terms as $term )
					$display_terms[] = $term->name . ' (' . $term->slug . ')';
				echo implode( ', ', array_map( 'esc_html', $display_terms ) );
			?></td>
		</tr>
		<tr>
			<td><?php _e( 'Product Types', 'woocommerce' ); ?>:</td>
			<td><?php
				$display_terms = array();
				$terms = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
				foreach ( $terms as $term )
					$display_terms[] = $term->name . ' (' . $term->slug . ')';
				echo implode( ', ', array_map( 'esc_html', $display_terms ) );
			?></td>
		</tr>
	</tbody>

	<thead>
		<tr>
			<th colspan="2"><?php _e( 'Theme', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td><?php _e( 'Theme Name', 'woocommerce' ); ?>:</td>
			<td><?php
				$active_theme = wp_get_theme();
				echo $active_theme->Name;
			?></td>
		</tr>
		<tr>
			<td><?php _e( 'Theme Version', 'woocommerce' ); ?>:</td>
			<td><?php
				echo $active_theme->Version;
			?></td>
		</tr>
		<tr>
			<td><?php _e( 'Author URL', 'woocommerce' ); ?>:</td>
			<td><?php
				echo $active_theme->{'Author URI'};
			?></td>
		</tr>
	</tbody>

	<thead>
		<tr>
			<th colspan="2"><?php _e( 'Templates', 'woocommerce' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td><?php _e( 'Template Overrides', 'woocommerce' ); ?>:</td>
			<td><?php

				$template_path = $woocommerce->plugin_path() . '/templates/';
				$found_files   = array();
				$files         = $this->scan_template_files( $template_path );

				foreach ( $files as $file ) {
					if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
						$found_files[] = '/' . $file;
					} elseif( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
						$found_files[] = '/woocommerce/' . $file;
					}
				}

				if ( $found_files ) {
					echo implode( ', <br/>', $found_files );
				} else {
					_e( 'No core overrides present in theme.', 'woocommerce' );
				}

			?></td>
		</tr>
	</tbody>

</table>

<script type="text/javascript">

	/*
	@var i string default
	@var l how many repeat s
	@var s string to repeat
	@var w where s should indent
	*/
	jQuery.wc_strPad = function(i,l,s,w) {
		var o = i.toString();
		if (!s) { s = '0'; }
		while (o.length < l) {
			// empty
			if(w == 'undefined'){
				o = s + o;
			}else{
				o = o + s;
			}
		}
		return o;
	};


	jQuery('a.debug-report').click(function(){

		var report = "";

		jQuery('.wc_status_table thead, .wc_status_table tbody').each(function(){

			$this = jQuery( this );

			if ( $this.is('thead') ) {

				report = report + "\n### " + jQuery.trim( $this.text() ) + " ###\n\n";

			} else {

				jQuery('tr', $this).each(function(){

					$this = jQuery( this );

					name = jQuery.wc_strPad( jQuery.trim( $this.find('td:eq(0)').text() ), 25, ' ' );
					value = jQuery.trim( $this.find('td:eq(1)').text() );


					value_array = value.split( ', ' );
					if( value_array.length > 1 ){

						// if value have a list of plugins ','
						// split to add new line
						output = '';
						temp_line ='';
						jQuery.each( value_array, function(key, line){

							tab = ( key == 0 )?0:25;
							temp_line = temp_line + jQuery.wc_strPad( '', tab, ' ', 'f' ) + line +'\n';
						});

						value = temp_line;
					}

					report = report +''+ name + value + "\n";
				});

			}
		} );

		var blob = new Blob( [report] );

		jQuery(this).attr( 'href', window.URL.createObjectURL( blob ) );

		return true;
	});

</script>