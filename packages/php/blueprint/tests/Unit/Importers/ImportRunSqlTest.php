<?php

namespace Automattic\WooCommerce\Blueprint\Tests\Unit\Importers;

use Automattic\WooCommerce\Blueprint\Importers\ImportRunSql;
use Automattic\WooCommerce\Blueprint\Steps\RunSql;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for ImportRunSql.
 */
class ImportRunSqlTest extends TestCase {
	/**
	 * The importer instance being tested.
	 *
	 * @var ImportRunSql
	 */
	private $importer;

	/**
	 * Set up the test case.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->importer = new ImportRunSql();
	}

	/**
	 * Test that the importer returns the correct step class.
	 */
	public function test_get_step_class(): void {
		$this->assertEquals( RunSql::class, $this->importer->get_step_class() );
	}

	/**
	 * Test that valid INSERT query is processed successfully.
	 */
	public function test_process_valid_insert_query(): void {
		$schema = $this->create_sql_schema(
			'INSERT INTO wp_posts (post_title) VALUES (\'Test Post\')',
			'test_insert'
		);

		$result = $this->importer->process( $schema );

		$this->assertTrue( $result->is_success() );
	}

	/**
	 * Test that valid UPDATE query is processed successfully.
	 */
	public function test_process_valid_update_query(): void {
		$schema = $this->create_sql_schema(
			'UPDATE wp_posts SET post_title = \'Updated Title\' WHERE ID = 1',
			'test_update'
		);

		$result = $this->importer->process( $schema );

		$this->assertTrue( $result->is_success() );
	}

	/**
	 * Test that REPLACE INTO query is processed successfully.
	 */
	public function test_process_valid_replace_query(): void {
		$schema = $this->create_sql_schema(
			'REPLACE INTO wp_options (option_name, option_value) VALUES (\'test_option\', \'test_value\')',
			'test_replace'
		);

		$result = $this->importer->process( $schema );

		$this->assertTrue( $result->is_success() );
	}

	/**
	 * Test that invalid query types are rejected.
	 *
	 * @param string $query The query to test.
	 *
	 * @dataProvider invalid_queries_provider
	 */
	public function test_process_invalid_query_types( string $query ): void {
		$schema = $this->create_sql_schema( $query, 'test_invalid_query' );

		$result = $this->importer->process( $schema );

		$this->assertFalse( $result->is_success() );
		$error_messages = $result->get_messages( 'error' );
		$this->assertNotEmpty( $error_messages );
		$this->assertStringContainsString( 'Only INSERT, UPDATE, REPLACE INTO queries are allowed', $error_messages[0]['message'] );
	}

	/**
	 * Data provider for invalid query types.
	 *
	 * @return array
	 */
	public function invalid_queries_provider(): array {
		return array(
			array( 'DELETE FROM wp_posts WHERE ID = 1' ),
			array( 'SELECT * FROM wp_posts' ),
			array( 'CREATE TABLE test_table (id INT)' ),
			array( 'DROP TABLE IF EXISTS test_table' ),
			array( 'ALTER TABLE wp_posts ADD COLUMN new_column INT' ),
			array( 'TRUNCATE TABLE wp_posts' ),
			array( 'GRANT ALL PRIVILEGES ON wp_posts TO \'user\'@\'localhost\'' ),
			array( 'REVOKE ALL PRIVILEGES ON wp_posts FROM \'user\'@\'localhost\'' ),
		);
	}

		/**
		 * Test SQL normalization with various formatting cases.
		 *
		 * @param string $input_sql    The input SQL query with various formatting.
		 * @param string $expected_sql The expected normalized SQL query.
		 *
		 * @dataProvider sql_normalization_provider
		 */
	public function test_normalize_sql( string $input_sql, string $expected_sql ): void {
		$normalized = $this->importer->normalize_sql( $input_sql );
		$this->assertEquals( $expected_sql, $normalized );
	}

