<?php
/**
 * Integration tests for Contracts::list_for_address_email().
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ArgumentValidator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;
use Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\AssertsValidationErrors;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts
 */
class ContractAddressEmailListTest extends EngineIntegrationTestCase {

	use AssertsValidationErrors;

	private const EXTENSION_SLUG = 'acme-subs';

	private const EMAIL = 'ada@example.com';

	/**
	 * @testdox list_for_address_email() returns the owner's contracts with the email on either address, newest first, each once.
	 */
	public function test_lists_the_owners_matching_contracts(): void {
		$billing  = $this->seed( array( 'billing' => self::EMAIL ) );
		$both     = $this->seed(
			array(
				'billing'  => self::EMAIL,
				'shipping' => self::EMAIL,
			)
		);
		$shipping = $this->seed( array( 'shipping' => 'ADA@example.com' ) );
		$this->seed( array( 'billing' => self::EMAIL ), 'another-extension' );
		$this->seed( array( 'billing' => 'grace@example.com' ) );

		$views = Contracts::list_for_address_email( self::EMAIL, 20, 0, array( 'extension_slug' => self::EXTENSION_SLUG ) );

		$this->assertSame( array( $shipping, $both, $billing ), $this->ids( $views ), 'Case-insensitive under the default collation; other owners excluded.' );
		$this->assertContainsOnlyInstancesOf( ContractView::class, $views );
		$this->assertNull( $views[0]->get_addresses(), 'Views carry no addresses.' );
		$this->assertNull( $views[0]->get_items(), 'Views carry no items.' );
		$this->assertSame( array(), Contracts::list_for_address_email( 'nobody@example.com', 20, 0, array( 'extension_slug' => self::EXTENSION_SLUG ) ) );
	}

	/**
	 * @testdox list_for_address_email() pages with limit and offset.
	 */
	public function test_pages_with_limit_and_offset(): void {
		$first  = $this->seed( array( 'billing' => self::EMAIL ) );
		$second = $this->seed( array( 'billing' => self::EMAIL ) );
		$third  = $this->seed( array( 'billing' => self::EMAIL ) );
		$args   = array( 'extension_slug' => self::EXTENSION_SLUG );

		$this->assertSame( array( $third, $second ), $this->ids( Contracts::list_for_address_email( self::EMAIL, 2, 0, $args ) ) );
		$this->assertSame( array( $first ), $this->ids( Contracts::list_for_address_email( self::EMAIL, 2, 2, $args ) ) );
		$this->assertSame( array( $third, $second, $first ), $this->ids( Contracts::list_for_address_email( self::EMAIL, 20, 0, $args ) ), 'The defaults page 20 from the start.' );
	}

