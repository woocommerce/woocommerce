<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Importers\ActivatePlugin;
use Automattic\WooCommerce\Blueprint\Importers\DeactivatePlugin;
use Automattic\WooCommerce\Blueprint\Importers\DeletePlugin;
use Automattic\WooCommerce\Blueprint\Importers\InstallPlugin;
use Automattic\WooCommerce\Blueprint\Importers\InstallTheme;
use Automattic\WooCommerce\Blueprint\Importers\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\LocalPluginResourceDownloader;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\LocalThemeResourceDownloader;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\OrgPluginResourceDownloader;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\OrgThemeResourceDownloader;

class BuiltInStepProcessors {
	private Schema $schema;

	public function __construct(Schema $schema) {
		$this->schema = $schema;
	}

	public function get_all() {
		return array(
			$this->create_install_plugins_processor(),
			$this->create_install_themes_processor(),
			new SetSiteOptions(),
			new DeletePlugin(),
			new ActivatePlugin(),
			new DeactivatePlugin(),
		);
	}

	private function create_install_plugins_processor() {
		$storage = new ResourceStorage();
		$storage->add_downloader(new OrgPluginResourceDownloader());

		if ( $this->schema instanceof ZipSchema) {
			$storage->add_downloader( new LocalPluginResourceDownloader($this->schema->get_unzipped_path()) );
		}

		return new InstallPlugin($storage);
	}

	private function create_install_themes_processor() {
		$storage = new ResourceStorage();
		$storage->add_downloader(new OrgThemeResourceDownloader());
		if ( $this->schema instanceof ZipSchema) {
			$storage->add_downloader( new LocalThemeResourceDownloader($this->schema->get_unzipped_path()) );
		}

		return new InstallTheme($storage);
	}
}
