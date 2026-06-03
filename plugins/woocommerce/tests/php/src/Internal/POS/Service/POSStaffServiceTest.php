<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Service;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\Service\POSStaffService;
use WC_Unit_Test_Case;
use WP_Error;
use WP_User;

/**
 * Tests for the POSStaffService::create_staff() factory.
 */
class POSStaffServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var POSStaffService
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new POSStaffService();
	}

	/**
	 * @testdox create_staff creates a WP user with the pos_staff role.
	 */
	public function test_create_staff_creates_user_with_pos_staff_role(): void {
		$user_id = $this->sut->create_staff( 'pos-staff-1@example.com', 'POS Staff One' );

		$this->assertIsInt( $user_id );
		$this->assertGreaterThan( 0, $user_id );

		$user = get_userdata( $user_id );
		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( array( Capabilities::POS_STAFF_ROLE ), $user->roles );
		$this->assertSame( 'POS Staff One', $user->display_name );
		$this->assertSame( 'pos-staff-1@example.com', $user->user_email );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox create_staff returns WP_Error when email is missing or invalid.
	 */
	public function test_create_staff_rejects_invalid_email(): void {
		$result = $this->sut->create_staff( '', 'Display Name' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_staff_invalid_email', $result->get_error_code() );

		$result = $this->sut->create_staff( 'not-an-email', 'Display Name' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_staff_invalid_email', $result->get_error_code() );
	}

	/**
	 * @testdox create_staff returns WP_Error when display name is blank.
	 */
	public function test_create_staff_rejects_missing_display_name(): void {
		$result = $this->sut->create_staff( 'pos-staff-2@example.com', '' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_staff_missing_display_name', $result->get_error_code() );
	}

	/**
	 * @testdox create_staff returns WP_Error when email is already in use.
	 */
	public function test_create_staff_rejects_duplicate_email(): void {
		$user_id = self::factory()->user->create( array( 'user_email' => 'taken@example.com' ) );

		$result = $this->sut->create_staff( 'taken@example.com', 'New Staff' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'woocommerce_pos_staff_email_exists', $result->get_error_code() );

		wp_delete_user( $user_id );
	}

	/**
	 * @testdox create_staff fires the woocommerce_created_pos_staff action.
	 */
	public function test_create_staff_fires_created_action(): void {
		$captured = array();
		$listener = function ( $user_id, $user_data ) use ( &$captured ) {
			$captured[] = array(
				'user_id'   => $user_id,
				'user_data' => $user_data,
			);
		};
		add_action( 'woocommerce_created_pos_staff', $listener, 10, 2 );

		$user_id = $this->sut->create_staff( 'pos-staff-3@example.com', 'POS Staff Three' );

		$this->assertCount( 1, $captured );
		$this->assertSame( $user_id, $captured[0]['user_id'] );
		$this->assertSame( Capabilities::POS_STAFF_ROLE, $captured[0]['user_data']['role'] );

		remove_action( 'woocommerce_created_pos_staff', $listener, 10 );
		wp_delete_user( $user_id );
	}

	/**
	 * @testdox create_staff is resolvable via the DI container.
	 */
	public function test_create_staff_resolvable_via_container(): void {
		$resolved = wc_get_container()->get( POSStaffService::class );
		$this->assertInstanceOf( POSStaffService::class, $resolved );
	}
}
