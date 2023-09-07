<?php

/**
 * This script will generate one html page for each existing REST API controller
 * inside the plugins/woocommerce/bin/rest-api-docs/doc directory.
 * Files will be named "rest-api-" followed by a name composed from the sidebar path
 * or the class name of the controller (see the filename_for_controller function).
 *
 * What the script pretty much does is taking the data returned by 'extract-rest-api-doc.php',
 * convert it to the format expected by the GitHub REST API page JavaScript (using the contents of
 * page-data-template.json as the base since there's a lot of data there that doesn't need changes),
 * and pass it to page-template.php which will JSONize it and put it inside a <script> tag
 * (page-template.php is otherwise a static HTML page).
 */

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\Documentation\Enums\InputParameterLocation;

include_once __DIR__ . '/../../vendor/autoload.php';

include __DIR__ . '/extract-rest-api-doc.php';

$parsedown = new Parsedown();

$rest_api_data = extract_info_from_attributes();
$page_data_template = json_decode(file_get_contents(__DIR__ . '/doc/page-data-template.json'), JSON_OBJECT_AS_ARRAY);
$page_data = $page_data_template;

foreach($rest_api_data['controllers'] as $controller_info) {
	build_page($controller_info);
}


function build_page($controller_info) {
	global $page_data_template, $page_data;

	$page_data = $page_data_template;
	adjust_data($controller_info);

	ob_start();
	include __DIR__ . '/doc/page-template.php';
	$page_content = ob_get_clean();
	file_put_contents(__DIR__ . '/doc/' . filename_for_controller($controller_info), $page_content);
}


function adjust_data($controller_info) {
	build_sidebar($controller_info);

	build_header($controller_info);
	build_endpoints_data($controller_info);
}


function build_sidebar($current_controller_info) {
	global $rest_api_data, $page_data;

	//$page_data['props']['pageProps'] = &$page_data['props']['pageProps'];

	$child_pages = [];
	foreach($rest_api_data['controllers'] as $controller) {
		$is_current_controller = $controller['class_name'] === $current_controller_info['class_name'];

		$sidebar_path = $controller['sidebar_path'] ?? title_from_class_name($controller['class_name']);

		$sidebar_path_parts = explode('/', $sidebar_path);
		$sidebar_path_parts_lower = array_map(fn($x) => strtolower($x), $sidebar_path_parts);
		$child_page_data = [
			'href' => './' . filename_for_controller($controller),
			'title' => $sidebar_path_parts[0],
			'childPages' => []
		];
		$child_pages_for_controller = &$child_page_data['childPages'];
		if(count($sidebar_path_parts) > 1) {
			$child_page_data['childPages'][] = [
				'href' => './' . filename_for_controller($controller),
				'title' => $sidebar_path_parts[1],
				'childPages' => []
			];
			$child_pages_for_controller = &$child_page_data['childPages'][0]['childPages'];
		}

		foreach($controller['endpoints'] as $endpoint_info) {
			$route = '/' . implode('/', [$controller['root_path'], $endpoint_info['http_route']]);
			$element_id = to_snake_case( str_replace(' ', '-', $endpoint_info['description_title'] ?? ''));
			$href = $is_current_controller ?
				'#' . $element_id :
				'./' . filename_for_controller($controller) . '#' . $element_id;
			$child_pages_for_controller[] = [
				'href' => $href, //'/rest/' . implode('/', $sidebar_path_parts_lower) . '#' . to_snake_case( str_replace(' ', '-', $endpoint_info['description_title'] ?? '')),
				'title' => $endpoint_info['description_title'] ?? $endpoint_info['http_verb'] . ' ' . $route,
				'childPages' => []
			];
		}

		$child_pages[] = $child_page_data;
	}

	$page_data['props']['pageProps']['mainContext']['sidebarTree'] = [
		'href' => '/rest',
		'title' => 'REST API',
		'childPages' => $child_pages
	];
}


