<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use Plugin_Upgrader;

class ImportInstallTheme implements StepProcessor {
	use UseWPFunctions;
	private ResourceStorages $storage;
	private StepProcessorResult $result;

	public function __construct( ResourceStorages $storage ) {
		$this->result  = StepProcessorResult::success( InstallTheme::get_step_name() );
		$this->storage = $storage;
	}
	public function process( $schema ): StepProcessorResult {
		$installed_themes = $this->wp_get_themes();
		$theme            = $schema->themeZipFile;

		if ( isset( $installed_themes[ $theme->slug ] ) ) {
			$this->result->add_info( "Skipped installing {$theme->slug}. It is already installed." );
			return $this->result;
		}
		if ( $this->storage->is_supported_resource( $theme->resource ) === false ) {
			$this->result->add_error( "Invalid resource type for {$theme->slug}" );
			return $this->result;
		}

		$downloaded_path = $this->storage->download( $theme->slug, $theme->resource );

		if ( ! $downloaded_path ) {
			$this->result->add_error( "Unable to download {$theme->slug} with {$theme->resource} resource type." );
			return $this->result;
		}

		$this->result->add_debug( "'$theme->slug' has been downloaded in $downloaded_path" );

		$install = $this->install( $downloaded_path );

		if ( $install ) {
			$this->result->add_debug( "Theme '$theme->slug' installed successfully." );
		} else {
			$this->result->add_error( "Failed to install theme '$theme->slug'." );
		}

		$theme_switch = $theme->activate === true && $this->wp_switch_theme( $theme->slug );

		if ( $theme_switch ) {
			$this->result->add_info( "Switched theme to '$theme->slug'." );
		} else {
			$this->result->add_error( "Failed to switch theme to '$theme->slug'." );
		}

		return $this->result;
	}

	protected function install( $local_plugin_path ) {
		$unzip_result = $this->wp_unzip_file( $local_plugin_path, $this->wp_get_theme_root() );

		if ( $this->is_wp_error( $unzip_result ) ) {
			return false;
		}

		return true;
	}

	public function get_step_class(): string {
		return InstallTheme::class;
	}
}
