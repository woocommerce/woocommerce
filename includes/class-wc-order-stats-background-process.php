<?php
/**
 * Order stats background process.
 *
 * @package WooCommerce/Classes
 * @version 3.5.0
 * @since   3.5.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Background_Process', false ) ) {
	include_once dirname( __FILE__ ) . '/abstracts/class-wc-background-process.php';
}

/**
 * WC_Order_Stats_Background_Process class.
 */
class WC_Order_Stats_Background_Process extends WC_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'wc_order_stats';
		parent::__construct();
	}

	/**
	 * Code to execute for each item in the queue
	 *
	 * @param string $item Queue item to iterate over.
	 * @return bool
	 */
	protected function task( $item ) {
		if ( ! $item || empty( $item['start_time'] ) ) {
			return false;
		}

		$start_time = $item['start_time'];
		$end_time = ! empty( $item['end_time'] ) ? $item['end_time'] : $start_time + HOUR_IN_SECONDS;

		$data = WC_Order_Stats::summarize_orders( $start_time, $end_time );
		WC_Order_Stats::update( $start_time, $end_time, $data );

		return false;
	}
}
