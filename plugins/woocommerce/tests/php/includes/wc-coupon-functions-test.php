<?php
declare( strict_types = 1 );

/**
 * Coupon functions tests
 *
 * @package WooCommerce\Tests\Coupon.
 */

/**
 * Class WC_Coupon_Functions_Test
 */
class WC_Coupon_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Data provider for test_existing_coupon_is_found_by_the_code_shown_to_merchants.
	 *
	 * Stored titles are in the formats earlier versions saved, which used kses to encode them.
	 *
	 * @return array[]
	 */
	public function data_provider_existing_coupon_codes(): array {
		return array(
			'plain code'                            => array( 'SUMMER10', 'summer10' ),
			'ampersand'                             => array( 'B&amp;Q', 'B&Q' ),
			'apostrophe'                            => array( "TEST'S", "test's" ),
			'ampersand and apostrophe'              => array( "M&amp;M'S", "M&M'S" ),
			'double quotes'                         => array( 'TEST"2024"', 'TEST"2024"' ),
			'percent followed by digits'            => array( '%20TEST', '%20TEST' ),
			'percent sign'                          => array( '50%OFF', '50%off' ),
			'non-ASCII'                             => array( 'CAFÉ', 'café' ),
			'non-ASCII with ampersand'              => array( 'ÜBER&amp;ÄRGER', 'über&ärger' ),
			'decoded entity'                        => array( '©2024', '©2024' ),
			'greater-than sign'                     => array( 'a&gt;b', 'a>b' ),
			'less-than sign before a digit'         => array( '10&lt;20', '10<20' ),
			'less-than sign before a letter'        => array( 'a&lt;b', 'a<b' ),
			'ampersand before a legacy entity name' => array( '50&amp;cent', '50&cent' ),
			'ampersand before a semicolon'          => array( 'R&amp;D;x', 'R&D;x' ),
			'backslash saved by an administrator'   => array( 'a\\b', 'a\\b' ),
		);
	}

	/**
	 * @testdox Should find coupons saved by earlier versions by the code shown to merchants.
	 *
	 * @dataProvider data_provider_existing_coupon_codes
	 *
	 * @param string $stored_title Post title as saved by an earlier version.
	 * @param string $shown_code   Code as shown to the merchant, and so as entered by customers.
	 */
	public function test_existing_coupon_is_found_by_the_code_shown_to_merchants( string $stored_title, string $shown_code ): void {
		global $wpdb;

		$coupon_id = wp_insert_post(
			array(
				'post_title'  => 'placeholder',
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
			)
		);
		$wpdb->update( $wpdb->posts, array( 'post_title' => $stored_title ), array( 'ID' => $coupon_id ) );
		clean_post_cache( $coupon_id );

		wp_set_current_user( 0 );
		$this->assertSame( $coupon_id, wc_get_coupon_id_by_code( wc_format_coupon_code( $shown_code ) ) );
	}
}
