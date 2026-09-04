<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Onboarding;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingSetupWizard;
use WC_Unit_Test_Case;

/**
 * Tests for the OnboardingSetupWizard class.
 */
class OnboardingSetupWizardTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OnboardingSetupWizard
	 */
	private $sut;

	/**
	 * Original query parameters.
	 *
	 * @var array
	 */
	private array $original_get = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->sut          = new OnboardingSetupWizard();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_GET = $this->original_get;

		parent::tearDown();
	}

	/**
	 * @testdox Should allow zooming on the setup wizard.
	 */
	public function test_set_viewport_meta_tag_allows_zoom_on_setup_wizard(): void {
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/setup-wizard',
		);

		$result = $this->sut->set_viewport_meta_tag( 'original viewport value' );

		$this->assertSame(
			'width=device-width, initial-scale=1.0',
			$result,
			'The setup wizard viewport should not restrict zooming.'
		);
	}

	/**
	 * @testdox Should leave the viewport meta tag unchanged outside the setup wizard.
	 */
	public function test_set_viewport_meta_tag_leaves_other_admin_pages_unchanged(): void {
		$_GET = array(
			'page' => 'wc-admin',
			'path' => '/analytics/overview',
		);

		$result = $this->sut->set_viewport_meta_tag( 'original viewport value' );

		$this->assertSame(
			'original viewport value',
			$result,
			'Other admin pages should retain their original viewport meta tag.'
		);
	}
}
