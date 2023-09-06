<?php

/**
 * This script will generate the src/Internal/RestApi/Infrastructure/RestApiEngine.php file
 * from the template file (bin/RestApiEngineTemplate.php) and the controllers in
 * src/Internal/RestApi/Controllers.
 */

if ( version_compare( phpversion(), '8', '<' )) {
	echo "*** This script requires PHP 8.0 or newer.\n";
	exit(1);
}

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowUnauthenticatedAttribute;

$param_regex_replacements = [
	'int' => '[\d]+',
	'date' => '([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9])',
	//TODO: Add more aliases for useful/frequently used parameter regexes.
];

//* Include whatever is needed first.

include __DIR__ . '/../vendor/autoload.php';

$directories_to_include = [
	__DIR__ . '/../src/Internal/RestApi/Infrastructure/Attributes',
	__DIR__ . '/../src/Internal/RestApi/Controllers'
];

foreach( $directories_to_include as $directory ) {
	$files = array_diff( scandir( $directory ), array( '..', '.' ) );
	foreach( $files as $file ) {
		require_once $directory . '/' . $file;
	}
}

$results = [];
$class_count = 0;
$endpoint_count = 0;

//* Loop through all classes having a RestApiController attribute.

foreach( get_declared_classes() as $class_name ) {

	$reflection_class = new ReflectionClass( $class_name );
	$rest_api_controller_attributes = $reflection_class->getAttributes( RestApiControllerAttribute::class );

	if( empty( $rest_api_controller_attributes ) ) {
		continue;
	}

	$class_count++;

	$rest_api_controller_attribute = $rest_api_controller_attributes[0]->newInstance();

	//* Look for an AllowedRoles attribute.

	$allowed_roles_attribute = $reflection_class->getAttributes( AllowedRolesAttribute::class );
	if( empty( $allowed_roles_attribute ) ) {
		$class_allowed_roles = null;
	}
	else {
		$class_allowed_roles = $allowed_roles_attribute[0]->newInstance()->roles;
	}

	//* Look for an AllowUnauthenticated attribute.

	$allow_unauthenticated_attribute = $reflection_class->getAttributes( AllowUnauthenticatedAttribute::class );
	if( empty( $allow_unauthenticated_attribute ) ) {
		$class_allow_unauthenticated = false;
	}
	else {
		$class_allow_unauthenticated = $allow_unauthenticated_attribute[0]->newInstance()->allow_unauthenticated;
	}

	$class_endpoint_root = $rest_api_controller_attribute->root_path ?? '';

	$class_info = [ 'class_name' => $class_name, 'endpoints' => [], 'has_init' => false ];

	//* Now loop through all the static public methods having a RestApiEndpoint attribute.

	$class_methods = $reflection_class->getMethods( ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC );
	foreach( $class_methods as $method ) {

		if( $method->getName() === 'init' ) {
			$class_info[ 'has_init' ] = true;
		}

		$rest_api_endpoint_attributes = $method->getAttributes( RestApiEndpointAttribute::class );
		if( empty( $rest_api_endpoint_attributes ) ) {
			continue;
		}

		$endpoint_count++;

		//* Look for an AllowedRoles attribute.

		$allowed_roles_attribute = $method->getAttributes( AllowedRolesAttribute::class );
		if( empty( $allowed_roles_attribute ) ) {
			$method_allowed_roles = null;
		} else {
			$method_allowed_roles = $allowed_roles_attribute[0]->newInstance()->roles;
		}

		//* Look for an AllowUnauthenticated attribute.

		$allow_unauthenticated_attribute = $method->getAttributes( AllowUnauthenticatedAttribute::class );
		if( empty( $allow_unauthenticated_attribute ) ) {
			$method_allow_unauthenticated = null;
		}
		else {
			$method_allow_unauthenticated = $allow_unauthenticated_attribute[0]->newInstance()->allow_unauthenticated;
		}

		$rest_api_endpoint_attribute = $rest_api_endpoint_attributes[0]->newInstance();

		//* Compose the route.

		$route = $rest_api_endpoint_attribute->route;
		if( $route !== '' && $route[0] === '/' ) {
			$route = $class_endpoint_root . $route;
		} else {
			$route = $class_endpoint_root . '/' . $route;
		}

		if( $route[0] !== '/' ) {
			$route = '/' . $route;
		}

		foreach( $param_regex_replacements as $alias => $regex ) {
			$route = str_replace( ":$alias:", $regex, $route );
		}

		//* Register the endpoint info.

		$method_info = [
			'verbs' => $rest_api_endpoint_attribute->verb,
			'route' => $route,
			'class_method' => $method->getName(),
			'allowed_roles' => $method_allowed_roles ?? $class_allowed_roles,
			'allow_unauthenticated' => $method_allow_unauthenticated ?? $class_allow_unauthenticated
		];

		$class_info['endpoints'][] = $method_info;
	}

	$results[] = $class_info;
}

//* All class/method info collected, now generate the engine file from the template.

$template_file_contents = file_get_contents( __DIR__ . '/RestApiEngineTemplate.php' );
$template_replacement = '';

$endpoint_template = <<<END

				register_rest_route(
					'wc/v4',
					'%s',
					array(
						'methods'             => '%s',
						'callback'            => fn( \\WP_REST_Request \$request ) =>
							static::safe_invoke( '%s', '%s', \$request, '%s', %s ),
						'permission_callback' => %s,
					)
				);

END;

foreach( $results as $controller_data ) {
	$class_name = $controller_data['class_name'];
	foreach($controller_data['endpoints'] as $endpoint_data) {
		$template_replacement .= sprintf(
			$endpoint_template,
			$endpoint_data['route'],
			$endpoint_data['verbs'],
			$controller_data['class_name'],
			$endpoint_data['class_method'],
			$endpoint_data['allowed_roles'],
			$controller_data['has_init'] ? 'true' : 'false',
			$endpoint_data['allow_unauthenticated'] ? "'__return_true'" : "fn() => get_current_user_id() !== 0"
		);
	}
}

$template_file_contents = str_replace( '/* TEMPLATE CONTENTS */',  $template_replacement, $template_file_contents );
$template_file_contents = str_replace(
	'AUTOGENERATED_WARNING',
	"THIS IS AN AUTOMATICALLY GENERATED FILE. Do not make changes directly to this file.\n * Instead, make any necessary changes to bin/RestApiEngineTemplate.php.",
	$template_file_contents
);

file_put_contents( __DIR__ . '/../src/Internal/RestApi/Infrastructure/RestApiEngine.php', $template_file_contents );

echo "Done! A total of $endpoint_count REST API endpoints in $class_count controller classes were generated.\n";
