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
 * Because the `woo_email` post type declares `'custom-fields'` support (see
 * {@see Integration::add_email_post_type()}), WP core auto-surfaces every
 * `show_in_rest = true` meta key registered via {@see WCEmailTemplateDivergenceDetector::register_meta()}
 * under the standard `meta` property of the `wp/v2/woo_email` response. This
 * test pins that exposure contract for the email list UI.
 *
 * Lives in a sibling class (rather than alongside the unit-level detector tests) so
 * we can extend `WC_REST_Unit_Test_Case` and use the real REST stack.
 *
 * @group rest
 * @group email-editor
 */
class WCEmailTemplateMetaRestExposureTest extends \WC_REST_Unit_Test_Case {

	/**
	 * Previous value of the email editor feature flag, captured in setUp() so we
	 * can deterministically restore the original state in tearDown() and avoid
	 * order-dependent failures when other tests touch the same option.
	 *
	 * @var string|false|null Either the previous option value (string), `false`
	 *   when the option did not exist, or `null` before setUp() runs.
	 */
	private $previous_feature_flag_value = null;

	/**
	 * Setup test case.
	 *
	 * The bootstrap order matters: WC_REST_Unit_Test_Case::setUp() fires
	 * `rest_api_init` after `parent::setUp()`, so the woo_email post type and its
	 * meta must be registered before the parent setup runs — otherwise the post
	 * type's REST routes never get registered and GET wp/v2/woo_email returns 404.
	 */
	public function setUp(): void {
		$this->previous_feature_flag_value = get_option( 'woocommerce_feature_block_email_editor_enabled', false );
		update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );
		wc_get_container()->get( Package::class )->init();
		wc_get_container()->get( Integration::class )->initialize();
		Email_Editor_Container::container()->get( Bootstrap::class )->initialize();

		/**
		 * Fires once WordPress, all plugins, and the theme are fully loaded and instantiated.
		 *
		 * @since 1.5.0
		 */
		do_action( 'init' );

		parent::setUp();
	}

	/**
	 * Cleanup after test.
	 */
	public function tearDown(): void {
		if ( false === $this->previous_feature_flag_value ) {
			delete_option( 'woocommerce_feature_block_email_editor_enabled' );
		} else {
			update_option( 'woocommerce_feature_block_email_editor_enabled', $this->previous_feature_flag_value );
		}
		parent::tearDown();
	}

	/**
	 * @testdox Should expose template status and version under `meta` in wp/v2/woo_email GET response.
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
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY,
			'abc123def456'
		);
		update_post_meta(
			$post_id,
			WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY,
			true
		);

		$request  = new WP_REST_Request( 'GET', "/wp/v2/woo_email/{$post_id}" );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), 'GET wp/v2/woo_email/{id} must succeed for an authenticated administrator.' );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'meta', $data, 'wp/v2/woo_email response must include a meta property when the post type supports custom-fields.' );
		$this->assertIsArray( $data['meta'], 'meta property must be an array.' );

		$this->assertArrayHasKey(
			WCEmailTemplateDivergenceDetector::STATUS_META_KEY,
			$data['meta'],
			'Status meta must be auto-surfaced under the meta property of the wp/v2/woo_email response.'
		);
		$this->assertSame(
			WCEmailTemplateDivergenceDetector::STATUS_CORE_UPDATED_CUSTOMIZED,
			$data['meta'][ WCEmailTemplateDivergenceDetector::STATUS_META_KEY ],
			'Status meta value must reflect the stamped post meta.'
		);

		$this->assertArrayHasKey(
			WCEmailTemplateDivergenceDetector::VERSION_META_KEY,
			$data['meta'],
			'Version meta must be auto-surfaced under the meta property of the wp/v2/woo_email response.'
		);
		$this->assertSame(
			'9.4.0',
			$data['meta'][ WCEmailTemplateDivergenceDetector::VERSION_META_KEY ],
			'Version meta value must reflect the stamped post meta.'
		);

		$this->assertArrayHasKey(
			WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY,
			$data['meta'],
			'Source hash meta must be auto-surfaced under the meta property of the wp/v2/woo_email response.'
		);
		$this->assertSame(
			'abc123def456',
			$data['meta'][ WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY ] ?? null,
			'Source hash meta value must reflect the stamped post meta.'
		);

		$this->assertArrayHasKey(
			WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY,
			$data['meta'],
			'Backfilled meta must be auto-surfaced under the meta property of the wp/v2/woo_email response.'
		);
		$this->assertTrue(
			$data['meta'][ WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY ] ?? null,
			'Backfilled meta value must reflect the stamped post meta.'
		);
	}

	/**
	 * @testdox Should return empty-string meta values when no meta is stamped.
	 */
	public function test_template_status_meta_returns_empty_when_unstamped(): void {
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

		$this->assertArrayHasKey( 'meta', $data );
		$this->assertIsArray( $data['meta'] );

		// WP core surfaces registered single-string meta with a default empty string when no value is stored.
		// The JS data hook treats empty/missing/non-matching values as `null` — see VALID_TEMPLATE_STATUSES allowlist.
		$this->assertArrayHasKey( WCEmailTemplateDivergenceDetector::STATUS_META_KEY, $data['meta'] );
		$this->assertSame(
			'',
			$data['meta'][ WCEmailTemplateDivergenceDetector::STATUS_META_KEY ],
			'Unstamped posts must surface an empty status (e.g. third-party emails not in the sync registry); the JS data hook normalises this to null.'
		);

		$this->assertArrayHasKey( WCEmailTemplateDivergenceDetector::VERSION_META_KEY, $data['meta'] );
		$this->assertSame(
			'',
			$data['meta'][ WCEmailTemplateDivergenceDetector::VERSION_META_KEY ],
			'Unstamped posts must surface an empty version; the JS data hook normalises this to null.'
		);

		// WP core surfaces registered string meta with an empty-string default when no value is stored.
		$this->assertArrayHasKey( WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY, $data['meta'] );
		$this->assertSame(
			'',
			$data['meta'][ WCEmailTemplateDivergenceDetector::SOURCE_HASH_META_KEY ],
			'Unstamped posts must surface an empty source hash.'
		);

		// WP core surfaces registered boolean meta as `false` when no value is stored.
		$this->assertArrayHasKey( WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY, $data['meta'] );
		$this->assertFalse(
			$data['meta'][ WCEmailTemplateDivergenceDetector::BACKFILLED_META_KEY ],
			'Unstamped posts must surface false for the backfilled flag.'
		);
	}
}