	/**
	 * @testdox list_for_address_email() reports an empty email, a negative limit and offset, and a missing owner together.
	 */
	public function test_reports_every_shape_error_together(): void {
		$this->assert_validation_errors(
			static function (): void {
				Contracts::list_for_address_email( '', -1, -2 );
			},
			array(
				array( ValidationErrorCode::REQUIRED, 'email' ),
				array( ValidationErrorCode::REQUIRED, 'extension_slug' ),
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'limit', array( 'min' => 0 ) ),
				array( ValidationErrorCode::OUT_OF_BOUNDS, 'offset', array( 'min' => 0 ) ),
			)
		);
	}

	/**
	 * @testdox list_for_address_email() refuses a blank email as required, so it cannot match stored empty emails.
	 */
	public function test_refuses_a_blank_email(): void {
		$this->seed( array( 'billing' => '' ) );

		foreach ( array( ' ', "\t \n" ) as $email ) {
			$this->assert_validation_errors(
				static function () use ( $email ): void {
					Contracts::list_for_address_email( $email, 20, 0, array( 'extension_slug' => self::EXTENSION_SLUG ) );
				},
				array(
					array( ValidationErrorCode::REQUIRED, 'email' ),
				)
			);
		}
	}

	/**
	 * @testdox list_for_address_email() refuses an email longer than the 320-character column and accepts one at the limit.
	 */
	public function test_refuses_an_email_longer_than_the_column(): void {
		$args = array( 'extension_slug' => self::EXTENSION_SLUG );

		$this->assert_validation_errors(
			static function () use ( $args ): void {
				Contracts::list_for_address_email( str_repeat( 'a', 309 ) . '@example.com', 20, 0, $args );
			},
			array(
				array( ValidationErrorCode::TOO_LONG, 'email', array( 'max' => 320 ) ),
			)
		);

		$this->assertSame( array(), Contracts::list_for_address_email( str_repeat( 'a', 308 ) . '@example.com', 20, 0, $args ) );
	}

	/**
	 * @testdox list_for_address_email() refuses an ill-typed, malformed or `any` extension_slug.
	 * @dataProvider provide_invalid_owners
	 *
	 * @param mixed             $slug     The extension_slug value.
	 * @param array<int, mixed> $expected Expected error.
	 */
	public function test_refuses_an_invalid_owner( $slug, array $expected ): void {
		$this->seed( array( 'billing' => self::EMAIL ) );

		$this->assert_validation_errors(
			static function () use ( $slug ): void {
				Contracts::list_for_address_email( self::EMAIL, 20, 0, array( 'extension_slug' => $slug ) );
			},
			array( $expected )
		);
	}

	/**
	 * @return array<string, array{0: mixed, 1: array<int, mixed>}>
	 */
	public function provide_invalid_owners(): array {
		return array(
			'empty'     => array( '', array( ValidationErrorCode::REQUIRED, 'extension_slug' ) ),
			'not text'  => array( 5, array( ValidationErrorCode::INVALID_TYPE, 'extension_slug', array( 'type' => 'string' ) ) ),
			'malformed' => array( 'Acme Subs', array( ValidationErrorCode::INVALID_PATTERN, 'extension_slug', array( 'pattern' => ArgumentValidator::SLUG_PATTERN ) ) ),
			'any'       => array( 'any', array( ValidationErrorCode::INVALID_VALUE, 'extension_slug' ) ),
		);
	}

	/**
	 * @testdox list_for_address_email() ignores unknown keys with a notice.
	 */
	public function test_ignores_unknown_keys(): void {
		$this->setExpectedIncorrectUsage( Contracts::class . '::list_for_address_email' );
		$id   = $this->seed( array( 'billing' => self::EMAIL ) );
		$args = array(
			'extension_slug' => self::EXTENSION_SLUG,
			'status'         => 'cancelled',
		);

		$this->assertSame( array( $id ), $this->ids( Contracts::list_for_address_email( self::EMAIL, 20, 0, $args ) ), 'The unknown status filter is ignored.' );
	}

	/**
	 * @testdox list_for_address_email() throws RuntimeException on a failed read instead of returning no contracts.
	 */
	public function test_a_failed_read_throws(): void {
		$this->seed( array( 'billing' => self::EMAIL ) );
		$this->fail_next_query( '/contract_addresses WHERE email = /' );

		$this->expectException( RuntimeException::class );
		Contracts::list_for_address_email( self::EMAIL, 20, 0, array( 'extension_slug' => self::EXTENSION_SLUG ) );
	}

	/**
	 * Create a released contract with the given address emails.
	 *
	 * @param array<string, string> $emails         Address type => email.
	 * @param string                $extension_slug Owning extension.
	 */
	private function seed( array $emails, string $extension_slug = self::EXTENSION_SLUG ): int {
		$addresses = array();
		foreach ( $emails as $type => $email ) {
			$addresses[ $type ] = array(
				'first_name' => 'Ada',
				'email'      => $email,
			);
		}

		return (int) $this->create_released(
			array(
				'extension_slug' => $extension_slug,
				'addresses'      => $addresses,
			)
		)->get_id();
	}

	/**
	 * The ids of contract views, in order.
	 *
	 * @param array<int, ContractView> $views Views.
	 * @return array<int, int>
	 */
	private function ids( array $views ): array {
		return array_map(
			static function ( ContractView $view ): int {
				return $view->get_id();
			},
			$views
		);
	}
}
