<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\AccountUtil;

/**
 * Tests for the account utility class.
 */
class AccountUtilTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox `get_edit_address_title` returns the default billing and shipping titles.
	 */
	public function test_get_edit_address_title() {
		$this->assertSame( 'Billing address', AccountUtil::get_edit_address_title( 'billing' ) );
		$this->assertSame( 'Shipping address', AccountUtil::get_edit_address_title( 'shipping' ) );
	}

	/**
	 * @testdox `get_edit_address_title` preserves the existing title filter.
	 */
	public function test_get_edit_address_title_filter() {
		$filter = function ( $title, $address_type ) {
			$this->assertSame( 'Shipping address', $title );
			$this->assertSame( 'shipping', $address_type );

			return 'Delivery address';
		};

		add_filter( 'woocommerce_my_account_edit_address_title', $filter, 10, 2 );

		try {
			$this->assertSame( 'Delivery address', AccountUtil::get_edit_address_title( 'shipping' ) );
		} finally {
			remove_filter( 'woocommerce_my_account_edit_address_title', $filter, 10 );
		}
	}

	/**
	 * @testdox `get_edit_address_title` falls back to the default title when the filter does not return a string.
	 */
	public function test_get_edit_address_title_filter_non_string_fallback() {
		$filter = function () {
			return null;
		};

		add_filter( 'woocommerce_my_account_edit_address_title', $filter );

		try {
			$this->assertSame( 'Billing address', AccountUtil::get_edit_address_title( 'billing' ) );
		} finally {
			remove_filter( 'woocommerce_my_account_edit_address_title', $filter );
		}
	}
}
