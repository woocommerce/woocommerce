<?php
/**
 * Admin View: Page - Status Report.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$system_status  = new WC_REST_System_Status_Controller;
$environment    = $system_status->get_environment_info();
$database       = $system_status->get_database_info();
$active_plugins = $system_status->get_active_plugins();
$theme          = $system_status->get_theme_info();
$security       = $system_status->get_security_info();
$settings       = $system_status->get_settings();
$pages          = $system_status->get_pages();
?>
<div class="updated woocommerce-message inline">
	<p><?php _e( 'Please copy and paste this information in your ticket when contacting support:', 'woocommerce' ); ?> </p>
	<p class="submit"><a href="#" class="button-primary debug-report"><?php _e( 'Get system report', 'woocommerce' ); ?></a>
	<a class="button-secondary docs" href="https://docs.woocommerce.com/document/understanding-the-woocommerce-system-status-report/" target="_blank"><?php _e( 'Understanding the status report', 'woocommerce' ); ?></a></p>
	<div id="debug-report">
		<textarea readonly="readonly"></textarea>
		<p class="submit"><button id="copy-for-support" class="button-primary" href="#" data-tip="<?php esc_attr_e( 'Copied!', 'woocommerce' ); ?>"><?php _e( 'Copy for support', 'woocommerce' ); ?></button></p>
		<p class="copy-error hidden"><?php _e( 'Copying to clipboard failed. Please press Ctrl/Cmd+C to copy.', 'woocommerce' ); ?></p>
	</div>
</div>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WordPress Environment"><h2><?php _e( 'WordPress environment', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Home URL"><?php _e( 'Home URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The URL of your site\'s homepage.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $environment['home_url'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Site URL"><?php _e( 'Site URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The root URL of your site.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $environment['site_url'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Version"><?php _e( 'WC version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The version of WooCommerce installed on your site.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $environment['version'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Log Directory Writable"><?php _e( 'Log directory writable', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Several WooCommerce extensions can write logs which makes debugging problems easier. The directory must be writable for this to happen.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['log_directory_writable'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> <code class="private">' . esc_html( $environment['log_directory'] ) . '</code></mark> ';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'To allow logging, make %1$s writable or define a custom %2$s.', 'woocommerce' ), '<code>' . $environment['log_directory'] . '</code>', '<code>WC_LOG_DIR</code>' ) . '</mark>';
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="WP Version"><?php _e( 'WP version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The version of WordPress installed on your site.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $environment['wp_version'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Multisite"><?php _e( 'WP multisite', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Whether or not you have WordPress Multisite enabled.', 'woocommerce' ) ); ?></td>
			<td><?php echo ( $environment['wp_multisite'] ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Memory Limit"><?php _e( 'WP memory limit', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The maximum amount of memory (RAM) that your site can use at one time.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['wp_memory_limit'] < 67108864 ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%1$s - We recommend setting memory to at least 64MB. See: %2$s', 'woocommerce' ), size_format( $environment['wp_memory_limit'] ), '<a href="https://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP" target="_blank">' . __( 'Increasing memory allocated to PHP', 'woocommerce' ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . size_format( $environment['wp_memory_limit'] ) . '</mark>';
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="WP Debug Mode"><?php _e( 'WP debug mode', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether or not WordPress is in Debug Mode.', 'woocommerce' ) ); ?></td>
			<td>
				<?php if ( $environment['wp_debug_mode'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Cron"><?php _e( 'WP cron', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether or not WP Cron Jobs are enabled.', 'woocommerce' ) ); ?></td>
			<td>
				<?php if ( $environment['wp_cron'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Language"><?php _e( 'Language', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The current language used by WordPress. Default = English', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $environment['language'] ) ?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Server Environment"><h2><?php _e( 'Server environment', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Server Info"><?php _e( 'Server info', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Information about the web server that is currently hosting your site.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $environment['server_info'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="PHP Version"><?php _e( 'PHP version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The version of PHP installed on your hosting server.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( version_compare( $environment['php_version'], '5.6', '<' ) ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%1$s - We recommend a minimum PHP version of 5.6. See: %2$s', 'woocommerce' ), esc_html( $environment['php_version'] ), '<a href="https://docs.woocommerce.com/document/how-to-update-your-php-version/" target="_blank">' . __( 'How to update your PHP version', 'woocommerce' ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $environment['php_version'] ) . '</mark>';
				}
				?></td>
		</tr>
		<?php if ( function_exists( 'ini_get' ) ) : ?>
			<tr>
				<td data-export-label="PHP Post Max Size"><?php _e( 'PHP post max size', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'The largest filesize that can be contained in one post.', 'woocommerce' ) ); ?></td>
				<td><?php echo esc_html( size_format( $environment['php_post_max_size'] ) ) ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Time Limit"><?php _e( 'PHP time limit', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', 'woocommerce' ) ); ?></td>
				<td><?php echo esc_html( $environment['php_max_execution_time'] ) ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Max Input Vars"><?php _e( 'PHP max input vars', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'woocommerce' ) ); ?></td>
				<td><?php echo esc_html( $environment['php_max_input_vars'] ) ?></td>
			</tr>
			<tr>
				<td data-export-label="cURL Version"><?php _e( 'cURL version', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'The version of cURL installed on your server.', 'woocommerce' ) ); ?></td>
				<td><?php echo esc_html( $environment['curl_version'] ) ?></td>
			</tr>
			<tr>
				<td data-export-label="SUHOSIN Installed"><?php _e( 'SUHOSIN installed', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'Suhosin is an advanced protection system for PHP installations. It was designed to protect your servers on the one hand against a number of well known problems in PHP applications and on the other hand against potential unknown vulnerabilities within these applications or the PHP core itself. If enabled on your server, Suhosin may need to be configured to increase its data submission limits.', 'woocommerce' ) ); ?></td>
				<td><?php echo $environment['suhosin_installed'] ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
			</tr>
		<?php endif;
		if ( $wpdb->use_mysqli ) {
			$ver = mysqli_get_server_info( $wpdb->dbh );
		} else {
			$ver = mysql_get_server_info();
		}
		if ( ! empty( $wpdb->is_mysql ) && ! stristr( $ver, 'MariaDB' ) ) : ?>
			<tr>
				<td data-export-label="MySQL Version"><?php _e( 'MySQL version', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'The version of MySQL installed on your hosting server.', 'woocommerce' ) ); ?></td>
				<td>
					<?php
					if ( version_compare( $environment['mysql_version'], '5.6', '<' ) ) {
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%1$s - We recommend a minimum MySQL version of 5.6. See: %2$s', 'woocommerce' ), esc_html( $environment['mysql_version'] ), '<a href="https://wordpress.org/about/requirements/" target="_blank">' . __( 'WordPress requirements', 'woocommerce' ) . '</a>' ) . '</mark>';
					} else {
						echo '<mark class="yes">' . esc_html( $environment['mysql_version'] ) . '</mark>';
					}
					?>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<td data-export-label="Max Upload Size"><?php _e( 'Max upload size', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The largest filesize that can be uploaded to your WordPress installation.', 'woocommerce' ) ); ?></td>
			<td><?php echo size_format( $environment['max_upload_size'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Default Timezone is UTC"><?php _e( 'Default timezone is UTC', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The default timezone for your server.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( 'UTC' !== $environment['default_timezone'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Default timezone is %s - it should be UTC', 'woocommerce' ), $environment['default_timezone'] ) . '</mark>';
				} else {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="fsockopen/cURL"><?php _e( 'fsockopen/cURL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Payment gateways can use cURL to communicate with remote servers to authorize payments, other plugins may also use it when communicating with remote services.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['fsockopen_or_curl_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce' ) . '</mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="SoapClient"><?php _e( 'SoapClient', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Some webservices like shipping use SOAP to get information from remote servers, for example, live shipping quotes from FedEx require SOAP to be installed.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['soapclient_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Your server does not have the %s class enabled - some gateway plugins which use SOAP may not work as expected.', 'woocommerce' ), '<a href="https://php.net/manual/en/class.soapclient.php">SoapClient</a>' ) . '</mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="DOMDocument"><?php _e( 'DOMDocument', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'HTML/Multipart emails use DOMDocument to generate inline CSS in templates.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['domdocument_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Your server does not have the %s class enabled - HTML/Multipart emails, and also some extensions, will not work without DOMDocument.', 'woocommerce' ), '<a href="https://php.net/manual/en/class.domdocument.php">DOMDocument</a>' ) . '</mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="GZip"><?php _e( 'GZip', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'GZip (gzopen) is used to open the GEOIP database from MaxMind.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['gzip_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Your server does not support the %s function - this is required to use the GeoIP database from MaxMind.', 'woocommerce' ), '<a href="https://php.net/manual/en/zlib.installation.php">gzopen</a>' ) . '</mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Multibyte String"><?php _e( 'Multibyte string', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Multibyte String (mbstring) is used to convert character encoding, like for emails or converting characters to lowercase.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['mbstring_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Your server does not support the %s functions - this is required for better character encoding. Some fallbacks will be used instead for it.', 'woocommerce' ), '<a href="https://php.net/manual/en/mbstring.installation.php">mbstring</a>' ) . '</mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Remote Post"><?php _e( 'Remote post', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'PayPal uses this method of communicating when sending back transaction information.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['remote_post_successful'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'wp_remote_post() failed. Contact your hosting provider.', 'woocommerce' ) . ' ' . esc_html( $environment['remote_post_response'] ) . '</mark>';
				} ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Remote Get"><?php _e( 'Remote get', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'WooCommerce plugins may use this method of communication when checking for plugin updates.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( $environment['remote_get_successful'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'wp_remote_get() failed. Contact your hosting provider.', 'woocommerce' ) . ' ' . esc_html( $environment['remote_get_response'] ) . '</mark>';
				} ?>
			</td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Database"><h2><?php _e( 'Database', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="WC Database Version"><?php _e( 'WC database version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The version of WooCommerce that the database is formatted for. This should be the same as your WooCommerce version.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $database['wc_database_version'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Database Prefix"><?php _e( 'Database prefix', 'woocommerce' ); ?></td>
			<td class="help">&nbsp;</td>
			<td><?php
				if ( strlen( $database['database_prefix'] ) > 20 ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( '%1$s - We recommend using a prefix with less than 20 characters. See: %2$s', 'woocommerce' ), esc_html( $database['database_prefix'] ), '<a href="https://docs.woocommerce.com/document/completed-order-email-doesnt-contain-download-links/#section-2" target="_blank">' . __( 'How to update your database table prefix', 'woocommerce' ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $database['database_prefix'] ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<?php
		foreach ( $database['database_tables'] as $table => $table_exists ) {
			?>
			<tr>
				<td><?php echo esc_html( $table ); ?></td>
				<td class="help">&nbsp;</td>
				<td><?php echo ! $table_exists ? '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'Table does not exist', 'woocommerce' ) . '</mark>' : '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>'; ?></td>
			</tr>
			<?php
		}

		if ( $settings['geolocation_enabled'] ) {
			?>
			<tr>
				<td data-export-label="MaxMind GeoIP Database"><?php _e( 'MaxMind GeoIP database', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( __( 'The GeoIP database from MaxMind is used to geolocate customers.', 'woocommerce' ) ); ?></td>
				<td><?php
					if ( file_exists( $database['maxmind_geoip_database'] ) ) {
						echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> <code class="private">' . esc_html( $database['maxmind_geoip_database'] ) . '</code></mark> ';
					} else {
						printf( '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'The MaxMind GeoIP Database does not exist - Geolocation will not function. You can download and install it manually from %1$s to the path: %2$s. Scroll down to "Downloads" and download the "Binary / gzip" file next to "GeoLite Country". Please remember to uncompress GeoIP.dat.gz and upload the GeoIP.dat file only.', 'woocommerce' ), make_clickable( 'http://dev.maxmind.com/geoip/legacy/geolite/' ), '<code class="private">' . $database['maxmind_geoip_database'] . '</code>' ) . '</mark>', WC_LOG_DIR );
					}
				?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Security"><h2><?php _e( 'Security', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Secure connection (HTTPS)"><?php _e( 'Secure connection (HTTPS)', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Is the connection to your store secure?', 'woocommerce' ) ); ?></td>
			<td>
				<?php if ( $security['secure_connection'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="error"><span class="dashicons dashicons-warning"></span><?php printf( __( 'Your store is not using HTTPS. <a href="%s" target="_blank">Learn more about HTTPS and SSL Certificates</a>.', 'woocommerce' ), 'https://docs.woocommerce.com/document/ssl-and-https/' ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Hide errors from visitors"><?php _e( 'Hide errors from visitors', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( __( 'Error messages can contain sensitive information about your store environment. These should be hidden from untrusted visitors.', 'woocommerce' ) ); ?></td>
			<td>
				<?php if ( $security['hide_errors'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="error"><span class="dashicons dashicons-warning"></span><?php _e( 'Error messages should not be shown to visitors.', 'woocommerce' ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Active Plugins (<?php echo count( $active_plugins ) ?>)"><h2><?php _e( 'Active plugins', 'woocommerce' ); ?> (<?php echo count( $active_plugins ) ?>)</h2></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $active_plugins as $plugin ) {
			if ( ! empty( $plugin['name'] ) ) {
				$dirname = dirname( $plugin['plugin'] );

				// Link the plugin name to the plugin url if available.
				$plugin_name = esc_html( $plugin['name'] );
				if ( ! empty( $plugin['url'] ) ) {
					$plugin_name = '<a href="' . esc_url( $plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage' , 'woocommerce' ) . '" target="_blank">' . $plugin_name . '</a>';
				}

				$version_string = '';
				$network_string = '';
				if ( strstr( $plugin['url'], 'woothemes.com' ) || strstr( $plugin['url'], 'woocommerce.com' ) ) {
					if ( ! empty( $plugin['version_latest'] ) && version_compare( $plugin['version_latest'], $plugin['version'], '>' ) ) {
						/* translators: %s: plugin latest version */
						$version_string = ' &ndash; <strong style="color:red;">' . sprintf( esc_html__( '%s is available', 'woocommerce' ), $plugin['version_latest'] ) . '</strong>';
					}

					if ( false != $plugin['network_activated'] ) {
						$network_string = ' &ndash; <strong style="color:black;">' . __( 'Network enabled', 'woocommerce' ) . '</strong>';
					}
				}
				?>
				<tr>
					<td><?php echo $plugin_name; ?></td>
					<td class="help">&nbsp;</td>
					<td><?php
						/* translators: %s: plugin author */
						printf( __( 'by %s', 'woocommerce' ), $plugin['author_name'] );
						echo ' &ndash; ' . esc_html( $plugin['version'] ) . $version_string . $network_string;
					?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Settings"><h2><?php _e( 'Settings', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="API Enabled"><?php _e( 'API enabled', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Does your site have REST API enabled?', 'woocommerce' ) ); ?></td>
			<td><?php echo $settings['api_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Force SSL"><?php _e( 'Force SSL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Does your site force a SSL Certificate for transactions?', 'woocommerce' ) ); ?></td>
			<td><?php echo $settings['force_ssl'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Currency"><?php _e( 'Currency', 'woocommerce' ) ?></td>
			<td class="help"><?php echo wc_help_tip( __( 'What currency prices are listed at in the catalog and which currency gateways will take payments in.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $settings['currency'] ) ?> (<?php echo esc_html( $settings['currency_symbol'] ) ?>)</td>
		</tr>
		<tr>
			<td data-export-label="Currency Position"><?php _e( 'Currency position', 'woocommerce' ) ?></td>
			<td class="help"><?php echo wc_help_tip( __( 'The position of the currency symbol.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $settings['currency_position'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Thousand Separator"><?php _e( 'Thousand separator', 'woocommerce' ) ?></td>
			<td class="help"><?php echo wc_help_tip( __( 'The thousand separator of displayed prices.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $settings['thousand_separator'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Decimal Separator"><?php _e( 'Decimal separator', 'woocommerce' ) ?></td>
			<td class="help"><?php echo wc_help_tip( __( 'The decimal separator of displayed prices.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $settings['decimal_separator'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Number of Decimals"><?php _e( 'Number of decimals', 'woocommerce' ) ?></td>
			<td class="help"><?php echo wc_help_tip( __( 'The number of decimal points shown in displayed prices.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $settings['number_of_decimals'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Taxonomies: Product Types"><?php _e( 'Taxonomies: Product types', 'woocommerce' ); ?></th>
			<td class="help"><?php echo wc_help_tip( __( 'A list of taxonomy terms that can be used in regard to order/product statuses.', 'woocommerce' ) ); ?></td>
			<td><?php
				$display_terms = array();
				foreach ( $settings['taxonomies'] as $slug => $name ) {
					$display_terms[] = strtolower( $name ) . ' (' . $slug . ')';
				}
				echo implode( ', ', array_map( 'esc_html', $display_terms ) );
			?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WC Pages"><h2><?php _e( 'WC pages', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$alt = 1;
			foreach ( $pages as $page ) {
				$error   = false;

				if ( $page['page_id'] ) {
					$page_name = '<a href="' . get_edit_post_link( $page['page_id'] ) . '" aria-label="' . sprintf( __( 'Edit %s page', 'woocommerce' ), esc_html( $page['page_name'] ) ) . '">' . esc_html( $page['page_name'] ) . '</a>';
				} else {
					$page_name = esc_html( $page['page_name'] );
				}

				echo '<tr><td data-export-label="' . esc_attr( $page_name ) . '">' . $page_name . ':</td>';
				echo '<td class="help">' . wc_help_tip( sprintf( __( 'The URL of your WooCommerce shop\'s %s (along with the Page ID).', 'woocommerce' ), $page_name ) ) . '</td><td>';

				// Page ID check.
				if ( ! $page['page_set'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'Page not set', 'woocommerce' ) . '</mark>';
					$error = true;
				} elseif ( ! $page['page_exists'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'Page ID is set, but the page does not exist', 'woocommerce' ) . '</mark>';
					$error = true;
				} elseif ( ! $page['page_visible'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Page visibility should be <a href="%s" target="_blank">public</a>', 'woocommerce' ), 'https://codex.wordpress.org/Content_Visibility' ) . '</mark>';
					$error = true;
				} else {
					// Shortcode check
					if ( $page['shortcode_required'] ) {
						if ( ! $page['shortcode_present'] ) {
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( __( 'Page does not contain the shortcode.', 'woocommerce' ), $page['shortcode'] ) . '</mark>';
							$error = true;
						}
					}
				}

				if ( ! $error ) echo '<mark class="yes">#' . absint( $page['page_id'] ) . ' - ' . str_replace( home_url(), '', get_permalink( $page['page_id'] ) ) . '</mark>';

				echo '</td></tr>';
			}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Theme"><h2><?php _e( 'Theme', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Name"><?php _e( 'Name', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The name of the current active theme.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $theme['name'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Version"><?php _e( 'Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The installed version of the current active theme.', 'woocommerce' ) ); ?></td>
			<td><?php
				echo esc_html( $theme['version'] );
				if ( version_compare( $theme['version'], $theme['version_latest'], '<' ) ) {
					/* translators: %s: theme latest version */
					echo ' &ndash; <strong style="color:red;">' . sprintf( __( '%s is available', 'woocommerce' ), esc_html( $theme['version_latest'] ) ) . '</strong>';
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="Author URL"><?php _e( 'Author URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The theme developers URL.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $theme['author_url'] ) ?></td>
		</tr>
		<tr>
			<td data-export-label="Child Theme"><?php _e( 'Child theme', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether or not the current theme is a child theme.', 'woocommerce' ) ); ?></td>
			<td><?php
				echo $theme['is_child_theme'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<span class="dashicons dashicons-no-alt"></span> &ndash; ' . sprintf( __( 'If you\'re modifying WooCommerce on a parent theme you didn\'t build personally, then we recommend using a child theme. See: <a href="%s" target="_blank">How to create a child theme</a>', 'woocommerce' ), 'https://codex.wordpress.org/Child_Themes' );
			?></td>
		</tr>
		<?php
		if ( $theme['is_child_theme'] ) :
		?>
		<tr>
			<td data-export-label="Parent Theme Name"><?php _e( 'Parent theme name', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The name of the parent theme.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $theme['parent_name'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Parent Theme Version"><?php _e( 'Parent theme version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The installed version of the parent theme.', 'woocommerce' ) ); ?></td>
			<td><?php
				echo esc_html( $theme['parent_version'] );
				if ( version_compare( $theme['parent_version'], $theme['parent_version_latest'], '<' ) ) {
					/* translators: %s: parant theme latest version */
					echo ' &ndash; <strong style="color:red;">' . sprintf( __( '%s is available', 'woocommerce' ), esc_html( $theme['parent_version_latest'] ) ) . '</strong>';
				}
			?></td>
		</tr>
		<tr>
			<td data-export-label="Parent Theme Author URL"><?php _e( 'Parent theme author URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'The parent theme developers URL.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $theme['parent_author_url'] ) ?></td>
		</tr>
		<?php endif ?>
		<tr>
			<td data-export-label="WooCommerce Support"><?php _e( 'WooCommerce support', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( __( 'Displays whether or not the current active theme declares WooCommerce support.', 'woocommerce' ) ); ?></td>
			<td><?php
				if ( ! $theme['has_woocommerce_support'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . __( 'Not declared', 'woocommerce' ) . '</mark>';
				} else {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				}
			?></td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Templates"><h2><?php _e( 'Templates', 'woocommerce' ); ?><?php echo wc_help_tip( __( 'This section shows any files that are overriding the default WooCommerce template pages.', 'woocommerce' ) ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( $theme['has_woocommerce_file'] ) : ?>
		<tr>
			<td data-export-label="Archive Template"><?php _e( 'Archive template', 'woocommerce' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php _e( 'Your theme has a woocommerce.php file, you will not be able to override the woocommerce/archive-product.php custom template since woocommerce.php has priority over archive-product.php. This is intended to prevent display issues.', 'woocommerce' ); ?></td>
		</tr>
		<?php endif ?>
		<?php
			if ( ! empty( $theme['overrides'] ) ) { ?>
					<tr>
						<td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce' ); ?></td>
						<td class="help">&nbsp;</td>
						<td>
							<?php
							$total_overrides = count( $theme['overrides'] );
							for ( $i = 0; $i < $total_overrides; $i++ ) {
								$override = $theme['overrides'][ $i ];
								if ( $override['core_version'] && ( empty( $override['version'] ) || version_compare( $override['version'], $override['core_version'], '<' ) ) ) {
									$current_version = $override['version'] ? $override['version'] : '-';
									printf(
										__( '%1$s version %2$s is out of date. The core version is %3$s', 'woocommerce' ),
										'<code>' . $override['file'] . '</code>',
										'<strong style="color:red">' . $current_version . '</strong>',
										$override['core_version']
									);
								} else {
									echo esc_html( $override['file'] );
								}
								if ( ( count( $theme['overrides'] ) - 1 ) !== $i ) {
									echo ', ';
								}
								echo '<br />';
							}
							?>
						</td>
					</tr>
					<?php
			} else {
				?>
				<tr>
					<td data-export-label="Overrides"><?php _e( 'Overrides', 'woocommerce' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td>&ndash;</td>
				</tr>
				<?php
			}

			if ( true === $theme['has_outdated_templates'] ) {
				?>
				<tr>
					<td data-export-label="Outdated Templates"><?php _e( 'Outdated templates', 'woocommerce' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td><mark class="error"><span class="dashicons dashicons-warning"></span></mark><a href="https://docs.woocommerce.com/document/fix-outdated-templates-woocommerce/" target="_blank"><?php _e( 'Learn how to update', 'woocommerce' ) ?></a></td>
				</tr>
				<?php
			}
		?>
	</tbody>
</table>

<?php do_action( 'woocommerce_system_status_report' ); ?>
