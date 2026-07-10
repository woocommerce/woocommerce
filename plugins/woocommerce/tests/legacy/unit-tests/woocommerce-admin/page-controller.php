<?php
/**
 * PageController tests
 *
 * @package WooCommerce\Admin\Tests\PageController
 */

use Automattic\WooCommerce\Admin\PageController;

/**
 * WC_Admin_Tests_Page_Controller Class
 *
 * @package WooCommerce\Admin\Tests\PageController
 */
class WC_Admin_Tests_Page_Controller extends WP_UnitTestCase {

	/**
	 * Test get_breadcrumbs()
	 */
	public function test_get_breadcrumbs_no_parent() {

		// orders page registration data.
		$orders_page = array(
			'id'        => 'woocommerce-orders',
			'screen_id' => 'edit-shop_order',
			'path'      => add_query_arg( 'post_type', 'shop_order', 'edit.php' ),
			'title'     => array( 'Orders' ),
		);

		$controller = PageController::get_instance();

		// Connect existing pages to wc-admin.
		$controller->connect_page( $orders_page );

		// Need to set current screen to use "get_current_screen()".
		set_current_screen( 'edit-shop_order' );

		// Set the private current_page variable to order page.
		$reflection = new \ReflectionClass( $controller );
		$property   = $reflection->getProperty( 'current_page' );
		$property->setAccessible( true );
		$property->setValue( $controller, $orders_page );

		$breadcrumbs = $controller->get_breadcrumbs();

		$this->assertEquals(
			2,
			count( $breadcrumbs ),
			'Orders page should have 2 breadcrumbs items.'
		);

		$this->assertEquals(
			array(
				'admin.php?page=wc-admin',
				'WooCommerce',
			),
			$breadcrumbs[0],
			'Orders home breadcrumb should be WooCommerce.'
		);

		$this->assertEquals(
			'Orders',
			$breadcrumbs[1],
			'Orders current breadcrumb should be a simple "Orders" string.'
		);
	}

	/**
	 * Test get_breadcrumbs()
	 */
	public function test_get_breadcrumbs_with_parent() {

		// coupon page registration data.
		$coupon_page = array(
			'id'        => 'woocommerce-coupons',
			'parent'    => 'woocommerce-marketing',
			'screen_id' => 'edit-shop_coupon',
			'path'      => add_query_arg( 'post_type', 'shop_coupon', 'edit.php' ),
			'title'     => array( 'Coupons' ),
		);

		// marketing page registration data.
		$marketing_page = array(
			'id'       => 'woocommerce-marketing',
			'title'    => 'Marketing',
			'path'     => '/marketing',
			'icon'     => 'dashicons-megaphone',
			'position' => 58,
		);

		$controller = PageController::get_instance();

		// Connect existing pages to wc-admin.
		$controller->connect_page( $coupon_page );

		// Register wc-admin JS page.
		$controller->register_page( $marketing_page );

		// Need to set current screen to use "get_current_screen()".
		set_current_screen( 'edit-shop_coupon' );

		// Set the private current_page variable to coupon page.
		$reflection = new \ReflectionClass( $controller );
		$property   = $reflection->getProperty( 'current_page' );
		$property->setAccessible( true );
		$property->setValue( $controller, $coupon_page );

		$breadcrumbs = $controller->get_breadcrumbs();

		$this->assertEquals(
			3,
			count( $breadcrumbs ),
			'Coupons page should have 3 breadcrumbs items.'
		);

		$this->assertEquals(
			array(
				'admin.php?page=wc-admin',
				'WooCommerce',
			),
			$breadcrumbs[0],
			'Coupons home breadcrumb should be WooCommerce.'
		);

		$this->assertEquals(
			array(
				'admin.php?page=wc-admin&path=/marketing',
				'Marketing',
			),
			$breadcrumbs[1],
			'Coupons parent should be Marketing.'
		);

		$this->assertEquals(
			'Coupons',
			$breadcrumbs[2],
			'Coupons current breadcrumb should be a simple "Coupons" string.'
		);
	}

	/**
	 * @testdox Registered pages match current requests with route parameters.
	 */
	public function test_registered_page_path_parameter_matches_current_request() {
		$this->assert_registered_page_for_request(
			'codex-param-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-param%2FWoo',
			array(
				array(
					'id'   => 'codex-param-page',
					'path' => '/codex-param/:unicornName',
				),
			)
		);
	}

	/**
	 * @testdox Registered page path parameters only match one segment.
	 */
	public function test_registered_page_path_parameter_does_not_match_extra_segments() {
		$current_page = $this->determine_registered_page_for_request(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-param%2FWoo%2Fdetails',
			array(
				array(
					'id'   => 'codex-param-page',
					'path' => '/codex-param/:unicornName',
				),
			)
		);

		$this->assertFalse( $current_page );
	}

	/**
	 * @testdox Registered page path parameters match requests with a trailing slash.
	 */
	public function test_registered_page_path_parameter_matches_trailing_slash_request() {
		$this->assert_registered_page_for_request(
			'codex-param-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-param%2FWoo%2F',
			array(
				array(
					'id'   => 'codex-param-page',
					'path' => '/codex-param/:unicornName',
				),
			)
		);
	}

