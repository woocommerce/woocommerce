<?php

/**
 * This is a VERY CRUDE example of how REST API documentation can be automatically generated
 * from the attributes that define the classes and their endpoints.
 * It will generate a bin/rest-api-doc.html file.
 */

if (version_compare(phpversion(), '8.1', '<')) {
	echo "*** This script requires PHP 8.1 or newer.\n";
	exit(1);
}

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\ArrayTypeAttribute as ArrayType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionTitleAttribute as DescriptionTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionTextAttribute as DescriptionText;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\InputTypeAttribute as InputType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\OutputTypeAttribute as OutputType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowUnauthenticatedAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\CategoryAboutTitleAttribute as CategoryAboutTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\CategoryAboutTextAttribute as CategoryAboutText;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\CategoryPathAttribute as CategoryPath;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\CategoryTitleAttribute as CategoryTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\CategorySubtitleAttribute as CategorySubtitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\InputParameterAttribute as InputParameter;


include __DIR__ . '/../vendor/autoload.php';

function include_files(array $directories) {
	foreach($directories as $directory) {
		$files = array_diff(scandir($directory), array('..', '.'));
		foreach($files as $file) {
			$full_path = $directory . '/' . $file;
			if(is_dir($full_path)) {
				include_files([$full_path]);
			}
			else {
				require_once $full_path;
			}
		}
	}
}

function maybe_invoke(string $value) {
	if(!str_starts_with($value, 'invoke::')) {
		return $value;
	}

	$value = substr($value, strlen('invoke::'));
	return call_user_func('Automattic\\WooCommerce\\Internal\\RestApi\\Infrastructure\\Attributes\\Documentation\\TextHolders\\' . $value);
}


function maybe_get_single_attribute(object $reflection_class, string $attribute_class_name) {
	$attributes = $reflection_class->getAttributes($attribute_class_name);
	if(empty($attributes)) {
		return null;
	}

	return current($attributes)->newInstance();
}


function extract_info_from_attributes() {
	$directories_to_include = [
		__DIR__ . '/../src/Internal/RestApi/Infrastructure/Attributes',
		__DIR__ . '/../src/Internal/RestApi/Controllers'
	];

	include_files($directories_to_include);

	$declared_classes = get_declared_classes();

	return [
		'controllers' => extract_info_from_controllers($declared_classes),
		'types' => extract_info_from_types($declared_classes)
	];
}


