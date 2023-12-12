<?php
/**
 * A class for handling the overwriting of version strings in the Jetpack Autoloader for merged feature plugins.
 *
 * @package woocommerce/monorepo-plugin
 */

namespace Automattic\WooCommerce\Monorepo\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Util\Filesystem;
use RuntimeException;

/**
 * Class JetpackManifestEditor.
 *
 * @package woocommerce/monorepo-plugin
 */
class JetpackManifestEditor {
	/**
	 * The Composer instance to use.
	 *
	 * @var Composer
	 */
	private $composer;

	/**
	 * The IO interface to use.
	 *
	 * @var IOInterface
	 */
	private $io;

	/**
	 * The Filesystem instance to use.
	 *
	 * @var Filesystem
	 */
	private $filesystem;

	/**
	 * Constructor.
	 *
	 * @param IOInterface $io The IO interface to use.
	 */
	public function __construct( Composer $composer, IOInterface $io ) {
		$this->composer   = $composer;
		$this->io         = $io;
		$this->filesystem = new Filesystem();
	}


	/**
	 * Updates the autoload manifests for the given package.
	 *
	 * @param RootPackageInterface $root_package The package to change the manifests for.
	 */
	public function update_autoload_manifests( RootPackageInterface $root_package ) {
		// All of the manifest files are in the vendor/composer directory.
		$manifest_dir = $this->composer->getConfig()->get( 'vendor-dir' ) . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR;

		// Add an offset to the version string so that we can still tell
		// the difference between subsequent versions of an autoload.
		$new_version = $root_package->getVersion();
		if ( preg_match( '/^([\\d.]+)?(.*)$/', $new_version, $version_parts ) ) {
			$new_version = '10' . $version_parts[1] . $version_parts[2];
		}
		if ( $root_package->getVersion() === $new_version ) {
			return;
		}

		$this->io->write( "<info>Updating Merged Feature Plugin Autoloads ($manifest_dir)</info>" );

		// Update all of the manifest files.
		$this->update_manifest_versions( $manifest_dir, 'jetpack_autoload_classmap.php', $new_version );
		$this->update_manifest_versions( $manifest_dir, 'jetpack_autoload_psr4.php', $new_version );
		$this->update_manifest_versions( $manifest_dir, 'jetpack_autoload_filemap.php', $new_version );
	}

	/**
	 * Updates the versions for local autoloads in the manifest file.
	 *
	 * @param string $manifest_dir The directory that holds the manifest files.
	 * @param string $file The manifest file to update.
	 * @param string $new_version The new version to set into the manifest file.
	 */
	private function update_manifest_versions( $manifest_dir, $file, $new_version ) {
		$manifest_content = @file_get_contents( $manifest_dir . $file );
		if ( ! $manifest_content ) {
			return;
		}

		$this->io->write( "<info>Updating: $file</info>" );

		// We should replace anything mapped from the base directory since that's a path we're mapping an autoload for.
		$manifest_content = preg_replace(
			"/('version'\s+=>\s+')[^']+(',\s+'path'\s+=>\s+(?:array\( )?\\\$baseDir)/",
			'${1}' . $new_version . '${2}',
			$manifest_content
		);

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@file_put_contents( $manifest_dir . $file, $manifest_content );
	}
}
