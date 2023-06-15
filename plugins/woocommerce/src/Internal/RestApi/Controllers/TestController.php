<?php
/**
 * TestController class file.
 */

namespace Automattic\WooCommerce\Internal\RestApi\Controller;

use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowedRolesAttribute as AllowedRoles;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\AllowUnauthenticatedAttribute as AllowUnauthenticated;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\ControllerBase;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiControllerAttribute as RestApiController;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\DescriptionAttribute as Description;
use Automattic\WooCommerce\Internal\RestApi\Infrastructure\Attributes\RestApiEndpointAttribute as RestApiEndpoint;

// phpcs:disable Squiz.Commenting.ClassComment.Missing, Squiz.Commenting.FunctionComment.Missing

#[RestApiController( 'test' )]
#[Description( 'Miscellaneous dummy-ish endpoints to test the REST API engine and its associated PHP attributes.' )]
class TestController extends ControllerBase {

	#[RestApiEndpoint( 'GET', 'admins_only' )]
	#[AllowedRoles( 'administrator' )]
	#[Description( 'Test for the "AllowedRoles" attribute' )]
	public static function administrator_only( \WP_Rest_Request $request, ?\WP_User $user ) {
		return "Hello {$user->display_name}, you are indeed an administrator!";
	}

	#[RestApiEndpoint( 'GET', 'welcome' )]
	#[AllowUnauthenticated( true )]
	#[Description( 'Test for the "AllowUnauthenticated" attribute' )]
	public static function welcome( \WP_Rest_Request $request, ?\WP_User $user ) {
		$name = $user ? $user->display_name : 'mystery visitor';
		return "Hello $name, welcome to the chaos!";
	}

	#[RestApiEndpoint( 'GET', '' )]
	#[Description( 'Test for the root endpoint of a controller' )]
	public static function root( \WP_Rest_Request $request, ?\WP_User $user ) {
		return "Congratulations {$user->display_name}, you found the root /test endpoint.";
	}

	#[RestApiEndpoint( 'POST', 'echo' )]
	#[Description( 'Just echo the request body back to the response' )]
	public static function echo( \WP_Rest_Request $request, ?\WP_User $user ) {
		return $request->get_json_params();
	}
}

// phpcs:enable Squiz.Commenting.ClassComment.Missing, Squiz.Commenting.FunctionComment.Missing
