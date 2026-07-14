<?php
/**
 * Plugin Name: Process Waiting Actions
 * Description: Utility intended to be used during E2E testing, to make it easy to process any pending scheduled actions.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: WooCommerce
 *
 * Intended to function as a (mu-)plugin while tests are running. It listens for requests made with the
 * 'process-waiting-actions' query parameter and then starts an Action Scheduler queue runner. It exits immediately
 * after this, to avoid overhead of building up a full response.
 *
 * @package Automattic\WooCommerce\E2EPlaywright
 */

declare(strict_types=1);

add_action(
	'init',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['process-waiting-actions'] ) ) {
			return;
		}

		if ( ! class_exists( ActionScheduler_QueueRunner::class ) ) {
			return;
		}

		exit( ActionScheduler_QueueRunner::instance()->run( 'E2E Tests' ) ? 1 : 0 );
	}
);
