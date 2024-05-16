<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders\LocalThemeResourceDownloader;
use Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders\OrgThemeResourceDownloader;
use Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders\LocalPluginResourceDownloader;
use Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders\OrgPluginResourceDownloader;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\InstallPlugins;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\InstallThemes;

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
	public function add_step_processor(StepProcessor $step_processor) {
	    $this->step_processors[$step_processor->get_supported_step()] = $step_processor;
	}

	public function create_from_name($name) {
		// Return if we already have an instance
		if (isset($this->step_processors[$name])) {
			return $this->step_processors[$name];
		}

		$step_processor_class = __NAMESPACE__ . '\\StepProcessors\\' . Util::snake_to_camel($name);
		if (!class_exists($step_processor_class)) {
			// throw error
			return null;
		}

		switch ($name) {
			case 'installPlugins':
				$step_processor =  $this->create_install_plugins_processor();
				break;
			case 'installThemes':
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
			$storage->add_downloader( new LocalPluginResourceDownloader($this->schema->get_unzip_path()) );
		}

		return new InstallPlugins($storage);
	}

	private function create_install_themes_processor() {
		$storage = new ResourceStorage();
		$storage->add_downloader(new OrgThemeResourceDownloader());
		if ( $this->schema instanceof ZipSchema) {
			$storage->add_downloader( new LocalThemeResourceDownloader($this->schema->get_unzip_path()) );
		}

		return new InstallThemes($storage);
	}
}
