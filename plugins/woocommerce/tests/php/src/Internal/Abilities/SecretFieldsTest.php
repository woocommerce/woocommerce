<?php
/**
 * SecretFieldsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Internal\Abilities\SecretFields;

/**
 * Tests for the SecretFields class.
 */
class SecretFieldsTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Should recognize credentials by field type and by name.
	 *
	 * @testWith ["api_username", "password", true]
	 *           ["api_signature", "text", true]
	 *           ["testPublishableKey", "text", false]
	 *           ["webhookSecret", "text", true]
	 *           ["woocommerce_stripe_api_key", "text", true]
	 *           ["tokenization", "checkbox", false]
	 *           ["title", "text", false]
	 *
	 * @param string $id       Setting ID.
	 * @param string $type     Field type.
	 * @param bool   $expected Whether it is a secret.
	 */
	public function test_is_secret( string $id, string $type, bool $expected ): void {
		$this->assertSame( $expected, SecretFields::is_secret( $id, $type ) );
	}
}
