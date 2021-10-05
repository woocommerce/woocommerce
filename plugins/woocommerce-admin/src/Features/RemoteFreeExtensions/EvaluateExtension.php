<?php
/**
 * Evaluates the spec and returns a status.
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RuleEvaluator;

/**
 * Evaluates the extension and returns it.
 */
class EvaluateExtension {
	/**
	 * Evaluates the extension and returns it.
	 *
	 * @param object $extension The extension to evaluate.
	 * @return object The evaluated extension.
	 */
	public static function evaluate( $extension ) {
		$rule_evaluator = new RuleEvaluator();

		if ( isset( $extension->is_visible ) ) {
			$is_visible            = $rule_evaluator->evaluate( $extension->is_visible );
			$extension->is_visible = $is_visible;
		} else {
			$extension->is_visible = true;
		}

		$installed_plugins       = PluginsHelper::get_installed_plugin_slugs();
		$activated_plugins       = PluginsHelper::get_active_plugin_slugs();
		$extension->is_installed = in_array( explode( ':', $extension->key )[0], $installed_plugins, true );
		$extension->is_activated = in_array( explode( ':', $extension->key )[0], $activated_plugins, true );

		return $extension;
	}
}
