<?php

defined( 'ABSPATH' ) || exit;

register_woocommerce_admin_test_helper_rest_route(
	'/tools/load-template-version',
	'tools_load_template_version',
	array(
		'methods' => 'POST',
		'args'    => array(
			'template_name' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'version' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-available-templates',
	'tools_get_available_templates',
	array(
		'methods' => 'GET',
	)
);

register_woocommerce_admin_test_helper_rest_route(
	'/tools/get-available-versions',
	'tools_get_available_versions',
	array(
		'methods' => 'GET',
		'args'    => array(
			'template_name' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

/**
 * Get predefined templates with their versions and content.
 *
 * @return array Templates data.
 */
function get_predefined_templates() {
	$templates_dir = dirname( __FILE__ ) . '/templates/';
	$templates = array(
		'coming-soon' => array(
			'display_name' => 'Coming soon',
			'post_title' => 'Page: Coming soon',
			'post_excerpt' => 'Let your shoppers know your site or part of your site is under construction.',
			'versions'     => array(),
		),
	);

	// Define all available template versions and their files
	$template_files = array(
		'coming-soon' => array(
			'9.7.1 - Coming soon entire site' => $templates_dir . '9.7.1 - Coming soon entire site.html',
			'9.7.1 - Coming soon store only' => $templates_dir . '9.7.1 - Coming soon store only.html',
		),
	);

	// Load content from files for each template type
	foreach ( $template_files as $template_name => $versions ) {
		if ( isset( $templates[$template_name] ) ) {
			foreach ( $versions as $version => $file_path ) {
				if ( file_exists( $file_path ) ) {
					$content = file_get_contents( $file_path );

					$templates[$template_name]['versions'][$version] = array(
						'post_content' => $content,
						'post_title' => $templates[$template_name]['post_title'],
						'post_excerpt' => $templates[$template_name]['post_excerpt'],
					);
				}
			}
		}
	}

	return $templates;
}

/**
 * Load a template from a specific version.
 *
 * @param WP_REST_Request $request Request data.
 * @return WP_REST_Response|WP_Error Response object or error.
 */
function tools_load_template_version( $request ) {
	$template_name = $request->get_param( 'template_name' );
	$version = $request->get_param( 'version' );

	$templates = get_predefined_templates();

	// Check if template exists
	if ( ! isset( $templates[ $template_name ] ) ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => "Template not found: {$template_name}",
			),
			404
		);
	}

	// Check if version exists
	if ( ! isset( $templates[ $template_name ]['versions'][ $version ] ) ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => "Template version not found: {$template_name} {$version}",
			),
			404
		);
	}

	// Remove any customizations
	$template = get_block_template( "woocommerce/woocommerce//{$template_name}", 'wp_template' );
	if ( $template && isset( $template->wp_id ) ) {
		$delete_result = wp_delete_post( $template->wp_id, true );
		if ( false === $delete_result ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => "Failed to delete the {$template_name} template.",
				),
				500
			);
		}
	}

	// Create a new customized version
	$template_post = array(
		'post_title'    => '',
		'post_content'  => '',
		'post_status'   => 'publish',
		'post_type'     => 'wp_template',
		'post_name'     => $template_name,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	foreach ( $templates[ $template_name ]['versions'][ $version ] as $key => $value ) {
		$template_post[ $key ] = $value;
	}

	$template_id = wp_insert_post( $template_post );

	if ( is_wp_error( $template_id ) ) {
		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => 'Failed to create template: ' . $template_id->get_error_message(),
			),
			500
		);
	}

	// Set template metadata and taxonomy terms
	update_post_meta( $template_id, 'theme', 'woocommerce/woocommerce' );
	wp_set_object_terms( $template_id, 'wp_template', 'wp_template_type' );
	wp_set_object_terms( $template_id, 'woocommerce/woocommerce', 'wp_theme' );

	return new \WP_REST_Response(
		array(
			'success' => true,
			'message' => "Created new custom template for '{$template_name}' using version {$version}.",
		),
		200
	);
}

/**
 * Get all available templates.
 *
 * @return WP_REST_Response Response with available templates.
 */
function tools_get_available_templates() {
	$templates = get_predefined_templates();
	$available_templates = array();

	foreach ( $templates as $template_name => $template_data ) {
		$available_templates[ $template_name ] = $template_data['display_name'];
	}

	return new \WP_REST_Response( $available_templates, 200 );
}

/**
 * Get available versions for a specific template.
 *
 * @param WP_REST_Request $request Request data.
 * @return WP_REST_Response Response with available versions.
 */
function tools_get_available_versions( $request ) {
	$template_name = $request->get_param( 'template_name' );
	$templates = get_predefined_templates();

	if ( ! isset( $templates[ $template_name ] ) ) {
		return new \WP_REST_Response( array(), 200 );
	}

	$versions = array_keys( $templates[ $template_name ]['versions'] );
	// Sort versions in ascending order
	sort( $versions );

	return new \WP_REST_Response( $versions, 200 );
}
