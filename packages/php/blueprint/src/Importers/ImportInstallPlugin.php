<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use Plugin_Upgrader;

class ImportInstallPlugin implements StepProcessor {
	use UseWPFunctions;
	private ResourceStorages $storage;
	private array $installed_plugin_paths = array();

	public function __construct( ResourceStorages $storage ) {
		$this->storage = $storage;
	}
	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( InstallPlugin::get_step_name() );

		$installed_plugins = $this->get_installed_plugins_paths();
		$plugin            = $schema->pluginZipFile;

		if ( isset( $installed_plugins[ $plugin->slug ] ) ) {
			$result->add_info( "Skipped installing {$plugin->slug}. It is already installed." );
			return $result;
		}
		if ( $this->storage->is_supported_resource( $plugin->resource ) === false ) {
			$result->add_error( "Invalid resource type for {$plugin->slug}." );
			return $result;
		}

		$downloaded_path = $this->storage->download( $plugin->slug, $plugin->resource );
		if ( ! $downloaded_path ) {
			$result->add_error( "Unable to download {$plugin->slug} with {$plugin->resource} resource type." );
			return $result;
		}

		$install = $this->install( $downloaded_path );
		$install && $result->add_info( "Installed {$plugin->slug}." );

		if ( isset( $plugin->options, $plugin->options->activate ) && $plugin->options->activate === true ) {
			$activate = $this->activate( $plugin->slug );

			if ( $activate instanceof \WP_Error ) {
				$result->add_error( "Failed to activate {$plugin->slug}." );
			}

			if ( null === $activate ) {
				$result->add_info( "Activated {$plugin->slug}." );
			}
		}

		return $result;
	}

	protected function install( $local_plugin_path ) {
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';
		}

		$upgrader = new \Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
		return $upgrader->install( $local_plugin_path );
	}

	protected function activate( $slug ) {
		if ( empty( $this->installed_plugin_paths ) ) {
			$this->installed_plugin_paths = $this->get_installed_plugins_paths();
		}

		$path = $this->installed_plugin_paths[ $slug ] ?? false;

		if ( ! $path ) {
			return new \WP_Error( 'plugin_not_installed', "Plugin {$slug} is not installed." );
		}

		return $this->wp_activate_plugin( $path );
	}

	protected function get_installed_plugins_paths() {
		if ( ! function_exists( 'get_plugins' ) ) {
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

	public function get_step_class(): string {
		return InstallPlugin::class;
	}
}
