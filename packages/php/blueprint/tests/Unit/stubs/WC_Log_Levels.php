<?php

if ( ! class_exists( 'WC_Log_Levels' ) ) {
	/**
	 * WC Log Levels Class
	 */
	class WC_Log_Levels {
		const EMERGENCY = 'emergency';
		const ALERT     = 'alert';
		const CRITICAL  = 'critical';
		const ERROR     = 'error';
		const WARNING   = 'warning';
		const NOTICE    = 'notice';
		const INFO      = 'info';
		const DEBUG     = 'debug';
	}
}
