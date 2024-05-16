<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\ResourceStorage;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessorResult;
use Plugin_Upgrader;

class InstallPlugins implements StepProcessor {
	private ResourceStorage $storage;
	private array $installed_plugin_paths = array();

	public function __construct(ResourceStorage $storage) {
		$this->storage = $storage;
	}
	public function process($schema): StepProcessorResult {
		$result = StepProcessorResult::success('InstallPlugins');

		$installed_plugins = $this->get_installed_plugins_paths();

		foreach ($schema->plugins as $plugin) {
			if (isset($installed_plugins[$plugin->slug])) {
				$result->add_info("Skipped installing {$plugin->slug}. It is already installed.");
				continue;
			}
			if ($this->storage->is_supported_resource($plugin->resource) === false ) {
				$result->add_error("Invalid resource type for {$plugin->slug}.");
				continue;
			}

			$downloaded_path = $this->storage->download($plugin->slug, $plugin->resource);
			if (! $downloaded_path ) {
				$result->add_error("Unable to download {$plugin->slug} with {$plugin->resource} resource type.");
				continue;
			}

			$install = $this->install($downloaded_path);
			$install && $result->add_info("Installed {$plugin->slug}.");

			if ($plugin->activate === true) {
				$activate = $this->activate($plugin->slug);
				$activate && $result->add_info("Activated {$plugin->slug}.");
			}

		}

		return $result;
	}

	protected function install( $local_plugin_path ) {
		if (!class_exists('Plugin_Upgrader')) {
			include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		return $upgrader->install( $local_plugin_path );
	}

	protected function activate( $slug ) {
		if (empty($this->installed_plugin_paths)) {
			$this->installed_plugin_paths = $this->get_installed_plugins_paths();
		}

		$path = $this->installed_plugin_paths[ $slug ] ?? false;
		return activate_plugin($path);
	}

	protected function get_installed_plugins_paths() {
		if (!function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins           = get_plugins();
		$installed_plugins = array();

		foreach ( $plugins as $path => $plugin ) {
			$path_parts                 = explode( '/', $path );
			$slug                       = $path_parts[0];
			$installed_plugins[ $slug ] = $path;
		}

		return $installed_plugins;
	}

	public function get_supported_step(): string {
		return 'installPlugins';
	}
}
