<?php

declare(strict_types = 1);

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\EmailTemplates;

use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplateApiController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;

/**
 * Tests for the TemplateApiController class.
 */
class TemplateApiControllerTest extends \WC_Unit_Test_Case {
	/**
	 * @var TemplateApiController $template_api_controller
	 */
	private TemplateApiController $template_api_controller;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->template_api_controller = new TemplateApiController();
	}

	/**
	 * Test that getTemplateData returns empty array for non-WooCommerce templates.
	 */
	public function testItDoesNotGetTemplateDataForNonWooTemplate(): void {
		$template_data = array(
			'slug' => 'non-woo-template',
		);

		$result = $this->template_api_controller->get_template_data( $template_data );
		$this->assertEmpty( $result );
	}

	/**
	 * Test that getTemplateData returns sender settings for WooCommerce templates.
	 */
	public function testItGetsTemplateDataForWooTemplate(): void {
		$from_name    = 'Test Store';
		$from_address = 'test@example.com';

		update_option( 'woocommerce_email_from_name', $from_name );
		update_option( 'woocommerce_email_from_address', $from_address );

		$template_data = array(
			'slug' => WooEmailTemplate::TEMPLATE_SLUG,
		);

		$result = $this->template_api_controller->get_template_data( $template_data );

		$this->assertArrayHasKey( 'sender_settings', $result );
		$this->assertEquals( $from_name, $result['sender_settings']['from_name'] );
		$this->assertEquals( $from_address, $result['sender_settings']['from_address'] );
	}

	/**
	 * Test that saveData updates WooCommerce email settings.
	 */
	public function testItSavesTemplateDataForWooTemplate(): void {
		$new_from_name    = 'New Store Name';
		$new_from_address = 'new@example.com';

		$template       = new \WP_Block_Template();
		$template->slug = WooEmailTemplate::TEMPLATE_SLUG;

		$data = array(
			'sender_settings' => array(
				'from_name'    => $new_from_name,
				'from_address' => $new_from_address,
			),
		);

		$this->template_api_controller->save_template_data( $data, $template );

		$this->assertEquals( $new_from_name, get_option( 'woocommerce_email_from_name' ) );
		$this->assertEquals( $new_from_address, get_option( 'woocommerce_email_from_address' ) );
	}

	/**
	 * Test that saveTemplateData does not update settings for non-WooCommerce templates.
	 */
	public function testItDoesNotSaveTemplateDataForNonWooTemplate(): void {
		$original_from_name    = 'Original Store';
		$original_from_address = 'original@example.com';

		update_option( 'woocommerce_email_from_name', $original_from_name );
		update_option( 'woocommerce_email_from_address', $original_from_address );

		$template       = new \WP_Block_Template();
		$template->slug = 'non-woo-template';

		$data = array(
			'sender_settings' => array(
				'from_name'    => 'New Store',
				'from_address' => 'new@example.com',
			),
		);

		$this->template_api_controller->save_template_data( $data, $template );

		$this->assertEquals( $original_from_name, get_option( 'woocommerce_email_from_name' ) );
		$this->assertEquals( $original_from_address, get_option( 'woocommerce_email_from_address' ) );
	}

	/**
	 * Test that saveTemplateData returns an error for invalid email addresses.
	 */
	public function testItReturnsErrorForInvalidEmailAddress(): void {
		$template       = new \WP_Block_Template();
		$template->slug = WooEmailTemplate::TEMPLATE_SLUG;

		$data = array(
			'sender_settings' => array(
				'from_name'    => 'Test Store',
				'from_address' => 'invalid-email',
			),
		);

		$result = $this->template_api_controller->save_template_data( $data, $template );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertEquals( $result->get_error_code(), 'invalid_email_address' );
	}
}
