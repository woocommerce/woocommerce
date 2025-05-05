<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\ResourceStorages;
use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ImportInstallTheme
 *
 * This class handles the import process for installing themes.
 *
 * @package Automattic\WooCommerce\Blueprint\Importers
 */
class ImportInstallTheme implements StepProcessor {
	use UseWPFunctions;

	/**
	 * Collection of resource storages.
	 *
	 * @var ResourceStorages The resource storage used for downloading themes.
	 */
	private ResourceStorages $storage;

	/**
	 * The result of the step processing.
	 *
	 * @var StepProcessorResult The result of the step processing.
	 */
	private StepProcessorResult $result;

	/**
	 * ImportInstallTheme constructor.
	 *
	 * @param ResourceStorages $storage The resource storage used for downloading themes.
	 */
	public function __construct( ResourceStorages $storage ) {
		$this->result  = StepProcessorResult::success( InstallTheme::get_step_name() );
		$this->storage = $storage;
	}

	/**
	 * Process the schema to install the theme.
	 *
	 * @param object $schema The schema containing theme installation details.
	 *
	 * @return StepProcessorResult The result of the step processing.
	 */
	public function process( $schema ): StepProcessorResult {
		$installed_themes = $this->wp_get_themes();
		// phpcs:ignore
		$theme = $schema->themeData;

		if ( 'wordpress.org/themes' !== $theme->resource ) {
			$this->result->add_info( "Skipped installing a theme. Unsupported resource type. Only 'wordpress.org/themes' is supported at the moment." );
			return $this->result;
		}

		if ( ! isset( $schema->options ) ) {
			$schema->options = new \stdClass();
		}

		if ( isset( $installed_themes[ $theme->slug ] ) ) {
			$this->activate_theme( $schema );
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

		$this->activate_theme( $schema );

		return $this->result;
	}

	/**
	 * Attempt to activate the theme if the schema specifies to do so.
	 *
	 * @param object $schema installTheme schema.
	 *
	 * @return void
	 */
	protected function activate_theme( $schema ) {
		// phpcs:ignore
		$theme = $schema->themeData;
		if ( isset( $schema->options->activate ) && true === $schema->options->activate ) {
			$this->wp_switch_theme( $theme->slug );
			$current_theme = $this->wp_get_theme()->get_stylesheet();
			if ( $current_theme === $theme->slug ) {
				$this->result->add_info( "Switched theme to '$theme->slug'." );
			} else {
				$this->result->add_error( "Failed to switch theme to '$theme->slug'." );
			}
		}
	}


	/**
	 * Install the theme from the local path.
	 *
	 * @param string $local_path The local path of the theme to be installed.
	 *
	 * @return bool True if the installation was successful, false otherwise.
	 */
	protected function install( $local_path ) {
		$unzip_result = $this->wp_unzip_file( $local_path, $this->wp_get_theme_root() );

		if ( $this->is_wp_error( $unzip_result ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the class name of the step.
	 *
	 * @return string The class name of the step.
	 */
	public function get_step_class(): string {
		return InstallTheme::class;
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool {
		return current_user_can( 'install_themes' );
	}
}
