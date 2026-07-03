<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

/**
 * Tests relating to DatabaseUtil.
 */
class DatabaseUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @var DatabaseUtil
	 */
	private $sut;

	/**
	 * Set-up subject under test.
	 */
	public function set_up() {
		$this->sut = wc_get_container()->get( DatabaseUtil::class );
		parent::set_up();
	}

	/**
	 * @testdox Test that get_index_columns() will default to return the primary key index columns.
	 */
	public function test_get_index_columns_returns_primary_index_by_default() {
		global $wpdb;

		$this->assertEquals(
			array( 'product_id' ),
			$this->sut->get_index_columns( $wpdb->prefix . 'wc_product_meta_lookup' )
		);
	}

	/**
	 * @testdox Test that get_index_columns() will return an array containing all columns of a multi-column index.
	 */
	public function test_get_index_columns_returns_multicolumn_index() {
		global $wpdb;

		$this->assertEquals(
			array( 'min_price', 'max_price' ),
			$this->sut->get_index_columns( $wpdb->prefix . 'wc_product_meta_lookup', 'min_max_price' )
		);
	}

	/**
	 * @testdox Test that giving a non-existent index name to get_index_columns() will return an empty array.
	 */
	public function test_get_index_columns_returns_empty_for_invalid_index() {
		global $wpdb;

		$this->assertEmpty(
			$this->sut->get_index_columns( $wpdb->prefix . 'wc_product_meta_lookup', 'invalid_index_name' )
		);
	}

	/**
	 * @test Test that we are able to create FTS index on order address table.
	 */
	public function test_create_fts_index_order_address_table() {
		$db = wc_get_container()->get( DataSynchronizer::class );
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		$db->create_database_tables();
		// Add back removed filter.
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		if ( ! $this->sut->fts_index_on_order_item_table_exists() ) {
			$this->sut->create_fts_index_order_address_table();
		}
		$this->assertTrue( $this->sut->fts_index_on_order_address_table_exists() );
	}

	/**
	 * @test Test that we are able to create FTS index on order item table.
	 */
	public function test_create_fts_index_order_item_table() {
		if ( ! $this->sut->fts_index_on_order_item_table_exists() ) {
			$this->sut->create_fts_index_order_item_table();
		}
		$this->assertTrue( $this->sut->fts_index_on_order_item_table_exists() );
	}

	/**
	 * @testDox Insert or update works as expected.
	 */
	public function test_insert_or_update() {
		global $wpdb;
		$table_name   = $wpdb->posts;
		$data         = array(
			'post_title'   => 'Test Post',
			'post_content' => 'Test Content',
			'post_name'    => 'test-post',
		);
		$where        = array(
			'post_title' => 'Test Post',
			'post_name'  => 'test-post',
		);
		$format       = array( '%s', '%s', '%s' );
		$format_where = array( '%s', '%s' );

		$count_query   = "SELECT COUNT(*) FROM $table_name WHERE post_title = 'Test Post' AND post_name = 'test-post'";
		$content_query = "SELECT post_content FROM $table_name WHERE post_title = 'Test Post' AND post_name = 'test-post'";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Hardcoded query.
		$this->assertEquals( 0, $wpdb->get_var( $count_query ) );

		$result = $this->sut->insert_or_update( $table_name, $data, $where, $format, $format_where );
		$this->assertEquals( 1, $result );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Hardcoded query.
		$this->assertEquals( 'Test Content', $wpdb->get_var( $content_query ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Hardcoded query.
		$this->assertEquals( 1, $wpdb->get_var( $count_query ) );

		$data['post_content'] = 'Updated Content';
		$result               = $this->sut->insert_or_update( $table_name, $data, $where, $format, $format_where );
		$this->assertEquals( 1, $result );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Hardcoded query.
		$this->assertEquals( 'Updated Content', $wpdb->get_var( $content_query ) );

		$result = $this->sut->insert_or_update( $table_name, $data, $where, $format, $format_where );
		$this->assertEquals( 0, $result ); // No row updated.
		$this->assertTrue( false !== $result ); // Ensure boolean false.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Hardcoded query.
		$this->assertEquals( 'Updated Content', $wpdb->get_var( $content_query ) );
	}

	/**
	 * @testDox Test that sanitise_boolean_fts_search_term() works as expected.
	 */
	public function test_sanitise_boolean_fts_search_term(): void {
		$terms_sanitized_mapping = array(
			// Normal terms are suffixed with wildcard.
			'abc'             => 'abc*',
			'abc def'         => 'abc* def*',
			// Terms containing operators are quoted.
			'+abc -def'       => '"+abc -def"',
			'++abc-def'       => '"++abc-def"',
			'abc (>def <fgh)' => '"abc (>def <fgh)"',
			'"abc" def'       => '"abc def"',
			'abc*'            => '"abc*"',
			// Some edge cases.
			''                => '*',
			'"'               => '""',
		);

		foreach ( $terms_sanitized_mapping as $term => $expected ) {
			$this->assertEquals( $expected, $this->sut->sanitise_boolean_fts_search_term( $term ) );
		}
	}

	/**
	 * @testdox get_sql_identifier() returns a backtick-quoted identifier via %i when the database layer supports it.
	 */
	public function test_get_sql_identifier_uses_placeholder_when_cap_supported(): void {
		global $wpdb;

		// Stock WordPress (>= 6.2) reports support for the %i identifier placeholder.
		$this->assertTrue(
			$wpdb->has_cap( 'identifier_placeholders' ),
			'The test environment is expected to support the %i identifier placeholder.'
		);

		$this->assertSame( '`wp_posts`', $this->sut->get_sql_identifier( 'wp_posts' ) );
	}

	/**
	 * @testdox get_sql_identifier() falls back to manual backtick quoting when the database layer lacks %i support.
	 */
	public function test_get_sql_identifier_falls_back_when_cap_unsupported(): void {
		$real_wpdb = $GLOBALS['wpdb'];

		// Simulate a $wpdb drop-in that runs on a supported WordPress version but does not implement %i.
		// Its has_cap() honestly returns false, so get_sql_identifier() must take the manual-quoting path
		// and never call prepare() (the stub deliberately omits it).
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- intentionally swapping $wpdb for a stub; restored below.
		$GLOBALS['wpdb'] = new class() {
			/**
			 * Report no support for any capability, including identifier placeholders.
			 *
			 * @param string $capability Capability name.
			 * @return bool
			 */
			public function has_cap( $capability ) {
				unset( $capability );
				return false;
			}
		};

		try {
			$this->assertSame( '`wp_posts`', $this->sut->get_sql_identifier( 'wp_posts' ) );

			// Backticks within the identifier are escaped by doubling.
			$this->assertSame( '`odd``name`', $this->sut->get_sql_identifier( 'odd`name' ) );
		} finally {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the real $wpdb swapped above.
			$GLOBALS['wpdb'] = $real_wpdb;
		}
	}
}
