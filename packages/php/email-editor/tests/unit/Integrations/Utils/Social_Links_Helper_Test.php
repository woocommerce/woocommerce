<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * Unit test for Social_Links_Helper class
 */
class Social_Links_Helper_Test extends \Email_Editor_Unit_Test {
	/**
	 * Test it detects whiteish colors
	 */
	public function testItDetectsWhiteishColors(): void {
		// Test pure white.
		$this->assertTrue( Social_Links_Helper::detect_whiteish_color( '#ffffff' ) );
		$this->assertTrue( Social_Links_Helper::detect_whiteish_color( '#fff' ) );

		// Test very light colors.
		$this->assertTrue( Social_Links_Helper::detect_whiteish_color( '#fafafa' ) );
		$this->assertTrue( Social_Links_Helper::detect_whiteish_color( '#f5f5f5' ) );

		// Test non-whiteish colors.
		$this->assertFalse( Social_Links_Helper::detect_whiteish_color( '#000000' ) );
		$this->assertFalse( Social_Links_Helper::detect_whiteish_color( '#ff0000' ) );
		$this->assertFalse( Social_Links_Helper::detect_whiteish_color( '#00ff00' ) );
		$this->assertFalse( Social_Links_Helper::detect_whiteish_color( '#0000ff' ) );

		// Test empty input.
		$this->assertFalse( Social_Links_Helper::detect_whiteish_color( '' ) );
	}

	/**
	 * Test it gets service brand colors
	 */
	public function testItGetsServiceBrandColors(): void {
		// Test known services.
		$this->assertEquals( '#0866ff', Social_Links_Helper::get_service_brand_color( 'facebook' ) );
		$this->assertEquals( '#1da1f2', Social_Links_Helper::get_service_brand_color( 'twitter' ) );
		$this->assertEquals( '#0d66c2', Social_Links_Helper::get_service_brand_color( 'linkedin' ) );
		$this->assertEquals( '#f00075', Social_Links_Helper::get_service_brand_color( 'instagram' ) );

		// Test unknown service.
		$this->assertEquals( '', Social_Links_Helper::get_service_brand_color( 'unknown_service' ) );
	}

	/**
	 * Test it gets default social link size
	 */
	public function testItGetsDefaultSocialLinkSize(): void {
		$this->assertEquals( 'has-normal-icon-size', Social_Links_Helper::get_default_social_link_size() );
	}

	/**
	 * Test it gets social link size option value
	 */
	public function testItGetsSocialLinkSizeOptionValue(): void {
		$sizes = array(
			'has-small-icon-size'  => '16px',
			'has-normal-icon-size' => '24px',
			'has-large-icon-size'  => '36px',
			'has-huge-icon-size'   => '48px',
		);

		foreach ( $sizes as $size_class => $expected_size ) {
			$this->assertEquals( $expected_size, Social_Links_Helper::get_social_link_size_option_value( $size_class ) );
		}

		// Test unknown size.
		$this->assertEquals( '24px', Social_Links_Helper::get_social_link_size_option_value( 'unknown_size' ) );
	}
}
