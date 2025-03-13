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
		if ( function_exists( 'as_schedule_recurring_action' ) && false === as_has_scheduled_action( self::HOOK_NAME ) ) {
			as_schedule_recurring_action( time(), HOUR_IN_SECONDS, self::HOOK_NAME, array() );
		}
	}
}
