<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\ResourceDownloaders\LocalThemeResourceDownloader;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\OrgThemeResourceDownloader;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\LocalPluginResourceDownloader;
use Automattic\WooCommerce\Blueprint\ResourceDownloaders\OrgPluginResourceDownloader;

use Automattic\WooCommerce\Blueprint\Importers\InstallPlugin;
use Automattic\WooCommerce\Blueprint\Importers\InstallTheme;

/**
 * Simple factory to create step processors.
 */
class StepProcessorFactory {
	private Schema $schema;

	/**
	 * @var StepProcessor[] $step_processors
	 */
	private array $step_processors = array();

	public function __construct(Schema $schema) {
	    $this->schema = $schema;
	}

	/**
	 * @return StepProcessor[]
	 */
	public function get_step_processors() {
	    return $this->step_processors;
	}

	public function set_step_processors(array $step_processors) {
	    $this->step_processors = $step_processors;
	}

	public function create_from_name($name) {
		// Return if we already have an instance
		if (isset($this->step_processors[$name])) {
			return $this->step_processors[$name];
		}

		$step_processor_class = __NAMESPACE__ . '\\Importers\\' . Util::snake_to_camel($name);
		if (!class_exists($step_processor_class)) {
			// throw error
			throw new \InvalidArgumentException("Class for step processor {$name} does not exist at {$step_processor_class}");
		}

		switch ($name) {
			case 'installPlugin':
				$step_processor =  $this->create_install_plugins_processor();
				break;
			case 'installTheme':
				$step_processor = $this->create_install_themes_processor();
				break;
			default:
				$step_processor =  new $step_processor_class;
				break;
		}

		$this->step_processors[$step_processor->get_supported_step()] = $step_processor;
		return $step_processor;
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
