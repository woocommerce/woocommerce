<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\EmailTemplates;

use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\TemplatesController;
use Automattic\WooCommerce\Internal\EmailEditor\EmailTemplates\WooEmailTemplate;
use WC_Unit_Test_Case;

/**
 * Tests for the TemplatesController class: the email template stays out of the Site Editor.
 */
class TemplatesControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TemplatesController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TemplatesController();
	}

	/**
	 * Clean up the admin screen.
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * @testdox Leaves the WooCommerce email template out of the Site Editor and keeps site templates.
	 */
	public function test_site_editor_does_not_list_the_email_template(): void {
		set_current_screen( 'site-editor' );

		$slugs = $this->filtered_slugs();

		$this->assertSame( array( 'single' ), $slugs );
	}

	/**
	 * @testdox Keeps the email template on other admin screens and outside the admin.
	 * @testWith ["edit-post"]
	 *           ["front"]
	 *
	 * @param string $screen Current screen.
	 */
	public function test_other_screens_keep_the_email_template( string $screen ): void {
		set_current_screen( $screen );

		$this->assertSame( array( 'single', WooEmailTemplate::TEMPLATE_SLUG ), $this->filtered_slugs() );
	}

	/**
	 * Slugs left after filtering a site template and the email template.
	 */
	private function filtered_slugs(): array {
		$site_template        = new \WP_Block_Template();
		$site_template->slug  = 'single';
		$email_template       = new \WP_Block_Template();
		$email_template->slug = WooEmailTemplate::TEMPLATE_SLUG;

		$templates = $this->sut->filter_email_templates( array( $site_template, $email_template ) );

		return array_values( array_map( fn( $template ) => $template->slug, $templates ) );
	}
}
