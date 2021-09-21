<?php
/**
 * ControllerTestCase Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Domain\Services\ExtendRestApi;
use Automattic\WooCommerce\Blocks\Domain\Package as DomainPackage;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\HtmlFormatter;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\CurrencyFormatter;
use Automattic\WooCommerce\Blocks\Domain\Services\FeatureGating;

abstract class ControllerTestCase extends \WP_Test_REST_TestCase {
	protected $mock_extend;

	/**
	 * Setup Rest API server.
	 */
	public function setUp() {
		parent::setUp();

		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server;
		do_action( 'rest_api_init', $wp_rest_server );

		wp_set_current_user( 0 );
		update_option( 'woocommerce_weight_unit', 'g' );

		$formatters = new Formatters();
		$formatters->register( 'money', MoneyFormatter::class );
		$formatters->register( 'html', HtmlFormatter::class );
		$formatters->register( 'currency', CurrencyFormatter::class );
		$this->mock_extend = new ExtendRestApi( new DomainPackage( '', '', new FeatureGating( 2 ) ), $formatters );
	}

	/**
	 * Tear down Rest API server.
	 */
	public function tearDown() {
		parent::tearDown();
		/** @var \WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
	}
}