	/**
	 * Data provider for SQL normalization test cases.
	 *
	 * @return array
	 */
	public function sql_normalization_provider(): array {
		return array(
			'multiple spaces'           => array(
				'INSERT   INTO     wp_posts    (post_title)     VALUES     (\'test\')',
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'newlines and tabs'         => array(
				"INSERT\nINTO\twp_posts\n(post_title)\nVALUES\n('test')",
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'spaces around operators'   => array(
				'UPDATE wp_posts SET post_title=\'test\'WHERE id=1AND status=\'publish\'',
				'UPDATE wp_posts SET post_title = \'test\' WHERE id = 1 AND status = \'publish\'',
			),
			'spaces around parentheses' => array(
				'INSERT INTO wp_posts( post_title )VALUES( \'test\' )',
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'mixed whitespace'          => array(
				"UPDATE\n  wp_posts\t\tSET    post_title  =  'test'\n  WHERE    id=1",
				'UPDATE wp_posts SET post_title = \'test\' WHERE id = 1',
			),
			'arithmetic operators'      => array(
				'UPDATE wp_posts SET menu_order=menu_order+1WHERE id>5',
				'UPDATE wp_posts SET menu_order = menu_order + 1 WHERE id > 5',
			),
			'multiple conditions'       => array(
				'UPDATE wp_posts SET post_status=\'publish\'WHERE post_type=\'post\'AND post_date<\'2023-01-01\'',
				'UPDATE wp_posts SET post_status = \'publish\' WHERE post_type = \'post\' AND post_date < \'2023-01-01\'',
			),
			'replace query'             => array(
				"REPLACE    INTO\n\twp_options(option_name,option_value)VALUES('test_key','test_value')",
				'REPLACE INTO wp_options (option_name, option_value) VALUES (\'test_key\', \'test_value\')',
			),
			'commas without space'      => array(
				'INSERT INTO wp_posts(title,content,status)VALUES(\'test\',\'content\',\'draft\')',
				'INSERT INTO wp_posts (title, content, status) VALUES (\'test\', \'content\', \'draft\')',
			),
			'comparison operators'      => array(
				'UPDATE wp_posts SET status=\'draft\' WHERE post_date>=\'2023-01-01\'AND id<=100',
				'UPDATE wp_posts SET status = \'draft\' WHERE post_date >= \'2023-01-01\' AND id <= 100',
			),
		);
	}

	/**
	 * Test SQL normalization with excessive whitespace.
	 */
	public function test_normalize_sql_excessive_whitespace(): void {
		$input    = "INSERT\n\n\tINTO\n\n\twp_posts\n\n\t(post_title)\n\n\tVALUES\n\n\t('test')";
		$expected = 'INSERT INTO wp_posts (post_title) VALUES (\'test\')';

		$normalized = $this->importer->normalize_sql( $input );
		$this->assertEquals( $expected, $normalized );
	}

	/**
	 * Test SQL normalization with inconsistent casing.
	 */
	public function test_normalize_sql_inconsistent_casing(): void {
		$input    = 'insert INTO wp_posts (post_title) values (\'test\')';
		$expected = 'INSERT INTO wp_posts (post_title) VALUES (\'test\')';

		$normalized = $this->importer->normalize_sql( $input );
		$this->assertEquals( $expected, $normalized );
	}

	/**
	 * Test SQL comment normalization.
	 *
	 * @param string $input_sql    The input SQL query with comments.
	 * @param string $expected_sql The expected normalized SQL query.
	 *
	 * @dataProvider sql_comment_provider
	 */
	public function test_normalize_sql_comments( string $input_sql, string $expected_sql ): void {
		$normalized = $this->importer->normalize_sql( $input_sql );
		$this->assertEquals( $expected_sql, $normalized );
	}

	/**
	 * Data provider for SQL comment test cases.
	 *
	 * @return array
	 */
	public function sql_comment_provider(): array {
		return array(
			'single line comments' => array(
				"INSERT INTO wp_posts -- This is a comment\n(post_title) VALUES ('test')",
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'hash comments'        => array(
				"INSERT INTO wp_posts #This is a comment\n(post_title) VALUES ('test')",
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'multi line comments'  => array(
				"INSERT INTO wp_posts /* This is a\nmulti-line comment */ (post_title) VALUES ('test')",
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'nested comments'      => array(
				"INSERT INTO wp_posts /* Outer /* Inner */ Comment */ (post_title) VALUES ('test')",
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
			'mixed comments'       => array(
				"INSERT INTO wp_posts -- First comment\n/* Second comment */\n#Third comment\n(post_title) VALUES ('test')",
				'INSERT INTO wp_posts (post_title) VALUES (\'test\')',
			),
		);
	}


	/**
	 * Test that SQL injection patterns are detected and rejected.
	 *
	 * @param string $query The query to test.
	 *
	 * @dataProvider sql_injection_patterns_provider
	 */
	public function test_process_sql_injection_patterns( string $query ): void {
		$schema = $this->create_sql_schema( $query, 'test_sql_injection' );

		$result = $this->importer->process( $schema );

		$this->assertFalse( $result->is_success() );
		$error_messages = $result->get_messages( 'error' );
		$this->assertNotEmpty( $error_messages );

		// Some injection patterns might trigger the invalid query type check first.
		$expected_messages = array(
			'SQL query contains potential injection patterns',
			'Only INSERT, UPDATE, REPLACE INTO queries are allowed',
		);

		$actual_message = $error_messages[0]['message'];
		$this->assertTrue(
			in_array( $actual_message, $expected_messages, true ),
			sprintf( 'Expected one of [%s], but got "%s"', implode( ', ', $expected_messages ), $actual_message )
		);
	}

	/**
	 * Data provider for SQL injection patterns.
	 *
	 * @return array
	 */
	public function sql_injection_patterns_provider(): array {
		return array(
			array( 'INSERT INTO wp_posts (post_title) VALUES (\'test\') UNION SELECT * FROM wp_users' ),
			array( 'UPDATE wp_posts SET post_title = \'test\' WHERE 1=1 OR 1=1' ),
			array( 'UPDATE wp_posts SET post_title = \'test\' WHERE ID = 1 AND 0=0' ),
			array( 'INSERT INTO wp_posts (post_title) VALUES (\'test\') UNION ALL SELECT user_login FROM wp_users' ),
			array( 'UPDATE wp_posts SET post_title = (SELECT SLEEP(5)) WHERE ID = 1' ),
			array( 'UPDATE wp_posts SET post_title = (SELECT BENCHMARK(1000000,MD5(\'test\'))) WHERE ID = 1' ),
			array( 'INSERT INTO wp_posts (post_title) VALUES ((SELECT LOAD_FILE(\'/etc/passwd\')))' ),
		);
	}


	/**
	 * Test that queries affecting protected tables are rejected.
	 */
	public function test_protected_tables_access(): void {
		global $wpdb;
		$protected_tables = array(
			'users',
			'usermeta',
			$wpdb->prefix . 'users',
			$wpdb->prefix . 'usermeta',
		);

		foreach ( $protected_tables as $table ) {
			$schema = $this->create_sql_schema(
				"INSERT INTO $table (user_login) VALUES ('test_user')",
				'test_protected_table'
			);

			$result = $this->importer->process( $schema );

			$this->assertFalse( $result->is_success() );
			$error_messages = $result->get_messages( 'error' );
			$this->assertNotEmpty( $error_messages );
			$this->assertStringContainsString( 'Modifications to admin users or roles are not allowed', $error_messages[0]['message'] );
		}
	}

	/**
	 * Test that queries affecting user capabilities are rejected.
	 */
	public function test_user_capabilities_protection(): void {
		global $wpdb;
		$queries = array(
			"INSERT INTO {$wpdb->prefix}options (option_name, option_value) VALUES ('wp_user_roles', 'test')",
			"UPDATE {$wpdb->prefix}options SET option_value = 'test' WHERE option_name LIKE '%capabilities%'",
			"REPLACE INTO {$wpdb->prefix}options (option_name, option_value) VALUES ('role_administrator', 'test')",
		);

		foreach ( $queries as $query ) {
			$schema = $this->create_sql_schema( $query, 'test_capabilities' );

			$result = $this->importer->process( $schema );

			$this->assertFalse( $result->is_success() );
			$error_messages = $result->get_messages( 'error' );
			$this->assertNotEmpty( $error_messages );
			$this->assertStringContainsString( 'Modifications to user roles or capabilities are not allowed', $error_messages[0]['message'] );
		}
	}


	/**
	 * Test that SQL execution error is handled properly.
	 */
	public function test_process_sql_execution_error(): void {
		$schema = $this->create_sql_schema(
			'INSERT INTO wp_test_table (test_column) VALUES (\'Test Value\')',
			'test_error'
		);

		$result = $this->importer->process( $schema );

		$this->assertFalse( $result->is_success() );
		$error_messages = $result->get_messages( 'error' );
		$this->assertNotEmpty( $error_messages );
		$this->assertStringContainsString( 'Error executing SQL', $error_messages[0]['message'] );
	}

	/**
	 * Test that queries with multiple statements are rejected.
	 */
	public function test_process_multiple_statements_rejected(): void {
		$schema = $this->create_sql_schema(
			'INSERT INTO wp_posts (post_title) VALUES (\'Test Post\'); UPDATE wp_posts SET post_status = \'publish\'',
			'test_multiple_statements'
		);

		$result = $this->importer->process( $schema );

		$this->assertFalse( $result->is_success() );
		$error_messages = $result->get_messages( 'error' );
		$this->assertNotEmpty( $error_messages );
		$this->assertStringContainsString( 'Error executing SQL', $error_messages[0]['message'] );
	}


	/**
	 * Create a schema object for SQL testing.
	 *
	 * @param string $sql_contents The SQL query.
	 * @param string $name        The name of the SQL step.
	 * @return stdClass
	 */
	private function create_sql_schema( string $sql_contents, string $name ): stdClass {
		$schema                = new stdClass();
		$schema->sql           = new stdClass();
		$schema->sql->contents = $sql_contents;
		$schema->sql->name     = $name;
		return $schema;
	}
}
