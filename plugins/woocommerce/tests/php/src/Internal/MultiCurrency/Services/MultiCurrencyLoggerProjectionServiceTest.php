<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLoggerProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyLoggerProjectionService class.
 */
class MultiCurrencyLoggerProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project log source and supported levels.
	 */
	public function test_projects_log_source_and_supported_levels(): void {
		$this->assertSame( 'woopayments-multi-currency', MultiCurrencyLoggerProjectionService::get_log_source() );
		$this->assertSame(
			array( 'debug', 'error', 'notice' ),
			MultiCurrencyLoggerProjectionService::get_supported_levels()
		);
	}

	/**
	 * @testdox Should project runtime blockers when logger is unavailable.
	 */
	public function test_projects_runtime_blockers_when_logger_is_unavailable(): void {
		$this->assertSame(
			array( 'wc_logger_unavailable' ),
			MultiCurrencyLoggerProjectionService::get_runtime_blockers( false )
		);
		$this->assertSame(
			array(),
			MultiCurrencyLoggerProjectionService::get_runtime_blockers( true )
		);
	}

	/**
	 * @testdox Should project log manifest when logger is available.
	 */
	public function test_projects_log_manifest_when_logger_is_available(): void {
		$this->assertSame(
			array(
				'should_log' => true,
				'level'      => 'debug',
				'message'    => 'Currency converted.',
				'context'    => array(
					'source' => 'woopayments-multi-currency',
				),
				'blockers'   => array(),
			),
			MultiCurrencyLoggerProjectionService::get_log_manifest( 'debug', 'Currency converted.', true )
		);
	}

	/**
	 * @testdox Should project log manifest blockers.
	 */
	public function test_projects_log_manifest_blockers(): void {
		$this->assertSame(
			array(
				'should_log' => false,
				'level'      => 'debug',
				'message'    => 'Currency converted.',
				'context'    => array(
					'source' => 'woopayments-multi-currency',
				),
				'blockers'   => array( 'wc_logger_unavailable' ),
			),
			MultiCurrencyLoggerProjectionService::get_log_manifest( 'debug', 'Currency converted.', false )
		);
		$this->assertSame(
			array(
				'should_log' => false,
				'level'      => 'warning',
				'message'    => 'Currency converted.',
				'context'    => array(
					'source' => 'woopayments-multi-currency',
				),
				'blockers'   => array( 'unsupported_level' ),
			),
			MultiCurrencyLoggerProjectionService::get_log_manifest( 'warning', 'Currency converted.', true )
		);
	}

	/**
	 * @testdox Should project public level manifests.
	 */
	public function test_projects_public_level_manifests(): void {
		$this->assertSame(
			'debug',
			MultiCurrencyLoggerProjectionService::get_debug_manifest( 'Debug message.', true )['level']
		);
		$this->assertSame(
			'error',
			MultiCurrencyLoggerProjectionService::get_error_manifest( 'Error message.', true )['level']
		);
		$this->assertSame(
			'notice',
			MultiCurrencyLoggerProjectionService::get_notice_manifest( 'Notice message.', true )['level']
		);
		$this->assertSame(
			array( 'source' => 'woopayments-multi-currency' ),
			MultiCurrencyLoggerProjectionService::get_notice_manifest( 'Notice message.', true )['context']
		);
	}
}
