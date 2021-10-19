<?php
/**
 * Plugin Name: Action Scheduler
 * Plugin URI: https://actionscheduler.org
 * Description: A robust scheduling library for use in WordPress plugins.
 * Author: Automattic
 * Author URI: https://automattic.com/
 * Version: 3.3.0
 * License: GPLv3
 *
 * Copyright 2019 Automattic, Inc.  (https://automattic.com/contact/)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package ActionScheduler
 */

if ( ! function_exists( 'action_scheduler_register_3_dot_3_dot_0' ) && function_exists( 'add_action' ) ) {

	if ( ! class_exists( 'ActionScheduler_Versions' ) ) {
		require_once __DIR__ . '/classes/ActionScheduler_Versions.php';
		add_action( 'plugins_loaded', array( 'ActionScheduler_Versions', 'initialize_latest_version' ), 1, 0 );
	}

	add_action( 'plugins_loaded', 'action_scheduler_register_3_dot_3_dot_0', 0, 0 );

	/**
	 * Registers this version of Action Scheduler.
	 */
	function action_scheduler_register_3_dot_3_dot_0() {
		$versions = ActionScheduler_Versions::instance();
		$versions->register( '3.3.0', 'action_scheduler_initialize_3_dot_3_dot_0' );
	}

	/**
	 * Initializes this version of Action Scheduler.
	 */
	function action_scheduler_initialize_3_dot_3_dot_0() {
		// A final safety check is required even here, because historic versions of Action Scheduler
		// followed a different pattern (in some unusual cases, we could reach this point and the
		// ActionScheduler class is already defined—so we need to guard against that).
		if ( ! class_exists( 'ActionScheduler' ) ) {
			require_once __DIR__ . '/classes/abstracts/ActionScheduler.php';
			ActionScheduler::init( __FILE__ );
		}
	}

	// Support usage in themes - load this version if no plugin has loaded a version yet.
	if ( did_action( 'plugins_loaded' ) && ! doing_action( 'plugins_loaded' ) && ! class_exists( 'ActionScheduler' ) ) {
		action_scheduler_initialize_3_dot_3_dot_0();
		do_action( 'action_scheduler_pre_theme_init' );
		ActionScheduler_Versions::initialize_latest_version();
	}
}
