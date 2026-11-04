<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Refunds;

use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils;
use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use WC_Unit_Test_Case;

/**
 * Tests for the backward compatibility shims that keep the old V4 refund engine
 * FQCNs working after the classes moved to Internal\RestApi\Refunds.
 */
class ClassAliasesTest extends WC_Unit_Test_Case {

	private const OLD_DATA_UTILS     = 'Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils';
	private const OLD_PREVIEW_SCHEMA = 'Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema';

	/**
	 * @testdox The old DataUtils FQCN autoloads and aliases the relocated class.
	 */
	public function test_old_data_utils_name_aliases_new_class(): void {
		$this->assertTrue( class_exists( self::OLD_DATA_UTILS ), 'The old DataUtils FQCN should still autoload via its shim' );

		$this->assertSame( DataUtils::class, ( new \ReflectionClass( self::OLD_DATA_UTILS ) )->getName(), 'The old DataUtils FQCN should be an alias of the relocated class, not a subclass' );
	}

	/**
	 * @testdox The old RefundPreviewSchema FQCN autoloads and still extends the V4 AbstractSchema.
	 */
	public function test_old_preview_schema_name_extends_abstract_schema(): void {
		$this->assertTrue( class_exists( self::OLD_PREVIEW_SCHEMA ), 'The old RefundPreviewSchema FQCN should still autoload via its shim' );

		$old_name = self::OLD_PREVIEW_SCHEMA;
		$this->assertInstanceOf( AbstractSchema::class, new $old_name(), 'An instance built from the old FQCN should still be an AbstractSchema' );
	}

	/**
	 * @testdox The old RefundPreviewSchema FQCN keeps the members it inherited from AbstractSchema.
	 */
	public function test_old_preview_schema_keeps_inherited_members(): void {
		$old_name = self::OLD_PREVIEW_SCHEMA;
		$schema   = new $old_name();

		$this->assertSame( array( 'view', 'edit' ), constant( $old_name . '::VIEW_EDIT_CONTEXT' ), 'The VIEW_EDIT_CONTEXT constant should still exist on the old FQCN' );
		$this->assertSame( array(), $schema->get_writable_item_schema_properties(), 'All preview properties are readonly, so the writable set should be empty' );
		$this->assertSame( $schema->get_item_schema(), ( new RefundPreviewSchema() )->get_item_schema(), 'The old FQCN should produce the same schema as the relocated class' );

		$this->expectException( \LogicException::class );
		$schema->get_item_response( array(), new \WP_REST_Request() );
	}

	/**
	 * @testdox A subclass of the old RefundPreviewSchema can add properties to the item schema.
	 */
	public function test_old_preview_schema_subclass_properties_reach_item_schema(): void {
		$sut = new class() extends \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema {
			/**
			 * Adds one property to the inherited ones.
			 *
			 * @return array
			 */
			public function get_item_schema_properties(): array {
				return parent::get_item_schema_properties() + array( 'extra' => array( 'type' => 'string' ) );
			}
		};

		$properties = $sut->get_item_schema()['properties'];

		$this->assertArrayHasKey( 'extra', $properties, 'A property added by a subclass should appear in the item schema' );
		$this->assertArrayHasKey( 'total', $properties, 'The inherited properties should still be in the item schema' );
	}

	/**
	 * @testdox The DI container resolves the old FQCNs.
	 */
	public function test_container_resolves_old_names(): void {
		$this->assertInstanceOf( DataUtils::class, wc_get_container()->get( self::OLD_DATA_UTILS ), 'The container should resolve the old DataUtils FQCN' );
		$this->assertInstanceOf( AbstractSchema::class, wc_get_container()->get( self::OLD_PREVIEW_SCHEMA ), 'The container should resolve the old RefundPreviewSchema FQCN' );
	}
}
