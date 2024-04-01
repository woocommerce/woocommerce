<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\RuleEvaluator;

register_woocommerce_admin_test_helper_rest_route(
	'/remote-spec-validator/validate',
	'wca_test_helper_validate_remote_spec',
	array(
		'methods' => 'POST',
		'args'	=> array(
			'spec' => array(
				'description'       => 'The remote spec to validate.',
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

/**
 * @param WP_REST_Request $request The full request data.
 */
function wca_test_helper_validate_remote_spec( $request ) {
	$spec = json_decode( $request->get_param( 'spec' ) );
	$rule_evaluator = new RuleEvaluator();
	$result = [
		'valid' => $rule_evaluator->evaluate( $spec ),
	];
	
	return new WP_REST_RESPONSE( $result, 200 );
}
