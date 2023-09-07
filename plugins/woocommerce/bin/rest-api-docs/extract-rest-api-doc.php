<?php

/**
 * This script will extract all the documentation related attributes from the REST API controllers,
 * DTOs and enums, and return an array with all the information.
 *
 * Usage: include the file and run extract_info_from_attributes().
 */

if (version_compare(phpversion(), '8.1', '<')) {
	echo "*** This script requires PHP 8.1 or newer.\n";
	exit(1);
}

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\ArrayTypeAttribute as ArrayType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionAttribute as Description;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DescriptionTitleAttribute as DescriptionTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\DtoAttribute as Dto;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\HttpStatusCodeAttribute as HttpStatusCode;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\InputTypeAttribute as InputType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\OutputTypeAttribute as OutputType;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowUnauthenticatedAttribute;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\AboutTitleAttribute as AboutTitle;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\AboutTextAttribute as AboutText;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\SidebarPathAttribute as SidebarPath;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\InputParameterAttribute as InputParameter;


include_once __DIR__ . '/../../vendor/autoload.php';


function extract_info_from_attributes() {
	$directories_to_include = [
		__DIR__ . '/../../src/Internal/RestApi/Infrastructure/Attributes',
		__DIR__ . '/../../src/Internal/RestApi/Controllers'
	];

	include_files($directories_to_include);

	$declared_classes = get_declared_classes();

	return [
		'controllers' => extract_info_from_controllers($declared_classes),
		'types' => extract_info_from_types($declared_classes)
	];
}


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
		[SidebarPath::class, 'path', 'sidebar_path'],
		[DescriptionTitle::class, 'title', 'title'],
		[Description::class, 'description', 'description'],
		[AboutTitle::class, 'title', 'about_title'],
		[AboutText::class, 'text', 'about_text']
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
		[Description::class, 'description', 'description_text'],
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
			'type' => maybe_invoke($input_parameter_attribute->type),
			'description' => maybe_invoke($input_parameter_attribute->description),
			'location' => $input_parameter_attribute->location,
			'required' => $input_parameter_attribute->required
		];

		if(!$input_parameter_attribute->required) {
			$parameter_info['default'] = $input_parameter_attribute->default;
		}

		$input_parameters[] = $parameter_info;
	}

	$endpoint_info['input_parameters'] = $input_parameters;

	$http_status_codes = [];
	$http_status_code_attributes = $method_reflection_object->getAttributes(HttpStatusCode::class);
	foreach($http_status_code_attributes as $http_status_code_attribute) {
		$http_status_code_attribute = $http_status_code_attribute->newInstance();
		$http_status_codes[] = [
			'code' => $http_status_code_attribute->code,
			'description' => maybe_invoke($http_status_code_attribute->description)
		];
	}

	$endpoint_info['http_status_codes'] = $http_status_codes;

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

