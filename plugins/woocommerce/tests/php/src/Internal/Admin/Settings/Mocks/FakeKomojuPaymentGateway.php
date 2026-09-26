<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\Mocks;

/**
 * Fake KOMOJU payment gateway to use in tests.
 *
 * Mirrors the public `$secretKey` property that the KOMOJU extension resolves in its
 * gateway constructor, so tests can cover the provider path that reads it.
 */
class FakeKomojuPaymentGateway extends FakePaymentGateway {

	/**
	 * The secret key the gateway resolved for itself.
	 *
	 * Left null to stand in for extension versions older than 2.5.0, which have no such
	 * property. The provider guards with isset(), so a null here exercises its fallback.
	 *
	 * @var ?string
	 */
	public $secretKey; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
}
