<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions\Init as RemoteFreeExtensions;

/**
 * Marketing Task
 */
class Marketing {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'          => 'marketing',
			'title'       => __( 'Set up marketing tools', 'woocommerce-admin' ),
			'content'     => __(
				'Add recommended marketing tools to reach new customers and grow your business',
				'woocommerce-admin'
			),
			'is_complete' => self::has_installed_extensions(),
			'can_view'    => Features::is_enabled( 'remote-free-extensions' ) && count( self::get_plugins() ) > 0,
			'time'        => __( '1 minute', 'woocommerce-admin' ),
		);
	}

	/**
	 * Get the marketing plugins.
	 *
	 * @return array
	 */
	public static function get_plugins() {
		$bundles = RemoteFreeExtensions::get_extensions(
			array(
				'task-list/reach',
				'task-list/grow',
			)
		);

		return array_reduce(
			$bundles,
			function( $plugins, $bundle ) {
				$visible = array();
				foreach ( $bundle['plugins'] as $plugin ) {
					if ( $plugin->is_visible ) {
						$visible[] = $plugin;
					}
				}
				return array_merge( $plugins, $visible );
			},
			array()
		);
	}

	/**
	 * Check if the store has installed marketing extensions.
	 *
	 * @return bool
	 */
	public static function has_installed_extensions() {
		$plugins   = self::get_plugins();
		$remaining = array();
		$installed = array();

		foreach ( $plugins as $plugin ) {
			if ( ! $plugin->is_installed ) {
				$remaining[] = $plugin;
			} else {
				$installed[] = $plugin;
			}
		}

		// All extensions installed.
		if ( count( $remaining ) === 0 ) {
			return true;
		}

		// Make sure the task has been actioned and at least one extension is installed.
		if ( count( $installed ) > 0 && Task::is_task_actioned( 'marketing' ) ) {
			return true;
		}

		return false;
	}
}
