<?php
/**
 * Autoloader Generator.
 *
 * @package automattic/jetpack-autoloader
 */

// phpcs:disable PHPCompatibility.Keywords.NewKeywords.t_useFound
// phpcs:disable PHPCompatibility.LanguageConstructs.NewLanguageConstructs.t_ns_separatorFound
// phpcs:disable PHPCompatibility.FunctionDeclarations.NewClosure.Found
// phpcs:disable PHPCompatibility.Keywords.NewKeywords.t_namespaceFound
// phpcs:disable PHPCompatibility.Keywords.NewKeywords.t_dirFound
// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.InterpolatedVariableNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase


namespace Automattic\Jetpack\Autoloader;

use Composer\Autoload\AutoloadGenerator as BaseGenerator;
use Composer\Autoload\ClassMapGenerator;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

/**
 * Class AutoloadGenerator.
 */
class AutoloadGenerator extends BaseGenerator {

	/**
	 * Instantiate an AutoloadGenerator object.
	 *
	 * @param IOInterface $io IO object.
	 */
	public function __construct( IOInterface $io = null ) {
		$this->io = $io;
	}

	/**
	 * Dump the autoloader.
	 *
	 * @param Config                       $config Config object.
	 * @param InstalledRepositoryInterface $localRepo Installed Reposetories object.
	 * @param PackageInterface             $mainPackage Main Package object.
	 * @param InstallationManager          $installationManager Manager for installing packages.
	 * @param string                       $targetDir Path to the current target directory.
	 * @param bool                         $scanPsr0Packages Whether to search for packages. Currently hard coded to always be false.
	 * @param string                       $suffix The autoloader suffix, ignored since we want our autoloader to only be included once.
	 */
	public function dump(
		Config $config,
		InstalledRepositoryInterface $localRepo,
		PackageInterface $mainPackage,
		InstallationManager $installationManager,
		$targetDir,
		$scanPsr0Packages = null, // Not used we always optimize.
		$suffix = null
	) {

		$filesystem = new Filesystem();
		$filesystem->ensureDirectoryExists( $config->get( 'vendor-dir' ) );

		$basePath   = $filesystem->normalizePath( realpath( getcwd() ) );
		$vendorPath = $filesystem->normalizePath( realpath( $config->get( 'vendor-dir' ) ) );
		$targetDir  = $vendorPath . '/' . $targetDir;
		$filesystem->ensureDirectoryExists( $targetDir );

		$packageMap = $this->buildPackageMap( $installationManager, $mainPackage, $localRepo->getCanonicalPackages() );
		$autoloads  = $this->parseAutoloads( $packageMap, $mainPackage );

		$classMap = $this->getClassMap( $autoloads, $filesystem, $vendorPath, $basePath );

		// Generate the files.
		file_put_contents( $targetDir . '/autoload_classmap_package.php', $this->getAutoloadClassmapPackagesFile( $classMap ) );
		$this->io->writeError( '<info>Generated ' . $targetDir . '/autoload_classmap_package.php</info>', true );

		file_put_contents( $vendorPath . '/autoload_packages.php', $this->getAutoloadPackageFile( $suffix ) );
		$this->io->writeError( '<info>Generated ' . $vendorPath . '/autoload_packages.php</info>', true );

	}

	/**
	 * This function differs from the composer parseAutoloadsType in that beside returning the path.
	 * It also return the path and the version of a package.
	 *
	 * Currently supports only psr-4 and clasmap parsing.
	 *
	 * @param array            $packageMap Map of all the packages.
	 * @param string           $type Type of autoloader to use, currently not used, since we only support psr-4.
	 * @param PackageInterface $mainPackage Instance of the Package Object.
	 *
	 * @return array
	 */
	protected function parseAutoloadsType( array $packageMap, $type, PackageInterface $mainPackage ) {
		$autoloads = array();

		if ( 'psr-4' !== $type && 'classmap' !== $type ) {
			return parent::parseAutoloadsType( $packageMap, $type, $mainPackage );
		}

		foreach ( $packageMap as $item ) {
			list($package, $installPath) = $item;
			$autoload                    = $package->getAutoload();

			if ( $package === $mainPackage ) {
				$autoload = array_merge_recursive( $autoload, $package->getDevAutoload() );
			}

			if ( null !== $package->getTargetDir() && $package !== $mainPackage ) {
				$installPath = substr( $installPath, 0, -strlen( '/' . $package->getTargetDir() ) );
			}

			if ( 'psr-4' === $type && isset( $autoload['psr-4'] ) && is_array( $autoload['psr-4'] ) ) {
				foreach ( $autoload['psr-4'] as $namespace => $paths ) {
					$paths = is_array( $paths ) ? $paths : array( $paths );
					foreach ( $paths as $path ) {
						$relativePath              = empty( $installPath ) ? ( empty( $path ) ? '.' : $path ) : $installPath . '/' . $path;
						$autoloads[ $namespace ][] = array(
							'path'    => $relativePath,
							'version' => $package->getVersion(), // Version of the class comes from the package - should we try to parse it?
						);
					}
				}
			}

			if ( 'classmap' === $type && isset( $autoload['classmap'] ) && is_array( $autoload['classmap'] ) ) {
				foreach ( $autoload['classmap'] as $paths ) {
					$paths = is_array( $paths ) ? $paths : array( $paths );
					foreach ( $paths as $path ) {
						$relativePath = empty( $installPath ) ? ( empty( $path ) ? '.' : $path ) : $installPath . '/' . $path;
						$autoloads[]  = array(
							'path'    => $relativePath,
							'version' => $package->getVersion(), // Version of the class comes from the package - should we try to parse it?
						);
					}
				}
			}
		}

		return $autoloads;
	}

