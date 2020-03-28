<?php
/**
 * InstalledExtensions class file.
 *
 * @package WooCommerce Admin/Classes
 */

namespace Automattic\WooCommerce\Admin\Marketing;

use Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * Installed Marketing Extensions class.
 */
class InstalledExtensions {

	/**
	 * Gets an array of plugin data for the "Installed marketing extensions" card.
	 *
	 * Valid extensions statuses are: installed, activated, configured
	 */
	public static function get_data() {
		$data = [];

		$mailchimp = self::get_mailchimp_extension_data();
		$facebook = self::get_facebook_extension_data();

		if ( $mailchimp ) {
			$data[] = $mailchimp;
		}

		if ( $facebook ) {
			$data[] = $facebook;
		}

		return $data;
	}

	/**
	 * Get allowed plugins.
	 *
	 * @return array
	 */
	public static function get_allowed_plugins() {
		return [
			'mailchimp-for-woocommerce',
			'facebook-for-woocommerce',
		];
	}

	/**
	 * Get MailChimp extension data.
	 *
	 * @return array|bool
	 */
	protected static function get_mailchimp_extension_data() {
		$slug = 'mailchimp-for-woocommerce';

		if ( ! PluginsHelper::is_plugin_installed( $slug ) ) {
			return false;
		}

		$data = self::get_extension_base_data( $slug );
		$data['icon'] = plugins_url( 'images/marketing/mailchimp.svg', WC_ADMIN_PLUGIN_FILE );

		if ( 'activated' === $data['status'] && function_exists( 'mailchimp_is_configured' ) ) {
			$data['docsUrl']     = 'https://mailchimp.com/help/connect-or-disconnect-mailchimp-for-woocommerce/';
			$data['settingsUrl'] = admin_url( 'admin.php?page=mailchimp-woocommerce' );

			if ( mailchimp_is_configured() ) {
				$data['status'] = 'configured';
			}
		}

		return $data;
	}

	/**
	 * Get Facebook extension data.
	 *
	 * @return bool
	 */
	protected static function get_facebook_extension_data() {
		$slug = 'facebook-for-woocommerce';

		if ( ! PluginsHelper::is_plugin_installed( $slug ) ) {
			return false;
		}

		$data = self::get_extension_base_data( $slug );
		$data['icon'] = plugins_url( 'images/marketing/facebook.svg', WC_ADMIN_PLUGIN_FILE );

		if ( 'activated' === $data['status'] && function_exists( 'facebook_for_woocommerce' ) ) {
			$integration = facebook_for_woocommerce()->get_integration();

			if ( $integration->is_configured() ) {
				$data['status'] = 'configured';
			}

			$data['settingsUrl'] = facebook_for_woocommerce()->get_settings_url();
			$data['docsUrl'] = facebook_for_woocommerce()->get_documentation_url();
		}

		return $data;
	}


	/**
	 * Get an array of basic data for a given extension.
	 *
	 * @param string $slug Plugin slug.
	 *
	 * @return array|false
	 */
	protected static function get_extension_base_data( $slug ) {
		$status      = PluginsHelper::is_plugin_active( $slug ) ? 'activated' : 'installed';
		$plugin_data = PluginsHelper::get_plugin_data( $slug );

		if ( ! $plugin_data ) {
			return false;
		}

		return [
			'slug'        => $slug,
			'status'      => $status,
			'name'        => $plugin_data['Name'],
			'description' => $plugin_data['Description'],
			'supportUrl'  => 'https://woocommerce.com/my-account/create-a-ticket/',
		];
	}

}
