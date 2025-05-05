<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

/**
 * A custom GetRuleProcessor class to support context_vars and context_plugins rule types.
 *
 * GetRuleProcessor class.
 */
class GetRuleProcessorForContext {
	/**
	 * Contains the context variables.
	 *
	 * @var array $context The context variables.
	 */
	protected array $context;

	/**
	 * Constructor.
	 *
	 * @param array $context The context variables.
	 */
	public function __construct( array $context = array() ) {
		$this->context = $context;
	}
	/**
	 * Get the processor for the specified rule type.
	 *
	 * @param string $rule_type The rule type.
	 *
	 * @return RuleProcessorInterface The matching processor for the specified rule type, or a FailRuleProcessor if no matching processor is found.
	 */
	public function get_processor( $rule_type ) {
		switch ( $rule_type ) {
			case 'context_plugins':
				return new ContextPluginsRuleProcessor( $this->context['plugins'] ?? array() );
		}

		return GetRuleProcessor::get_processor( $rule_type );
	}
}
