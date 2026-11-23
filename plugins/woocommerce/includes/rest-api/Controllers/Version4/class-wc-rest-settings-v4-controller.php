<?php
/**
 * REST API Settings V4 controller (compatibility stub).
 *
 * @deprecated 11.5.0 The v4 REST API was removed.
 * @package WooCommerce\RestApi
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class_alias( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\NoOpStub::class, 'WC_REST_Settings_V4_Controller' );
