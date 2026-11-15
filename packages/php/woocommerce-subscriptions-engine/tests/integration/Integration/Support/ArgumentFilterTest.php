<?php
/**
 * Integration tests for the shared facade unknown-key filtering.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Support;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentFilter;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Support\ArgumentFilter
 */
class ArgumentFilterTest extends EngineIntegrationTestCase {

	public function test_filter_known_keys_drops_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'Acme::write' );
		$messages = array();
		add_action(
			'doing_it_wrong_run',
			static function ( $function_name, $message ) use ( &$messages ): void {
				$messages[] = $message;
			},
			10,
			2
		);

		$filtered = ArgumentFilter::filter_known_keys(
			'Acme::write',
			array(
				'known' => 1,
				'other' => 2,
			),
			array( 'known' => true ),
			'item key'
		);

		$this->assertSame( array( 'known' => 1 ), $filtered );
		$this->assertSame( array( 'Unknown item key "other" ignored.' ), $messages );
	}

	public function test_rows_keep_their_keys_and_drop_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'facade' );

		$rows = ArgumentFilter::filter_known_keys_in_rows(
			'facade',
			array(
				'billing'  => array(
					'first_name' => 'Ada',
					'zip'        => '1',
				),
				'shipping' => array( 'first_name' => 'Grace' ),
			),
			array( 'first_name' => true ),
			'address key'
		);

		$this->assertSame(
			array(
				'billing'  => array( 'first_name' => 'Ada' ),
				'shipping' => array( 'first_name' => 'Grace' ),
			),
			$rows
		);
	}
}
