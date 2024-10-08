<?php
/**
 * Admin View: Page - Status Report.
 *
 * @package WooCommerce
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Utilities\RestApiUtil;

defined( 'ABSPATH' ) || exit;

global $wpdb;

$report             = wc_get_container()->get( RestApiUtil::class )->get_endpoint_data( '/wc/v3/system_status' );
$environment        = $report['environment'];
$database           = $report['database'];
$post_type_counts   = isset( $report['post_type_counts'] ) ? $report['post_type_counts'] : array();
$active_plugins     = $report['active_plugins'];
$inactive_plugins   = $report['inactive_plugins'];
$dropins_mu_plugins = $report['dropins_mu_plugins'];
$theme              = $report['theme'];
$security           = $report['security'];
$settings           = $report['settings'];
$logging            = $report['logging'];
$wp_pages           = $report['pages'];
$plugin_updates     = new WC_Plugin_Updates();
$untested_plugins   = $plugin_updates->get_untested_plugins( WC()->version, Constants::get_constant( 'WC_SSR_PLUGIN_UPDATE_RELEASE_VERSION_TYPE' ) );

$active_plugins_count   = is_countable( $active_plugins ) ? count( $active_plugins ) : 0;
$inactive_plugins_count = is_countable( $inactive_plugins ) ? count( $inactive_plugins ) : 0;

// Include necessary WordPress file to use get_plugin_data().
if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
// Define the path to the main WooCommerce plugin file using the correct constant.
$plugin_path = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
// Initialize the WooCommerce version variable.
$wc_version = '';
// Check if the plugin file exists before trying to access it.
if ( file_exists( $plugin_path ) ) {
	$plugin_data = get_plugin_data( $plugin_path );
	$wc_version  = $plugin_data['Version'] ?? ''; // Use null coalescing operator to handle undefined index.
}

?>
<div class="updated woocommerce-message inline">
	<p>
		<?php esc_html_e( 'Please copy and paste this information in your ticket when contacting support:', 'woocommerce' ); ?>
	</p>
	<p class="submit">
		<a href="#" class="button-primary debug-report"><?php esc_html_e( 'Get system report', 'woocommerce' ); ?></a>
		<a class="button-secondary docs" href="https://woocommerce.com/document/understanding-the-woocommerce-system-status-report/" target="_blank">
			<?php esc_html_e( 'Understanding the status report', 'woocommerce' ); ?>
		</a>
	</p>
	<div id="debug-report">
		<textarea readonly="readonly"></textarea>
		<p class="submit">
			<button id="download-for-support" class="button-primary" href="#">
				<?php esc_html_e( 'Download for support', 'woocommerce' ); ?>
			</button>
			<button id="copy-for-support" class="button" href="#" data-tip="<?php esc_attr_e( 'Copied!', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Copy for support', 'woocommerce' ); ?>
			</button>
			<button id="copy-for-github" class="button" href="#" data-tip="<?php esc_attr_e( 'Copied!', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Copy for GitHub', 'woocommerce' ); ?>
			</button>
		</p>
		<p class="copy-error hidden">
			<?php esc_html_e( 'Copying to clipboard failed. Please press Ctrl/Cmd+C to copy.', 'woocommerce' ); ?>
		</p>
	</div>
</div>
<table class="wc_status_table widefat" cellspacing="0" id="status">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WordPress Environment"><h2><?php esc_html_e( 'WordPress environment', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="WordPress address (URL)"><?php esc_html_e( 'WordPress address (URL)', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The root URL of your site.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $environment['site_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Site address (URL)"><?php esc_html_e( 'Site address (URL)', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The homepage URL of your site.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $environment['home_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Version"><?php esc_html_e( 'WooCommerce version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of WooCommerce installed on your site.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( ! empty( $wc_version ) ? $wc_version : $environment['version'] ); ?></td>

		</tr>
		<tr>
			<td data-export-label="Legacy REST API Package Version"><?php esc_html_e( 'WooCommerce Legacy REST API package', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The WooCommerce Legacy REST API plugin running on this site.', 'woocommerce' ) ); ?></td>
			<td>
				<?php
				if ( WC()->legacy_rest_api_is_available() ) {
					$plugin_path = wc_get_container()->get( \Automattic\WooCommerce\Utilities\PluginUtil::class )->get_wp_plugin_id( 'woocommerce-legacy-rest-api' );
					$version     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path )['Version'] ?? '';
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $version ) . ' <code class="private">' . esc_html( wc()->api->get_rest_api_package_path() ) . '</code></mark> ';
				} else {
					echo '<mark class="info-icon"><span class="dashicons dashicons-info"></span> ' . esc_html__( 'The Legacy REST API plugin is not installed on this site.', 'woocommerce' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Action Scheduler Version"><?php esc_html_e( 'Action Scheduler package', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Action Scheduler package running on your site.', 'woocommerce' ) ); ?></td>
			<td>
				<?php
				if ( class_exists( 'ActionScheduler_Versions' ) && class_exists( 'ActionScheduler' ) ) {
					$version = ActionScheduler_Versions::instance()->latest_version();
					$path    = ActionScheduler::plugin_path( '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					$version = null;
				}

				if ( ! is_null( $version ) ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $version ) . ' <code class="private">' . esc_html( $path ) . '</code></mark> ';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the Action Scheduler package.', 'woocommerce' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Log Directory Writable"><?php esc_html_e( 'Log directory writable', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Several WooCommerce extensions can write logs which makes debugging problems easier. The directory must be writable for this to happen.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['log_directory_writable'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> <code class="private">' . esc_html( $environment['log_directory'] ) . '</code></mark> ';
				} else {
					printf(
						'<mark class="error"><span class="dashicons dashicons-warning"></span> %s</mark>',
						sprintf(
							// Translators: %s: Log directory path.
							esc_html__( 'To allow logging, make %s writable.', 'woocommerce' ),
							'<code>' . esc_html( $environment['log_directory'] ) . '</code>'
						)
					);
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Version"><?php esc_html_e( 'WordPress version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of WordPress installed on your site.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				$latest_version = get_transient( 'woocommerce_system_status_wp_version_check' );

				if ( false === $latest_version ) {
					$version_check = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );
					$api_response  = json_decode( wp_remote_retrieve_body( $version_check ), true );

					if ( $api_response && isset( $api_response['offers'], $api_response['offers'][0], $api_response['offers'][0]['version'] ) ) {
						$latest_version = $api_response['offers'][0]['version'];
					} else {
						$latest_version = $environment['wp_version'];
					}
					set_transient( 'woocommerce_system_status_wp_version_check', $latest_version, DAY_IN_SECONDS );
				}

				if ( version_compare( $environment['wp_version'], $latest_version, '<' ) ) {
					/* Translators: %1$s: Current version, %2$s: New version */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - There is a newer version of WordPress available (%2$s)', 'woocommerce' ), esc_html( $environment['wp_version'] ), esc_html( $latest_version ) ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $environment['wp_version'] ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Multisite"><?php esc_html_e( 'WordPress multisite', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Whether or not you have WordPress Multisite enabled.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo ( $environment['wp_multisite'] ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Memory Limit"><?php esc_html_e( 'WordPress memory limit', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The maximum amount of memory (RAM) that your site can use at one time.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['wp_memory_limit'] < 67108864 ) {
					/* Translators: %1$s: Memory limit, %2$s: Docs link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend setting memory to at least 64MB. See: %2$s', 'woocommerce' ), esc_html( size_format( $environment['wp_memory_limit'] ) ), '<a href="https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php" target="_blank">' . esc_html__( 'Increasing memory allocated to PHP', 'woocommerce' ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( size_format( $environment['wp_memory_limit'] ) ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Debug Mode"><?php esc_html_e( 'WordPress debug mode', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays whether or not WordPress is in Debug Mode.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php if ( $environment['wp_debug_mode'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Cron"><?php esc_html_e( 'WordPress cron', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays whether or not WP Cron Jobs are enabled.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php if ( $environment['wp_cron'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Language"><?php esc_html_e( 'Language', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The current language used by WordPress. Default = English', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $environment['language'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="External object cache"><?php esc_html_e( 'External object cache', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays whether or not WordPress is using an external object cache.', 'woocommerce' ) ); ?></td>
			<td>
				<?php if ( $environment['external_object_cache'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Server Environment"><h2><?php esc_html_e( 'Server environment', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Server Info"><?php esc_html_e( 'Server info', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Information about the web server that is currently hosting your site.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $environment['server_info'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="PHP Version"><?php esc_html_e( 'PHP version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of PHP installed on your hosting server.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php echo '<mark class="yes">' . esc_html( $environment['php_version'] ) . '</mark>'; ?>
			</td>
		</tr>
		<?php if ( function_exists( 'ini_get' ) ) : ?>
			<tr>
				<td data-export-label="PHP Post Max Size"><?php esc_html_e( 'PHP post max size', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The largest filesize that can be contained in one post.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo esc_html( size_format( $environment['php_post_max_size'] ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Time Limit"><?php esc_html_e( 'PHP time limit', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The amount of time (in seconds) that your site will spend on a single operation before timing out (to avoid server lockups)', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo esc_html( $environment['php_max_execution_time'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Max Input Vars"><?php esc_html_e( 'PHP max input vars', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The maximum number of variables your server can use for a single function to avoid overloads.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo esc_html( $environment['php_max_input_vars'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="cURL Version"><?php esc_html_e( 'cURL version', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of cURL installed on your server.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo esc_html( $environment['curl_version'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="SUHOSIN Installed"><?php esc_html_e( 'SUHOSIN installed', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'Suhosin is an advanced protection system for PHP installations. It was designed to protect your servers on the one hand against a number of well known problems in PHP applications and on the other hand against potential unknown vulnerabilities within these applications or the PHP core itself. If enabled on your server, Suhosin may need to be configured to increase its data submission limits.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo $environment['suhosin_installed'] ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
			</tr>
		<?php endif; ?>

		<?php

		if ( $environment['mysql_version'] ) :
			?>
			<tr>
				<td data-export-label="MySQL Version"><?php esc_html_e( 'MySQL version', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of MySQL installed on your hosting server.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td>
					<?php
					if ( version_compare( $environment['mysql_version'], '5.6', '<' ) && ! strstr( $environment['mysql_version_string'], 'MariaDB' ) ) {
						/* Translators: %1$s: MySQL version, %2$s: Recommended MySQL version. */
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend a minimum MySQL version of 5.6. See: %2$s', 'woocommerce' ), esc_html( $environment['mysql_version_string'] ), '<a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress requirements', 'woocommerce' ) . '</a>' ) . '</mark>';
					} else {
						echo '<mark class="yes">' . esc_html( $environment['mysql_version_string'] ) . '</mark>';
					}
					?>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<td data-export-label="Max Upload Size"><?php esc_html_e( 'Max upload size', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The largest filesize that can be uploaded to your WordPress installation.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( size_format( $environment['max_upload_size'] ) ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Default Timezone is UTC"><?php esc_html_e( 'Default timezone is UTC', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The default timezone for your server.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( 'UTC' !== $environment['default_timezone'] ) {
					/* Translators: %s: default timezone.. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Default timezone is %s - it should be UTC', 'woocommerce' ), esc_html( $environment['default_timezone'] ) ) . '</mark>';
				} else {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="fsockopen/cURL"><?php esc_html_e( 'fsockopen/cURL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Payment gateways can use cURL to communicate with remote servers to authorize payments, other plugins may also use it when communicating with remote services.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['fsockopen_or_curl_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="SoapClient"><?php esc_html_e( 'SoapClient', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Some webservices like shipping use SOAP to get information from remote servers, for example, live shipping quotes from FedEx require SOAP to be installed.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['soapclient_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s classname and link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not have the %s class enabled - some gateway plugins which use SOAP may not work as expected.', 'woocommerce' ), '<a href="https://php.net/manual/en/class.soapclient.php">SoapClient</a>' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="DOMDocument"><?php esc_html_e( 'DOMDocument', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'HTML/Multipart emails use DOMDocument to generate inline CSS in templates.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['domdocument_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s: classname and link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not have the %s class enabled - HTML/Multipart emails, and also some extensions, will not work without DOMDocument.', 'woocommerce' ), '<a href="https://php.net/manual/en/class.domdocument.php">DOMDocument</a>' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="GZip"><?php esc_html_e( 'GZip', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'GZip (gzopen) is used to open the GEOIP database from MaxMind.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['gzip_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s: classname and link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not support the %s function - this is required to use the GeoIP database from MaxMind.', 'woocommerce' ), '<a href="https://php.net/manual/en/zlib.installation.php">gzopen</a>' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Multibyte String"><?php esc_html_e( 'Multibyte string', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Multibyte String (mbstring) is used to convert character encoding, like for emails or converting characters to lowercase.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['mbstring_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s: classname and link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Your server does not support the %s functions - this is required for better character encoding. Some fallbacks will be used instead for it.', 'woocommerce' ), '<a href="https://php.net/manual/en/mbstring.installation.php">mbstring</a>' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Remote Post"><?php esc_html_e( 'Remote post', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'PayPal uses this method of communicating when sending back transaction information.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['remote_post_successful'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s: function name. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%s failed. Contact your hosting provider.', 'woocommerce' ), 'wp_remote_post()' ) . ' ' . esc_html( $environment['remote_post_response'] ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Remote Get"><?php esc_html_e( 'Remote get', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'WooCommerce plugins may use this method of communication when checking for plugin updates.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $environment['remote_get_successful'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s: function name. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%s failed. Contact your hosting provider.', 'woocommerce' ), 'wp_remote_get()' ) . ' ' . esc_html( $environment['remote_get_response'] ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<?php
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
		/**
		 * Filters the environment rows to show in the WooCommerce status report.
		 */
		$rows = apply_filters( 'woocommerce_system_status_environment_rows', array() );
		// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceVersionComment
		foreach ( $rows as $row ) {
			if ( ! empty( $row['success'] ) ) {
				$css_class = 'yes';
				$icon      = '<span class="dashicons dashicons-yes"></span>';
			} else {
				$css_class = 'error';
				$icon      = '<span class="dashicons dashicons-no-alt"></span>';
			}
			?>
			<tr>
				<td data-export-label="<?php echo esc_attr( $row['name'] ); ?>"><?php echo esc_html( $row['name'] ); ?>:</td>
				<td class="help"><?php echo esc_html( isset( $row['help'] ) ? $row['help'] : '' ); ?></td>
				<td>
					<mark class="<?php echo esc_attr( $css_class ); ?>">
						<?php echo wp_kses_post( $icon ); ?> <?php echo wp_kses_data( ! empty( $row['note'] ) ? $row['note'] : '' ); ?>
					</mark>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<table id="status-database" class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Database">
			<h2>
				<?php
					esc_html_e( 'Database', 'woocommerce' );
					self::output_tables_info();
				?>
			</h2>
		</th>
	</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="WC Database Version"><?php esc_html_e( 'WooCommerce database version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The database version for WooCommerce. This should be the same as your WooCommerce version.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $database['wc_database_version'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Database Prefix"><?php esc_html_e( 'Database prefix', 'woocommerce' ); ?></td>
			<td class="help">&nbsp;</td>
			<td>
				<?php
				if ( strlen( $database['database_prefix'] ) > 20 ) {
					/* Translators: %1$s: Database prefix, %2$s: Docs link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend using a prefix with less than 20 characters. See: %2$s', 'woocommerce' ), esc_html( $database['database_prefix'] ), '<a href="https://woocommerce.com/document/completed-order-email-doesnt-contain-download-links/#section-2" target="_blank">' . esc_html__( 'How to update your database table prefix', 'woocommerce' ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $database['database_prefix'] ) . '</mark>';
				}
				?>
			</td>
		</tr>

		<?php if ( ! empty( $database['database_size'] ) && ! empty( $database['database_tables'] ) ) : ?>
			<tr>
				<td><?php esc_html_e( 'Total Database Size', 'woocommerce' ); ?></td>
				<td class="help">&nbsp;</td>
				<td><?php printf( '%.2fMB', esc_html( $database['database_size']['data'] + $database['database_size']['index'] ) ); ?></td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Database Data Size', 'woocommerce' ); ?></td>
				<td class="help">&nbsp;</td>
				<td><?php printf( '%.2fMB', esc_html( $database['database_size']['data'] ) ); ?></td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Database Index Size', 'woocommerce' ); ?></td>
				<td class="help">&nbsp;</td>
				<td><?php printf( '%.2fMB', esc_html( $database['database_size']['index'] ) ); ?></td>
			</tr>

			<?php foreach ( $database['database_tables']['woocommerce'] as $table => $table_data ) { ?>
				<tr>
					<td><?php echo esc_html( $table ); ?></td>
					<td class="help">&nbsp;</td>
					<td>
						<?php
						if ( ! $table_data ) {
							echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Table does not exist', 'woocommerce' ) . '</mark>';
						} else {
							/* Translators: %1$f: Table size, %2$f: Index size, %3$s Engine. */
							printf( esc_html__( 'Data: %1$.2fMB + Index: %2$.2fMB + Engine %3$s', 'woocommerce' ), esc_html( wc_format_decimal( $table_data['data'], 2 ) ), esc_html( wc_format_decimal( $table_data['index'], 2 ) ), esc_html( $table_data['engine'] ) );
						}
						?>
					</td>
				</tr>
			<?php } ?>

			<?php foreach ( $database['database_tables']['other'] as $table => $table_data ) { ?>
				<tr>
					<td><?php echo esc_html( $table ); ?></td>
					<td class="help">&nbsp;</td>
					<td>
						<?php
							/* Translators: %1$f: Table size, %2$f: Index size, %3$s Engine. */
							printf( esc_html__( 'Data: %1$.2fMB + Index: %2$.2fMB + Engine %3$s', 'woocommerce' ), esc_html( wc_format_decimal( $table_data['data'], 2 ) ), esc_html( wc_format_decimal( $table_data['index'], 2 ) ), esc_html( $table_data['engine'] ) );
						?>
					</td>
				</tr>
			<?php } ?>
		<?php else : ?>
			<tr>
				<td><?php esc_html_e( 'Database information:', 'woocommerce' ); ?></td>
				<td class="help">&nbsp;</td>
				<td>
					<?php
					esc_html_e(
						'Unable to retrieve database information. Usually, this is not a problem, and it only means that your install is using a class that replaces the WordPress database class (e.g., HyperDB) and WooCommerce is unable to get database information.',
						'woocommerce'
					);
					?>
				</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>
<?php if ( $post_type_counts ) : ?>
	<table class="wc_status_table widefat" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Post Type Counts"><h2><?php esc_html_e( 'Post Type Counts', 'woocommerce' ); ?></h2></th>
		</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $post_type_counts as $ptype ) {
				?>
				<tr>
					<td><?php echo esc_html( $ptype['type'] ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo absint( $ptype['count'] ); ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
<?php endif; ?>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Security"><h2><?php esc_html_e( 'Security', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Secure connection (HTTPS)"><?php esc_html_e( 'Secure connection (HTTPS)', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Is the connection to your store secure?', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php if ( $security['secure_connection'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="error"><span class="dashicons dashicons-warning"></span>
					<?php
					/* Translators: %s: docs link. */
					echo wp_kses_post( sprintf( __( 'Your store is not using HTTPS. <a href="%s" target="_blank">Learn more about HTTPS and SSL Certificates</a>.', 'woocommerce' ), 'https://woocommerce.com/document/ssl-and-https/' ) );
					?>
					</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Hide errors from visitors"><?php esc_html_e( 'Hide errors from visitors', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Error messages can contain sensitive information about your store environment. These should be hidden from untrusted visitors.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php if ( $security['hide_errors'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="error"><span class="dashicons dashicons-warning"></span><?php esc_html_e( 'Error messages should not be shown to visitors.', 'woocommerce' ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Active Plugins (<?php echo esc_attr( $active_plugins_count ); ?>)"><h2><?php esc_html_e( 'Active plugins', 'woocommerce' ); ?> (<?php echo esc_attr( $active_plugins_count ); ?>)</h2></th>
		</tr>
	</thead>
	<tbody>
		<?php self::output_plugins_info( $active_plugins, $untested_plugins ); ?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Inactive Plugins (<?php echo esc_attr( $inactive_plugins_count ); ?>)"><h2><?php esc_html_e( 'Inactive plugins', 'woocommerce' ); ?> (<?php echo esc_attr( $inactive_plugins_count ); ?>)</h2></th>
		</tr>
	</thead>
	<tbody>
		<?php self::output_plugins_info( $inactive_plugins, $untested_plugins ); ?>
	</tbody>
</table>
<?php
$dropins_count = is_countable( $dropins_mu_plugins['dropins'] ) ? count( $dropins_mu_plugins['dropins'] ) : 0;
if ( 0 < $dropins_count ) :
	?>
	<table class="wc_status_table widefat" cellspacing="0">
		<thead>
			<tr>
				<th colspan="3" data-export-label="Dropin Plugins (<?php $dropins_count; ?>)"><h2><?php esc_html_e( 'Dropin Plugins', 'woocommerce' ); ?> (<?php $dropins_count; ?>)</h2></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $dropins_mu_plugins['dropins'] as $dropin ) {
				?>
				<tr>
					<td><?php echo wp_kses_post( $dropin['plugin'] ); ?></td>
					<td class="help">&nbsp;</td>
					<td><?php echo wp_kses_post( $dropin['name'] ); ?>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
	<?php
endif;

$mu_plugins_count = is_countable( $dropins_mu_plugins['mu_plugins'] ) ? count( $dropins_mu_plugins['mu_plugins'] ) : 0;
if ( 0 < $mu_plugins_count ) :
	?>
	<table class="wc_status_table widefat" cellspacing="0">
		<thead>
			<tr>
				<th colspan="3" data-export-label="Must Use Plugins (<?php echo esc_attr( $mu_plugins_count ); ?>)"><h2><?php esc_html_e( 'Must Use Plugins', 'woocommerce' ); ?> (<?php echo esc_attr( $mu_plugins_count ); ?>)</h2></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $dropins_mu_plugins['mu_plugins'] as $mu_plugin ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$plugin_name = esc_html( $mu_plugin['name'] );
				if ( ! empty( $mu_plugin['url'] ) ) {
					$plugin_name = '<a href="' . esc_url( $mu_plugin['url'] ) . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'woocommerce' ) . '" target="_blank">' . $plugin_name . '</a>';
				}
				?>
				<tr>
					<td><?php echo wp_kses_post( $plugin_name ); ?></td>
					<td class="help">&nbsp;</td>
					<td>
					<?php
						/* translators: %s: plugin author */
						printf( esc_html__( 'by %s', 'woocommerce' ), esc_html( $mu_plugin['author_name'] ) );
						echo ' &ndash; ' . esc_html( $mu_plugin['version'] );
					?>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
<?php endif; ?>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Settings"><h2><?php esc_html_e( 'Settings', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Legacy API Enabled"><?php esc_html_e( 'Legacy API enabled', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Does your site have the Legacy REST API enabled?', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo $settings['api_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Force SSL"><?php esc_html_e( 'Force SSL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Does your site force a SSL Certificate for transactions?', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo $settings['force_ssl'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Currency"><?php esc_html_e( 'Currency', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'What currency prices are listed at in the catalog and which currency gateways will take payments in.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $settings['currency'] ); ?> (<?php echo esc_html( $settings['currency_symbol'] ); ?>)</td>
		</tr>
		<tr>
			<td data-export-label="Currency Position"><?php esc_html_e( 'Currency position', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The position of the currency symbol.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $settings['currency_position'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Thousand Separator"><?php esc_html_e( 'Thousand separator', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The thousand separator of displayed prices.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $settings['thousand_separator'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Decimal Separator"><?php esc_html_e( 'Decimal separator', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The decimal separator of displayed prices.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $settings['decimal_separator'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Number of Decimals"><?php esc_html_e( 'Number of decimals', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The number of decimal points shown in displayed prices.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $settings['number_of_decimals'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Taxonomies: Product Types"><?php esc_html_e( 'Taxonomies: Product types', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'A list of taxonomy terms that can be used in regard to order/product statuses.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				$display_terms = array();
				foreach ( $settings['taxonomies'] as $slug => $name ) {
					$display_terms[] = strtolower( $name ) . ' (' . $slug . ')';
				}
				echo implode( ', ', array_map( 'esc_html', $display_terms ) );
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Taxonomies: Product Visibility"><?php esc_html_e( 'Taxonomies: Product visibility', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'A list of taxonomy terms used for product visibility.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				$display_terms = array();
				foreach ( $settings['product_visibility_terms'] as $slug => $name ) {
					$display_terms[] = strtolower( $name ) . ' (' . $slug . ')';
				}
				echo implode( ', ', array_map( 'esc_html', $display_terms ) );
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Connected to WooCommerce.com"><?php esc_html_e( 'Connected to WooCommerce.com', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Is your site connected to WooCommerce.com?', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo 'yes' === $settings['woocommerce_com_connected'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Enforce Approved Product Download Directories"><?php esc_html_e( 'Enforce Approved Product Download Directories', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Is your site enforcing the use of Approved Product Download Directories?', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo $settings['enforce_approved_download_dirs'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>

		<tr>
			<td data-export-label="HPOS feature enabled"><?php esc_html_e( 'HPOS enabled:', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Is HPOS enabled?', 'woocommerce' ) ); ?></td>
			<td><?php echo $settings['HPOS_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Order datastore"><?php esc_html_e( 'Order datastore:', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Datastore currently in use for orders.', 'woocommerce' ) ); ?></td>
			<td><?php echo esc_html( $settings['order_datastore'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="HPOS data sync enabled"><?php esc_html_e( 'HPOS data sync enabled:', 'woocommerce' ); ?></td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Is data sync enabled for HPOS?', 'woocommerce' ) ); ?></td>
			<td><?php echo $settings['HPOS_sync_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>

	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Logging"><h2><?php esc_html_e( 'Logging', 'woocommerce' ); ?></h2></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td data-export-label="Enabled"><?php esc_html_e( 'Enabled', 'woocommerce' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_html__( 'Is logging enabled?', 'woocommerce' ) ); ?></td>
		<td><?php echo $logging['logging_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
	</tr>
	<tr>
		<td data-export-label="Handler"><?php esc_html_e( 'Handler', 'woocommerce' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_html__( 'How log entries are being stored.', 'woocommerce' ) ); ?></td>
		<td><?php echo esc_html( $logging['default_handler'] ); ?></td>
	</tr>
	<tr>
		<td data-export-label="Retention period"><?php esc_html_e( 'Retention period', 'woocommerce' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_html__( 'How many days log entries will be kept before being auto-deleted.', 'woocommerce' ) ); ?></td>
		<td>
			<?php
			printf(
				esc_html(
					// translators: %s is a number of days.
					_n(
						'%s day',
						'%s days',
						$logging['retention_period_days'],
						'woocommerce'
					)
				),
				esc_html( number_format_i18n( $logging['retention_period_days'] ) )
			);
			?>
		</td>
	</tr>
	<tr>
		<td data-export-label="Level threshold"><?php esc_html_e( 'Level threshold', 'woocommerce' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_html__( 'The minimum severity level of logs that will be stored.', 'woocommerce' ) ); ?></td>
		<td><?php echo $logging['level_threshold'] ? esc_html( $logging['level_threshold'] ) : '<mark class="no">&ndash;</mark>'; ?></td>
	</tr>
	<tr>
		<td data-export-label="Log directory size"><?php esc_html_e( 'Log directory size', 'woocommerce' ); ?></td>
		<td class="help"><?php echo wc_help_tip( esc_html__( 'The total size of the files in the log directory.', 'woocommerce' ) ); ?></td>
		<td><?php echo esc_html( $logging['log_directory_size'] ); ?></td>
	</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="WC Pages"><h2><?php esc_html_e( 'WooCommerce pages', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$alt = 1;
		foreach ( $wp_pages as $_page ) {
			$found_error = false;

			if ( $_page['page_id'] ) {
				/* Translators: %s: page name. */
				$page_name = '<a href="' . get_edit_post_link( $_page['page_id'] ) . '" aria-label="' . sprintf( esc_html__( 'Edit %s page', 'woocommerce' ), esc_html( $_page['page_name'] ) ) . '">' . esc_html( $_page['page_name'] ) . '</a>';
			} else {
				$page_name = esc_html( $_page['page_name'] );
			}

			echo '<tr><td data-export-label="' . esc_attr( $page_name ) . '">' . wp_kses_post( $page_name ) . ':</td>';
			/* Translators: %s: page name. */
			echo '<td class="help">' . wc_help_tip( sprintf( esc_html__( 'The URL of your %s page (along with the Page ID).', 'woocommerce' ), $page_name ) ) . '</td><td>';

			// Page ID check.
			if ( ! $_page['page_set'] ) {
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Page not set', 'woocommerce' ) . '</mark>';
				$found_error = true;
			} elseif ( ! $_page['page_exists'] ) {
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Page ID is set, but the page does not exist', 'woocommerce' ) . '</mark>';
				$found_error = true;
			} elseif ( ! $_page['page_visible'] ) {
				/* Translators: %s: docs link. */
				echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . wp_kses_post( sprintf( __( 'Page visibility should be <a href="%s" target="_blank">public</a>', 'woocommerce' ), 'https://wordpress.org/support/article/content-visibility/' ) ) . '</mark>';
				$found_error = true;
			} elseif ( $_page['shortcode_required'] || $_page['block_required'] ) {
				// Shortcode and block check.
				if ( ! $_page['shortcode_present'] && ! $_page['block_present'] ) {
					/* Translators: %1$s: shortcode text, %2$s: block slug. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . ( $_page['block_required'] ? sprintf( esc_html__( 'Page does not contain the %1$s shortcode or the %2$s block.', 'woocommerce' ), esc_html( $_page['shortcode'] ), esc_html( $_page['block'] ) ) : sprintf( esc_html__( 'Page does not contain the %s shortcode.', 'woocommerce' ), esc_html( $_page['shortcode'] ) ) ) . '</mark>'; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */
					$found_error = true;
				}

				// Warn merchants if both the shortcode and block are present, which will be a confusing shopper experience.
				if ( $_page['shortcode_present'] && $_page['block_present'] ) {
					/* Translators: %1$s: shortcode text, %2$s: block slug. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Page contains both the %1$s shortcode and the %2$s block.', 'woocommerce' ), esc_html( $_page['shortcode'] ), esc_html( $_page['block'] ) ) . '</mark>'; /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */
					$found_error = true;
				}
			}

			if ( ! $found_error ) {

				$additional_info = '';

				// We only state the used type on the Checkout and the Cart page.
				if ( in_array( $_page['block'], array( 'woocommerce/checkout', 'woocommerce/cart' ), true ) ) {
					// We check first if, in a blocks theme, the template content does not load the page content.
					if ( CartCheckoutUtils::is_overriden_by_custom_template_content( $_page['block'] ) ) {
						$additional_info = __( "This page's content is overridden by custom template content", 'woocommerce' );
					} elseif ( $_page['shortcode_present'] ) {
						/* Translators: %1$s: shortcode text. */
						$additional_info = sprintf( __( 'Contains the <strong>%1$s</strong> shortcode', 'woocommerce' ), esc_html( $_page['shortcode'] ) );
					} elseif ( $_page['block_present'] ) {
						/* Translators: %1$s: block slug. */
						$additional_info = sprintf( __( 'Contains the <strong>%1$s</strong> block', 'woocommerce' ), esc_html( $_page['block'] ) );
					}

					if ( ! empty( $additional_info ) ) {
						$additional_info = '<mark class="no"> - <span class="dashicons dashicons-info"></span> ' . $additional_info . '</mark>';
					}
				}

				echo '<mark class="yes">#' . absint( $_page['page_id'] ) . ' - ' . esc_html( str_replace( home_url(), '', get_permalink( $_page['page_id'] ) ) ) . '</mark>' . $additional_info; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
			}

			echo '</td></tr>';
		}
		?>
	</tbody>
</table>
<table class="wc_status_table widefat" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Theme"><h2><?php esc_html_e( 'Theme', 'woocommerce' ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td data-export-label="Name"><?php esc_html_e( 'Name', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The name of the current active theme.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $theme['name'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Version"><?php esc_html_e( 'Version', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The installed version of the current active theme.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( version_compare( $theme['version'], $theme['version_latest'], '<' ) ) {
					/* translators: 1: current version. 2: latest version */
					echo esc_html( sprintf( __( '%1$s (update to version %2$s is available)', 'woocommerce' ), $theme['version'], $theme['version_latest'] ) );
				} else {
					echo esc_html( $theme['version'] );
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Author URL"><?php esc_html_e( 'Author URL', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'The theme developers URL.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td><?php echo esc_html( $theme['author_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Child Theme"><?php esc_html_e( 'Child theme', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays whether or not the current theme is a child theme.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $theme['is_child_theme'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s docs link. */
					echo '<span class="dashicons dashicons-no-alt"></span> &ndash; ' . wp_kses_post( sprintf( __( 'If you are modifying WooCommerce on a parent theme that you did not build personally we recommend using a child theme. See: <a href="%s" target="_blank">How to create a child theme</a>', 'woocommerce' ), 'https://developer.wordpress.org/themes/advanced-topics/child-themes/' ) );
				}
				?>
				</td>
		</tr>
		<?php if ( $theme['is_child_theme'] ) : ?>
			<tr>
				<td data-export-label="Parent Theme Name"><?php esc_html_e( 'Parent theme name', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The name of the parent theme.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo esc_html( $theme['parent_name'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Parent Theme Version"><?php esc_html_e( 'Parent theme version', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The installed version of the parent theme.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td>
					<?php
					echo esc_html( $theme['parent_version'] );
					if ( version_compare( $theme['parent_version'], $theme['parent_version_latest'], '<' ) ) {
						/* translators: %s: parent theme latest version */
						echo ' &ndash; <strong style="color:red;">' . sprintf( esc_html__( '%s is available', 'woocommerce' ), esc_html( $theme['parent_version_latest'] ) ) . '</strong>';
					}
					?>
				</td>
			</tr>
			<tr>
				<td data-export-label="Parent Theme Author URL"><?php esc_html_e( 'Parent theme author URL', 'woocommerce' ); ?>:</td>
				<td class="help"><?php echo wc_help_tip( esc_html__( 'The parent theme developers URL.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
				<td><?php echo esc_html( $theme['parent_author_url'] ); ?></td>
			</tr>
		<?php endif ?>
		<?php if ( isset( $theme['is_block_theme'] ) ) : ?>
		<tr>
			<td data-export-label="Theme type"><?php esc_html_e( 'Theme type', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays whether the current active theme is a block theme or a classic theme.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( $theme['is_block_theme'] ) {
					esc_html_e( 'Block theme', 'woocommerce' );
				} else {
					esc_html_e( 'Classic theme', 'woocommerce' );
				}
				?>
			</td>
		</tr>
		<?php endif ?>
		<tr>
			<td data-export-label="WooCommerce Support"><?php esc_html_e( 'WooCommerce support', 'woocommerce' ); ?>:</td>
			<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays whether or not the current active theme declares WooCommerce support.', 'woocommerce' ) ); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?></td>
			<td>
				<?php
				if ( ! $theme['has_woocommerce_support'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Not declared', 'woocommerce' ) . '</mark>';
				} else {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				}
				?>
			</td>
		</tr>
	</tbody>
</table>
<table class="wc_status_table widefat" id="status-table-templates" cellspacing="0">
	<thead>
		<tr>
			<th colspan="3" data-export-label="Templates"><h2><?php esc_html_e( 'Templates', 'woocommerce' ); ?><?php echo wc_help_tip( esc_html__( 'This section shows any files that are overriding the default WooCommerce template pages.', 'woocommerce' ) ); ?></h2></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( $theme['has_woocommerce_file'] ) : ?>
		<tr>
			<td data-export-label="Archive Template"><?php esc_html_e( 'Archive template', 'woocommerce' ); ?>:</td>
			<td class="help">&nbsp;</td>
			<td><?php esc_html_e( 'Your theme has a woocommerce.php file, you will not be able to override the woocommerce/archive-product.php custom template since woocommerce.php has priority over archive-product.php. This is intended to prevent display issues.', 'woocommerce' ); ?></td>
		</tr>
		<?php endif ?>
		<?php if ( ! empty( $theme['overrides'] ) ) : ?>
			<tr>
				<td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'woocommerce' ); ?></td>
				<td class="help">&nbsp;</td>
				<td>
					<?php
					$total_overrides = is_countable( $theme['overrides'] ) ? count( $theme['overrides'] ) : 0;
					for ( $i = 0; $i < $total_overrides; $i++ ) {
						$override = $theme['overrides'][ $i ];
						if ( $override['core_version'] && ( empty( $override['version'] ) || version_compare( $override['version'], $override['core_version'], '<' ) ) ) {
							$current_version = $override['version'] ? $override['version'] : '-';
							printf(
								/* Translators: %1$s: Template name, %2$s: Template version, %3$s: Core version. */
								esc_html__( '%1$s version %2$s is out of date. The core version is %3$s', 'woocommerce' ),
								'<code>' . esc_html( $override['file'] ) . '</code>',
								'<strong style="color:red">' . esc_html( $current_version ) . '</strong>',
								esc_html( $override['core_version'] )
							);
						} else {
							echo esc_html( $override['file'] );
						}

						if ( ( $total_overrides - 1 ) !== $i ) {
							echo ', ';
						}
						echo '<br />';
					}
					?>
				</td>
			</tr>
		<?php else : ?>
			<tr>
				<td data-export-label="Overrides"><?php esc_html_e( 'Overrides', 'woocommerce' ); ?>:</td>
				<td class="help">&nbsp;</td>
				<td>&ndash;</td>
			</tr>
		<?php endif; ?>

		<?php if ( true === $theme['has_outdated_templates'] ) : ?>
			<tr>
				<td data-export-label="Outdated Templates"><?php esc_html_e( 'Outdated templates', 'woocommerce' ); ?>:</td>
				<td class="help">&nbsp;</td>
				<td>
					<mark class="error">
						<span class="dashicons dashicons-warning"></span>
					</mark>
					<a href="https://woocommerce.com/document/fix-outdated-templates-woocommerce/" target="_blank">
						<?php esc_html_e( 'Learn how to update', 'woocommerce' ); ?>
					</a> |
					<mark class="info">
						<span class="dashicons dashicons-info"></span>
					</mark>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-status&tab=tools' ) ); ?>">
						<?php esc_html_e( 'Clear system status theme info cache', 'woocommerce' ); ?>
					</a>
				</td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php
	// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
	/**
	 * Action fired when the WooCommerce system status report is rendered.
	 */
	do_action( 'woocommerce_system_status_report' );
	// phpcs:enable WooCommerce.Commenting.CommentHooks.MissingSinceComment
?>

<table class="wc_status_table widefat" cellspacing="0">
	<thead>
	<tr>
		<th colspan="3" data-export-label="Status report information"><h2><?php esc_html_e( 'Status report information', 'woocommerce' ); ?><?php echo wc_help_tip( esc_html__( 'This section shows information about this status report.', 'woocommerce' ) ); ?></h2></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td data-export-label="Generated at"><?php esc_html_e( 'Generated at', 'woocommerce' ); ?>:</td>
		<td class="help">&nbsp;</td>
		<td><?php echo esc_html( current_time( 'Y-m-d H:i:s P' ) ); ?></td>

	</tr>
	</tbody>
</table>
