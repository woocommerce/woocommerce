<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-logging-levels/v1',
	'tools_get_logging_levels',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-block-template-logging-threshold/v1',
	'tools_get_block_template_logging_threshold',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/update-block-template-logging-threshold/v1',
	'tools_update_block_template_logging_threshold',
	array(
		'methods' => 'POST',
		'args'    => array(
			'threshold' => array(
				'description'       => 'Logging threshold',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

/**
 * Get the list of logging levels.
 */
function tools_get_logging_levels() {
	$levels = array(
		array(
			'label' => 'Emergency',
			'value' => \WC_Log_Levels::EMERGENCY,
		),
		array(
			'label' => 'Alert',
			'value' => \WC_Log_Levels::ALERT,
		),
		array(
			'label' => 'Critical',
			'value' => \WC_Log_Levels::CRITICAL,
		),
		array(
			'label' => 'Error',
			'value' => \WC_Log_Levels::ERROR,
		),
		array(
			'label' => 'Warning',
			'value' => \WC_Log_Levels::WARNING,
		),
		array(
			'label' => 'Notice',
			'value' => \WC_Log_Levels::NOTICE,
		),
		array(
			'label' => 'Info',
			'value' => \WC_Log_Levels::INFO,
		),
		array(
			'label' => 'Debug',
			'value' => \WC_Log_Levels::DEBUG,
		),
	);

	return new WP_REST_Response( $levels, 200 );
}

/**
 * Get the block template logging threshold.
 */
function tools_get_block_template_logging_threshold() {
	$threshold = get_option( 'woocommerce_block_template_logging_threshold', \WC_Log_Levels::WARNING );

	return new WP_REST_Response( $threshold, 200 );
}

/**
 * Update the block template logging threshold.
 *
 * @param WP_REST_Request $request The full request data.
 */
function tools_update_block_template_logging_threshold( $request ) {
	$threshold = $request->get_param( 'threshold' );

	if ( ! isset( $threshold ) || ! \WC_Log_Levels::is_valid_level( $threshold ) ) {
		return new WP_REST_Response( 'Invalid threshold', 400 );
	}

	update_option( 'woocommerce_block_template_logging_threshold', $threshold );

	return new WP_REST_Response( $threshold, 200 );
}
