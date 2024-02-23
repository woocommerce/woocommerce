<?php
/**
 * Evaluates the spec and returns a status.
 */

namespace Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions;

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
	private static function evaluate( $extension ) {
		global $wp_version;
		$rule_evaluator = new RuleEvaluator();

		if ( isset( $extension->is_visible ) ) {
			$is_visible            = $rule_evaluator->evaluate( $extension->is_visible );
			$extension->is_visible = $is_visible;
		} else {
			$extension->is_visible = true;
		}

		// Run PHP and WP version chcecks.
		if ( true === $extension->is_visible ) {
			if ( isset( $extension->min_php_version ) && ! version_compare( PHP_VERSION, $extension->min_php_version, '>=' ) ) {
				$extension->is_visible = false;
			}

			if ( isset( $extension->min_wp_version ) && ! version_compare( $wp_version, $extension->min_wp_version, '>=' ) ) {
				$extension->is_visible = false;
			}
		}

		$installed_plugins       = PluginsHelper::get_installed_plugin_slugs();
		$activated_plugins       = PluginsHelper::get_active_plugin_slugs();
		$extension->is_installed = in_array( explode( ':', $extension->key )[0], $installed_plugins, true );
		$extension->is_activated = in_array( explode( ':', $extension->key )[0], $activated_plugins, true );

		return $extension;
	}

	/**
	 * Evaluates the specs and returns the bundles with visible extensions.
	 *
	 * @param array $specs extensions spec array.
	 * @param array $allowed_bundles Optional array of allowed bundles to be returned.
	 * @return array The bundles and errors.
	 */
	public static function evaluate_bundles( $specs, $allowed_bundles = array() ) {
		$bundles = array();

		foreach ( $specs as $spec ) {
			$spec              = (object) $spec;
			$bundle            = (array) $spec;
			$bundle['plugins'] = array();

			if ( ! empty( $allowed_bundles ) && ! in_array( $spec->key, $allowed_bundles, true ) ) {
				continue;
			}

			$errors = array();
			foreach ( $spec->plugins as $plugin ) {
				try {
					$extension = self::evaluate( (object) $plugin );
					if ( ! property_exists( $extension, 'is_visible' ) || $extension->is_visible ) {
						$bundle['plugins'][] = $extension;
					}
				} catch ( \Throwable $e ) {
					$errors[] = $e;
				}
			}

			$bundles[] = $bundle;
		}

		return array(
			'bundles' => $bundles,
			'errors'  => $errors,
		);
	}
}
