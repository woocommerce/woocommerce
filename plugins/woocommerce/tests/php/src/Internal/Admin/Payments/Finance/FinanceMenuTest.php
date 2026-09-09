<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceMenu;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceMenu class.
 */
class FinanceMenuTest extends WC_Unit_Test_Case {

	/**
	 * Admin menu globals mutated by add_menu_page() and add_submenu_page().
	 *
	 * @var string[]
	 */
	private const MENU_GLOBALS = array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages' );

	/**
	 * The System Under Test.
	 *
	 * @var FinanceMenu
	 */
	private FinanceMenu $sut;

	/**
	 * Backup of the admin menu globals and the PageController pages.
	 *
	 * @var array|null
	 */
	private ?array $menu_backup = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = new FinanceMenu();
	}

	/**
	 * Restore the admin menu state mutated by the menu registration tests.
	 */
	public function tearDown(): void {
		try {
			if ( null !== $this->menu_backup ) {
				foreach ( self::MENU_GLOBALS as $global_name ) {
					if ( array_key_exists( $global_name, $this->menu_backup['globals'] ) ) {
						$GLOBALS[ $global_name ] = $this->menu_backup['globals'][ $global_name ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restoring the pre-test values.
					} else {
						unset( $GLOBALS[ $global_name ] );
					}
				}

				$pages_property = new \ReflectionProperty( PageController::class, 'pages' );
				$pages_property->setAccessible( true );
				$pages_property->setValue( PageController::get_instance(), $this->menu_backup['pages'] );

				$this->menu_backup = null;
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should attach the admin menu and user data fields hooks on register().
	 */
	public function test_register_attaches_hooks(): void {
		$this->sut->register();

		$this->assertSame( 10, has_action( 'admin_menu', array( $this->sut, 'handle_admin_menu' ) ) );
		$this->assertSame( 10, has_filter( 'woocommerce_admin_get_user_data_fields', array( $this->sut, 'handle_woocommerce_admin_get_user_data_fields' ) ) );
	}

	/**
	 * @testdox Should define the parent page with an unrounded position between Payments and Analytics.
	 */
	public function test_parent_page_definition(): void {
		$parent = $this->sut->get_parent_page();

		$this->assertSame( 'woocommerce-finance', $parent['id'] );
		$this->assertSame( 'wc-admin&path=/finance/overview', $parent['path'] );
		$this->assertSame( 'manage_woocommerce', $parent['capability'] );
		$this->assertSame( 56.5, $parent['position'] );
		$this->assertTrue( $parent['js_page'] );
	}

	/**
	 * @testdox Should define Overview and Payouts sub pages under the Finance parent, Overview first and sharing the parent path.
	 */
	public function test_sub_pages_definition(): void {
		$sub_pages = $this->sut->get_sub_pages();

		$this->assertSame( array( 'woocommerce-finance-overview', 'woocommerce-finance-payouts' ), array_column( $sub_pages, 'id' ) );
		$this->assertSame( array( '/finance/overview', '/finance/payouts' ), array_column( $sub_pages, 'path' ) );
		$this->assertSame( array( 'woocommerce-finance', 'woocommerce-finance' ), array_column( $sub_pages, 'parent' ) );
		$this->assertSame( array( 'manage_woocommerce', 'manage_woocommerce' ), array_column( $sub_pages, 'capability' ) );
	}

	/**
	 * @testdox Should add the Finance top-level menu at position 56.5 with the Overview and Payouts sub menus.
	 */
	public function test_handle_admin_menu_registers_menu_and_sub_menus(): void {
		global $menu, $submenu;

		$this->backup_menu_state();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->sut->handle_admin_menu();

		$parent_path = 'wc-admin&path=/finance/overview';

		$this->assertArrayHasKey( '56.5', $menu, 'The Finance menu should keep its fractional position.' );
		$this->assertSame( $parent_path, $menu['56.5'][2] );
		$this->assertSame( 'manage_woocommerce', $menu['56.5'][1] );

		$this->assertArrayHasKey( $parent_path, $submenu );
		$this->assertSame(
			array( 'Overview', 'Payouts' ),
			array_column( $submenu[ $parent_path ], 0 )
		);
		$this->assertSame(
			array( $parent_path, 'wc-admin&path=/finance/payouts' ),
			array_column( $submenu[ $parent_path ], 2 )
		);

		$this->assertSame( $parent_path, PageController::get_instance()->get_path_from_id( 'woocommerce-finance' ) );
	}

	/**
	 * @testdox Should append the last provider preference to the user data fields without dropping existing ones.
	 */
	public function test_handle_user_data_fields_appends_preference(): void {
		$fields = $this->sut->handle_woocommerce_admin_get_user_data_fields( array( 'existing_field' ) );

		$this->assertSame( array( 'existing_field', 'payments_finance_last_provider' ), $fields );
	}

	/**
	 * @testdox Should treat a non-array user data fields value as empty.
	 */
	public function test_handle_user_data_fields_with_invalid_input(): void {
		$fields = $this->sut->handle_woocommerce_admin_get_user_data_fields( 'not-an-array' );

		$this->assertSame( array( 'payments_finance_last_provider' ), $fields );
	}

	/**
	 * Snapshot the admin menu globals and the PageController pages, then start from an empty admin menu.
	 */
	private function backup_menu_state(): void {
		global $menu, $submenu;

		$backup_globals = array();
		foreach ( self::MENU_GLOBALS as $global_name ) {
			if ( isset( $GLOBALS[ $global_name ] ) ) {
				$backup_globals[ $global_name ] = $GLOBALS[ $global_name ];
			}
		}

		$this->menu_backup = array(
			'globals' => $backup_globals,
			'pages'   => PageController::get_instance()->get_pages(),
		);

		$menu    = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
