<?php

namespace Automattic\WooCommerce\Internal\Api\DesignTime\Scripts;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionMethod;
use ReflectionProperty;

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.YodaConditions.NotYoda

/**
 * The WooCommerce API builder class.
 * The entry point is the "run" method.
 */
class ApiBuilder {
	private static $curl;

	private static $url_argument_regexes = [
		'\w+' => 'x',
		'\d+' => '0',
		'[a-z]{3}_[a-zA-Z0-9]{24}' => 'aaa_aaaaaaaaaaaaaaaaaaaaaaa',
		'[a-z0-9_]+' => 'x',
		'[A-Za-z]{3}' => 'xxx',
		'[\d]+' => '0',
		'\w[\w\s\-]*' => 'x',
		'[\w-]+' => 'x',
		'[\w-]{3}' => 'xxx'
	];

	public static function run(): void {
		if (version_compare(phpversion(), '8', '<')) {
			echo "*** This script requires PHP 8.0 or newer.\n";
			exit(1);
		}

		if(!function_exists('curl_version')) {
			echo "*** This script requires the PHP curl extension.\n";
			exit(1);
		}

		self::$curl = curl_init();
		if(false === self::$curl) {
			echo "*** CURL initialization failed.\n";
			exit(1);
		}

		$server_url = getenv('SERVER_URL');
		if(!$server_url) {
			$server_url = "http://localhost";
		}
		$server_url = trim($server_url, '/');

		echo "Server URL: $server_url\n";

		$endpoint_url = getenv('ENDPOINT_URL');
		if($endpoint_url) {
			$endpoint_urls = explode('|', $endpoint_url);
			for($i=0; $i<count($endpoint_urls); $i++) {
				$url = $endpoint_urls[$i];
				if (str_starts_with($url, $server_url)) {
					$url = str_replace($server_url . '/wp-json', '', $url);
				}
				if (!str_starts_with($url, '/wc/v3')) {
					$url = '/wc/v3/' . $url;
				}
				$endpoint_urls[$i] = $url;
			}

			if(count($endpoint_urls) === 1) {
				echo "Endpoint URL: $endpoint_urls[0]\n";
			}
			else {
				echo "Endpoint URLs:\n";
				foreach($endpoint_urls as $url) {
					echo "  $url\n";
				}
			}
		}

		echo "\n";

		//* Get list of routes

		if($endpoint_url) {
			$routes = $endpoint_urls;
		}
		else {
			$full_schema_url = $server_url . '/wp-json/wc/v3';
			$full_schema_info = self::query_server($full_schema_url, 'GET');
			if (is_string($full_schema_info)) {
				echo "*** Failed to retrieve the full API schema: $full_schema_info\n";
				echo "Queried URL: $full_schema_url\n";
				exit(1);
			}

			$routes = array_keys($full_schema_info['routes']);
			$routes = array_diff($routes, ['/wc/v3']);
		}

		//* Transform URL argument regexes

		$transformed_routes = [];
		foreach($routes as $route) {
			$matches = [];
			preg_match_all('/(?<=\(\?P)[^)]*/', $route, $matches);
			foreach($matches[0] as $match) {
				$unnamed_match = preg_replace('/\<[^>]*\>/', '', $match);
				$match_replacement = self::$url_argument_regexes[$unnamed_match] ?? null;
				if($match_replacement === null) {
					echo "*** Route \"$route\": regex \"\{$unnamed_match}\" is not in the replacements array.\n";
					exit(1);
				}
				$route = str_replace("(?P$match)", self::$url_argument_regexes[$unnamed_match], $route);
			}
			$transformed_routes[] = $route;
		}

		//* Get the route details for each route

		$routes_info = [];
		$i = 1;
		$count = count($routes);
		foreach($transformed_routes as $route) {
			echo "$i/$count - $route\n";
			$route_data = self::query_server( $server_url . '/wp-json' . $route, 'OPTIONS');
			if(is_string($route_data)) {
				echo "*** Failed to get info for API endpoint \"$route\": $route_data";
				exit(1);
			}
			if(empty($route_data)) {
				echo "*** No information obtained for \"$route\", does that endpoint exist?\n";
				exit(1);
			}
			$routes_info[$route] = $route_data;
			$i++;
		}

		//* Process extra documentation, this information is expected to be
		//  inside a "_doc" key in the endpoint schema.

		$categories = [];
		$routes_docs = [];

		foreach($routes_info as $route => $route_info) {
			$doc_info = $route_info['schema']['_doc'] ?? null;
			if($doc_info === null) {
				echo "*** $route: no '_doc' key found inside 'schema'\n";
				continue;
			}

			$route_docs = [];

			//* Process category information for the route.
			//  Each category is expected to be defined only once (in one of the endpoints) in an array under a "category" key,
			//  containing "id", "name" and "description"; and can be referenced in other endpoints by the same "category" key,
			//  but this time holding a string, which is the category id.

			$category_info = $doc_info['category'] ?? null;
			if($category_info === null) {
				echo "*** $route: 'schema - _doc' doesn't have a 'category' key.\n";
			}
			else if(is_string($category_info)) {
				$route_docs['category'] = $category_info;
			}
			else if(!is_array($category_info)) {
				echo "*** $route: 'schema - _doc - category' value is not a string nor an array.\n";
			}
			else if(!isset($category_info['id'])) {
				echo "*** $route: 'schema - _doc - category' doesn't have an 'id' key.\n";
			}
			else if(isset($categories[$category_info['id']])) {
				echo "*** $route: 'schema - _doc - category - id' defines category {$category_info['id']}, which is already defined.\n";
			}
			else {
				$category_id = $category_info['id'];
				unset($category_info['id']);
				$categories[$category_id] = $category_info;

				$route_docs['category'] = $category_id;
			}

			//* Process extra information for each endpoint of the route.

			$endpoints_docs = $doc_info['endpoints'] ?? null;
			if($endpoints_docs === null) {
				echo "*** $route: 'schema - _doc' doesn't have a 'endpoints' key.\n";
			}
			else if(!is_array($endpoints_docs)) {
				echo "*** $route: 'schema - _doc - endpoints' value is not an array.\n";
			}
			else {
				foreach($endpoints_docs as $verbs => $endpoint_docs) {
					$is_known_verb = false;
					foreach($route_info['endpoints'] as $endpoint_info) {
						if( self::verb_list_as_string($endpoint_info['methods']) === $verbs) {
							$route_docs[$verbs] = $endpoint_docs;
							$is_known_verb = true;
						}
					}
					if(!$is_known_verb) {
						echo "*** $route: 'schema - _doc - endpoints' has a key for verbs $verbs, which don't match any verb definition for the route.\n";
					}
				}
			}

			//* Done with the docs for the route

			foreach($route_info['endpoints'] as $endpoint_info) {
				$verbs = self::verb_list_as_string($endpoint_info['methods']);
				if(!isset($route_docs[$verbs])){
					echo "*** $route: 'schema - _doc - endpoints' doesn't contain an entry for verb(s) $verbs.\n";
				}
			}

			$routes_docs[$route] = $route_docs;
		}

		foreach($routes_info as $route => $route_info) {
			if(isset($route_info['category']) && !isset($categories[$route_info['category']])) {
				echo "*** $route: declared category '{$route_info['category']}' is not defined.\n";
			}
		}

		//* Process name and description for each endpoint, separately for each HTTP verb.



		$data = [
			'routes' => $routes_info,
			'categories' => $categories,
			'per_route_docs' => $routes_docs
		];

		file_put_contents(__DIR__ . "/data.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		//var_dump($route_info);
	}

	private static function query_server(string $url, string $http_verb) {
		curl_setopt(self::$curl, CURLOPT_URL, $url);
		curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt(self::$curl, CURLOPT_CUSTOMREQUEST, $http_verb);

		$result = curl_exec(self::$curl);

		curl_close(self::$curl);

		if($result === false) {
			$message = "CURL failed with code " . curl_errno(self::$curl);
			$error_message = curl_error(self::$curl);
			if($error_message) {
				$message .= ' - ' . $error_message;
			}
			return $message . ".\n";
		}

		$http_return_code = (string)curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);
		if($http_return_code[0] !== '2') {
			return "HTTP request failed with status code {$http_return_code}.\n";
		}

		$decoded_response = json_decode($result, true);
		if($decoded_response === null) {
			return "Failed to JSON decode response.\n";
		}

		return $decoded_response;
	}

	private static function verb_list_as_string($verb_list) {
		sort($verb_list);
		return join(',',$verb_list);
	}
}

ApiBuilder::run();
