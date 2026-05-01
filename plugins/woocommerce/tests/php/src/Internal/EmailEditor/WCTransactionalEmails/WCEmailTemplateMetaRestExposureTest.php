<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\EmailEditor\Bootstrap;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\Internal\EmailEditor\Integration;
use Automattic\WooCommerce\Internal\EmailEditor\Package;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCEmailTemplateDivergenceDetector;
use WP_REST_Request;

/**
 * REST integration coverage for `_wc_email_template_status` and `_wc_email_template_version`
 * exposure on the `woo_email` post type.
 *
 * The post type does not declare `'custom-fields'` support, so the standard `meta`
 * property of the wp/v2 response is not auto-populated. Instead, both keys are
 * surfaced as top-level read-only fields via `register_rest_field()` (see
 * {@see WCEmailTemplateDivergenceDetector::register_rest_fields()}). This test
 * pins that exposure contract for the email list UI.
 *
 * Lives in a sibling class (rather than alongside the unit-level detector tests) so
 * we can extend `WC_REST_Unit_Test_Case` and use the real REST stack.
 *
 * @group rest
 * @group email-editor
 */
class WCEmailTemplateMetaRestExposureTest extends \WC_REST_Unit_Test_Case {

	/**
	 * Setup test case.
	 *
	 * The bootstrap order matters: WC_REST_Unit_Test_Case::setUp() fires
	 * `rest_api_init` after `parent::setUp()`, so the woo_email post type and its
	 * meta must be registered before the parent setup runs — otherwise the post
	 * type's REST routes never get registered and GET wp/v2/woo_email returns 404.
	 */
	public function setUp(): void {
		add_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();
		do_action( 'init' );

		parent::setUp();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'no' );
		parent::tearDown();
	}

	/**
	 * @testdox Should expose template status and version as top-level fields in wp/v2/woo_email GET response.
	 */
	public function test_template_status_meta_visible_via_rest_get_post(): void {
		$admin_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user_id );

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'woo_email',
				'post_status' => 'publish',
				'post_author' => $admin_user_id,
			)
		);
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED
		);
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::VERSION_META_KEY,
			'9.4.0'
		);

		$request  = new WP_REST_Request( 'GET', "/wp/v2/woo_email/{$post_id}" );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), 'GET wp/v2/woo_email/{id} must succeed for an authenticated administrator.' );
		$data = $response->get_data();

		$this->assertArrayHasKey(
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			$data,
			'Status field must be exposed at the top level of the wp/v2/woo_email response.'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			$data[ WCEmailTemplateDivergenceDetector::STATUS_META_KEY ],
			'Status field value must reflect the stamped post meta.'
		);

		$this->assertArrayHasKey(
			WCEmailTemplateDivergenceDetector::VERSION_META_KEY,
			$data,
			'Version field must be exposed at the top level of the wp/v2/woo_email response.'
		);
		$this->assertSame(
			'9.4.0',
			$data[ WCEmailTemplateDivergenceDetector::VERSION_META_KEY ],
			'Version field value must reflect the stamped post meta.'
		);
	}

	/**
	 * @testdox Should return null for top-level fields when no meta is stamped.
	 */
	public function test_template_status_meta_returns_null_when_unstamped(): void {
		$admin_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user_id );

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'woo_email',
				'post_status' => 'publish',
				'post_author' => $admin_user_id,
			)
		);

		$request  = new WP_REST_Request( 'GET', "/wp/v2/woo_email/{$post_id}" );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( WCEmailTemplateDivergenceDetector::STATUS_META_KEY, $data );
		$this->assertNull(
			$data[ WCEmailTemplateDivergenceDetector::STATUS_META_KEY ],
			'Unstamped posts must surface a null status (e.g. third-party emails not in the sync registry).'
		);

		$this->assertArrayHasKey( WCEmailTemplateDivergenceDetector::VERSION_META_KEY, $data );
		$this->assertNull(
			$data[ WCEmailTemplateDivergenceDetector::VERSION_META_KEY ],
			'Unstamped posts must surface a null version.'
		);
	}
}
