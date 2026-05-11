<?php
/**
 * Server-side Tracks event recorder for the WC Email Template Sync test helper plugin.
 *
 * Stub — implementation filled in by Task 2.1.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

namespace WC_Email_Template_Sync_Test_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Mirrors server-side recordEvent calls and the backfill-complete action to an option
 * so Playwright tests can drain and assert. Dormant unless wc_test_tracks_enabled=yes.
 */
class Tracks_Recorder {

	/**
	 * Register hooks. Filled in by Task 2.1.
	 */
	public function register(): void {
	}
}
