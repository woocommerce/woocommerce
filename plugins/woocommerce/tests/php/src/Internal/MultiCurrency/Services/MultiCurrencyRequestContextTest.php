<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRequestContext;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyRequestContext class.
 */
class MultiCurrencyRequestContextTest extends WC_Unit_Test_Case {

	/**
	 * Original request data.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_request;

	/**
	 * Original server data.
	 *
	 * @var array<string,mixed>
	 */
	private array $original_server;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->original_server  = $_SERVER;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$_REQUEST = $this->original_request;
		$_SERVER  = $this->original_server;

		parent::tearDown();
	}

	/**
	 * @testdox Should treat normal requests as frontend requests.
	 */
	public function test_treats_normal_request_as_frontend_request(): void {
		$sut = $this->create_context();

		$this->assertTrue( $sut->is_frontend_request(), 'Normal requests should be frontend requests.' );
		$this->assertTrue( $sut->should_register_frontend_hooks(), 'Frontend hooks should register for normal frontend requests.' );
		$this->assertTrue( $sut->should_register_selected_currency_entry_hooks(), 'Selected-currency writers should register for normal frontend requests.' );
	}

	/**
	 * @testdox Should block frontend hooks for admin context.
	 */
	public function test_blocks_frontend_hooks_for_admin_context(): void {
		$sut = $this->create_context( true );

		$this->assertFalse( $sut->is_frontend_request(), 'Admin requests should not be frontend requests.' );
		$this->assertFalse( $sut->should_register_frontend_hooks(), 'Frontend hooks should not register in admin.' );
		$this->assertFalse( $sut->should_register_selected_currency_entry_hooks(), 'Selected-currency writers should not register in admin unless this is Store API.' );
	}

	/**
	 * @testdox Should block frontend hooks for cron context.
	 */
	public function test_blocks_frontend_hooks_for_cron_context(): void {
		$sut = $this->create_context( false, true );

		$this->assertFalse( $sut->is_frontend_request(), 'Cron requests should not be frontend requests.' );
		$this->assertFalse( $sut->should_register_frontend_hooks(), 'Frontend hooks should not register during cron.' );
		$this->assertFalse( $sut->should_register_selected_currency_entry_hooks(), 'Selected-currency writers should not register during cron unless this is Store API.' );
	}

	/**
	 * @testdox Should block frontend hooks for admin REST context.
	 */
	public function test_blocks_frontend_hooks_for_admin_rest_context(): void {
		$_SERVER['HTTP_REFERER'] = admin_url( 'admin.php?page=wc-admin' );
		$sut                     = $this->create_context( false, false, true, false );

		$this->assertTrue( $sut->is_admin_api_request(), 'Admin-originated WooCommerce REST requests should be detected.' );
		$this->assertFalse( $sut->is_frontend_request(), 'Admin REST requests should not be frontend requests.' );
		$this->assertFalse( $sut->should_register_frontend_hooks(), 'Frontend hooks should not register for admin REST.' );
		$this->assertFalse( $sut->should_register_selected_currency_entry_hooks(), 'Selected-currency writers should not register for non-Store admin REST.' );
	}

	/**
	 * @testdox Should allow REST request overrides for non-Store REST context.
	 */
	public function test_allows_rest_request_overrides_for_non_store_rest_context(): void {
		$sut = $this->create_context( false, false, true, false );

		$this->assertTrue( $sut->should_register_rest_request_overrides(), 'Non-Store REST requests should register request-local currency overrides.' );
	}

	/**
	 * @testdox Should block REST request overrides for Store API context.
	 */
	public function test_blocks_rest_request_overrides_for_store_api_context(): void {
		$sut = $this->create_context( false, false, true, true );

		$this->assertFalse( $sut->should_register_rest_request_overrides(), 'Store API requests should not register non-Store REST overrides.' );
	}

	/**
	 * @testdox Should block REST request overrides for Store API batch context.
	 */
	public function test_blocks_rest_request_overrides_for_store_api_batch_context(): void {
		$_REQUEST['rest_route'] = '/wc/store/v1/batch';
		$sut                    = $this->create_context( false, false, true, false );

		$this->assertFalse( $sut->should_register_rest_request_overrides(), 'Store API batch requests should not register non-Store REST overrides.' );
	}

	/**
	 * @testdox Should block REST request overrides for non-REST context.
	 */
	public function test_blocks_rest_request_overrides_for_non_rest_context(): void {
		$sut = $this->create_context();

		$this->assertFalse( $sut->should_register_rest_request_overrides(), 'Non-REST requests should not register REST overrides.' );
	}

	/**
	 * @testdox Should allow selected currency writers for Store API context.
	 */
	public function test_allows_selected_currency_writers_for_store_api_context(): void {
		$sut = $this->create_context( true, false, false, true );

		$this->assertFalse( $sut->should_register_frontend_hooks(), 'Frontend hooks should remain blocked in admin Store API contexts.' );
		$this->assertTrue( $sut->should_register_selected_currency_entry_hooks(), 'Selected-currency writers should register for Store API requests.' );
	}

	/**
	 * @testdox Should detect Store API batch routes.
	 */
	public function test_detects_store_api_batch_routes(): void {
		$_REQUEST['rest_route'] = '/wc/store/v1/batch';
		$sut                    = $this->create_context();

		$this->assertTrue( $sut->is_store_batch_request(), 'Store API batch routes should be detected from rest_route.' );
	}

	/**
	 * Create a request context test double.
	 *
	 * @param bool      $is_admin    Whether the request is admin.
	 * @param bool      $doing_cron  Whether cron is running.
	 * @param bool      $is_rest     Whether this is a REST request.
	 * @param bool|null $is_store_api Store API result, or null to use URI fallback.
	 * @return MultiCurrencyRequestContext
	 */
	private function create_context(
		bool $is_admin = false,
		bool $doing_cron = false,
		bool $is_rest = false,
		?bool $is_store_api = false
	): MultiCurrencyRequestContext {
		return new class( $is_admin, $doing_cron, $is_rest, $is_store_api ) extends MultiCurrencyRequestContext {
			/**
			 * Whether the request is admin.
			 *
			 * @var bool
			 */
			private bool $is_admin;

			/**
			 * Whether cron is running.
			 *
			 * @var bool
			 */
			private bool $doing_cron;

			/**
			 * Whether this is a REST request.
			 *
			 * @var bool
			 */
			private bool $is_rest;

			/**
			 * Store API result.
			 *
			 * @var bool|null
			 */
			private ?bool $is_store_api;

			/**
			 * Constructor.
			 *
			 * @param bool      $is_admin    Whether the request is admin.
			 * @param bool      $doing_cron  Whether cron is running.
			 * @param bool      $is_rest     Whether this is a REST request.
			 * @param bool|null $is_store_api Store API result, or null to use URI fallback.
			 */
			public function __construct( bool $is_admin, bool $doing_cron, bool $is_rest, ?bool $is_store_api ) {
				$this->is_admin     = $is_admin;
				$this->doing_cron   = $doing_cron;
				$this->is_rest      = $is_rest;
				$this->is_store_api = $is_store_api;
			}

			/**
			 * Tell whether the request is admin.
			 *
			 * @return bool
			 */
			protected function is_admin_request(): bool {
				return $this->is_admin;
			}

			/**
			 * Tell whether cron is running.
			 *
			 * @return bool
			 */
			protected function is_cron_request(): bool {
				return $this->doing_cron;
			}

			/**
			 * Tell whether this is a WooCommerce REST request.
			 *
			 * @return bool
			 */
			protected function is_wc_rest_api_request(): bool {
				return $this->is_rest;
			}

			/**
			 * Get the WooCommerce Store API request result.
			 *
			 * @return bool|null
			 */
			protected function get_wc_store_api_request(): ?bool {
				return $this->is_store_api;
			}
		};
	}
}