function build_header($controller_data) {
	global $page_data, $parsedown;

	//$page_data['props']['pageProps'] = &$page_data['props']['pageProps'];

	$title = $controller_data['title'] ?? title_from_class_name($controller_data['class_name']) . ' (missing category title)';
	$description = $controller_data['description'] ?? "Endpoints for " . title_from_class_name($controller_data['class_name']) . ' (missing category subtitle)';

	$page_data['props']['pageProps']['automatedPageContext']['title'] = $title;
	$page_data['props']['pageProps']['automatedPageContext']['intro'] = '<p>' . $description . '</p>';

	if(empty($controller_data['about_text'])) {
		$page_data['props']['pageProps']['automatedPageContext']['renderedPage'] = '';
	} else {
		$about_title = $controller_data['about_title'] ?? 'About ' . title_from_class_name($controller_data['class_name']) . ' (missing about title)';
		$element_id = to_snake_case($about_title);
		$about_text = $parsedown->text($controller_data['about_text']);
		$rendered_about = "<h2 id=\"$element_id\" tabindex=\"-1\"><a class=\"heading-link\" href=\"#$element_id\">$about_title<span class=\"heading-link-symbol\" aria-hidden=\"true\"></span></a></h2>\n<p>$about_text</p>";
		$page_data['props']['pageProps']['automatedPageContext']['renderedPage'] = $rendered_about;
	}

	$page_data['props']['pageProps']['mainContext']['page']['title'] = $title;
	$page_data['props']['pageProps']['mainContext']['page']['fullTitle'] = $title . ' - WooCommerce REST API docs';
}


function build_endpoints_data($controller_info) {
	global $page_data;

	//$page_data['props']['pageProps'] = &$page_data['props']['pageProps'];

	$result = [];
	foreach($controller_info['endpoints'] as $endpoint_info) {
		$result[] = build_endpoint_data($endpoint_info, $controller_info);
	}
	$page_data['props']['pageProps']['restOperations'] = $result;
}


function build_endpoint_data($endpoint_info, $controller_info) {
	global $parsedown, $page_data;

	//$page_data['props']['pageProps'] = &$page_data['props']['pageProps'];

	$data_template = $page_data['props']['pageProps']['restOperations'][0];

	$data_template['serverUrl'] = 'https://www.example.com';
	$data_template['verb'] = strtolower($endpoint_info['http_verb']);
	$route = '/' . implode('/', [$controller_info['root_path'], $endpoint_info['http_route']]);
	$route = preg_replace('/\(\?\<([^>]+)[^)]*\)/', '{$1}', $route);
	$data_template['requestPath'] = $route;
	$data_template['title'] = $endpoint_info['description_title'] ?? $endpoint_info['http_verb'] . ' ' . $route . ' (missing endpoint description title)';
	$data_template['descriptionHTML'] = $parsedown->text($endpoint_info['description_text'] ??  '(missing endpoint description text)');

	$data_template['codeExamples'][0]['request']['parameters'] = [];
	$parameters = [];
	foreach($endpoint_info['input_parameters'] as $parameter_info) {
		if($parameter_info['location'] == InputParameterLocation::Body) {
			continue;
		}

		$parameter_data = [
			'name' => $parameter_info['name'],
			'description' => $parsedown->text($parameter_info['description'] ?? ''),
			'in' => $parameter_info['location'],
			'required' => $parameter_info['required'],
			'schema' => [
				'type' => $parameter_info['type']
			]
		];

		if(!$parameter_info['required']) {
			$parameter_data['schema']['default'] = $parameter_info['default'];
		}

		$parameters[] = $parameter_data;

		$data_template['codeExamples'][0]['request']['parameters'][$parameter_info['name']] = strtoupper($parameter_info['name']);
	}
	$data_template['parameters'] = $parameters;

	$http_status_codes = [];
	foreach($endpoint_info['http_status_codes'] as $status_code_info) {
		$http_status_codes[] = [
			'httpStatusCode' => $status_code_info['code'],
			'description' => $parsedown->text($status_code_info['description'])
		];
	}

	if(empty($http_status_codes)) {
		$http_status_codes[] = [
			'httpStatusCode' => 200,
			'description' => '<p>Ok</p>'
		];
	}

	$data_template['statusCodes'] = $http_status_codes;

	return $data_template;
}


function title_from_class_name($class_name) {
	$title = explode('\\', $class_name);
	return str_replace( 'Controller', '', end($title));
}


function to_snake_case($input) {
	return
	str_replace('.', '',
		str_replace(' ', '-',
			preg_replace('/(?<!^)[A-Z]/', '-$0', strtolower($input)
			)
		)
	);
}


function filename_for_controller($controller_info) {
	$value = 'rest-api-' . to_snake_case($controller_info['sidebar_path'] ?? title_from_class_name($controller_info['class_name'])) . '.html';
	return str_replace('/', '-', $value);
}
