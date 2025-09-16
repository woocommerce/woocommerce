<?php
declare(strict_types=1);

require_once __DIR__ . '/test-abstract-schema-v4.php';

/**
 * Abstract Schema tests for V4 REST API.
 */
class WC_REST_Abstract_Schema_V4_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Test schema instance.
	 *
	 * @var Test_Abstract_Schema_V4
	 */
	private $schema;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->schema = new Test_Abstract_Schema_V4();
	}

	/**
	 * Test get_item_schema_properties method.
	 */
	public function test_get_item_schema_properties() {
		$properties = $this->schema->get_item_schema_properties();

		$this->assertIsArray( $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
	}


	/**
	 * Test context constants.
	 */
	public function test_context_constants() {
		$this->assertEquals( array( 'view', 'edit', 'embed' ), Test_Abstract_Schema_V4::VIEW_EDIT_EMBED_CONTEXT );
		$this->assertEquals( array( 'view', 'edit' ), Test_Abstract_Schema_V4::VIEW_EDIT_CONTEXT );
	}

	/**
	 * Test identifier constant.
	 */
	public function test_identifier_constant() {
		$this->assertEquals( 'test_resource', Test_Abstract_Schema_V4::IDENTIFIER );
	}
}
