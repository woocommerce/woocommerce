<?php
/**
 * Admin View: Page - Status Report
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="updated woocommerce-message">
	<p><?php _e( 'Please copy and paste this information in your ticket when contacting support:', 'woocommerce' ); ?> </p>
	<p class="submit"><a href="#" class="button-primary debug-report"><?php _e( 'Get System Report', 'woocommerce' ); ?></a>
	<a class="skip button-primary" href="http://docs.woothemes.com/document/understanding-the-woocommerce-system-status-report/" target="_blank"><?php _e( 'Understanding the Status Report', 'woocommerce' ); ?></a></p>
	<div id="debug-report">
		<textarea readonly="readonly"></textarea>
		<p class="submit"><button id="copy-for-support" class="button-primary" href="#" data-tip="<?php _e( 'Copied!', 'woocommerce' ); ?>"><?php _e( 'Copy for Support', 'woocommerce' ); ?></button></p>
	</div>
</div>
<br/>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WordPress Environment"><?php _e( 'WordPress Environment', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Home URL"><?php _e( 'Home URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The URL of your site\'s homepage.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo home_url(); ?></td>
		</tr>
		<tr>
			<td data-export-label="Site URL"><?php _e( 'Site URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The root URL of your site.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo site_url(); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Version"><?php _e( 'WC Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of WooCommerce installed on your site.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo esc_html( WC()->version ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Database Version"><?php _e( 'WC Database Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of WooCommerce that the database is formatted for. This should be the same as your WooCommerce Version.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo esc_html( get_option( 'woocommerce_db_version' ) ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Log Directory Writable"><?php _e( 'Log Directory Writable', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Several WooCommerce extensions can write logs which makes debugging problems easier. The directory must be writable for this to happen.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				if ( @fopen( WC_LOG_DIR . 'test-log.log', 'a' ) ) {
					echo '<mark class="yes">' . '&#10004; <code>' . WC_LOG_DIR . '</code></mark> ';
				} else {
					printf( '<mark class="error">' . '&#10005; ' . __( 'To allow logging, make <code>%s</code> writable or define a custom <code>WC_LOG_DIR</code>.', 'woocommerce' ) . '</mark>', WC_LOG_DIR );
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="WP Version"><?php _e( 'WP Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of WordPress installed on your site.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php bloginfo('version'); ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Multisite"><?php _e( 'WP Multisite', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Whether or not you have WordPress Multisite enabled.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php if ( is_multisite() ) echo '&#10004;'; else echo '&ndash;'; ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Memory Limit"><?php _e( 'WP Memory Limit', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The maximum amount of memory (RAM) that your site can use at one time.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				$memory = wc_let_to_num( WP_MEMORY_LIMIT );

				if ( $memory < 67108864 ) {
					echo '<mark class="error">' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s" target="_blank">Increasing memory allocated to PHP</a>', 'woocommerce' ), size_format( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . size_format( $memory ) . '</mark>';
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="WP Debug Mode"><?php _e( 'WP Debug Mode', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Displays whether or not WordPress is in Debug Mode.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php if ( defined('WP_DEBUG') && WP_DEBUG ) echo '<mark class="yes">' . '&#10004;' . '</mark>'; else echo '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Language"><?php _e( 'Language', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The current language used by WordPress. Default = English', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo get_locale() ?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Server Environment"><?php _e( 'Server Environment', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Server Info"><?php _e( 'Server Info', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Information about the web server that is currently hosting your site.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="PHP Version"><?php _e( 'PHP Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of PHP installed on your hosting server.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				// Check if phpversion function exists
				if ( function_exists( 'phpversion' ) ) {
					$php_version = phpversion();

					if ( version_compare( $php_version, '5.4', '<' ) ) {
						echo '<mark class="error">' . sprintf( __( '%s - We recommend a minimum PHP version of 5.4. See: <a href="%s" target="_blank">How to update your PHP version</a>', 'woocommerce' ), esc_html( $php_version ), 'http://docs.woothemes.com/document/how-to-update-your-php-version/' ) . '</mark>';
					} else {
						echo '<mark class="yes">' . esc_html( $php_version ) . '</mark>';
					}
				} else {
					_e( "Couldn't determine PHP version because phpversion() doesn't exist.", 'woocommerce' );
				}
				?></td>
		</tr>
		<?php if ( function_exists( 'ini_get' ) ) : ?>
			<tr>
				<td data-export-label="PHP Post Max Size"><?php _e( 'PHP Post Max Size', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The largest filesize that can be contained in one post.', 'woocommerce' ) . '">[?]</a>'; ?></td>
				<td><?php echo size_format( wc_let_to_num( ini_get('post_max_size') ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Time Limit"><?php _e( 'PHP Time Limit', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', 'woocommerce' ) . '">[?]</a>'; ?></td>
				<td><?php echo ini_get('max_execution_time'); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Max Input Vars"><?php _e( 'PHP Max Input Vars', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'woocommerce' ) . '">[?]</a>'; ?></td>
				<td><?php echo ini_get('max_input_vars'); ?></td>
			</tr>
			<tr>
				<td data-export-label="SUHOSIN Installed"><?php _e( 'SUHOSIN Installed', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Suhosin is an advanced protection system for PHP installations. It was designed to protect your servers on the one hand against a number of well known problems in PHP applications and on the other hand against potential unknown vulnerabilities within these applications or the PHP core itself. If enabled on your server, Suhosin may need to be configured to increase its data submission limits.', 'woocommerce' ) . '">[?]</a>'; ?></td>
				<td><?php echo extension_loaded( 'suhosin' ) ? '&#10004;' : '&ndash;'; ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<td data-export-label="MySQL Version"><?php _e( 'MySQL Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The version of MySQL installed on your hosting server.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td>
				<?php
				/** @global wpdb $wpdb */
				global $wpdb;
				echo $wpdb->db_version();
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Max Upload Size"><?php _e( 'Max Upload Size', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The largest filesize that can be uploaded to your WordPress installation.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo size_format( wp_max_upload_size() ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Default Timezone is UTC"><?php _e( 'Default Timezone is UTC', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The default timezone for your server.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				$default_timezone = date_default_timezone_get();
				if ( 'UTC' !== $default_timezone ) {
					echo '<mark class="error">' . '&#10005; ' . sprintf( __( 'Default timezone is %s - it should be UTC', 'woocommerce' ), $default_timezone ) . '</mark>';
				} else {
					echo '<mark class="yes">' . '&#10004;' . '</mark>';
				} ?>
			</td>
		</tr>
		<?php
			$posting = array();

			// fsockopen/cURL
			$posting['fsockopen_curl']['name'] = 'fsockopen/cURL';
			$posting['fsockopen_curl']['help'] = '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Payment gateways can use cURL to communicate with remote servers to authorize payments, other plugins may also use it when communicating with remote services.', 'woocommerce' ) . '">[?]</a>';

			if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
				$posting['fsockopen_curl']['success'] = true;
			} else {
				$posting['fsockopen_curl']['success'] = false;
				$posting['fsockopen_curl']['note']    = __( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce' ). '</mark>';
			}

			// SOAP
			$posting['soap_client']['name'] = 'SoapClient';
			$posting['soap_client']['help'] = '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Some webservices like shipping use SOAP to get information from remote servers, for example, live shipping quotes from FedEx require SOAP to be installed.', 'woocommerce' ) . '">[?]</a>';

			if ( class_exists( 'SoapClient' ) ) {
				$posting['soap_client']['success'] = true;
			} else {
				$posting['soap_client']['success'] = false;
				$posting['soap_client']['note']    = sprintf( __( 'Your server does not have the <a href="%s">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected.', 'woocommerce' ), 'http://php.net/manual/en/class.soapclient.php' ) . '</mark>';
			}

			// DOMDocument
			$posting['dom_document']['name'] = 'DOMDocument';
			$posting['dom_document']['help'] = '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'HTML/Multipart emails use DOMDocument to generate inline CSS in templates.', 'woocommerce' ) . '">[?]</a>';

			if ( class_exists( 'DOMDocument' ) ) {
				$posting['dom_document']['success'] = true;
			} else {
				$posting['dom_document']['success'] = false;
				$posting['dom_document']['note']    = sprintf( __( 'Your server does not have the <a href="%s">DOMDocument</a> class enabled - HTML/Multipart emails, and also some extensions, will not work without DOMDocument.', 'woocommerce' ), 'http://php.net/manual/en/class.domdocument.php' ) . '</mark>';
			}

			// GZIP
			$posting['gzip']['name'] = 'GZip';
			$posting['gzip']['help'] = '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'GZip (gzopen) is used to open the GEOIP database from MaxMind.', 'woocommerce' ) . '">[?]</a>';

			if ( is_callable( 'gzopen' ) ) {
				$posting['gzip']['success'] = true;
			} else {
				$posting['gzip']['success'] = false;
				$posting['gzip']['note']    = sprintf( __( 'Your server does not support the <a href="%s">gzopen</a> function - this is required to use the GeoIP database from MaxMind. The API fallback will be used instead for geolocation.', 'woocommerce' ), 'http://php.net/manual/en/zlib.installation.php' ) . '</mark>';
			}

			// WP Remote Post Check
			$posting['wp_remote_post']['name'] = __( 'Remote Post', 'woocommerce');
			$posting['wp_remote_post']['help'] = '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'PayPal uses this method of communicating when sending back transaction information.', 'woocommerce' ) . '">[?]</a>';

			$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', array(
				'sslverify'  => false,
				'timeout'    => 60,
				'user-agent' => 'WooCommerce/' . WC()->version,
				'body'       => array(
					'cmd'    => '_notify-validate'
				)
			) );

			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$posting['wp_remote_post']['success'] = true;
			} else {
				$posting['wp_remote_post']['note']    = __( 'wp_remote_post() failed. PayPal IPN won\'t work with your server. Contact your hosting provider.', 'woocommerce' );
				if ( is_wp_error( $response ) ) {
					$posting['wp_remote_post']['note'] .= ' ' . sprintf( __( 'Error: %s', 'woocommerce' ), wc_clean( $response->get_error_message() ) );
				} else {
					$posting['wp_remote_post']['note'] .= ' ' . sprintf( __( 'Status code: %s', 'woocommerce' ), wc_clean( $response['response']['code'] ) );
				}
				$posting['wp_remote_post']['success'] = false;
			}

			// WP Remote Get Check
			$posting['wp_remote_get']['name'] = __( 'Remote Get', 'woocommerce');
			$posting['wp_remote_get']['help'] = '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'WooCommerce plugins may use this method of communication when checking for plugin updates.', 'woocommerce' ) . '">[?]</a>';

			$response = wp_remote_get( 'http://www.woothemes.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );

			if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				$posting['wp_remote_get']['success'] = true;
			} else {
				$posting['wp_remote_get']['note']    = __( 'wp_remote_get() failed. The WooCommerce plugin updater won\'t work with your server. Contact your hosting provider.', 'woocommerce' );
				if ( is_wp_error( $response ) ) {
					$posting['wp_remote_get']['note'] .= ' ' . sprintf( __( 'Error: %s', 'woocommerce' ), wc_clean( $response->get_error_message() ) );
				} else {
					$posting['wp_remote_get']['note'] .= ' ' . sprintf( __( 'Status code: %s', 'woocommerce' ), wc_clean( $response['response']['code'] ) );
				}
				$posting['wp_remote_get']['success'] = false;
			}

			$posting = apply_filters( 'woocommerce_debug_posting', $posting );

			foreach ( $posting as $post ) {
				$mark = ! empty( $post['success'] ) ? 'yes' : 'error';
				?>
				<tr>
					<td data-export-label="<?php echo esc_html( $post['name'] ); ?>"><?php echo esc_html( $post['name'] ); ?>:</td>
					<td class="help"><?php echo isset( $post['help'] ) ? $post['help'] : ''; ?></td>
					<td>
						<mark class="<?php echo $mark; ?>">
							<?php echo ! empty( $post['success'] ) ? '&#10004' : '&#10005'; ?>
							<?php echo ! empty( $post['note'] ) ? wp_kses_data( $post['note'] ) : ''; ?>
						</mark>
					</td>
				</tr>
				<?php
			}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Server Locale"><?php _e( 'Server Locale', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$locale = localeconv();
			$locale_help = array(
				'decimal_point'     => __( 'The character used for decimal points.', 'woocommerce' ),
				'thousands_sep'     => __( 'The character used for a thousands separator.', 'woocommerce' ),
				'mon_decimal_point' => __( 'The character used for decimal points in monetary values.', 'woocommerce' ),
				'mon_thousands_sep' => __( 'The character used for a thousands separator in monetary values.', 'woocommerce' ),
			);

			foreach ( $locale as $key => $val ) {
				if ( in_array( $key, array( 'decimal_point', 'mon_decimal_point', 'thousands_sep', 'mon_thousands_sep' ) ) ) {
					echo '<tr><td data-export-label="' . $key . '">' . $key . ':</td><td class="help"><a href="#" class="help_tip" data-tip="' . esc_attr( $locale_help[$key]  ) . '">[?]</a></td><td>' . ( $val ? $val : __( 'N/A', 'woocommerce' ) ) . '</td></tr>';
				}
			}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Active Plugins (<?php echo count( (array) get_option( 'active_plugins' ) ); ?>)"><?php _e( 'Active Plugins', 'woocommerce' ); ?> (<?php echo count( (array) get_option( 'active_plugins' ) ); ?>)</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		foreach ( $active_plugins as $plugin ) {

			$plugin_data    = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$dirname        = dirname( $plugin );
			$version_string = '';
			$network_string = '';

			if ( ! empty( $plugin_data['Name'] ) ) {

				// link the plugin name to the plugin url if available
				$plugin_name = esc_html( $plugin_data['Name'] );

				if ( ! empty( $plugin_data['PluginURI'] ) ) {
					$plugin_name = '<a href="' . esc_url( $plugin_data['PluginURI'] ) . '" title="' . __( 'Visit plugin homepage' , 'woocommerce' ) . '" target="_blank">' . $plugin_name . '</a>';
				}

				if ( strstr( $dirname, 'woocommerce-' ) ) {

					if ( false === ( $version_data = get_transient( md5( $plugin ) . '_version_data' ) ) ) {
						$changelog = wp_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $dirname . '/changelog.txt' );
						$cl_lines  = explode( "\n", wp_remote_retrieve_body( $changelog ) );
						if ( ! empty( $cl_lines ) ) {
							foreach ( $cl_lines as $line_num => $cl_line ) {
								if ( preg_match( '/^[0-9]/', $cl_line ) ) {

									$date         = str_replace( '.' , '-' , trim( substr( $cl_line , 0 , strpos( $cl_line , '-' ) ) ) );
									$version      = preg_replace( '~[^0-9,.]~' , '' ,stristr( $cl_line , "version" ) );
									$update       = trim( str_replace( "*" , "" , $cl_lines[ $line_num + 1 ] ) );
									$version_data = array( 'date' => $date , 'version' => $version , 'update' => $update , 'changelog' => $changelog );
									set_transient( md5( $plugin ) . '_version_data', $version_data, DAY_IN_SECONDS );
									break;
								}
							}
						}
					}

					if ( ! empty( $version_data['version'] ) && version_compare( $version_data['version'], $plugin_data['Version'], '>' ) ) {
						$version_string = ' &ndash; <strong style="color:red;">' . esc_html( sprintf( _x( '%s is available', 'Version info', 'woocommerce' ), $version_data['version'] ) ) . '</strong>';
					}

					if ( $plugin_data['Network'] != false ) {
						$network_string = ' &ndash; <strong style="color:black;">' . __( 'Network enabled', 'woocommerce' ) . '</strong>';
					}
				}

				?>
				<tr>
					<td><?php echo $plugin_name; ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo sprintf( _x( 'by %s', 'by author', 'woocommerce' ), $plugin_data['Author'] ) . ' &ndash; ' . esc_html( $plugin_data['Version'] ) . $version_string . $network_string; ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Settings"><?php _e( 'Settings', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="API Enabled"><?php _e( 'API Enabled', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Does your site have REST API enabled?', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo 'yes' === get_option( 'woocommerce_api_enabled' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Taxes Enabled"><?php _e( 'Taxes Enabled', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Does your site have taxes enabled?', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo wc_tax_enabled() ? '<mark class="yes">' . '&#10004;' . '</mark>' : '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Shipping Enabled"><?php _e( 'Shipping Enabled', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Does your site have shipping enabled?', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo 'yes' === get_option( 'woocommerce_calc_shipping' ) ? '<mark class="yes">' . '&#10004;' . '</mark>' : '<mark class="no">' . '&ndash;' . '</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Force SSL"><?php _e( 'Force SSL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Does your site force a SSL Certificate for transactions?', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ? '<mark class="yes">'.'&#10004;'.'</mark>' : '<mark class="no">'.'&ndash;'.'</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Currency"><?php _e( 'Currency', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'What currency prices are listed at in the catalog and which currency gateways will take payments in.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo get_woocommerce_currency(); ?> (<?php echo get_woocommerce_currency_symbol() ?>)</td>
		</tr>
		<tr>
			<td data-export-label="Currency Position"><?php _e( 'Currency Position', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The position of the currency symbol.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo get_option( 'woocommerce_currency_pos' ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Thousand Separator"><?php _e( 'Thousand Separator', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The thousand separator of displayed prices.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo wc_get_price_thousand_separator(); ?></td>
		</tr>
		<tr>
			<td data-export-label="Decimal Separator"><?php _e( 'Decimal Separator', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The decimal separator of displayed prices.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo wc_get_price_decimal_separator(); ?></td>
		</tr>
		<tr>
			<td data-export-label="Number of Decimals"><?php _e( 'Number of Decimals', 'woocommerce' ) ?></td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The number of decimal points shown in displayed prices.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo wc_get_price_decimals(); ?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WC Pages"><?php _e( 'WC Pages', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$check_pages = array(
				_x( 'Shop Base', 'Page setting', 'woocommerce' ) => array(
						'option'    => 'woocommerce_shop_page_id',
						'shortcode' => '',
						'help'      => __( 'The URL of your WooCommerce shop\'s homepage (along with the Page ID).', 'woocommerce' ),
					),
				_x( 'Cart', 'Page setting', 'woocommerce' ) => array(
						'option'    => 'woocommerce_cart_page_id',
						'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']',
						'help'      => __( 'The URL of your WooCommerce shop\'s cart (along with the page ID).', 'woocommerce' ),
					),
				_x( 'Checkout', 'Page setting', 'woocommerce' ) => array(
						'option'    => 'woocommerce_checkout_page_id',
						'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']',
						'help'      => __( 'The URL of your WooCommerce shop\'s checkout (along with the page ID).', 'woocommerce' ),
					),
				_x( 'My Account', 'Page setting', 'woocommerce' ) => array(
						'option'    => 'woocommerce_myaccount_page_id',
						'shortcode' => '[' . apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) . ']',
						'help'      => __( 'The URL of your WooCommerce shop\'s “My Account” Page (along with the page ID).', 'woocommerce' ),
					)
			);

			$alt = 1;

			foreach ( $check_pages as $page_name => $values ) {
				$error   = false;
				$page_id = get_option( $values['option'] );

				if ( $page_id ) {
					$page_name = '<a href="' . get_edit_post_link( $page_id ) . '" title="' . sprintf( _x( 'Edit %s page', 'WC Pages links in the System Status', 'woocommerce' ), esc_html( $page_name ) ) . '">' . esc_html( $page_name ) . '</a>';
				} else {
					$page_name = esc_html( $page_name );
				}

				echo '<tr><td data-export-label="' . esc_attr( $page_name ) . '">' . $page_name . ':</td>';
				echo '<td class="help"><a href="#" class="help_tip" data-tip="' . esc_attr( $values['help']  ) . '">[?]</a></td><td>';

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
			}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Taxonomies"><?php _e( 'Taxonomies', 'woocommerce' ); ?><?php echo ' <a href="#" class="help_tip" data-tip="' . esc_attr__( 'A list of taxonomy terms that can be used in regard to order/product statuses.', 'woocommerce' ) . '">[?]</a>'; ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Product Types"><?php _e( 'Product Types', 'woocommerce' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php
				$display_terms = array();
				$terms = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
				foreach ( $terms as $term ) {
					$display_terms[] = strtolower( $term->name ) . ' (' . $term->slug . ')';
				}
				echo implode( ', ', array_map( 'esc_html', $display_terms ) );
			?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Theme"><?php _e( 'Theme', 'woocommerce' ); ?></th>
		</tr>
	</thead>
		<?php
		$active_theme = wp_get_theme();
		if ( $active_theme->{'Author URI'} == 'http://www.woothemes.com' ) {

			$theme_dir = substr( strtolower( str_replace( ' ','', $active_theme->Name ) ), 0, 45 );

			if ( false === ( $theme_version_data = get_transient( $theme_dir . '_version_data' ) ) ) {

				$theme_changelog = wp_remote_get( 'http://dzv365zjfbd8v.cloudfront.net/changelogs/' . $theme_dir . '/changelog.txt' );
				$cl_lines  = explode( "\n", wp_remote_retrieve_body( $theme_changelog ) );
				if ( ! empty( $cl_lines ) ) {

					foreach ( $cl_lines as $line_num => $cl_line ) {
						if ( preg_match( '/^[0-9]/', $cl_line ) ) {

							$theme_date         = str_replace( '.' , '-' , trim( substr( $cl_line , 0 , strpos( $cl_line , '-' ) ) ) );
							$theme_version      = preg_replace( '~[^0-9,.]~' , '' ,stristr( $cl_line , "version" ) );
							$theme_update       = trim( str_replace( "*" , "" , $cl_lines[ $line_num + 1 ] ) );
							$theme_version_data = array( 'date' => $theme_date , 'version' => $theme_version , 'update' => $theme_update , 'changelog' => $theme_changelog );
							set_transient( $theme_dir . '_version_data', $theme_version_data , DAY_IN_SECONDS );
							break;
						}
					}
				}
			}
		}
		?>
	<tbody>
		<tr>
			<td data-export-label="Name"><?php _e( 'Name', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The name of the current active theme.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo $active_theme->Name; ?></td>
		</tr>
		<tr>
			<td data-export-label="Version"><?php _e( 'Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The installed version of the current active theme.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				echo $active_theme->Version;

				if ( ! empty( $theme_version_data['version'] ) && version_compare( $theme_version_data['version'], $active_theme->Version, '!=' ) ) {
					echo ' &ndash; <strong style="color:red;">' . $theme_version_data['version'] . ' ' . __( 'is available', 'woocommerce' ) . '</strong>';
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="Author URL"><?php _e( 'Author URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The theme developers URL.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo $active_theme->{'Author URI'}; ?></td>
		</tr>
		<tr>
			<td data-export-label="Child Theme"><?php _e( 'Child Theme', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Displays whether or not the current theme is a child theme.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				echo is_child_theme() ? '<mark class="yes">' . '&#10004;' . '</mark>' : '&#10005; &ndash; ' . sprintf( __( 'If you\'re modifying WooCommerce or a parent theme you didn\'t build personally we recommend using a child theme. See: <a href="%s" target="_blank">How to create a child theme</a>', 'woocommerce' ), 'http://codex.wordpress.org/Child_Themes' );
			?></td>
		</tr>
		<?php
		if( is_child_theme() ) :
			$parent_theme = wp_get_theme( $active_theme->Template );
		?>
		<tr>
			<td data-export-label="Parent Theme Name"><?php _e( 'Parent Theme Name', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The name of the parent theme.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo $parent_theme->Name; ?></td>
		</tr>
		<tr>
			<td data-export-label="Parent Theme Version"><?php _e( 'Parent Theme Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The installed version of the parent theme.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo  $parent_theme->Version; ?></td>
		</tr>
		<tr>
			<td data-export-label="Parent Theme Author URL"><?php _e( 'Parent Theme Author URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'The parent theme developers URL.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php echo $parent_theme->{'Author URI'}; ?></td>
		</tr>
		<?php endif ?>
		<tr>
			<td data-export-label="WooCommerce Support"><?php _e( 'WooCommerce Support', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo '<a href="#" class="help_tip" data-tip="' . esc_attr__( 'Displays whether or not the current active theme declares WooCommerce support.', 'woocommerce' ) . '">[?]</a>'; ?></td>
			<td><?php
				if ( ! current_theme_supports( 'woocommerce' ) && ! in_array( $active_theme->template, wc_get_core_supported_themes() ) ) {
					echo '<mark class="error">' . __( 'Not Declared', 'woocommerce' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . '&#10004;' . '</mark>';
				}
			?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Templates"><?php _e( 'Templates', 'woocommerce' ); ?><?php echo ' <a href="#" class="help_tip" data-tip="' . esc_attr__( 'This section shows any files that are overriding the default WooCommerce template pages.', 'woocommerce' ) . '">[?]</a>'; ?></th>
		</tr>
	</thead>
	<tbody>
		<?php

			$template_paths     = apply_filters( 'woocommerce_template_overrides_scan_paths', array( 'WooCommerce' => WC()->plugin_path() . '/templates/' ) );
			$scanned_files      = array();
			$found_files        = array();
			$outdated_templates = false;

			foreach ( $template_paths as $plugin_name => $template_path ) {
				$scanned_files[ $plugin_name ] = WC_Admin_Status::scan_template_files( $template_path );
			}

			foreach ( $scanned_files as $plugin_name => $files ) {
				foreach ( $files as $file ) {
					if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/' . $file;
					} elseif ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
						$theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
					} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
						$theme_file = get_template_directory() . '/' . $file;
					} elseif( file_exists( get_template_directory() . '/woocommerce/' . $file ) ) {
						$theme_file = get_template_directory() . '/woocommerce/' . $file;
					} else {
						$theme_file = false;
					}

					if ( $theme_file ) {
						$core_version  = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $file );
						$theme_version = WC_Admin_Status::get_file_version( $theme_file );

						if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
							if ( ! $outdated_templates ) {
								$outdated_templates = true;
							}
							$found_files[ $plugin_name ][] = sprintf( __( '<code>%s</code> version <strong style="color:red">%s</strong> is out of date. The core version is %s', 'woocommerce' ), str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ), $theme_version ? $theme_version : '-', $core_version );
						} else {
							$found_files[ $plugin_name ][] = sprintf( '<code>%s</code>', str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ) );
						}
					}
				}
			}

			if ( $found_files ) {
				foreach ( $found_files as $plugin_name => $found_plugin_files ) {
					?>
					<tr>
						<td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce' ); ?> (<?php echo $plugin_name; ?>):</td>
						<td class="help">&nbsp;</td>
						<td><?php echo implode( ', <br/>', $found_plugin_files ); ?></td>
					</tr>
					<?php
				}
			} else {
				?>
				<tr>
					<td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td>&ndash;</td>
				</tr>
				<?php
			}

			if ( true === $outdated_templates ) {
				?>
				<tr>
					<td>&nbsp;</td>
					<td class="help">&nbsp;</td>
					<td><a href="http://speakinginbytes.com/2014/02/woocommerce-2-1-outdated-templates/" target="_blank"><?php _e( 'Learn how to update outdated templates', 'woocommerce' ) ?></a></td>
				</tr>
				<?php
			}
		?>
	</tbody>
</table>

<?php do_action( 'woocommerce_system_status_report' ); ?>

<script type="text/javascript">

	jQuery( 'a.help_tip' ).click( function() {
		return false;
	});

	jQuery( 'a.debug-report' ).click( function() {

		var report = '';

		jQuery( '#status thead, #status tbody' ).each(function(){

			if ( jQuery( this ).is('thead') ) {

				var label = jQuery( this ).find( 'th:eq(0)' ).data( 'export-label' ) || jQuery( this ).text();
				report = report + "\n### " + jQuery.trim( label ) + " ###\n\n";

			} else {

				jQuery('tr', jQuery( this ) ).each(function(){

					var label       = jQuery( this ).find( 'td:eq(0)' ).data( 'export-label' ) || jQuery( this ).find( 'td:eq(0)' ).text();
					var the_name    = jQuery.trim( label ).replace( /(<([^>]+)>)/ig, '' ); // Remove HTML
					var the_value   = jQuery.trim( jQuery( this ).find( 'td:eq(2)' ).text() );
					var value_array = the_value.split( ', ' );

					if ( value_array.length > 1 ) {

						// If value have a list of plugins ','
						// Split to add new line
						var output = '';
						var temp_line ='';
						jQuery.each( value_array, function( key, line ){
							temp_line = temp_line + line + '\n';
						});

						the_value = temp_line;
					}

					report = report + '' + the_name + ': ' + the_value + "\n";
				});

			}
		});

		try {
			jQuery( "#debug-report" ).slideDown();
			jQuery( "#debug-report textarea" ).val( report ).focus().select();
			jQuery( this ).fadeOut();
			return false;
		} catch( e ){
			console.log( e );
		}

		return false;
	});

	jQuery( document ).ready( function ( $ ) {
		$( '#copy-for-support' ).tipTip({
			'attribute':  'data-tip',
			'activation': 'click',
			'fadeIn':     50,
			'fadeOut':    50,
			'delay':      0
		});

		$( 'body' ).on( 'copy', '#copy-for-support', function ( e ) {
			e.clipboardData.clearData();
			e.clipboardData.setData( 'text/plain', $( '#debug-report textarea' ).val() );
			e.preventDefault();
		});

	});

</script>
