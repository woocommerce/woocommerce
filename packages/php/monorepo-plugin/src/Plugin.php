<?php
/**
 * Custom Composer plugin to override any standard behavior required within the monorepo.
 *
 * @package woocommerce/monorepo-plugin
 */

namespace Automattic\WooCommerce\Monorepo\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Class Plugin.
 *
 * @package woocommerce/monorepo-plugin
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

	/**
	 * Composer object.
	 *
	 * @var Composer Composer object.
	 */
	private $composer;

	/**
	 * The IO interface to use.
	 *
	 * @var IOInterface
	 */
	private $io;

	/**
	 * Runs when the plugin activates.
	 *
	 * @param Composer $composer The composer instance.
	 * @param IOInterface $io The IO interface.
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$this->composer = $composer;
		$this->io       = $io;
	}

	/**
	 * Runs when the plugin deactivates.
	 *
	 * @param Composer $composer The composer instance.
	 * @param IOInterface $io The IO interface.
	 */
	public function deactivate( Composer $composer, IOInterface $io ) { }

	/**
	 * Runs when the plugin is uninstalled.
	 *
	 * @param Composer $composer The composer instance.
	 * @param IOInterface $io The IO interface.
	 */
	public function uninstall( Composer $composer, IOInterface $io ) { }

	/**
	 * Subscribe to the events that we're interested in.
	 */
	public static function getSubscribedEvents() {
		return array(
			// Make sure this event will run after the Jetpack Autoloader plugin.
			ScriptEvents::POST_AUTOLOAD_DUMP => array( 'onPostAutoloadDump', -100000 ),
			ScriptEvents::PRE_AUTOLOAD_DUMP  => array( 'onPreAutoloadDump', -100000 ),
		);
	}

	/**
	 * An event handler that runs after the autoload dump event.
	 *
	 * @param Event $event The event to handle.
	 */
	public function onPostAutoloadDump( Event $event ) {
		$root_package = $this->composer->getPackage();

		// Update the Jetpack autoload manifests with a custom string version so that we can ensure they will always be loaded
		// preferentially over other packages that may provide the same autoloads.
		$manifest_editor = new JetpackManifestEditor( $this->composer, $this->io );
		$manifest_editor->update_autoload_manifests( $root_package );
	}

	/**
	 * An event handler that runs before the autoload dump event.
	 *
	 * @param Event $event The event to handle.
	 */
	public function onPreAutoloadDump( Event $event ) {
		$root_package = $this->composer->getPackage();

		// Inject out custom autoloader for picking up new classes without re-running autoload dump.
		$dev_autoload          = $root_package->getDevAutoload();
		$dev_autoload['files'] = array_merge(
			$dev_autoload['files'] ?? array(),
			array( 'vendor/woocommerce/monorepo-plugin/autoload-dev.php' )
		);

		$root_package->setDevAutoload( $dev_autoload );
	}
}
