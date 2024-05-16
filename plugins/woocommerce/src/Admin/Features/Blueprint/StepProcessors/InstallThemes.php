<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\ResourceStorage;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Plugin_Upgrader;

class InstallThemes implements StepProcessor {
	private ResourceStorage $storage;
	private StepProcessorResult $result;

	public function __construct(ResourceStorage $storage) {
		$this->result = StepProcessorResult::success('InstallThemes');
		$this->storage = $storage;
	}
	public function process($schema): StepProcessorResult {
		$installed_themes = wp_get_themes();

		foreach ($schema->themes as $theme) {
			if (isset($installed_themes[$theme->slug])) {
				$this->result->add_info("Skipped installing {$theme->slug}. It is already installed.");
				continue;
			}
			if ($this->storage->is_supported_resource($theme->resource) === false ) {
				$this->result->add_error("Invalid resource type for {$theme->slug}");
				continue;
			}

			$downloaded_path = $this->storage->download($theme->slug, $theme->resource);

			if (! $downloaded_path ) {
				$this->result->add_error("Unable to download {$theme->slug} with {$theme->resource} resource type.");
				continue;
			}

			$this->result->add_debug("'$theme->slug' has been downloaded in $downloaded_path");

			$install = $this->install($downloaded_path);
			$install && $this->result->add_debug("Theme '$theme->slug' installed successfully.");
			$theme_switch = $theme->activate === true && $this->switch_theme($theme->slug);
			$theme_switch && $this->result->add_debug("Switched theme to '$theme->slug'.");
		}

		return $this->result;
	}

	protected function install( $local_plugin_path ) {
		if (!class_exists('WP_Filesystem_Base')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$unzip_result = unzip_file($local_plugin_path, get_theme_root());

		if (is_wp_error($unzip_result)) {
			return false;
		}


		return true;
	}

	protected function switch_theme( $slug ) {
		return \switch_theme($slug);
	}

	public function get_supported_step(): string {
		return 'installThemes';
	}
}
