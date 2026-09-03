<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\PushNotifications\Entities;

use Automattic\WooCommerce\Internal\PushNotifications\Entities\PushTokenResolution;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * PushTokenResolution test.
 *
 * @covers \Automattic\WooCommerce\Internal\PushNotifications\Entities\PushTokenResolution
 */
class PushTokenResolutionTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Rejects unknown resolution outcomes.
	 */
	public function test_rejects_unknown_resolution_outcomes(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid push token resolution outcome.' );

		new PushTokenResolution( array(), 'unknown', 0, 0 );
	}

	/**
	 * @testdox Rejects negative diagnostic counts.
	 *
	 * @dataProvider provide_negative_counts
	 *
	 * @param int $registered_token_owner_count Registered token owner count.
	 * @param int $eligible_user_count          Eligible user count.
	 */
	public function test_rejects_negative_diagnostic_counts(
		int $registered_token_owner_count,
		int $eligible_user_count
	): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Push token resolution counts cannot be negative.' );

		new PushTokenResolution(
			array(),
			PushTokenResolution::OUTCOME_NO_REGISTERED_TOKENS,
			$registered_token_owner_count,
			$eligible_user_count
		);
	}

	/**
	 * Provides invalid diagnostic count combinations.
	 *
	 * @return array<string, array{int, int}>
	 */
	public function provide_negative_counts(): array {
		return array(
			'negative registered owners' => array( -1, 0 ),
			'negative eligible users'    => array( 0, -1 ),
		);
	}
}
