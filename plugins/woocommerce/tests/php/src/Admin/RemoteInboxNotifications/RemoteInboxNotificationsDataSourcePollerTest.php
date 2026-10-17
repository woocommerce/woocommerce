<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\RemoteInboxNotifications;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsDataSourcePoller;
use Automattic\WooCommerce\Admin\RemoteSpecs\DataSourcePoller;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the published snapshot recorded by the remote inbox notifications poller.
 *
 * @covers \Automattic\WooCommerce\Admin\RemoteInboxNotifications\RemoteInboxNotificationsDataSourcePoller
 */
class RemoteInboxNotificationsDataSourcePollerTest extends WC_Unit_Test_Case {

	/**
	 * Transient holding the latest complete read.
	 */
	private const SNAPSHOT_TRANSIENT = 'woocommerce_admin_remote_inbox_notifications_published_snapshot';

	/**
	 * Auxiliary data source added through the data sources filter.
	 */
	private const AUXILIARY_URL = 'https://example.com/remote-inbox-notifications.json';

	/**
	 * The System Under Test.
	 *
	 * @var RemoteInboxNotificationsDataSourcePoller
	 */
	private $sut;

	/**
	 * Response body per data source, or null for a transport failure.
	 *
	 * @var array<string, string|null>
	 */
	private array $responses = array();

	/**
	 * Data sources returned by the data sources filter.
	 *
	 * @var string[]
	 */
	private array $data_sources = array();

	/**
	 * Route the poller to controlled data sources and responses.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut          = RemoteInboxNotificationsDataSourcePoller::get_instance();
		$canonical_url      = RemoteInboxNotificationsDataSourcePoller::get_data_sources()[0];
		$this->data_sources = array( $canonical_url, self::AUXILIARY_URL );
		$this->responses    = array(
			$canonical_url      => $this->encode_specs( 'remote-alert-a', 'remote-alert-b' ),
			self::AUXILIARY_URL => $this->encode_specs( 'remote-alert-c' ),
		);

		delete_transient( 'woocommerce_admin_' . RemoteInboxNotificationsDataSourcePoller::ID . '_specs' );
		delete_transient( self::SNAPSHOT_TRANSIENT );

		add_filter(
			DataSourcePoller::FILTER_NAME,
			fn( $data_sources, $poller_id ) => RemoteInboxNotificationsDataSourcePoller::ID === $poller_id ? $this->data_sources : $data_sources,
			10,
			2
		);
		add_filter( 'pre_http_request', array( $this, 'get_controlled_response' ), 10, 3 );
	}

	/**
	 * Serve the controlled response for a data source request.
	 *
	 * @param mixed  $preempt Preempted response.
	 * @param mixed  $args    Request arguments.
	 * @param string $url     Request URL.
	 * @return mixed Controlled response.
	 */
	public function get_controlled_response( $preempt, $args, $url ) {
		unset( $args );

		foreach ( $this->responses as $source_url => $body ) {
			if ( 0 !== strpos( $url, $source_url ) ) {
				continue;
			}

			if ( null === $body ) {
				return new WP_Error( 'http_request_failed', 'Controlled transport failure.' );
			}

			return array(
				'headers'  => array(),
				'body'     => $body,
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		}

		return $preempt;
	}

	/**
	 * @testdox A complete canonical response records its slugs and fetch time, independently of other sources.
	 */
	public function test_complete_read_records_published_snapshot(): void {
		$before = time();

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );

		$snapshot = $this->sut->get_published_snapshot();
		$this->assertNotNull( $snapshot, 'A complete read must record a snapshot.' );
		$this->assertEqualsCanonicalizing( array( 'remote-alert-a', 'remote-alert-b' ), $snapshot['slugs'] );
		$this->assertGreaterThanOrEqual( $before, $snapshot['fetched_at'], 'The snapshot time must not predate the read.' );
		$this->assertLessThanOrEqual( time(), $snapshot['fetched_at'], 'The snapshot time must not be in the future.' );
	}

