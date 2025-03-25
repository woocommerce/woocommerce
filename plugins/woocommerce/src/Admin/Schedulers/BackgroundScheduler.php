<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Schedulers;

/**
 * A thin wrapper around ActionScheduler to handle background
 * scheduling jobs on intervals.
 */
class BackgroundScheduler {

	/**
	 * Action scheduler hook name.
	 */
	const HOOK_NAME = 'woocommerce_background_scheduler';

	/**
	 * Initialize the background scheduler.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'schedule_background_actions' ) );
	}

	/**
	 * Schedule the background actions.
	 */
	public function schedule_background_actions() {
		as_schedule_recurring_action( time(), HOUR_IN_SECONDS, self::HOOK_NAME, array(), '', true );
	}
}
