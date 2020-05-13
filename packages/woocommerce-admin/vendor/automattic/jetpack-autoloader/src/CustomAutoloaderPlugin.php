<?php
/**
 * Custom Autoloader Composer Plugin, hooks into composer events to generate the custom autoloader.
 *
 * @package automattic/jetpack-autoloader
 */

// phpcs:disable PHPCompatibility.Keywords.NewKeywords.t_useFound
// phpcs:disable PHPCompatibility.LanguageConstructs.NewLanguageConstructs.t_ns_separatorFound
// phpcs:disable PHPCompatibility.Keywords.NewKeywords.t_namespaceFound
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase
// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

namespace Automattic\Jetpack\Autoloader;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;

/**
 * Class CustomAutoloaderPlugin.
 *
 * @package automattic/jetpack-autoloader
 */
class CustomAutoloaderPlugin implements PluginInterface, EventSubscriberInterface {

	/**
	 * IO object.
	 *
	 * @var IOInterface IO object.
	 */
	private $io;

	/**
	 * Composer object.
	 *
	 * @var Composer Composer object.
	 */
	private $composer;

	/**
	 * Do nothing.
	 *
	 * @param Composer    $composer Composer object.
	 * @param IOInterface $io IO object.
	 */
	public function activate( Composer $composer, IOInterface $io ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$this->composer = $composer;
		$this->io       = $io;
	}

	/**
	 * Tell composer to listen for events and do something with them.
	 *
	 * @return array List of succribed events.
	 */
	public static function getSubscribedEvents() {
		return array(
			ScriptEvents::POST_AUTOLOAD_DUMP => 'postAutoloadDump',
		);
	}

	/**
	 * Generate the custom autolaoder.
	 *
	 * @param Event $event Script event object.
	 */
	public function postAutoloadDump( Event $event ) {

		$installationManager = $this->composer->getInstallationManager();
		$repoManager         = $this->composer->getRepositoryManager();
		$localRepo           = $repoManager->getLocalRepository();
		$package             = $this->composer->getPackage();
		$config              = $this->composer->getConfig();
		$optimize            = true;
		$suffix              = $config->get( 'autoloader-suffix' )
			? $config->get( 'autoloader-suffix' )
			: md5( uniqid( '', true ) );

		$generator = new AutoloadGenerator( $this->io );

		$generator->dump( $config, $localRepo, $package, $installationManager, 'composer', $optimize, $suffix );
		$this->generated = true;
	}

}
