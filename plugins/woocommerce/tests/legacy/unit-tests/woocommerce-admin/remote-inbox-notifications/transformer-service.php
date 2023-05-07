<?php
/**
 * TransformerService tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\ArrayKeys;
use Automattic\WooCommerce\Admin\RemoteInboxNotifications\TransformerService;

/**
 * class WC_Admin_Tests_RemoteInboxNotifications_TransformerService
 */
class WC_Admin_Tests_RemoteInboxNotifications_TransformerService extends WC_Unit_Test_Case {
	/**
	 * Test it creates a transformer with snake case 'use' value
	 */
	public function test_it_creates_a_transformer_with_snake_case_use_value() {
		$array_keys = TransformerService::create_transformer( 'array_keys' );
		$this->assertInstanceOf( ArrayKeys::class, $array_keys );
	}

	/**
	 * Test it returns null when a transformer is not found.
	 */
	public function test_it_returns_null_when_transformer_is_not_found() {
		$transformer = TransformerService::create_transformer( 'i_do_not_exist' );
		$this->assertNull( $transformer );
	}

	/**
	 * @testdox An exception is thrown when the transformer config is missing 'use'
	 */
	public function test_it_throw_exception_when_transformer_config_is_missing_use() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Missing required config value: use' );
		TransformerService::apply( array( 'value' ), array( new stdClass() ), null );
	}

	/**
	 * @testdox An exception is thrown when the transformer is not found
	 */
	public function test_it_throws_exception_when_transformer_is_not_found() {
		$this->expectExceptionMessage( 'Unable to find a transformer by name: i_do_not_exist' );
		$transformer = $this->transformer_config( 'i_do_not_exist' );
		TransformerService::apply( array( 'value' ), array( $transformer ), null );
	}

	/**
	 * Given two transformers
	 * When the second transformer returns null
	 * Then the value transformed by the first transformer should be returned.
	 */
	public function test_it_returns_previously_transformed_value_when_transformer_returns_null() {
		$dot_notation = $this->transformer_config( 'dot_notation', array( 'path' => 'industries' ) );
		$array_search = $this->transformer_config( 'array_search', array( 'value' => 'i_do_not_exist' ) );
		$items        = array(
			'industries' => array( 'item1', 'item2' ),
		);
		$result       = TransformerService::apply( $items, array( $dot_notation, $array_search ), null );
		$this->assertEquals( $result, $items['industries'] );
	}

	/**
	 * Given a nested array
	 * When it uses DotNotation to select 'teams'
	 * When it uses ArrayColumn to select 'members' in 'teams'
	 * When it uses ArrayFlatten to flatten 'members'
	 * When it uses ArraySearch to select 'mothra-member'
	 * Then 'mothra-member' should be returned.
	 */
	public function test_it_returns_transformed_value() {
		// Given.
		$items = array(
			'teams' => array(
				array(
					'name'    => 'mothra',
					'members' => array( 'mothra-member' ),
				),
				array(
					'name'    => 'gezora',
					'members' => array( 'gezora-member' ),
				),
				array(
					'name'    => 'ghidorah',
					'members' => array( 'ghidorah-member' ),
				),
			),
		);

		// When.
		$dot_notation  = $this->transformer_config( 'dot_notation', array( 'path' => 'teams' ) );
		$array_column  = $this->transformer_config( 'array_column', array( 'key' => 'members' ) );
		$array_flatten = $this->transformer_config( 'array_flatten' );
		$array_search  = $this->transformer_config( 'array_search', array( 'value' => 'mothra-member' ) );

		$result = TransformerService::apply( $items, array( $dot_notation, $array_column, $array_flatten, $array_search ), null );

		// Then.
		$this->assertEquals( 'mothra-member', $result );
	}

	/**
	 * Creates transformer config object
	 *
	 * @param string $name name of the transformer in snake_case.
	 * @param array  $arguments transformer arguments.
	 *
	 * @return stdClass
	 */
	private function transformer_config( $name, array $arguments = array() ) {
		$transformer            = new stdClass();
		$transformer->use       = $name;
		$transformer->arguments = (object) $arguments;
		return $transformer;
	}
}
