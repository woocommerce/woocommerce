<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Admin\Features\Blueprint\PluginLocators\LocalThemeDownloader;
use Automattic\WooCommerce\Admin\Features\Blueprint\PluginLocators\OrgThemeDownloader;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\ConfigureCoreProfiler;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\ConfigureSettings;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\InstallPlugins;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors\InstallThemes;

class ImportSchema {
	use UseHooks;
	private Schema $schema;
	private StepProcessorFactory $step_factory;
	public function __construct( Schema $schema, StepProcessorFactory $step_factory = null) {
		$this->schema = $schema;
		if ($step_factory === null) {
			$step_factory = new StepProcessorFactory($schema);
		}

		$this->step_factory = $step_factory;
	}

	public function get_schema() {
		return $this->schema;
	}

	public static function crate_from_file($file) {
		// @todo check for mime type
		// @todo check for allowed types -- json or zip
		$path_info = pathinfo($file);
		$is_zip = $path_info['extension'] === 'zip';

		return $is_zip ? ImportSchema::crate_from_zip($file) : ImportSchema::create_from_json($file);
	}

	public static function create_from_json($json_path) {
		return new self(new JsonSchema($json_path));
	}

	public static function crate_from_zip($zip_path) {
		return new self(new ZipSchema($zip_path));
	}

	/**
	 * @return StepProcessorResult[]
	 */
	public function process() {
		$results = array();
		$result = StepProcessorResult::success('ImportSchema');
		$results[] = $result;

		$this->apply_filters('woooblueprint_importers', function(StepProcessor $step_processor) {
			$this->step_factory->add_step_processor($step_processor);
		});

		foreach ( $this->schema->get_steps() as $stepSchema ) {
			$stepProcessor = $this->step_factory->create_from_name($stepSchema->step);
			if ( ! $stepProcessor instanceof StepProcessor ) {
				$result->add_error("Unable to create a step processor for {$stepSchema->step}");
				continue;
			}

			$results[] = $stepProcessor->process( $stepSchema );
		}

		return $results;
	}

}
