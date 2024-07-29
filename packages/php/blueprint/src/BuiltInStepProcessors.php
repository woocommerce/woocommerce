<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Importers\ImportActivatePlugin;
use Automattic\WooCommerce\Blueprint\Importers\ImportActivateTheme;
use Automattic\WooCommerce\Blueprint\Importers\ImportDeactivatePlugin;
use Automattic\WooCommerce\Blueprint\Importers\ImportDeletePlugin;
use Automattic\WooCommerce\Blueprint\Importers\ImportInstallPlugin;
use Automattic\WooCommerce\Blueprint\Importers\ImportInstallTheme;
use Automattic\WooCommerce\Blueprint\Importers\ImportSetSiteOptions;
use Automattic\WooCommerce\Blueprint\ResourceStorages\LocalPluginResourceStorage;
use Automattic\WooCommerce\Blueprint\ResourceStorages\LocalThemeResourceStorage;
use Automattic\WooCommerce\Blueprint\ResourceStorages\OrgPluginResourceStorage;
use Automattic\WooCommerce\Blueprint\ResourceStorages\OrgThemeResourceStorage;
use Automattic\WooCommerce\Blueprint\Schemas\JsonSchema;
use Automattic\WooCommerce\Blueprint\Schemas\ZipSchema;

class BuiltInStepProcessors {
	private JsonSchema $schema;
	public function __construct( JsonSchema $schema ) {
		$this->schema = $schema;
	}

	public function get_all() {
		return array(
			$this->create_install_plugins_processor(),
			$this->create_install_themes_processor(),
			new ImportSetSiteOptions(),
			new ImportDeletePlugin(),
			new ImportActivatePlugin(),
			new ImportActivateTheme(),
			new ImportDeactivatePlugin(),
		);
	}

	private function create_install_plugins_processor() {
		$storages = new ResourceStorages();
		$storages->add_storage( new OrgPluginResourceStorage() );

		if ( $this->schema instanceof ZipSchema ) {
			$storages->add_storage( new LocalPluginResourceStorage( $this->schema->get_unzipped_path() ) );
		}

		return new ImportInstallPlugin( $storages );
	}

	private function create_install_themes_processor() {
		$storage = new ResourceStorages();
		$storage->add_storage( new OrgThemeResourceStorage() );
		if ( $this->schema instanceof ZipSchema ) {
			$storage->add_storage( new LocalThemeResourceStorage( $this->schema->get_unzipped_path() ) );
		}

		return new ImportInstallTheme( $storage );
	}
}
