<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email\Unsubscribes;

use Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage;
use WC_Unit_Test_Case;

/**
 * Storage test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage
 */
class StorageTest extends WC_Unit_Test_Case {

	/**
	 * @var Storage
	 */
	private $storage;

	/**
	 * Arbitrary kind used by these tests — represents whichever email class
	 * happens to be exercising the table.
	 */
	private const KIND = 'customer_checkout_recovery';

	/**
	 * Resolve the storage singleton from the container so each test exercises
	 * the same wiring production uses.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->storage = wc_get_container()->get( Storage::class );
	}

	/**
	 * @testdox hash_email() normalizes case and whitespace before hashing so equivalent addresses collide on the same row.
	 */
	public function test_hash_email_normalizes_case_and_whitespace(): void {
		$canonical = Storage::hash_email( 'jane@example.test' );

		$this->assertSame( $canonical, Storage::hash_email( '  jane@example.test  ' ) );
		$this->assertSame( $canonical, Storage::hash_email( 'JANE@Example.Test' ) );
		$this->assertSame( 64, strlen( $canonical ) );
	}

	/**
	 * @testdox hash_email() returns empty string for empty input so callers can early-out without raising.
	 */
	public function test_hash_email_empty_input(): void {
		$this->assertSame( '', Storage::hash_email( '' ) );
		$this->assertSame( '', Storage::hash_email( '   ' ) );
	}

	/**
	 * @testdox mark_unsubscribed() then is_unsubscribed() returns true; a different email or kind is unaffected.
	 */
	public function test_mark_and_check_unsubscribed(): void {
		$email = 'roundtrip-' . uniqid( '', true ) . '@example.test';

		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
		$this->assertTrue( $this->storage->mark_unsubscribed( $email, self::KIND ) );
		$this->assertTrue( $this->storage->is_unsubscribed( $email, self::KIND ) );

		$this->assertFalse( $this->storage->is_unsubscribed( 'unrelated-' . uniqid( '', true ) . '@example.test', self::KIND ) );
		$this->assertFalse( $this->storage->is_unsubscribed( $email, 'unrelated_kind' ), 'A different kind must not inherit the opt-out.' );
	}

	/**
	 * @testdox mark_unsubscribed_by_hash() records the row under the given hash so callers that already operate on the hash (the public Endpoint) never have to recover the raw address.
	 */
	public function test_mark_unsubscribed_by_hash(): void {
		$email = 'by-hash-' . uniqid( '', true ) . '@example.test';
		$hash  = Storage::hash_email( $email );

		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
		$this->assertTrue( $this->storage->mark_unsubscribed_by_hash( $hash, self::KIND ) );
		$this->assertTrue( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox mark_unsubscribed_by_hash() rejects empty hash or empty kind without writing a row.
	 */
	public function test_mark_unsubscribed_by_hash_rejects_empty(): void {
		$this->assertFalse( $this->storage->mark_unsubscribed_by_hash( '', self::KIND ) );
		$this->assertFalse( $this->storage->mark_unsubscribed_by_hash( str_repeat( 'a', 64 ), '' ) );
	}

	/**
	 * @testdox mark_unsubscribed_by_hash() refuses inputs that don't match HASH_PATTERN — a future caller that forgets to pre-validate can't insert junk rows.
	 */
	public function test_mark_unsubscribed_by_hash_rejects_malformed(): void {
		$this->assertFalse( $this->storage->mark_unsubscribed_by_hash( 'not-a-hash', self::KIND ) );
		$this->assertFalse( $this->storage->mark_unsubscribed_by_hash( str_repeat( 'g', 64 ), self::KIND ), 'Non-hex chars must be rejected.' );
		$this->assertFalse( $this->storage->mark_unsubscribed_by_hash( str_repeat( 'a', 63 ), self::KIND ), 'Wrong length must be rejected.' );
		$this->assertFalse( $this->storage->mark_unsubscribed_by_hash( str_repeat( 'A', 64 ), self::KIND ), 'Uppercase hex must be rejected — hash() returns lowercase.' );
	}

	/**
	 * @testdox erase_for_email() removes all rows for an address across every kind — the GDPR eraser clears all preferences for the requested email.
	 */
	public function test_erase_for_email_removes_all_kinds(): void {
		$email = 'erase-' . uniqid( '', true ) . '@example.test';

		$this->storage->mark_unsubscribed( $email, self::KIND );
		$this->storage->mark_unsubscribed( $email, 'another_kind' );

		$deleted = $this->storage->erase_for_email( $email );

		$this->assertGreaterThanOrEqual( 2, $deleted );
		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
		$this->assertFalse( $this->storage->is_unsubscribed( $email, 'another_kind' ) );
	}

	/**
	 * @testdox The personal-data eraser callback reports items_removed=true when rows existed, false otherwise.
	 */
	public function test_personal_data_eraser_callback_reports_outcome(): void {
		$with_rows = 'eraser-with-' . uniqid( '', true ) . '@example.test';
		$this->storage->mark_unsubscribed( $with_rows, self::KIND );

		$result = $this->storage->handle_personal_data_erasure( $with_rows );
		$this->assertTrue( $result['items_removed'] );
		$this->assertTrue( $result['done'] );

		$result = $this->storage->handle_personal_data_erasure( 'eraser-empty-' . uniqid( '', true ) . '@example.test' );
		$this->assertFalse( $result['items_removed'] );
		$this->assertTrue( $result['done'] );
	}
}
