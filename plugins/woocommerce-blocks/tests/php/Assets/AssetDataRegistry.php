<?php

namespace Automattic\WooCommerce\Blocks\Tests\Assets;

use \WP_UnitTestCase;
use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Blocks\Tests\Mocks\AssetDataRegistryMock;
use Automattic\WooCommerce\Blocks\Package;
use InvalidArgumentException;

/**
 * Tests for the AssetDataRegistry class.
 *
 * @since $VID:$
 */
class AssetDataRegistry extends WP_UnitTestCase {
	private $registry;

	public function setUp() {
		$this->registry = new AssetDataRegistryMock(
			Package::container()->get( API::class )
		);
	}

	public function test_initial_data() {
		$this->assertEmpty( $this->registry->get() );
	}

	public function test_add_data() {
		$this->registry->add( 'test', 'foo' );
		$this->assertEquals( [ 'test' => 'foo' ], $this->registry->get() );
	}

	public function test_add_lazy_data() {
		$lazy = function () {
			return 'bar';
		};
		$this->registry->add( 'foo', $lazy );
		// should not be in data yet
		$this->assertEmpty( $this->registry->get() );
		$this->registry->execute_lazy_data();
		// should be in data now
		$this->assertEquals( [ 'foo' => 'bar' ], $this->registry->get() );
	}

	public function test_invalid_key_on_adding_data() {
		$this->expectException( InvalidArgumentException::class );
		$this->registry->add( [ 'some_value' ], 'foo' );
	}

	public function test_already_existing_key_on_adding_data() {
		$this->registry->add( 'foo', 'bar' );
		$this->expectException( InvalidArgumentException::class );
		$this->registry->add( 'foo', 'yar' );
	}
}