	/**
	 * Take the autoloads array and return the classMap that contains the path and the version for each namespace.
	 *
	 * @param array      $autoloads Array of autoload settings defined defined by the packages.
	 * @param Filesystem $filesystem Filesystem class instance.
	 * @param string     $vendorPath Path to the vendor directory.
	 * @param string     $basePath Base Path.
	 *
	 * @return array $classMap
	 */
	private function getClassMap( array $autoloads, Filesystem $filesystem, $vendorPath, $basePath ) {
		$blacklist = null;

		if ( ! empty( $autoloads['exclude-from-classmap'] ) ) {
			$blacklist = '{(' . implode( '|', $autoloads['exclude-from-classmap'] ) . ')}';
		}

		$classmapString = '';

		// Scan the PSR-4 and classmap directories for class files, and add them to the class map.
		foreach ( $autoloads['psr-4'] as $namespace => $packages_info ) {
			foreach ( $packages_info as $package ) {
				$dir       = $filesystem->normalizePath(
					$filesystem->isAbsolutePath( $package['path'] )
					? $package['path']
					: $basePath . '/' . $package['path']
				);
				$namespace = empty( $namespace ) ? null : $namespace;
				$map       = ClassMapGenerator::createMap( $dir, $blacklist, $this->io, $namespace );

				foreach ( $map as $class => $path ) {
					$classCode       = var_export( $class, true );
					$pathCode        = $this->getPathCode( $filesystem, $basePath, $vendorPath, $path );
					$versionCode     = var_export( $package['version'], true );
					$classmapString .= <<<CLASS_CODE
	$classCode => array(
		'version' => $versionCode,
		'path'    => $pathCode
	),
CLASS_CODE;
					$classmapString .= PHP_EOL;
				}
			}
		}

		foreach ( $autoloads['classmap'] as $package ) {
			$dir = $filesystem->normalizePath(
				$filesystem->isAbsolutePath( $package['path'] )
				? $package['path']
				: $basePath . '/' . $package['path']
			);
			$map = ClassMapGenerator::createMap( $dir, $blacklist, $this->io, null );

			foreach ( $map as $class => $path ) {
				$classCode       = var_export( $class, true );
				$pathCode        = $this->getPathCode( $filesystem, $basePath, $vendorPath, $path );
				$versionCode     = var_export( $package['version'], true );
				$classmapString .= <<<CLASS_CODE
	$classCode => array(
		'version' => $versionCode,
		'path'    => $pathCode
	),
CLASS_CODE;
				$classmapString .= PHP_EOL;
			}
		}

		return 'array( ' . PHP_EOL . $classmapString . ');' . PHP_EOL;
	}

	/**
	 * Generate the PHP that will be used in the autoload_classmap_package.php files.
	 *
	 * @param srting $classMap class map array string that is to be written out to the file.
	 *
	 * @return string
	 */
	private function getAutoloadClassmapPackagesFile( $classMap ) {

		return <<<INCLUDE_CLASSMAP
<?php

// This file `autoload_classmap_packages.php` was auto generated by automattic/jetpack-autoloader.

\$vendorDir = dirname(__DIR__);
\$baseDir   = dirname(\$vendorDir);

return $classMap

INCLUDE_CLASSMAP;
	}

	/**
	 * Generate the PHP that will be used in the autoload_packages.php files.
	 *
	 * @param string $suffix  Unique suffix added to the jetpack_enqueue_packages function.
	 *
	 * @return string
	 */
	private function getAutoloadPackageFile( $suffix ) {
		$sourceLoader   = fopen( __DIR__ . '/autoload.php', 'r' );
		$file_contents  = stream_get_contents( $sourceLoader );
		$file_contents .= <<<INCLUDE_FILES
/**
 * Prepare all the classes for autoloading.
 */
function enqueue_packages_$suffix() {
	\$class_map = require_once dirname( __FILE__ ) . '/composer/autoload_classmap_package.php';
	foreach ( \$class_map as \$class_name => \$class_info ) {
		enqueue_package_class( \$class_name, \$class_info['version'], \$class_info['path'] );
	}

	\$autoload_file = __DIR__ . '/composer/autoload_files.php';
	\$includeFiles = file_exists( \$autoload_file )
		? require \$autoload_file
		: [];

	foreach ( \$includeFiles as \$fileIdentifier => \$file ) {
		if ( empty( \$GLOBALS['__composer_autoload_files'][ \$fileIdentifier ] ) ) {
			require \$file;

			\$GLOBALS['__composer_autoload_files'][ \$fileIdentifier ] = true;
		}
	}
}
enqueue_packages_$suffix();

INCLUDE_FILES;

		return $file_contents;
	}
}
