<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Infrastructure\Schema;

use Automattic\WooCommerce\Api\Infrastructure\Schema\AST\StringValueNode as AliasedStringValueNode;
use Automattic\WooCommerce\Api\Infrastructure\Schema\ResolveInfo as AliasedResolveInfo;
use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ResolveInfo;
use WC_Unit_Test_Case;

/**
 * Tests that the `aliases.php` bootstrap registers the surface aliases.
 *
 * These aliases let generated code reference the engine via the
 * Api\Infrastructure\Schema namespace even though webonyx itself constructs
 * the instances. If the alias is broken, every resolver's `resolve()`
 * parameter type-hint check fails at request time.
 */
class AliasesTest extends WC_Unit_Test_Case {
	/**
	 * @testdox the ResolveInfo alias resolves to the webonyx ResolveInfo class.
	 */
	public function test_resolve_info_alias_resolves_to_webonyx_resolve_info(): void {
		$this->assertTrue( class_exists( AliasedResolveInfo::class ) );
		$this->assertSame(
			ResolveInfo::class,
			( new \ReflectionClass( AliasedResolveInfo::class ) )->getName(),
			'The alias must resolve to the webonyx ResolveInfo class so resolver type hints accept what the engine passes.'
		);
	}

	/**
	 * @testdox the StringValueNode alias resolves to the webonyx StringValueNode class.
	 */
	public function test_string_value_node_alias_resolves_to_webonyx_string_value_node(): void {
		$this->assertTrue( class_exists( AliasedStringValueNode::class ) );
		$this->assertSame(
			StringValueNode::class,
			( new \ReflectionClass( AliasedStringValueNode::class ) )->getName(),
			'The alias must resolve to webonyx StringValueNode so custom-scalar parseLiteral() callbacks see the right type.'
		);
	}
}
