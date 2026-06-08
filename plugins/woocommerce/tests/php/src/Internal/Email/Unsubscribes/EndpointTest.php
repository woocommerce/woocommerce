<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Email\Unsubscribes;

use Automattic\WooCommerce\Internal\Email\Unsubscribes\Endpoint;
use Automattic\WooCommerce\Internal\Email\Unsubscribes\Storage;
use WC_Unit_Test_Case;
use WPDieException;

/**
 * Endpoint test.
 *
 * @covers \Automattic\WooCommerce\Internal\Email\Unsubscribes\Endpoint
 */
class EndpointTest extends WC_Unit_Test_Case {

	/**
	 * @var Storage
	 */
	private $storage;

	/**
	 * @var Endpoint
	 */
	private $endpoint;

	private const KIND = 'customer_checkout_recovery';

	/**
	 * Resolve the storage + endpoint singletons from the container so each
	 * test exercises the same wiring production uses.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->storage  = wc_get_container()->get( Storage::class );
		$this->endpoint = wc_get_container()->get( Endpoint::class );
	}

	/**
	 * Reset $_GET between tests so a previous payload doesn't bleed in.
	 */
	public function tearDown(): void {
		unset(
			$_GET[ Endpoint::QUERY_VAR ],
			$_GET[ Endpoint::QUERY_VAR_HASH ],
			$_GET['kind'],
			$_GET['sig']
		);
		parent::tearDown();
	}

	/**
	 * Drive maybe_handle() like a GET hit, swallowing the wp_die.
	 *
	 * @param array<string, mixed> $query Query params to set on $_GET.
	 */
	private function dispatch( array $query ): void {
		foreach ( $query as $key => $value ) {
			$_GET[ $key ] = $value;
		}
		try {
			$this->endpoint->maybe_handle();
		} catch ( WPDieException $e ) {
			// wp_die() is expected — the endpoint always renders.
			unset( $e );
		}
	}

	/**
	 * @testdox url_for() produces a URL whose signature verifies when maybe_handle() parses it back, completing the round trip.
	 */
	public function test_url_round_trip_marks_unsubscribed(): void {
		$email = 'roundtrip-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'] ?? '', $query );

		$this->dispatch( $query );

		$this->assertTrue( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox Tampering with the signature causes the endpoint to refuse the request and leave the unsubscribe state unchanged.
	 */
	public function test_invalid_signature_bails(): void {
		$email = 'tamper-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'] ?? '', $query );
		$query['sig'] = str_repeat( '0', 64 );

		$this->dispatch( $query );

		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox A link signed for one kind cannot be replayed to opt out of a different kind — the kind is part of the signed payload.
	 */
	public function test_kind_mismatch_bails(): void {
		$email = 'kind-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'] ?? '', $query );
		$query['kind'] = 'different_kind';

		$this->dispatch( $query );

		$this->assertFalse( $this->storage->is_unsubscribed( $email, 'different_kind' ) );
		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox Missing email-hash param is rejected even when the query-var is present.
	 */
	public function test_missing_hash_bails(): void {
		$email = 'missing-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'] ?? '', $query );
		unset( $query[ Endpoint::QUERY_VAR_HASH ] );

		$this->dispatch( $query );

		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox An email-hash that doesn't match the SHA-256 hex shape is rejected without invoking storage, so the endpoint never writes a row for a malformed payload.
	 */
	public function test_malformed_hash_bails(): void {
		$email = 'shape-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'] ?? '', $query );
		$query[ Endpoint::QUERY_VAR_HASH ] = 'not-a-hash';

		$this->dispatch( $query );

		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox url_for() never embeds the raw recipient email in the URL — the hash is what travels, so the address can't leak via logs, Referer headers, or link previews.
	 */
	public function test_url_does_not_contain_raw_email(): void {
		$email = 'pii-leak-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$this->assertStringNotContainsString( $email, $url );
		$this->assertStringNotContainsString( rawurlencode( $email ), $url );
		$this->assertStringNotContainsString( '@example.test', $url );
	}

	/**
	 * @testdox Missing kind param is rejected even when everything else verifies.
	 */
	public function test_missing_kind_bails(): void {
		$email = 'missing-kind-' . uniqid( '', true ) . '@example.test';
		$url   = Endpoint::url_for( 12345, $email, self::KIND );

		$parsed = wp_parse_url( $url );
		parse_str( $parsed['query'] ?? '', $query );
		unset( $query['kind'] );

		$this->dispatch( $query );

		$this->assertFalse( $this->storage->is_unsubscribed( $email, self::KIND ) );
	}

	/**
	 * @testdox Requests without the query-var are passed through (maybe_handle returns without rendering).
	 */
	public function test_no_query_var_passes_through(): void {
		// No exception should be raised here — quick-bail path.
		$this->endpoint->maybe_handle();
		$this->assertTrue( true );
	}
}
