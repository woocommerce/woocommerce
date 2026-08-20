<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Api;

use Automattic\WooCommerce\Api\Attributes\PublicAccess;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use WC_Unit_Test_Case;

/**
 * Unit tests for the contract surface of the authorization attributes
 * shipped with the dual API ({@see PublicAccess} and
 * {@see RequiredCapability}). Pins the {@code #[Attribute]} flag set so
 * future widenings/narrowings are caught explicitly.
 */
class AuthorizationAttributeTest extends WC_Unit_Test_Case {
	/**
	 * @return array<string, array{class-string, int}>
	 */
	public function provider_attribute_targets(): array {
		return array(
			'PublicAccess accepts TARGET_CLASS'          => array( PublicAccess::class, \Attribute::TARGET_CLASS ),
			'PublicAccess accepts TARGET_PROPERTY'       => array( PublicAccess::class, \Attribute::TARGET_PROPERTY ),
			'RequiredCapability accepts TARGET_CLASS'    => array( RequiredCapability::class, \Attribute::TARGET_CLASS ),
			'RequiredCapability accepts TARGET_PROPERTY' => array( RequiredCapability::class, \Attribute::TARGET_PROPERTY ),
		);
	}

	/**
	 * @testdox Authorization attribute accepts the target listed in the provider.
	 *
	 * @dataProvider provider_attribute_targets
	 * @param class-string $attribute_class The attribute class under test.
	 * @param int          $target_flag     A single {@see \Attribute} TARGET_* flag the class must accept.
	 */
	public function test_attribute_accepts_target( string $attribute_class, int $target_flag ): void {
		$reflection = new \ReflectionClass( $attribute_class );
		$attributes = $reflection->getAttributes( \Attribute::class );

		$this->assertNotEmpty( $attributes, $attribute_class . ' should be decorated with #[Attribute].' );

		$attribute = $attributes[0]->newInstance();
		$this->assertNotSame(
			0,
			$attribute->flags & $target_flag,
			$attribute_class . ' should accept the requested TARGET_* flag.'
		);
	}

	/**
	 * @testdox RequiredCapability remains repeatable after the property-target widening.
	 */
	public function test_required_capability_is_still_repeatable(): void {
		$reflection = new \ReflectionClass( RequiredCapability::class );
		$attributes = $reflection->getAttributes( \Attribute::class );

		$attribute = $attributes[0]->newInstance();
		$this->assertNotSame( 0, $attribute->flags & \Attribute::IS_REPEATABLE );
	}
}
