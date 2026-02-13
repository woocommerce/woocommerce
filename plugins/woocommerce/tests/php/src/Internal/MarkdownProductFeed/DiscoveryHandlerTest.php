<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MarkdownProductFeed;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\MarkdownProductFeed\DiscoveryHandler;
use WC_Unit_Test_Case;

/**
 * Tests for the DiscoveryHandler class.
 */
class DiscoveryHandlerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var DiscoveryHandler
	 */
	private DiscoveryHandler $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DiscoveryHandler();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_actions( 'wp_head' );
		remove_all_actions( 'send_headers' );
		remove_all_actions( 'init' );
		remove_all_actions( 'template_redirect' );
		remove_all_filters( 'query_vars' );
		parent::tearDown();
	}

	/**
	 * @testdox Should not register any hooks when the feature is disabled.
	 */
	public function test_register_does_nothing_when_feature_disabled(): void {
		$this->set_feature_enabled( false );

		$this->sut->register();

		$this->assertFalse(
			has_action( 'wp_head', array( $this->sut, 'handle_wp_head' ) ),
			'wp_head hook should not be registered when feature is disabled'
		);
		$this->assertFalse(
			has_action( 'send_headers', array( $this->sut, 'handle_send_headers' ) ),
			'send_headers hook should not be registered when feature is disabled'
		);
		$this->assertFalse(
			has_filter( 'query_vars', array( $this->sut, 'handle_query_vars' ) ),
			'query_vars filter should not be registered when feature is disabled'
		);

		$this->reset_feature_mock();
	}

	/**
	 * @testdox Should register all hooks when the feature is enabled.
	 */
	public function test_register_hooks_when_feature_enabled(): void {
		$this->set_feature_enabled( true );

		$this->sut->register();

		$this->assertNotFalse(
			has_action( 'wp_head', array( $this->sut, 'handle_wp_head' ) ),
			'wp_head hook should be registered when feature is enabled'
		);
		$this->assertNotFalse(
			has_action( 'send_headers', array( $this->sut, 'handle_send_headers' ) ),
			'send_headers hook should be registered when feature is enabled'
		);
		$this->assertNotFalse(
			has_action( 'init', array( $this->sut, 'handle_init' ) ),
			'init hook should be registered when feature is enabled'
		);
		$this->assertNotFalse(
			has_action( 'template_redirect', array( $this->sut, 'handle_template_redirect' ) ),
			'template_redirect hook should be registered when feature is enabled'
		);
		$this->assertNotFalse(
			has_filter( 'query_vars', array( $this->sut, 'handle_query_vars' ) ),
			'query_vars filter should be registered when feature is enabled'
		);

		$this->reset_feature_mock();
	}

	/**
	 * @testdox Should add wc_llms_txt to the query vars array.
	 */
	public function test_handle_query_vars_adds_wc_llms_txt(): void {
		$input  = array( 'existing_var' );
		$result = $this->sut->handle_query_vars( $input );

		$this->assertContains( 'wc_llms_txt', $result, 'handle_query_vars should add wc_llms_txt to the query vars' );
		$this->assertContains( 'existing_var', $result, 'Existing query vars should be preserved' );
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
