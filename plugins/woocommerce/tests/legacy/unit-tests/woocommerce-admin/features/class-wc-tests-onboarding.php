<?php
/**
 * Onboarding Themes Tests.
 *
 * @package WooCommerce\Internal\Admin\Tests\OnboardingThemes
 */

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingThemes;

/**
 * Class WC_Admin_Tests_Onboarding
 */
class WC_Admin_Tests_Onboarding extends WC_Unit_Test_Case {

	/**
	 * Verifies that given an array of theme objects, the object containing Storefront will be sorted to the first position.
	 */
	public function test_sort_woocommerce_themes() {
		$theme1        = (object) array(
			'id'   => 1,
			'slug' => 'ribs',
		);
		$theme2        = (object) array(
			'id'   => 2,
			'slug' => 'chicken',
		);
		$theme3        = (object) array(
			'id'   => 3,
			'slug' => 'Storefront',
		);
		$theme4        = (object) array(
			'id'   => 4,
			'slug' => 'poutine',
		);
		$some_themes   = array( $theme1, $theme2, $theme3, $theme4 );
		$sorted_themes = OnboardingThemes::sort_woocommerce_themes( $some_themes );
		$this->assertEquals( 'Storefront', $sorted_themes[0]->slug );
	}

}
