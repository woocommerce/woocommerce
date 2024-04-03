<?php

namespace Automattic\WooCommerce\Admin;

use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller as RemoteSpecsDataSourcePoller;

/**
 * Specs data source poller class.
 * This handles polling specs from JSON endpoints, and
 * stores the specs in to the database as an option.
 *
 * @deprecated since 8.8.0
 */
abstract class DataSourcePoller extends RemoteSpecsDataSourcePoller {
	/**
	 * Log a deprecation to the error log.
	 */
	private static function log_deprecation() {
		/*
		// Temporarily disable deprecation message in logs since due to upgrade issues https://github.com/woocommerce/woocommerce/pull/45892.
		error_log( // phpcs:ignore
			sprintf(
				'%1$s is deprecated since version %2$s! Use %3$s instead.',
				self::class,
				'8.8.0',
				'Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller'
			)
		);
		*/
	}

	/**
	 * Constructor.
	 *
	 * @param string $id id of DataSourcePoller.
	 * @param array  $data_sources urls for data sources.
	 * @param array  $args Options for DataSourcePoller.
	 */
	public function __construct( $id, $data_sources = array(), $args = array() ) {
		self::log_deprecation();
		parent::__construct( $id, $data_sources, $args );
	}

	/**
	 * Reads the data sources for specs and persists those specs.
	 *
	 * @deprecated 8.8.0
	 * @return array list of specs.
	 */
	public function get_specs_from_data_sources() {
		self::log_deprecation();
		return parent::get_specs_from_data_sources();
	}

	/**
	 * Reads the data sources for specs and persists those specs.
	 *
	 * @deprecated 8.8.0
	 * @return bool Whether any specs were read.
	 */
	public function read_specs_from_data_sources() {
		self::log_deprecation();
		return parent::read_specs_from_data_sources();
	}

	/**
	 * Delete the specs transient.
	 *
	 * @deprecated 8.8.0
	 * @return bool success of failure of transient deletion.
	 */
	public function delete_specs_transient() {
		self::log_deprecation();
		return parent::delete_specs_transient();
	}

	/**
	 * Set the specs transient.
	 *
	 * @param array $specs The specs to set in the transient.
	 * @param int   $expiration The expiration time for the transient.
	 *
	 * @deprecated 8.8.0
	 */
	public function set_specs_transient( $specs, $expiration = 0 ) {
		self::log_deprecation();
		return parent::set_specs_transient( $specs, $expiration );
	}
}