function extract_info_from_controllers(array $declared_classes) {
	$controllers = [];

	foreach ($declared_classes as $class_name) {
		$reflection_class = new ReflectionClass($class_name);
		$rest_api_controller_attributes = $reflection_class->getAttributes(RestApiControllerAttribute::class);

		if (empty($rest_api_controller_attributes)) {
			continue;
		}

		$controller_info = extract_controller_info($reflection_class, current($rest_api_controller_attributes)->newInstance());
		$controllers[] = $controller_info;
	}

	return $controllers;
/*
		$rest_api_controller_attribute = $rest_api_controller_attributes[0]->newInstance();

		$class_name_parts = explode('\\', $class_name);
		end($class_name_parts);
		$class_name_without_namespace = current($class_name_parts);
		$result .= "\n<h1>$class_name_without_namespace</h1>\n";

		$description_attribute = $reflection_class->getAttributes(DescriptionAttribute::class);
		if (!empty($description_attribute)) {
			$description = $description_attribute[0]->newInstance()->description;
			if (str_starts_with($description, '::')) {
				$description = ($class_name . $description)();
			}
			$description = str_replace("\n", "<br/>\n", $description);
			$result .= "\n<div>$description</div>\n";
		}

		$allowed_roles_attribute = $reflection_class->getAttributes(AllowedRolesAttribute::class);
		if (!empty($allowed_roles_attribute)) {
			$roles = $allowed_roles_attribute[0]->newInstance()->roles;
			$result .= "\n<p>Default allowed roles: $roles</p>\n";
		}

		$allow_unauthenticated_attribute = $reflection_class->getAttributes(AllowUnauthenticatedAttribute::class);
		if (empty($allow_unauthenticated_attribute)) {
			$class_allow_unauthenticated = 'NO';
		} else {
			$class_allow_unauthenticated = $allow_unauthenticated_attribute[0]->newInstance()->allow_unauthenticated ? 'YES' : 'NO';
		}
		$result .= "\n<p>Default allow unauthenticated: $class_allow_unauthenticated</p>\n";

		$class_methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
		foreach ($class_methods as $method) {
			$method_attributes = $method->getAttributes(RestApiEndpointAttribute::class);
			if (empty($method_attributes)) {
				continue;
			}

			$endpoint_attribute = $method_attributes[0]->newInstance();
			$route = 'wp-json/wc/v4/' . $rest_api_controller_attribute->root_path . '/' . $endpoint_attribute->route;
			$route = str_replace('//', '/', $route);
			$result .= "\n<h2>$route</h2>\n";

			$description_attribute = $method->getAttributes(DescriptionAttribute::class);
			if (!empty($description_attribute)) {
				$description = $description_attribute[0]->newInstance()->description;
				if (str_starts_with($description, '::')) {
					$description = ($class_name . $description)();
				}
				$description = str_replace("\n", "<br/>\n", $description);
				$result .= "\n<div>$description</div>\n";
			}

			$verbs = $endpoint_attribute->verb;
			$result .= "\n<p>Verbs: $verbs</p>\n";

			$allowed_roles_attribute = $method->getAttributes(AllowedRolesAttribute::class);
			if (!empty($allowed_roles_attribute)) {
				$roles = $allowed_roles_attribute[0]->newInstance()->roles;
				$result .= "\n<p>Allowed roles: $roles</p>\n";
			}

			$allow_unauthenticated_attribute = $method->getAttributes(AllowUnauthenticatedAttribute::class);
			if (!empty($allow_unauthenticated_attribute)) {
				$method_allow_unauthenticated = $allow_unauthenticated_attribute[0]->newInstance()->allow_unauthenticated ? 'YES' : 'NO';
				$result .= "\n<p>Allow unauthenticated: $class_allow_unauthenticated</p>\n";
			}
		}
	}

	$result .= "\n</body></html>";

	file_put_contents(__DIR__ . '/rest-api-doc.html', $result);
	echo "Done, generated file: " . __DIR__ . "/rest-api-doc.html\n";
*/
}


function add_info_if_attribute_present(object $reflection_class, array &$data, array $params) {
	foreach($params as $param) {
		$attribute_instance = maybe_get_single_attribute($reflection_class, $param[0]);
		if($attribute_instance !== null) {
			$data[$param[2]] = maybe_invoke($attribute_instance->{$param[1]});
		}
	}
}

function extract_controller_info(ReflectionClass $reflection_class, RestApiControllerAttribute $rest_api_controller_attribute) {
	$controller_info = ['endpoints' => []];

	$controller_info['class_name'] = $reflection_class->getName();
	$controller_info['root_path'] = maybe_invoke($rest_api_controller_attribute->root_path);

	$simple_controller_docs_attributes = [
		[CategoryPath::class, 'path', 'category_path'],
		[CategoryTitle::class, 'title', 'category_title'],
		[CategorySubtitle::class, 'subtitle', 'category_subtitle'],
		[CategoryAboutTitle::class, 'title', 'about_title'],
		[CategoryAboutText::class, 'text', 'about_text']
	];

	add_info_if_attribute_present($reflection_class, $controller_info, $simple_controller_docs_attributes);

	$class_methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
	foreach ($class_methods as $method) {
		$method_attributes = $method->getAttributes(RestApiEndpointAttribute::class);
		if (empty($method_attributes)) {
			continue;
		}

		foreach($method_attributes as $method_attribute) {
			$controller_info['endpoints'][] = extract_endpoint_info($method, $method_attribute->newInstance());
		}
	}

	return $controller_info;
}


