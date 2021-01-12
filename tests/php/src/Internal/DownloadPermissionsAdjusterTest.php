<?php
/**
 * DownloadPermissionsAdjusterTest class file.
 */

namespace Automattic\WooCommerce\Tests\Internal;

use Automattic\WooCommerce\Internal\DownloadPermissionsAdjuster;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for DownloadPermissionsAdjuster.
 */
class DownloadPermissionsAdjusterTest extends \WC_Unit_Test_Case
{
	/**
	 * The system under test.
	 *
	 * @var DownloadPermissionsAdjuster
	 */
	private $sut;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->sut = new DownloadPermissionsAdjuster();
		$this->sut->init();
	}

	/**
	 * @testdox DownloadPermissionsAdjuster class hooks on 'adjust_download_permissions' on initialization.
	 */
	public function test_class_hooks_on_adjust_download_permissions()
	{
		remove_all_actions('adjust_download_permissions');
		$this->assertFalse(has_action('adjust_download_permissions'));
		$this->setUp();
		$this->assertTrue(has_action('adjust_download_permissions'));
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' does nothing if the product has no children.
	 */
	public function test_no_adjustment_is_scheduled_if_product_has_no_children() {
		$as_get_scheduled_actions_invoked = false;
		$as_schedule_single_action_invoked = false;

		$this->register_legacy_proxy_function_mocks(
			array(
				'as_get_scheduled_actions' => function($args, $return_format) use(&$as_get_scheduled_actions_invoked) {
					$as_get_scheduled_actions_invoked = true;
				},
				'as_schedule_single_action' => function($timestamp, $hook, $args) use(&$as_schedule_single_action_invoked) {
					$as_schedule_single_action_invoked = true;
				}
			)
		);

		$product = ProductHelper::create_simple_product();
		$this->sut->maybe_schedule_adjust_download_permissions($product);

		$this->assertFalse($as_get_scheduled_actions_invoked);
		$this->assertFalse($as_schedule_single_action_invoked);
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' does nothing if the an adjustment is already pending.
	 */
	public function test_no_adjustment_is_scheduled_if_already_scheduled() {
		$as_get_scheduled_actions_args = null;
		$as_schedule_single_action_invoked = false;

		$this->register_legacy_proxy_function_mocks(
			array(
				'as_get_scheduled_actions' => function($args, $return_format) use(&$as_get_scheduled_actions_args) {
					$as_get_scheduled_actions_args = $args;
					return array(1);
				},
				'as_schedule_single_action' => function($timestamp, $hook, $args) use(&$as_schedule_single_action_invoked) {
					$as_schedule_single_action_invoked = true;
				}
			)
		);

		$product = ProductHelper::create_variation_product();
		$this->sut->maybe_schedule_adjust_download_permissions($product);

		$expected_get_scheduled_actions_args = array(
			'hook' => 'adjust_download_permissions',
			'args' => array($product->get_id()),
			'status' => \ActionScheduler_Store::STATUS_PENDING
		);
		$this->assertEquals($expected_get_scheduled_actions_args, $as_get_scheduled_actions_args);
		$this->assertFalse($as_schedule_single_action_invoked);
	}

	/**
	 * @testdox 'maybe_schedule_adjust_download_permissions' schedules an adjustment if not scheduled already.
	 */
	public function test_no_adjustment_is_scheduled_if_not_yet_scheduled() {
		$as_get_scheduled_actions_args = null;
		$as_schedule_single_action_args = null;

		$this->register_legacy_proxy_function_mocks(
			array(
				'as_get_scheduled_actions' => function($params, $return_format) use(&$as_get_scheduled_actions_args) {
					$as_get_scheduled_actions_args = $params;
					return array();
				},
				'as_schedule_single_action' => function($timestamp, $hook, $args) use(&$as_schedule_single_action_args) {
					$as_schedule_single_action_args = array( $timestamp, $hook, $args);
				},
				'time' => function() { return 0; }
			)
		);

		$product = ProductHelper::create_variation_product();
		$this->sut->maybe_schedule_adjust_download_permissions($product);

		$expected_get_scheduled_actions_args = array(
			'hook' => 'adjust_download_permissions',
			'args' => array($product->get_id()),
			'status' => \ActionScheduler_Store::STATUS_PENDING
		);
		$this->assertEquals($expected_get_scheduled_actions_args, $as_get_scheduled_actions_args);

		$expected_as_schedule_single_action_args = array(
			1,
			'adjust_download_permissions',
			array( $product->get_id() )
		);
		$this->assertEquals($expected_as_schedule_single_action_args, $as_schedule_single_action_args);
	}
}
