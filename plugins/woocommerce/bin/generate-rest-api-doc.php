<?php

/**
 * This is a VERY CRUDE example of how REST API documentation can be automatically generated
 * from the attributes that define the classes and their endpoints.
 * It will generate a bin/rest-api-doc.html file.
 */

if (version_compare(phpversion(), '8', '<')) {
	echo "*** This script requires PHP 8.0 or newer.\n";
	exit(1);
}

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\DescriptionAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowUnauthenticatedAttribute;

include __DIR__ . '/../vendor/autoload.php';

$directories_to_include = [
	__DIR__ . '/../src/Internal/RestApi/Infrastructure/Attributes',
	__DIR__ . '/../src/Internal/RestApi/Controllers'
];

foreach($directories_to_include as $directory) {
	$files = array_diff(scandir($directory), array('..', '.'));
	foreach($files as $file) {
		require_once $directory . '/' . $file;
	}
}

$result = '<html><body>';

foreach(get_declared_classes() as $class_name) {
	$reflection_class = new ReflectionClass($class_name);
	$rest_api_controller_attributes = $reflection_class->getAttributes(RestApiControllerAttribute::class);

	if (empty($rest_api_controller_attributes)) {
		continue;
	}

	$rest_api_controller_attribute = $rest_api_controller_attributes[0]->newInstance();

	$class_name_parts = explode('\\', $class_name);
	end($class_name_parts);
	$class_name_without_namespace = current($class_name_parts);
	$result .= "\n<h1>$class_name_without_namespace</h1>\n";

	$description_attribute = $reflection_class->getAttributes(DescriptionAttribute::class);
	if(!empty($description_attribute)) {
		$description = $description_attribute[0]->newInstance()->description;
		if(str_starts_with($description, '::')) {
			$description = ($class_name . $description)();
		}
		$description = str_replace("\n", "<br/>\n", $description);
		$result .= "\n<div>$description</div>\n";
	}

	$allowed_roles_attribute = $reflection_class->getAttributes(AllowedRolesAttribute::class);
	if(!empty($allowed_roles_attribute)) {
		$roles = $allowed_roles_attribute[0]->newInstance()->roles;
		$result .= "\n<p>Default allowed roles: $roles</p>\n";
	}

	$allow_unauthenticated_attribute = $reflection_class->getAttributes(AllowUnauthenticatedAttribute::class);
	if(empty($allow_unauthenticated_attribute)) {
		$class_allow_unauthenticated = 'NO';
	}
	else {
		$class_allow_unauthenticated = $allow_unauthenticated_attribute[0]->newInstance()->allow_unauthenticated ? 'YES' : 'NO';
	}
	$result .= "\n<p>Default allow unauthenticated: $class_allow_unauthenticated</p>\n";

	$class_methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
	foreach($class_methods as $method) {
		$method_attributes = $method->getAttributes(RestApiEndpointAttribute::class);
		if (empty($method_attributes)) {
			continue;
		}

		$endpoint_attribute = $method_attributes[0]->newInstance();
		$route = 'wp-json/wc/v4/' . $rest_api_controller_attribute->root_path . '/' . $endpoint_attribute->route;
		$route = str_replace('//', '/', $route);
		$result .= "\n<h2>$route</h2>\n";

		$description_attribute = $method->getAttributes(DescriptionAttribute::class);
		if(!empty($description_attribute)) {
			$description = $description_attribute[0]->newInstance()->description;
			if(str_starts_with($description, '::')) {
				$description = ($class_name . $description)();
			}
			$description = str_replace("\n", "<br/>\n", $description);
			$result .= "\n<div>$description</div>\n";
		}

		$verbs = $endpoint_attribute->verb;
		$result .= "\n<p>Verbs: $verbs</p>\n";

		$allowed_roles_attribute = $method->getAttributes(AllowedRolesAttribute::class);
		if(!empty($allowed_roles_attribute)) {
			$roles = $allowed_roles_attribute[0]->newInstance()->roles;
			$result .= "\n<p>Allowed roles: $roles</p>\n";
		}

		$allow_unauthenticated_attribute = $method->getAttributes(AllowUnauthenticatedAttribute::class);
		if(!empty($allow_unauthenticated_attribute)) {
			$method_allow_unauthenticated = $allow_unauthenticated_attribute[0]->newInstance()->allow_unauthenticated ? 'YES' : 'NO';
			$result .= "\n<p>Allow unauthenticated: $class_allow_unauthenticated</p>\n";
		}
	}
}

$result .= "\n</body></html>";

file_put_contents(__DIR__ . '/rest-api-doc.html', $result);
echo "Done, generated file: " . __DIR__ . "/rest-api-doc.html\n";