	/**
	 * @testdox A read that returns specs without being complete clears the snapshot and keeps caching valid specs.
	 * @dataProvider incomplete_read_provider
	 *
	 * @param string|null $canonical_body      Canonical response body, or null for a transport failure.
	 * @param string|null $auxiliary_body      Auxiliary response body, or null for a transport failure.
	 * @param bool        $includes_canonical  Whether the canonical source is read.
	 * @param string[]    $expected_cached     Slugs the read must still cache.
	 */
	public function test_incomplete_read_clears_snapshot( ?string $canonical_body, ?string $auxiliary_body, bool $includes_canonical, array $expected_cached ): void {
		$canonical_url = $this->data_sources[0];

		$this->responses[ $canonical_url ]      = $canonical_body;
		$this->responses[ self::AUXILIARY_URL ] = $auxiliary_body;
		if ( ! $includes_canonical ) {
			$this->data_sources = array( self::AUXILIARY_URL );
		}
		set_transient(
			self::SNAPSHOT_TRANSIENT,
			array(
				'fetched_at' => time() - HOUR_IN_SECONDS,
				'slugs'      => array( 'remote-alert-a' ),
			),
			DAY_IN_SECONDS
		);

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );

		$this->assertNull( $this->sut->get_published_snapshot(), 'An incomplete read must not authorize hiding alerts, and its specs outrank an older snapshot.' );
		$this->assertEqualsCanonicalizing( $expected_cached, array_keys( $this->sut->get_cached_specs() ), 'Snapshot tracking must not change what the read caches.' );
	}

	/**
	 * Reads that do not prove the full published set.
	 *
	 * @return array<string, array{string|null, string|null, bool, string[]}>
	 */
	public function incomplete_read_provider(): array {
		$valid_canonical = $this->encode_specs( 'remote-alert-a' );
		$valid_auxiliary = $this->encode_specs( 'remote-alert-c' );
		$invalid_spec    = $this->create_spec( 'remote-alert-invalid' );
		unset( $invalid_spec->slug );

		return array(
			'canonical transport failure'   => array( null, $valid_auxiliary, true, array( 'remote-alert-c' ) ),
			'canonical empty list'          => array( '[]', $valid_auxiliary, true, array( 'remote-alert-c' ) ),
			'canonical error object'        => array( '{"code":"rest_no_route"}', $valid_auxiliary, true, array( 'remote-alert-c' ) ),
			'canonical truncated JSON'      => array( substr( $this->encode_specs( 'remote-alert-a', 'remote-alert-b' ), 0, -20 ), $valid_auxiliary, true, array( 'remote-alert-c' ) ),
			'canonical HTML error page'     => array( '<html><body>502 Bad Gateway</body></html>', $valid_auxiliary, true, array( 'remote-alert-c' ) ),
			'canonical missing slug'        => array( (string) wp_json_encode( array( $this->create_spec( 'remote-alert-a' ), $invalid_spec ) ), $valid_auxiliary, true, array( 'remote-alert-a', 'remote-alert-c' ) ),
			'canonical duplicate slug'      => array( $this->encode_specs( 'remote-alert-a', 'remote-alert-a' ), $valid_auxiliary, true, array( 'remote-alert-a', 'remote-alert-c' ) ),
			'canonical source filtered out' => array( $valid_canonical, $valid_auxiliary, false, array( 'remote-alert-c' ) ),
		);
	}


	/**
	 * @testdox An auxiliary outage does not veto the canonical published slugs.
	 */
	public function test_auxiliary_failure_does_not_veto_snapshot(): void {
		$this->responses[ self::AUXILIARY_URL ] = null;

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );

		$snapshot = $this->sut->get_published_snapshot();
		$this->assertNotNull( $snapshot );
		$this->assertEqualsCanonicalizing( array( 'remote-alert-a', 'remote-alert-b' ), $snapshot['slugs'] );
	}

	/**
	 * @testdox Identifiable invalid specs remain published even when none can be cached.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $include_valid Whether the canonical response also has a runnable spec.
	 */
	public function test_snapshot_preserves_invalid_spec_slugs( bool $include_valid ): void {
		$invalid = $this->create_spec( 'remote-alert-invalid' );
		unset( $invalid->type );
		$specs = array( $invalid );
		if ( $include_valid ) {
			$specs[] = $this->create_spec( 'remote-alert-valid' );
		}
		$this->data_sources                        = array( $this->data_sources[0] );
		$this->responses[ $this->data_sources[0] ] = (string) wp_json_encode( $specs );

		$this->assertSame( $include_valid, $this->sut->read_specs_from_data_sources(), 'The existing return value describes runnable specs.' );

		$snapshot = $this->sut->get_published_snapshot();
		$this->assertNotNull( $snapshot );
		$this->assertContains( 'remote-alert-invalid', $snapshot['slugs'] );
		$this->assertCount( $include_valid ? 2 : 1, $snapshot['slugs'] );
		$cached = $this->sut->get_cached_specs();
		$this->assertArrayNotHasKey( 'remote-alert-invalid', is_array( $cached ) ? $cached : array() );
	}

	/**
	 * @testdox Malformed identities cannot authorize withdrawal or crash spec merging.
	 * @testWith [null]
	 *           [""]
	 *           [["invalid"]]
	 *           [false]
	 *
	 * @param mixed $slug Malformed identity.
	 */
	public function test_malformed_identity_clears_snapshot( $slug ): void {
		$invalid                                   = $this->create_spec( 'remote-alert-invalid' );
		$invalid->slug                             = $slug;
		$this->responses[ $this->data_sources[0] ] = (string) wp_json_encode( array( $invalid ) );
		set_transient(
			self::SNAPSHOT_TRANSIENT,
			array(
				'fetched_at' => time(),
				'slugs'      => array( 'old' ),
			),
			DAY_IN_SECONDS
		);

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );

		$this->assertNull( $this->sut->get_published_snapshot() );
		$this->assertSame( array( 'remote-alert-c' ), array_keys( $this->sut->get_cached_specs() ) );
	}

	/**
	 * @testdox A failed read clears the previous snapshot, and an older complete read cannot replace a newer one.
	 */
	public function test_snapshot_only_moves_forward(): void {
		$this->sut->read_specs_from_data_sources();
		$complete_snapshot = $this->sut->get_published_snapshot();
		$this->assertNotNull( $complete_snapshot );

		$healthy_responses = $this->responses;
		$this->responses   = array_fill_keys( array_keys( $healthy_responses ), null );
		$this->sut->delete_specs_transient();

		$this->assertFalse( $this->sut->read_specs_from_data_sources() );
		$this->assertNull( $this->sut->get_published_snapshot(), 'A failed canonical read must not leave stale absence evidence available.' );

		$newer_snapshot = array(
			'fetched_at' => time() + HOUR_IN_SECONDS,
			'slugs'      => array( 'remote-alert-newer' ),
		);
		set_transient( self::SNAPSHOT_TRANSIENT, $newer_snapshot, DAY_IN_SECONDS );
		$this->responses = $healthy_responses;
		$this->sut->delete_specs_transient();

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );
		$this->assertSame( $newer_snapshot, $this->sut->get_published_snapshot(), 'An older complete read must not replace newer evidence.' );
	}

	/**
	 * @testdox An inconclusive read retains its timestamp without authorizing withdrawal, and a later complete read can recover.
	 */
	public function test_inconclusive_read_retains_timestamp(): void {
		$healthy_responses = $this->responses;
		$this->responses   = array_fill_keys( array_keys( $healthy_responses ), null );
		$before            = time();

		$this->assertFalse( $this->sut->read_specs_from_data_sources() );

		$stored = get_transient( self::SNAPSHOT_TRANSIENT );
		$this->assertIsArray( $stored, 'An inconclusive read must retain its timestamp to reject older reads.' );
		$this->assertGreaterThanOrEqual( $before, $stored['fetched_at'] );
		$this->assertLessThanOrEqual( time(), $stored['fetched_at'] );
		$this->assertNull( $this->sut->get_published_snapshot(), 'The retained timestamp must not authorize withdrawal.' );

		$stored['fetched_at'] -= HOUR_IN_SECONDS;
		set_transient( self::SNAPSHOT_TRANSIENT, $stored, DAY_IN_SECONDS );
		$this->responses = $healthy_responses;

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );
		$this->assertSame( array( 'remote-alert-a', 'remote-alert-b' ), $this->sut->get_published_snapshot()['slugs'] );
	}

	/**
	 * @testdox An older read cannot replace the timestamp of a newer inconclusive read.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $complete Whether the older read returns complete publication evidence.
	 */
	public function test_older_read_preserves_inconclusive_timestamp( bool $complete ): void {
		$newer_read = array(
			'fetched_at' => time() + HOUR_IN_SECONDS,
			'slugs'      => null,
		);
		set_transient( self::SNAPSHOT_TRANSIENT, $newer_read, DAY_IN_SECONDS );
		if ( ! $complete ) {
			$this->responses = array_fill_keys( array_keys( $this->responses ), null );
		}

		$this->assertSame( $complete, $this->sut->read_specs_from_data_sources() );

		$this->assertSame( $newer_read, get_transient( self::SNAPSHOT_TRANSIENT ), 'Older reads must preserve the newer timestamp even without published slugs.' );
		$this->assertNull( $this->sut->get_published_snapshot(), 'An older complete read must not restore stale publication evidence.' );
	}

	/**
	 * @testdox An older inconclusive read cannot erase a newer complete snapshot.
	 */
	public function test_older_inconclusive_read_preserves_complete_snapshot(): void {
		$newer_snapshot = array(
			'fetched_at' => time() + HOUR_IN_SECONDS,
			'slugs'      => array( 'remote-alert-newer' ),
		);
		set_transient( self::SNAPSHOT_TRANSIENT, $newer_snapshot, DAY_IN_SECONDS );
		$this->responses[ $this->data_sources[0] ] = null;

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );

		$this->assertSame( $newer_snapshot, $this->sut->get_published_snapshot(), 'An older inconclusive read must not discard newer evidence.' );
	}

	/**
	 * @testdox A malformed stored timestamp cannot block a fresh complete response.
	 */
	public function test_malformed_timestamp_does_not_block_complete_read(): void {
		set_transient(
			self::SNAPSHOT_TRANSIENT,
			array(
				'fetched_at' => (string) ( time() + HOUR_IN_SECONDS ),
				'slugs'      => null,
			),
			DAY_IN_SECONDS
		);

		$this->assertTrue( $this->sut->read_specs_from_data_sources() );

		$snapshot = $this->sut->get_published_snapshot();
		$this->assertNotNull( $snapshot, 'Only valid timestamps may prevent a complete read from saving its evidence.' );
		$this->assertSame( array( 'remote-alert-a', 'remote-alert-b' ), $snapshot['slugs'] );
	}

	/**
	 * @testdox Stored snapshot slugs are returned as a list of strings.
	 */
	public function test_snapshot_slugs_are_normalized(): void {
		set_transient(
			self::SNAPSHOT_TRANSIENT,
			array(
				'fetched_at' => 1,
				'slugs'      => array( 'remote-alert-a', array( 'remote-alert-b' ), 123 ),
			),
			DAY_IN_SECONDS
		);

		$this->assertSame( array( 'remote-alert-a', '123' ), $this->sut->get_published_snapshot()['slugs'] );
	}

	/**
	 * @testdox A malformed stored snapshot is ignored.
	 * @dataProvider malformed_snapshot_provider
	 *
	 * @param mixed $stored Stored transient value.
	 */
	public function test_malformed_snapshot_is_ignored( $stored ): void {
		set_transient( self::SNAPSHOT_TRANSIENT, $stored, DAY_IN_SECONDS );

		$this->assertNull( $this->sut->get_published_snapshot() );
	}

	/**
	 * Stored values that are not a snapshot.
	 *
	 * @return array<string, array{mixed}>
	 */
	public function malformed_snapshot_provider(): array {
		return array(
			'string'           => array( 'remote-alert-a' ),
			'missing slugs'    => array( array( 'fetched_at' => 1 ) ),
			'missing time'     => array( array( 'slugs' => array( 'remote-alert-a' ) ) ),
			'non-integer time' => array(
				array(
					'fetched_at' => '1',
					'slugs'      => array( 'remote-alert-a' ),
				),
			),
			'non-list slugs'   => array(
				array(
					'fetched_at' => 1,
					'slugs'      => 'remote-alert-a',
				),
			),
		);
	}

	/**
	 * Encode valid specs as a feed response body.
	 *
	 * @param string ...$slugs Spec slugs.
	 * @return string Response body.
	 */
	private function encode_specs( string ...$slugs ): string {
		return (string) wp_json_encode( array_map( array( $this, 'create_spec' ), $slugs ) );
	}

	/**
	 * Create a minimal valid remote alert spec.
	 *
	 * @param string $slug Spec slug.
	 * @return object Remote spec.
	 */
	private function create_spec( string $slug ): object {
		return (object) array(
			'slug'    => $slug,
			'status'  => Note::E_WC_ADMIN_NOTE_UNACTIONED,
			'type'    => Note::E_WC_ADMIN_NOTE_UPDATE,
			'source'  => 'woocommerce.com',
			'locales' => array(
				(object) array(
					'locale'  => 'en_US',
					'title'   => 'Remote alert',
					'content' => 'Remote alert content.',
				),
			),
		);
	}
}
