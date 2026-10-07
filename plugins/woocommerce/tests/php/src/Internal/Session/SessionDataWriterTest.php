<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Session;

use Automattic\WooCommerce\Internal\Session\SessionDataWriter;
use WC_Unit_Test_Case;

/**
 * Tests for the SessionDataWriter class.
 */
class SessionDataWriterTest extends WC_Unit_Test_Case {

	private const SESSION_KEY = 't_session_writer_test';

	/**
	 * The System Under Test.
	 *
	 * @var SessionDataWriter
	 */
	private $sut;

	/**
	 * Sessions table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut   = new SessionDataWriter();
		$this->table = $GLOBALS['wpdb']->prefix . 'woocommerce_sessions';
	}

	/**
	 * @testdox Inserts a new row when the session is not stored yet.
	 */
	public function test_inserts_new_row(): void {
		$saved = $this->sut->save( $this->table, self::SESSION_KEY, time() + 100, array( 'a' => 'mine' ), array() );

		$this->assertSame( array( 'a' => 'mine' ), $saved );
		$this->assertSame( array( 'a' => 'mine' ), $this->get_stored() );
	}

	/**
	 * @testdox Without a loaded snapshot, replaces the stored row.
	 */
	public function test_overwrites_without_loaded_snapshot(): void {
		$this->store( array( 'a' => 'theirs' ) );

		$this->sut->save( $this->table, self::SESSION_KEY, time() + 100, array( 'b' => 'mine' ), null );

		$this->assertSame( array( 'b' => 'mine' ), $this->get_stored() );
	}

	/**
	 * @testdox When another request saved since this one loaded, only this request's changes are applied to the stored row.
	 */
	public function test_applies_only_changed_keys_to_newer_data(): void {
		$this->store(
			array(
				'kept'    => 'theirs',
				'removed' => 'old',
				'added'   => 'theirs',
			)
		);
		$loaded  = array(
			'kept'    => 'old',
			'removed' => 'old',
			'changed' => 'old',
		);
		$current = array(
			'kept'    => 'old',
			'changed' => 'mine',
		);

		$saved = $this->sut->save( $this->table, self::SESSION_KEY, time() + 100, $current, $loaded );

		$expected = array(
			'kept'    => 'theirs',
			'added'   => 'theirs',
			'changed' => 'mine',
		);
		$this->assertSame( $expected, $saved );
		$this->assertSame( $expected, $this->get_stored() );
	}

	/**
	 * @testdox When both requests changed the same key, the last one to save wins.
	 */
	public function test_same_key_changed_by_both_requests_uses_the_last_save(): void {
		$this->store( array( 'a' => 'theirs' ) );

		$this->sut->save( $this->table, self::SESSION_KEY, time() + 100, array( 'a' => 'mine' ), array( 'a' => 'old' ) );

		$this->assertSame( array( 'a' => 'mine' ), $this->get_stored() );
	}

	/**
	 * @testdox When the stored row cannot be read, saves this request's whole session instead of only its changes.
	 */
	public function test_saves_whole_session_when_stored_row_cannot_be_read(): void {
		global $wpdb;
		$this->store(
			array(
				'a'    => 'theirs',
				'keep' => 'kept',
			)
		);
		$break_read = function ( $query ) {
			return 0 === strpos( $query, 'SELECT session_value FROM' ) ? str_replace( 'FROM ', 'FROM missing_', $query ) : $query;
		};
		add_filter( 'query', $break_read );
		$suppress = $wpdb->suppress_errors( true );

		try {
			$saved = $this->sut->save(
				$this->table,
				self::SESSION_KEY,
				time() + 100,
				array(
					'a'    => 'mine',
					'keep' => 'kept',
				),
				array(
					'a'    => 'old',
					'keep' => 'kept',
				)
			);
		} finally {
			$wpdb->suppress_errors( $suppress );
			remove_filter( 'query', $break_read );
		}

		$expected = array(
			'a'    => 'mine',
			'keep' => 'kept',
		);
		$this->assertSame( $expected, $saved );
		$this->assertSame( $expected, $this->get_stored(), 'Keys this request did not change should not be dropped' );
	}

	/**
	 * Store a session row directly.
	 *
	 * @param array $data Session data.
	 */
	private function store( array $data ): void {
		global $wpdb;
		$wpdb->replace(
			$this->table,
			array(
				'session_key'    => self::SESSION_KEY,
				'session_value'  => maybe_serialize( $data ),
				'session_expiry' => time() + 100,
			)
		);
	}

	/**
	 * Read the stored session row.
	 *
	 * @return array
	 */
	private function get_stored(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- trusted table name.
		return (array) maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$this->table} WHERE session_key = %s", self::SESSION_KEY ) ) );
	}
}
