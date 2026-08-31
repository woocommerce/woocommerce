<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductFilters;

use Automattic\WooCommerce\Internal\ProductFilters\CacheController;
use WC_Cache_Helper;
use WC_Unit_Test_Case;
use WP_Post;

/**
 * Tests for the CacheController class.
 */
class CacheControllerTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Filter data created after registration is invalidated by $event.
	 * @dataProvider cache_event_provider
	 *
	 * @param string $event         Event to trigger.
	 * @param string $hook          WordPress hook name.
	 * @param string $handler       Cache controller handler name.
	 */
	public function test_cache_event_hooks_handle_cache_created_later_in_same_request( string $event, string $hook, string $handler ): void {
		$controller = wc_get_container()->get( CacheController::class );
		$post       = $this->factory->post->create_and_get(
			array(
				'post_type'   => 'product_variation',
				'post_status' => 'publish',
			)
		);

		remove_action( $hook, array( $controller, $handler ), 10 );
		delete_transient( CacheController::CACHE_GROUP . '-transient-version' );
		$controller->register();

		WC_Cache_Helper::get_transient_version( CacheController::CACHE_GROUP );
		set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, 5 );

		if ( 'status transition' === $event ) {
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'private',
				)
			);
		} else {
			wp_delete_post( $post->ID, true );
		}

		$this->assertFalse( get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ) );
	}

	/**
	 * Data provider for cache events that can occur after registration.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function cache_event_provider(): array {
		return array(
			'status transition'  => array( 'status transition', 'transition_post_status', 'handle_transition_post_status' ),
			'permanent deletion' => array( 'permanent deletion', 'before_delete_post', 'handle_before_delete_post' ),
		);
	}

	/**
	 * @testdox Status transitions invalidate filter data only when a product crosses the publish boundary.
	 * @dataProvider status_transition_provider
	 *
	 * @param string|null $post_type      Post type, or null for a non-WP_Post value.
	 * @param string      $new_status     New post status.
	 * @param string      $old_status     Old post status.
	 * @param false|int   $expected_count Expected cache-entry count transient value.
	 */
	public function test_status_transition_invalidates_cache_when_product_crosses_publish_boundary(
		?string $post_type,
		string $new_status,
		string $old_status,
		$expected_count
	): void {
		if ( null === $post_type ) {
			$post = new \stdClass();
		} else {
			$post = $this->factory->post->create_and_get(
				array(
					'post_type'   => $post_type,
					'post_status' => $old_status,
				)
			);
			/** @var WP_Post $post */
		}

		set_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT, 5 );
		WC_Cache_Helper::get_transient_version( CacheController::CACHE_GROUP );

		wc_get_container()->get( CacheController::class )->handle_transition_post_status( $new_status, $old_status, $post );

		$this->assertSame(
			$expected_count,
			get_transient( CacheController::CACHE_ENTRY_COUNT_TRANSIENT ),
			'The cache-entry count should only be deleted for product visibility transitions.'
		);
	}

	/**
	 * Data provider for status transition cache invalidation.
	 *
	 * @return array<string, array{string|null, string, string, false|int}>
	 */
	public static function status_transition_provider(): array {
		return array(
			'product publish to private'           => array( 'product', 'private', 'publish', false ),
			'product variation publish to private' => array( 'product_variation', 'private', 'publish', false ),
			'product variation private to publish' => array( 'product_variation', 'publish', 'private', false ),
			'product private to draft'             => array( 'product', 'draft', 'private', 5 ),
			'product unchanged publish status'     => array( 'product', 'publish', 'publish', 5 ),
			'unrelated post type'                  => array( 'post', 'private', 'publish', 5 ),
			'non-WP_Post value'                    => array( null, 'private', 'publish', 5 ),
		);
	}
}
