<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\MarkdownProductFeed\MarkdownProductFeedController;
use WC_Unit_Test_Case;

/**
 * Tests for the MarkdownProductFeedController class.
 */
class MarkdownProductFeedControllerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var MarkdownProductFeedController
	 */
	private MarkdownProductFeedController $sut;

	/**
	 * Original FeaturesController instance, stored for tearDown restoration.
	 *
	 * @var FeaturesController|null
	 */
	private ?FeaturesController $original_features_controller = null;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new MarkdownProductFeedController();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_actions( 'template_redirect' );
		parent::tearDown();
	}

	/**
	 * @testdox Should not register any hooks when the feature is disabled.
	 */
	public function test_register_does_nothing_when_feature_disabled(): void {
		$this->set_feature_enabled( false );

		$priority_before = has_action( 'template_redirect', array( $this->sut, 'handle_template_redirect' ) );

		$this->sut->register();

		$priority_after = has_action( 'template_redirect', array( $this->sut, 'handle_template_redirect' ) );

		$this->assertFalse( $priority_before, 'Hook should not exist before register' );
		$this->assertFalse( $priority_after, 'Hook should not be added when feature is disabled' );

		$this->reset_feature_mock();
	}

	/**
	 * @testdox Should hook template_redirect when the feature is enabled.
	 */
	public function test_register_hooks_template_redirect_when_enabled(): void {
		$this->set_feature_enabled( true );

		$this->sut->register();

		$has_hook = has_action( 'template_redirect', array( $this->sut, 'handle_template_redirect' ) );

		$this->assertNotFalse( $has_hook, 'template_redirect should be hooked when feature is enabled' );

		$this->reset_feature_mock();
	}

	/**
	 * Replace FeaturesController in the DI container with a mock that returns
	 * the desired feature state for markdown_product_feed.
	 *
	 * @param bool $enabled Whether the feature should be reported as enabled.
	 */
	private function set_feature_enabled( bool $enabled ): void {
		$mock = $this->getMockBuilder( FeaturesController::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'feature_is_enabled' ) )
			->getMock();

		$mock->method( 'feature_is_enabled' )
			->willReturnCallback(
				function ( string $feature_id ) use ( $enabled ) {
					return 'markdown_product_feed' === $feature_id ? $enabled : false;
				}
			);

		wc_get_container()->replace( FeaturesController::class, $mock );
	}

	/**
	 * Reset the FeaturesController mock in the DI container.
	 */
	private function reset_feature_mock(): void {
		wc_get_container()->reset_replacement( FeaturesController::class );
	}
}
