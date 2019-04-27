<?php
/*
 * Plugin Name: Action Scheduler
 * Plugin URI: https://actionscheduler.org
 * Description: A robust scheduling library for use in WordPress plugins.
 * Author: Prospress
 * Author URI: https://prospress.com/
 * Version: 2.2.5
 * License: GPLv3
 *
 * Copyright 2019 Prospress, Inc.  (email : freedoms@prospress.com)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

if ( ! function_exists( 'action_scheduler_register_2_dot_2_dot_5' ) ) {

	if ( ! class_exists( 'ActionScheduler_Versions' ) ) {
		require_once( 'classes/ActionScheduler_Versions.php' );
		add_action( 'plugins_loaded', array( 'ActionScheduler_Versions', 'initialize_latest_version' ), 1, 0 );
	}

	add_action( 'plugins_loaded', 'action_scheduler_register_2_dot_2_dot_5', 0, 0 );

	function action_scheduler_register_2_dot_2_dot_5() {
		$versions = ActionScheduler_Versions::instance();
		$versions->register( '2.2.5', 'action_scheduler_initialize_2_dot_2_dot_5' );
	}

	function action_scheduler_initialize_2_dot_2_dot_5() {
		require_once( 'classes/ActionScheduler.php' );
		ActionScheduler::init( __FILE__ );
	}

}
