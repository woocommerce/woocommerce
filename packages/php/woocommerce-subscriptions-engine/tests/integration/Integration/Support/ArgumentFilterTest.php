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

	public function test_contract_items_keep_item_fields_and_drop_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'facade' );

		$rows = ArgumentFilter::filter_contract_items(
			'facade',
			array(
				array(
					'item_name' => 'Coffee',
					'price'     => '1',
				),
			)
		);

		$this->assertSame( array( array( 'item_name' => 'Coffee' ) ), $rows );
	}

	public function test_contract_addresses_keep_address_fields_and_drop_unknown_keys_with_a_notice(): void {
		$this->setExpectedIncorrectUsage( 'facade' );

		$addresses = ArgumentFilter::filter_contract_addresses(
			'facade',
			array(
				'billing' => array(
					'first_name' => 'Ada',
					'zip'        => '1',
				),
			)
		);

		$this->assertSame( array( 'billing' => array( 'first_name' => 'Ada' ) ), $addresses );
	}
}
