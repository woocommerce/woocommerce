<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

/**
 * Rule processor for context_plugins rules.
 *
 * Processes the following rule:
 * {
 *     "type": "context_plugins",
 *     "name": "name of a property in the plugin object",
 *     "value": "value to match",
 *     "operation": "operation"
 * }
 */
class ContextPluginsRuleProcessor implements RuleProcessorInterface {

	/**
	 * The list of plugin objects.
	 *
	 * Plugin object is unmodified object from https://woocommerce.com/wp-json/wccom/obw-free-extensions/4.0/extensions.json
	 *
	 * Example:
	 * {
	 *   "id": "WooCommerce Shipping",
	 *   "description": "description",
	 *   "is_visible": true,
	 *   "is_built_by_wc": true,
	 *   "key": "woocommerce-shipping",
	 * }
	 *
	 * @var array a list of plugins.
	 */
	private array $plugins;

	/**
	 * Constructor.
	 *
	 * @param array $plugins a list of plugins.
	 */
	public function __construct( array $plugins ) {
		$this->plugins = $plugins;
	}

	/**
	 * Performs a comparison operation against the option value.
	 *
	 * @param object $rule         The specific rule being processed by this rule processor.
	 * @param object $stored_state Stored state.
	 *
	 * @return bool The result of the operation.
	 */
	public function process( $rule, $stored_state ) {
		foreach ( $this->plugins as $plugin ) {
			if ( ! isset( $plugin->{$rule->name} ) ) {
				continue;
			}
			if ( ComparisonOperation::compare( $plugin->{$rule->name}, $rule->value, $rule->operation ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validates the rule.
	 *
	 * @param object $rule The rule to validate.
	 *
	 * @return bool Pass/fail.
	 */
	public function validate( $rule ) {
		if ( ! isset( $rule->name ) || ! isset( $rule->value ) || ! isset( $rule->operation ) ) {
			return false;
		}

		return true;
	}
}
