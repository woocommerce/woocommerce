<?php
/**
 * Tests for CustomizeStore onboarding task.
 */

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\CustomizeStore;

class CustomizeStoreTest extends \WC_Unit_Test_Case {

    /**
     * Ensure enqueue_site_editor_assets enqueues expected editor assets.
     */
    public function test_enqueue_site_editor_assets_enqueues_wp_editor() {
        // Instantiate the task (constructor accepts optional task_list, pass null).
        $task = new CustomizeStore( null );

        // Call the method directly to avoid relying on WP hook timing.
        $task->enqueue_site_editor_assets();

        // Assert that editor scripts/styles are enqueued.
        $this->assertTrue( wp_script_is( 'wp-editor', 'enqueued' ), 'wp-editor should be enqueued' );
        $this->assertTrue( wp_style_is( 'wp-editor', 'enqueued' ), 'wp-editor style should be enqueued' );
        $this->assertTrue( wp_script_is( 'wp-format-library', 'enqueued' ) || wp_script_is( 'wp-format-library', 'registered' ), 'wp-format-library should be registered or enqueued' );
    }
}
