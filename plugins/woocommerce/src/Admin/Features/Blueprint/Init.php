<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCPaymentGateways;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsAccount;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsAdvanced;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsEmails;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsGeneral;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsTax;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsIntegrations;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsProducts;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsSiteVisibility;
use Automattic\WooCommerce\Admin\Features\Blueprint\Exporters\ExportWCSettingsShipping;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class Init
 *
 * This class initializes the Blueprint feature for WooCommerce.
 */
class Init {
	use UseWPFunctions;

	/**
	 * Array of initialized exporters.
	 *
	 * @var StepExporter[]
	 */
	private array $initialized_exporters = array();

	/**
	 * Init constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );

		add_filter(
			'wooblueprint_export_landingpage',
			function () {
				return '/wp-admin/admin.php?page=wc-admin';
			}
		);

		add_filter( 'wooblueprint_exporters', array( $this, 'add_woo_exporters' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function init_rest_api() {
		( new RestApi() )->register_routes();
	}

	/**
	 * Return Woo Exporter classnames.
	 *
	 * @return StepExporter[]
	 */
	public function get_woo_exporters() {
		$classnames = array(
			ExportWCSettingsGeneral::class,
			ExportWCSettingsProducts::class,
			ExportWCSettingsTax::class,
			ExportWCSettingsShipping::class,
			ExportWCPaymentGateways::class,
			ExportWCSettingsAccount::class,
			ExportWCSettingsEmails::class,
			ExportWCSettingsIntegrations::class,
			ExportWCSettingsSiteVisibility::class,
			ExportWCSettingsAdvanced::class,
		);

		$exporters = array();
		foreach ( $classnames as $classname ) {
			$exporters[ $classname ]                   = $this->initialized_exporters[ $classname ] ?? new $classname();
			$this->initialized_exporters[ $classname ] = $exporters[ $classname ];
		}

		return array_values( $exporters );
	}

	/**
	 * Add Woo Specific Exporters.
	 *
	 * @param StepExporter[] $exporters Array of step exporters.
	 *
	 * @return StepExporter[]
	 */
	public function add_woo_exporters( array $exporters ) {
		return array_merge(
			$exporters,
			$this->get_woo_exporters()
		);
	}
}
