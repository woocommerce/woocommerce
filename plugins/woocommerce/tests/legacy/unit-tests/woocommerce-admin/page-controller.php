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
	 * @dataProvider provide_matching_route_patterns
	 * @testdox Registered page route patterns match supported current requests.
	 *
	 * @param string $registered_path Registered route pattern.
	 * @param string $request_uri      Current request URI.
	 */
	public function test_registered_page_route_pattern_matches_current_request( $registered_path, $request_uri ) {
		$this->assert_registered_page_for_request(
			'route-pattern-page',
			$request_uri,
			array(
				array(
					'id'   => 'route-pattern-page',
					'path' => $registered_path,
				),
			)
		);
	}

	/**
	 * Data provider for supported route pattern matches.
	 *
	 * @return array[]
	 */
	public function provide_matching_route_patterns() {
		return array(
			'path parameter'               => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample' ),
			'case-insensitive static path' => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2FROUTE-PARAMS%2Fsample' ),
			'trailing slash in pattern'    => array( '/route-params/:itemName/', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample' ),
			'repeated request slashes'     => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample%2F%2F' ),
			'wildcard base path'           => array( '/route-wildcard/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-wildcard' ),
			'wildcard base trailing slash' => array( '/route-wildcard/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-wildcard%2F' ),
			'wildcard descendant path'     => array( '/route-wildcard/*', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-wildcard%2Ftheme%2Falpha' ),
		);
	}

	/**
	 * @dataProvider provide_non_matching_route_patterns
	 * @testdox Registered page route patterns reject unsupported or unrelated current requests.
	 *
	 * @param string $registered_path Registered route pattern.
	 * @param string $request_uri      Current request URI.
	 */
	public function test_registered_page_route_pattern_does_not_match_current_request( $registered_path, $request_uri ) {
		$result = $this->get_registered_page_result_for_request(
			$request_uri,
			array(
				array(
					'id'   => 'route-pattern-page',
					'path' => $registered_path,
				),
			)
		);

		$this->assertFalse( $result['current_page'] );
		$this->assertFalse( $result['is_registered_page'] );
	}

	/**
	 * Data provider for unsupported or unrelated route pattern requests.
	 *
	 * @return array[]
	 */
	public function provide_non_matching_route_patterns() {
		return array(
			'parameter with extra segment' => array( '/route-params/:itemName', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-params%2Fsample%2Fdetails' ),
			'partial parameter segment'    => array( '/route-patterns/:itemId/prefix-:suffix', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fprefix-value' ),
			'non-terminal wildcard'        => array( '/route-patterns/:itemId/*/details', '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fone%2Fdetails' ),
			'different page root'          => array( '/route-params/:itemName', '/wp-admin/admin.php?page=other-page-root&path=%2Froute-params%2Fsample' ),
		);
	}

	/**
	 * @dataProvider provide_static_route_precedence_requests
	 * @testdox Exact registered pages win over earlier route patterns.
	 *
	 * @param string $request_uri Current request URI.
	 */
	public function test_registered_page_exact_path_takes_precedence_over_route_pattern( $request_uri ) {
		$this->assert_registered_page_for_request(
			'route-exact-page',
			$request_uri,
			array(
				array(
					'id'   => 'route-param-page',
					'path' => '/route-patterns/:itemId',
				),
				array(
					'id'   => 'route-exact-page',
					'path' => '/route-patterns/settings',
				),
			)
		);
	}

	/**
	 * Data provider for static route precedence requests.
	 *
	 * @return array[]
	 */
	public function provide_static_route_precedence_requests() {
		return array(
			'exact request'            => array( '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2Fsettings' ),
			'case-normalized request'  => array( '/wp-admin/admin.php?page=wc-admin&path=%2FROUTE-PATTERNS%2Fsettings' ),
			'slash-normalized request' => array( '/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2Fsettings%2F%2F' ),
		);
	}

	/**
	 * @testdox More specific registered route patterns win over wildcard patterns.
	 */
	public function test_registered_page_specific_route_pattern_takes_precedence_over_wildcard() {
		$this->assert_registered_page_for_request(
			'route-details-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123%2Fdetails',
			array(
				array(
					'id'   => 'route-wildcard-page',
					'path' => '/route-patterns/*',
				),
				array(
					'id'   => 'route-details-page',
					'path' => '/route-patterns/:itemId/details',
				),
			)
		);
	}

	/**
	 * @testdox Equal specificity registered route patterns use registration order.
	 */
	public function test_registered_page_equal_specificity_route_patterns_use_registration_order() {
		$this->assert_registered_page_for_request(
			'route-earlier-param-page',
			'/wp-admin/admin.php?page=wc-admin&path=%2Froute-patterns%2F123',
			array(
				array(
					'id'   => 'route-earlier-param-page',
					'path' => '/route-patterns/:earlierId',
				),
				array(
					'id'   => 'route-later-param-page',
					'path' => '/route-patterns/:laterId',
				),
			)
		);
	}

	/**
	 * Gets the PageController result for a simulated admin request.
	 *
	 * @param string $request_uri Request URI.
	 * @param array  $pages       Pages to register.
	 * @return array{current_page: array|false, is_registered_page: bool}
	 */
	private function get_registered_page_result_for_request( $request_uri, array $pages ) {
		$controller            = PageController::get_instance();
		$reflection            = new \ReflectionClass( $controller );
		$pages_property        = $reflection->getProperty( 'pages' );
		$current_page_property = $reflection->getProperty( 'current_page' );
		$pages_property->setAccessible( true );
		$current_page_property->setAccessible( true );

		$original_pages        = $pages_property->getValue( $controller );
		$original_current_page = $current_page_property->getValue( $controller );
		$original_request_uri  = $_SERVER['REQUEST_URI'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test cleanup restores the raw original request URI.
		$registered_pages      = array();

		foreach ( $pages as $page ) {
			$registered_pages[ $page['id'] ] = array(
				'id'      => $page['id'],
				'path'    => PageController::PAGE_ROOT . '&path=' . $page['path'],
				'js_page' => true,
			);
		}

		try {
			$pages_property->setValue( $controller, $registered_pages );
			$current_page_property->setValue( $controller, null );
			$_SERVER['REQUEST_URI'] = $request_uri;
			if ( ! did_action( 'current_screen' ) ) {
				set_current_screen( 'woocommerce_page_wc-admin' );
			}

			$controller->determine_current_page();
			return array(
				'current_page'       => $controller->get_current_page(),
				'is_registered_page' => wc_admin_is_registered_page(),
			);
		} finally {
			$pages_property->setValue( $controller, $original_pages );
			$current_page_property->setValue( $controller, $original_current_page );

			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
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
		$result       = $this->get_registered_page_result_for_request( $request_uri, $pages );
		$current_page = $result['current_page'];

		$this->assertTrue( $result['is_registered_page'], 'A matching page should be reported through wc_admin_is_registered_page().' );
		$this->assertIsArray( $current_page, 'A matching registered page should be detected.' );
		$this->assertSame( $expected_page_id, $current_page['id'] );
	}
}