function extract_endpoint_info(ReflectionMethod $method_reflection_object, RestApiEndpointAttribute $rest_api_endpoint_attribute) {
	$endpoint_info = [];

	$endpoint_info['method_name'] = $method_reflection_object->getName();
	$endpoint_info['http_verb'] = maybe_invoke($rest_api_endpoint_attribute->verb);
	$endpoint_info['http_route'] = maybe_invoke($rest_api_endpoint_attribute->route);

	$simple_endpoint_docs_attributes = [
		[DescriptionTitle::class, 'title', 'description_title'],
		[DescriptionText::class, 'text', 'description_text'],
		[InputType::class, 'type_name', 'input_type'],
		[OutputType::class, 'type_name', 'output_type'],
	];

	add_info_if_attribute_present($method_reflection_object, $endpoint_info, $simple_endpoint_docs_attributes);

	$input_parameters = [];
	$input_parameter_attributes = $method_reflection_object->getAttributes(InputParameter::class);
	foreach($input_parameter_attributes as $input_parameter_attribute) {
		$input_parameter_attribute = $input_parameter_attribute->newInstance();
		$parameter_info = [
			'name' => maybe_invoke($input_parameter_attribute->name),
			'description' => maybe_invoke($input_parameter_attribute->description),
			'location' => $input_parameter_attribute->location,
			'required' => $input_parameter_attribute->required
		];

		if(!$input_parameter_attribute->required) {
			$parameter_info['default'] = $input_parameter_attribute->default;
		}

		$input_parameters[] = $parameter_info;
	}

	if(!empty($input_parameters)) {
		$endpoint_info['input_parameters'] = $input_parameters;
	}

	return $endpoint_info;
}


function extract_info_from_types(array $declared_classes) {
	$types = [];

	foreach ($declared_classes as $class_name) {
		$reflection_class = enum_exists($class_name) ? new ReflectionEnum($class_name) : new ReflectionClass($class_name);
		$dto_attributes = $reflection_class->getAttributes(Dto::class);

		if (empty($dto_attributes)) {
			continue;
		}

		$type_info = extract_type_info($reflection_class, current($dto_attributes)->newInstance());
		$types[] = $type_info;
	}

	return $types;
}


function extract_type_info(ReflectionClass $reflection_class, Dto $dto_attribute) {
	$type_info = ['properties' => []];

	$type_info['class_name'] = $reflection_class->getName();
	$type_info['include_in_docs'] = $dto_attribute->include_in_documentation;
	if($dto_attribute->include_in_documentation) {
		$type_info['type_name'] = maybe_invoke($dto_attribute->name);
	}

	$attribute_instance = maybe_get_single_attribute($reflection_class, DescriptionAttribute::class);
	if($attribute_instance !== null) {
		$type_info['description'] = maybe_invoke($attribute_instance->description);
	}

	if($reflection_class->isEnum()) {
		$type_info['type_of_type'] = 'enum';
		$is_backed = $reflection_class->isBacked();
		if($is_backed) {
			$type_info['backing_type'] = $reflection_class->getBackingType()->getName();
		}
		$type_info['cases'] = [];
		$cases = $reflection_class->getCases();
		foreach ($cases as $case) {
			$type_info['cases'][] = extract_case_info($case, $is_backed);
		}
	} else {
		$type_info['type_of_type'] = 'class';
		$type_info['properties'] = [];
		$properties = $reflection_class->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property) {
			$type_info['properties'][] = extract_property_info($property);
		}
	}

	return $type_info;
}


function extract_property_info(ReflectionProperty $reflection_property) {
	$property_info = [];

	$property_info['property_name'] = $reflection_property->getName();
	$property_info['property_type'] = $reflection_property->getType()->getName();
	$property_info['required'] = !$reflection_property->getType()->allowsNull();
	if($reflection_property->getType()->allowsNull()) {
		$property_info['default_value'] = $reflection_property->getDefaultValue();
	}
	if($reflection_property->getType()->getName() === 'array') {
		$array_type = maybe_get_single_attribute($reflection_property, ArrayType::class);
		if($array_type !== null) {
			$property_info['array_type'] = $array_type->type_name;
		}
	}

	$attribute_instance = maybe_get_single_attribute($reflection_property, DescriptionAttribute::class);
	if($attribute_instance !== null) {
		$property_info['description'] = maybe_invoke($attribute_instance->description);
	}

	return $property_info;
}


function extract_case_info(object $reflected_enum_case, bool $is_backed) {
	$case_info = [];

	$case_info['name'] = $reflected_enum_case->getName();
	if($is_backed) {
		$case_info['value'] = $reflected_enum_case->getValue();
	}

	$attribute_instance = maybe_get_single_attribute($reflected_enum_case, DescriptionAttribute::class);
	if($attribute_instance !== null) {
		$case_info['description'] = maybe_invoke($attribute_instance->description);
	}

	return $case_info;
}

echo json_encode(extract_info_from_attributes(), JSON_PRETTY_PRINT);