	/**
	 * @testdox Registered pages match terminal wildcard requests.
	 */
	public function test_registered_page_terminal_wildcard_matches_base_and_descendant_paths() {
		$pages = array(
			array(
				'id'   => 'codex-wildcard-page',
				'path' => '/codex-wildcard/*',
			),
		);

		$this->assert_registered_page_for_request(
			'codex-wildcard-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-wildcard',
			$pages
		);
		$this->assert_registered_page_for_request(
			'codex-wildcard-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-wildcard%2F',
			$pages
		);
		$this->assert_registered_page_for_request(
			'codex-wildcard-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-wildcard%2Ftheme%2Falpha',
			$pages
		);
	}

	/**
	 * @testdox Exact registered pages win over earlier route patterns.
	 */
	public function test_registered_page_exact_path_takes_precedence_over_route_pattern() {
		$this->assert_registered_page_for_request(
			'codex-exact-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-routes%2Fsettings',
			array(
				array(
					'id'   => 'codex-param-page',
					'path' => '/codex-routes/:itemId',
				),
				array(
					'id'   => 'codex-exact-page',
					'path' => '/codex-routes/settings',
				),
			)
		);
	}

	/**
	 * @testdox More specific registered route patterns win over wildcard patterns.
	 */
	public function test_registered_page_specific_route_pattern_takes_precedence_over_wildcard() {
		$this->assert_registered_page_for_request(
			'codex-details-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-routes%2F123%2Fdetails',
			array(
				array(
					'id'   => 'codex-wildcard-page',
					'path' => '/codex-routes/*',
				),
				array(
					'id'   => 'codex-details-page',
					'path' => '/codex-routes/:itemId/details',
				),
			)
		);
	}

	/**
	 * @testdox Equal specificity registered route patterns use registration order.
	 */
	public function test_registered_page_equal_specificity_route_patterns_use_registration_order() {
		$this->assert_registered_page_for_request(
			'codex-earlier-param-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcodex-routes%2F123',
			array(
				array(
					'id'   => 'codex-earlier-param-page',
					'path' => '/codex-routes/:earlierId',
				),
				array(
					'id'   => 'codex-later-param-page',
					'path' => '/codex-routes/:laterId',
				),
			)
		);
	}

	/**
	 * @testdox Registered page route patterns require the same page root.
	 */
	public function test_registered_page_route_pattern_requires_same_page_root() {
		$current_page = $this->determine_registered_page_for_request(
			'/wp-admin/admin.php?page=codex-other-root&path=%2Fcodex-param%2FWoo',
			array(
				array(
					'id'   => 'codex-param-page',
					'path' => '/codex-param/:unicornName',
				),
			)
		);

		$this->assertFalse( $current_page );
	}

	/**
	 * Determines the current PageController page for a simulated admin request.
	 *
	 * @param string $request_uri Request URI.
	 * @param array  $pages       Pages to register.
	 * @return array|false
	 */
	private function determine_registered_page_for_request( $request_uri, array $pages ) {
		$controller            = PageController::get_instance();
		$reflection            = new \ReflectionClass( $controller );
		$pages_property        = $reflection->getProperty( 'pages' );
		$current_page_property = $reflection->getProperty( 'current_page' );
		$pages_property->setAccessible( true );
		$current_page_property->setAccessible( true );

		$original_pages        = $pages_property->getValue( $controller );
		$original_current_page = $current_page_property->getValue( $controller );
		$original_request_uri  = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request URI.
		$global_keys           = array( 'menu', 'submenu', 'admin_page_hooks', '_registered_pages', '_parent_pages', '_wp_submenu_nopriv' );
		$global_backups        = array();

		foreach ( $global_keys as $global_key ) {
			$global_backups[ $global_key ] = $GLOBALS[ $global_key ] ?? null;
			$GLOBALS[ $global_key ]        = array();
		}

		try {
			$pages_property->setValue( $controller, array() );
			$current_page_property->setValue( $controller, null );
			$_SERVER['REQUEST_URI'] = $request_uri;

			foreach ( $pages as $page ) {
				$controller->register_page(
					wp_parse_args(
						$page,
						array(
							'title'      => 'Codex test page',
							'page_title' => 'Codex test page',
							'capability' => 'manage_woocommerce',
						)
					)
				);
			}

			$controller->determine_current_page();
			return $current_page_property->getValue( $controller );
		} finally {
			$pages_property->setValue( $controller, $original_pages );
			$current_page_property->setValue( $controller, $original_current_page );

			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			}

			foreach ( $global_backups as $global_key => $global_value ) {
				if ( null === $global_value ) {
					unset( $GLOBALS[ $global_key ] );
				} else {
					$GLOBALS[ $global_key ] = $global_value;
				}
			}
		}
	}

	/**
	 * Asserts that a simulated request matches the expected registered page.
	 *
	 * @param string $expected_page_id Expected page ID.
	 * @param string $request_uri      Request URI.
	 * @param array  $pages            Pages to register.
	 */
	private function assert_registered_page_for_request( $expected_page_id, $request_uri, array $pages ) {
		$current_page = $this->determine_registered_page_for_request( $request_uri, $pages );

		$this->assertIsArray( $current_page, 'A matching registered page should be detected.' );
		$this->assertSame( $expected_page_id, $current_page['id'] );
	}
}
