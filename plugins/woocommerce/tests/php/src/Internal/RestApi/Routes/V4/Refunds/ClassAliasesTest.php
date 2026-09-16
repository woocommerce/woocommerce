<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Refunds;

use Automattic\WooCommerce\Internal\RestApi\Refunds\DataUtils;
use Automattic\WooCommerce\Internal\RestApi\Refunds\Schema\RefundPreviewSchema;
use WC_Unit_Test_Case;

/**
 * Tests for the class_alias shims that keep the old V4 refund engine FQCNs
 * resolving to the relocated Internal\RestApi\Refunds classes.
 */
class ClassAliasesTest extends WC_Unit_Test_Case {

	private const OLD_DATA_UTILS     = 'Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils';
	private const OLD_PREVIEW_SCHEMA = 'Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema';

	/**
	 * @testdox The old DataUtils FQCN autoloads and aliases the relocated class.
	 */
	public function test_old_data_utils_name_aliases_new_class(): void {
		$this->assertTrue( class_exists( self::OLD_DATA_UTILS ), 'The old DataUtils FQCN should still autoload via its shim' );

		$old_name = self::OLD_DATA_UTILS;
		$this->assertInstanceOf( DataUtils::class, new $old_name(), 'An instance built from the old FQCN should be the relocated class' );
	}

	/**
	 * @testdox The old RefundPreviewSchema FQCN autoloads and aliases the relocated class.
	 */
	public function test_old_preview_schema_name_aliases_new_class(): void {
		$this->assertTrue( class_exists( self::OLD_PREVIEW_SCHEMA ), 'The old RefundPreviewSchema FQCN should still autoload via its shim' );

		$old_name = self::OLD_PREVIEW_SCHEMA;
		$this->assertInstanceOf( RefundPreviewSchema::class, new $old_name(), 'An instance built from the old FQCN should be the relocated class' );
	}

	/**
	 * @testdox The DI container resolves the old FQCNs to the relocated classes.
	 */
	public function test_container_resolves_old_names(): void {
		$this->assertInstanceOf( DataUtils::class, wc_get_container()->get( self::OLD_DATA_UTILS ), 'The container should resolve the old DataUtils FQCN' );
		$this->assertInstanceOf( RefundPreviewSchema::class, wc_get_container()->get( self::OLD_PREVIEW_SCHEMA ), 'The container should resolve the old RefundPreviewSchema FQCN' );
	}
}
