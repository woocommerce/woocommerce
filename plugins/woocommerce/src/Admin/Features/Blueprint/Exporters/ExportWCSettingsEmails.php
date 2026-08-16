<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\SettingOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

/**
 * Class ExportWCSettingsEmails
 *
 * This class exports WooCommerce settings on the Emails page.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCSettingsEmails extends ExportWCSettings {
	use UseWPFunctions;

	/**
	 * Get the alias for this exporter.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCSettingsEmails';
	}

	/**
	 * Export WooCommerce settings.
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		$emails          = \WC_Emails::instance();
		$setting_options = new SettingOptions();

		$email_settings = $setting_options->get_page_options( $this->get_page_id() );

		// Get sub-settings for each email.
		foreach ( $emails->get_emails() as $email ) {
			$email_settings = array_merge(
				$email_settings,
				$setting_options->get_page_options( 'email_' . $email->id )
			);
		}

		return new SetSiteOptions( $email_settings );
	}

	/**
	 * Return label used in the frontend.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Emails', 'woocommerce' );
	}

	/**
	 * Return description used in the frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Includes all settings in WooCommerce | Settings | Emails.', 'woocommerce' );
	}

	/**
	 * Get the page ID for the settings page.
	 *
	 * @return string
	 */
	protected function get_page_id(): string {
		return 'email';
	}
}
