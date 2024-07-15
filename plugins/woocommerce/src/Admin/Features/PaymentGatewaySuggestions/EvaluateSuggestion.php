<?php
/**
 * Evaluates the spec and returns a status.
 */

namespace Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

/**
 * Evaluates the spec and returns the evaluated suggestion.
 */
class EvaluateSuggestion {
	/**
	 * Evaluates the spec and returns the suggestion.
	 *
	 * @param object|array $spec        The suggestion to evaluate.
	 * @param array        $logger_args Optional. Arguments for the rule evaluator logger.
	 *
	 * @return object The evaluated suggestion.
	 */
	public static function evaluate( $spec, $logger_args = array() ) {
		$rule_evaluator = new RuleEvaluator();
		$suggestion     = is_array( $spec ) ? (object) $spec : clone $spec;

		if ( isset( $suggestion->is_visible ) ) {
			// Determine the suggestion's logger slug.
			$logger_slug = ! empty( $suggestion->id ) ? $suggestion->id : '';
			// If the suggestion has no ID, use the title to generate a slug.
			if ( empty( $logger_slug ) ) {
				$logger_slug = ! empty( $suggestion->title ) ? sanitize_title_with_dashes( trim( $suggestion->title ) ) : 'anonymous-suggestion';
			}

			// Evaluate the visibility of the suggestion.
			$is_visible = $rule_evaluator->evaluate(
				$suggestion->is_visible,
				null,
				array(
					'slug'   => $logger_slug,
					'source' => $logger_args['source'] ?? 'wc-payment-gateway-suggestions',
				)
			);

			$suggestion->is_visible = $is_visible;
		}

		return $suggestion;
	}

	/**
	 * Evaluates the specs and returns the visible suggestions.
	 *
	 * @param array $specs payment suggestion spec array.
	 * @param array $logger_args Optional. Arguments for the rule evaluator logger.
	 *
	 * @return array The visible suggestions and errors.
	 */
	public static function evaluate_specs( $specs, $logger_args = array() ) {
		$suggestions = array();
		$errors      = array();

		foreach ( $specs as $spec ) {
			try {
				$suggestion = self::evaluate( $spec, $logger_args );
				if ( ! property_exists( $suggestion, 'is_visible' ) || $suggestion->is_visible ) {
					$suggestions[] = $suggestion;
				}
			} catch ( \Throwable $e ) {
				$errors[] = $e;
			}
		}

		return array(
			'suggestions' => $suggestions,
			'errors'      => $errors,
		);
	}
}
