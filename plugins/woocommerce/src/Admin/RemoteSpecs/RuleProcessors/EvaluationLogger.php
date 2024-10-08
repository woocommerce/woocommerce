<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

/**
 * Class EvaluationLogger
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors
 */
class EvaluationLogger {
	/**
	 * Slug of the spec.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Results of rules in the given spec.
	 *
	 * @var array
	 */
	private $results = array();

	/**
	 * Logger class to use.
	 *
	 * @var \WC_Logger_Interface|null
	 */
	private $logger;

	/**
	 * Logger source.
	 *
	 * @var string Logger source.
	 */
	private $source = '';

	/**
	 * EvaluationLogger constructor.
	 *
	 * @param string                    $slug   Slug/ID of a spec that is being evaluated.
	 * @param string|null               $source Logger source.
	 * @param \WC_Logger_Interface|null $logger Logger class to use. Default to using the WC logger.
	 */
	public function __construct( $slug, $source = null, \WC_Logger_Interface $logger = null ) {
		$this->slug = $slug;
		if ( null === $logger ) {
			$logger = wc_get_logger();
		}

		if ( $source ) {
			$this->source = $source;
		}

		$this->logger = $logger;
	}

	/**
	 * Add evaluation result of a rule.
	 *
	 * @param string  $rule_type Name of the rule being tested.
	 * @param boolean $result    Result of a given rule.
	 */
	public function add_result( $rule_type, $result ) {
		$this->results[] = array(
			'rule'   => $rule_type,
			'result' => $result ? 'passed' : 'failed',
		);
	}

	/**
	 * Log the results.
	 */
	public function log() {
		$should_log = defined( 'WC_ADMIN_DEBUG_RULE_EVALUATOR' ) && true === constant( 'WC_ADMIN_DEBUG_RULE_EVALUATOR' );

		/**
		 * Filter to determine if the rule evaluator should log the results.
		 *
		 * @since 9.2.0
		 *
		 * @param bool $should_log Whether the rule evaluator should log the results.
		 */
		if ( ! apply_filters( 'woocommerce_admin_remote_specs_evaluator_should_log', $should_log ) ) {
			return;
		}

		foreach ( $this->results as $result ) {
			$this->logger->debug(
				"[{$this->slug}] {$result['rule']}: {$result['result']}",
				array( 'source' => $this->source )
			);
		}
	}
}
