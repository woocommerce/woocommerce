<?php
/**
 * Integration tests for the owner-scoped address email read on ContractRepository.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository
 */
class ContractAddressEmailReadTest extends EngineIntegrationTestCase {

	/**
	 * Owning extension of the fixture contracts.
	 */
	private const OWNER = 'acme-subs';

	/**
	 * The email the reads look up.
	 */
	private const EMAIL = 'ada@example.com';

	/**
	 * The System Under Test.
	 *
	 * @var ContractRepository
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
	}

	/**
	 * @testdox A billing or a shipping address email matches; a contract with the email on both is returned once, newest first.
	 */
	public function test_matches_either_address_once_newest_first(): void {
		$billing  = $this->seed( array( 'billing' => self::EMAIL ) );
		$shipping = $this->seed( array( 'shipping' => self::EMAIL ) );
		$both     = $this->seed(
			array(
				'billing'  => self::EMAIL,
				'shipping' => self::EMAIL,
			)
		);
		$this->seed( array( 'billing' => 'grace@example.com' ) );
		$this->seed( array() );

		$this->assertSame( array( $both, $shipping, $billing ), $this->ids( $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 20, 0 ) ) );
	}

	/**
	 * @testdox Only the named extension's contracts are returned, matched on the exact owner.
	 */
	public function test_is_owner_scoped(): void {
		$mine = $this->seed( array( 'billing' => self::EMAIL ) );
		$this->seed( array( 'billing' => self::EMAIL ), 'another-extension' );

		$this->assertSame( array( $mine ), $this->ids( $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 20, 0 ) ) );
		$this->assertSame( array(), $this->sut->find_by_address_email( self::EMAIL, 'ACME-SUBS', 20, 0 ), 'The owner match is case-sensitive.' );
	}

	/**
	 * @testdox The email matches case-insensitively under the default collation, and a different email matches nothing.
	 */
	public function test_matches_by_the_column_collation(): void {
		$id = $this->seed( array( 'billing' => 'Ada@Example.com' ) );

		$this->assertSame( array( $id ), $this->ids( $this->sut->find_by_address_email( 'ada@example.COM', self::OWNER, 20, 0 ) ) );
		$this->assertSame( array(), $this->sut->find_by_address_email( 'ada@example.org', self::OWNER, 20, 0 ) );
	}

	/**
	 * @testdox The email is an exact match: a prefix, or a value with `%` or `_`, matches nothing.
	 */
	public function test_is_an_exact_match(): void {
		$id = $this->seed( array( 'billing' => 'ada_b@example.com' ) );

		$this->assertSame( array( $id ), $this->ids( $this->sut->find_by_address_email( 'ada_b@example.com', self::OWNER, 20, 0 ) ) );
		$this->assertSame( array(), $this->sut->find_by_address_email( 'ada_b@example.co', self::OWNER, 20, 0 ), 'A prefix does not match.' );
		$this->assertSame( array(), $this->sut->find_by_address_email( 'ada%@example.com', self::OWNER, 20, 0 ), '% is not a wildcard.' );
		$this->assertSame( array(), $this->sut->find_by_address_email( 'ada_c@example.com', self::OWNER, 20, 0 ), 'A different character does not match.' );
		$this->assertSame( array(), $this->sut->find_by_address_email( 'ad__b@example.com', self::OWNER, 20, 0 ), '_ is not a wildcard.' );
	}

	/**
	 * @testdox Pages with limit and offset over the de-duplicated set.
	 */
	public function test_pages_with_limit_and_offset(): void {
		$ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$ids[] = $this->seed(
				array(
					'billing'  => self::EMAIL,
					'shipping' => self::EMAIL,
				)
			);
		}
		$newest_first = array_reverse( $ids );

		$this->assertSame( array_slice( $newest_first, 0, 2 ), $this->ids( $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 2, 0 ) ) );
		$this->assertSame( array_slice( $newest_first, 2, 2 ), $this->ids( $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 2, 2 ) ) );
		$this->assertSame( array_slice( $newest_first, 4, 1 ), $this->ids( $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 2, 4 ) ) );
		$this->assertSame( array(), $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 2, 6 ) );
	}

	/**
	 * @testdox Each contract is a row-only read: no items or addresses loaded.
	 */
	public function test_returns_row_only_contracts(): void {
		$this->seed( array( 'billing' => self::EMAIL ) );

		$contracts = $this->sut->find_by_address_email( self::EMAIL, self::OWNER, 20, 0 );

		$this->assertCount( 1, $contracts );
		$this->assertSame( array(), $contracts[0]->get_addresses() );
		$this->assertSame( self::OWNER, $contracts[0]->get_extension_slug() );
	}

	/**
	 * @testdox A failed read throws RuntimeException without the email in its message.
	 */
	public function test_a_failed_read_throws_without_the_email(): void {
		$this->seed( array( 'billing' => self::EMAIL ) );
		$this->fail_next_query( '/contract_addresses WHERE email = /' );

		try {
			$this->sut->find_by_address_email( self::EMAIL, self::OWNER, 20, 0 );
			$this->fail( 'Expected the failed read to throw.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Failed to read the contracts of extension', $e->getMessage() );
			$this->assertStringContainsString( 'by address email', $e->getMessage() );
			$this->assertStringNotContainsString( self::EMAIL, $e->getMessage() );
		}
	}

	/**
	 * Insert a released contract with the given address emails.
	 *
	 * @param array<string, string> $emails         Address type => email.
	 * @param string                $extension_slug Owning extension.
	 */
	private function seed( array $emails, string $extension_slug = self::OWNER ): int {
		$addresses = array();
		foreach ( $emails as $type => $email ) {
			$addresses[ $type ] = array(
				'first_name' => 'Ada',
				'email'      => $email,
			);
		}

		return $this->insert_released(
			Contract::create(
				array(
					'extension_slug' => $extension_slug,
					'addresses'      => $addresses,
				)
			)
		);
	}

	/**
	 * Map a result set to its contract ids.
	 *
	 * @param array<int, Contract> $contracts Contracts.
	 * @return array<int, int>
	 */
	private function ids( array $contracts ): array {
		return array_map(
			static function ( Contract $contract ): int {
				return (int) $contract->get_id();
			},
			$contracts
		);
	}
}
