<?php
/**
 * TaxRateVersionStringInvalidatorTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Caches;

use Automattic\WooCommerce\Internal\Caches\TaxRateVersionStringInvalidator;
use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;

/**
 * Tests for the TaxRateVersionStringInvalidator class.
 */
class TaxRateVersionStringInvalidatorTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TaxRateVersionStringInvalidator
	 */
	private $sut;

	/**
	 * Version string generator.
	 *
	 * @var VersionStringGenerator
	 */
	private $version_generator;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut               = new TaxRateVersionStringInvalidator();
		$this->version_generator = wc_get_container()->get( VersionStringGenerator::class );
	}

	/**
	 * Tear down test.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_feature_rest_api_caching_enabled' );
		delete_option( 'woocommerce_rest_api_enable_backend_caching' );
		parent::tearDown();
	}

	/**
	 * Enable the feature and backend caching, and initialize a new invalidator with hooks registered.
	 *
	 * @return TaxRateVersionStringInvalidator The initialized invalidator.
	 */
	private function get_invalidator_with_hooks_enabled(): TaxRateVersionStringInvalidator {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );

		$invalidator = new TaxRateVersionStringInvalidator();
		$invalidator->init();

		return $invalidator;
	}

	/**
	 * @testdox Invalidate method deletes the tax rate version string from cache.
	 */
	public function test_invalidate_deletes_version_string() {
		$tax_rate_id = 123;

		$this->version_generator->generate_version( "tax_rate_{$tax_rate_id}" );

		$version_before = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->invalidate( $tax_rate_id );

		$version_after = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation' );
	}

	/**
	 * @testdox Hooks are registered when feature is enabled and backend caching is active.
	 */
	public function test_hooks_registered_when_feature_and_setting_enabled() {
		$invalidator = $this->get_invalidator_with_hooks_enabled();

		$this->assertNotFalse( has_action( 'woocommerce_tax_rate_added', array( $invalidator, 'handle_woocommerce_tax_rate_added' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_tax_rate_updated', array( $invalidator, 'handle_woocommerce_tax_rate_updated' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_tax_rate_deleted', array( $invalidator, 'handle_woocommerce_tax_rate_deleted' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when feature is disabled.
	 */
	public function test_hooks_not_registered_when_feature_disabled() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'no' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );

		$invalidator = new TaxRateVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'woocommerce_tax_rate_added', array( $invalidator, 'handle_woocommerce_tax_rate_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_tax_rate_updated', array( $invalidator, 'handle_woocommerce_tax_rate_updated' ) ) );
		$this->assertFalse( has_action( 'woocommerce_tax_rate_deleted', array( $invalidator, 'handle_woocommerce_tax_rate_deleted' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when backend caching setting is disabled.
	 */
	public function test_hooks_not_registered_when_backend_caching_disabled() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );

		$invalidator = new TaxRateVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'woocommerce_tax_rate_added', array( $invalidator, 'handle_woocommerce_tax_rate_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_tax_rate_updated', array( $invalidator, 'handle_woocommerce_tax_rate_updated' ) ) );
		$this->assertFalse( has_action( 'woocommerce_tax_rate_deleted', array( $invalidator, 'handle_woocommerce_tax_rate_deleted' ) ) );
	}

	/**
	 * @testdox Hooks are not registered when backend caching setting is not set (defaults to no).
	 */
	public function test_hooks_not_registered_when_backend_caching_not_set() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		delete_option( 'woocommerce_rest_api_enable_backend_caching' );

		$invalidator = new TaxRateVersionStringInvalidator();
		$invalidator->init();

		$this->assertFalse( has_action( 'woocommerce_tax_rate_added', array( $invalidator, 'handle_woocommerce_tax_rate_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_tax_rate_updated', array( $invalidator, 'handle_woocommerce_tax_rate_updated' ) ) );
		$this->assertFalse( has_action( 'woocommerce_tax_rate_deleted', array( $invalidator, 'handle_woocommerce_tax_rate_deleted' ) ) );
	}

	/**
	 * @testdox Creating a new tax rate invalidates the version string via hook.
	 */
	public function test_tax_rate_creation_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$tax_rate_id = 456;

		// Create version string that should be deleted when the hook fires.
		$this->version_generator->generate_version( "tax_rate_{$tax_rate_id}" );
		$version_before = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before hook fires' );

		// Trigger the hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_tax_rate_added', $tax_rate_id );

		$version_after = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after tax rate added hook fires' );
	}

	/**
	 * @testdox Updating a tax rate invalidates the version string via hook.
	 */
	public function test_tax_rate_update_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$tax_rate_id = 789;

		$this->version_generator->generate_version( "tax_rate_{$tax_rate_id}" );
		$version_before = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before update' );

		// Trigger the hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_tax_rate_updated', $tax_rate_id );

		$version_after = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after tax rate updated hook fires' );
	}

	/**
	 * @testdox Deleting a tax rate invalidates the version string via hook.
	 */
	public function test_tax_rate_deletion_invalidates_version_string() {
		$this->get_invalidator_with_hooks_enabled();

		$tax_rate_id = 101;

		$this->version_generator->generate_version( "tax_rate_{$tax_rate_id}" );
		$version_before = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNotNull( $version_before, 'Version string should exist before deletion' );

		// Trigger the hook.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_tax_rate_deleted', $tax_rate_id );

		$version_after = $this->version_generator->get_version( "tax_rate_{$tax_rate_id}", false );
		$this->assertNull( $version_after, 'Version string should be deleted after tax rate deleted hook fires' );
	}

	/**
	 * @testdox Hook handlers accept string IDs and cast them to integers.
	 */
	public function test_handlers_accept_string_ids() {
		$tax_rate_id = '123';

		$this->version_generator->generate_version( 'tax_rate_123' );
		$version_before = $this->version_generator->get_version( 'tax_rate_123', false );
		$this->assertNotNull( $version_before, 'Version string should exist before invalidation' );

		$this->sut->handle_woocommerce_tax_rate_added( $tax_rate_id );

		$version_after = $this->version_generator->get_version( 'tax_rate_123', false );
		$this->assertNull( $version_after, 'Version string should be deleted after invalidation with string ID' );
	}

	/**
	 * @testdox All hook handlers correctly invalidate version strings.
	 */
	public function test_all_handlers_invalidate_correctly() {
		$this->version_generator->generate_version( 'tax_rate_111' );
		$this->sut->handle_woocommerce_tax_rate_added( 111 );
		$this->assertNull(
			$this->version_generator->get_version( 'tax_rate_111', false ),
			'Added handler should invalidate version string'
		);

		$this->version_generator->generate_version( 'tax_rate_222' );
		$this->sut->handle_woocommerce_tax_rate_updated( 222 );
		$this->assertNull(
			$this->version_generator->get_version( 'tax_rate_222', false ),
			'Updated handler should invalidate version string'
		);

		$this->version_generator->generate_version( 'tax_rate_333' );
		$this->sut->handle_woocommerce_tax_rate_deleted( 333 );
		$this->assertNull(
			$this->version_generator->get_version( 'tax_rate_333', false ),
			'Deleted handler should invalidate version string'
		);
	}

	/**
	 * @testdox Creating a new tax rate invalidates the list version string.
	 */
	public function test_tax_rate_creation_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_tax_rates' );
		$version_before = $this->version_generator->get_version( 'list_tax_rates', false );
		$this->assertNotNull( $version_before, 'List version string should exist before creation' );

		$this->sut->handle_woocommerce_tax_rate_added( 456 );

		$version_after = $this->version_generator->get_version( 'list_tax_rates', false );
		$this->assertNull( $version_after, 'List version string should be deleted after tax rate added' );
	}

	/**
	 * @testdox Updating a tax rate invalidates the list version string.
	 */
	public function test_tax_rate_update_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_tax_rates' );
		$version_before = $this->version_generator->get_version( 'list_tax_rates', false );
		$this->assertNotNull( $version_before, 'List version string should exist before update' );

		$this->sut->handle_woocommerce_tax_rate_updated( 789 );

		$version_after = $this->version_generator->get_version( 'list_tax_rates', false );
		$this->assertNull( $version_after, 'List version string should be deleted after tax rate updated' );
	}

	/**
	 * @testdox Deleting a tax rate invalidates the list version string.
	 */
	public function test_tax_rate_deletion_invalidates_list_version_string() {
		$this->version_generator->generate_version( 'list_tax_rates' );
		$version_before = $this->version_generator->get_version( 'list_tax_rates', false );
		$this->assertNotNull( $version_before, 'List version string should exist before deletion' );

		$this->sut->handle_woocommerce_tax_rate_deleted( 101 );

		$version_after = $this->version_generator->get_version( 'list_tax_rates', false );
		$this->assertNull( $version_after, 'List version string should be deleted after tax rate deleted' );
	}
}
