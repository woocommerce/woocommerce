<?php
/**
 * Evaluates the spec and returns a status.
 */

namespace Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RuleEvaluator;

/**
 * Evaluates the spec and returns the evaluated method.
 */
class EvaluateExtension {
	/**
	 * Evaluates the spec and returns the extension.
	 *
	 * @param array $spec The extension section to evaluate.
	 * @return array The evaluated extension section.
	 */
	public static function evaluate( $spec ) {
		$rule_evaluator = new RuleEvaluator();

		foreach ( $spec->plugins as $plugin ) {

			if ( isset( $plugin->is_visible ) ) {
				$is_visible         = $rule_evaluator->evaluate( $plugin->is_visible );
				$plugin->is_visible = $is_visible;
			} else {
				$plugin->is_visible = true;
			}
		}

		return $spec;
	}
}
