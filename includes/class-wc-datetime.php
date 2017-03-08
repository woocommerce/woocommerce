<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Wrapper for PHP DateTime.
 *
 * @class    WC_DateTime
 * @since    2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_DateTime extends DateTime {

	/**
	 * Output an ISO 8601 date string in local timezone.
	 *
	 * @since  2.7.0
	 * @return string
	 */
	public function __toString() {
		return $this->format( DATE_ATOM );
	}

	/**
	 * Missing in PHP 5.2.
	 *
	 * @since  2.7.0
	 * @return int
	 */
	public function getTimestamp() {
		return method_exists( 'DateTime', 'getTimestamp' ) ? parent::getTimestamp() : $this->format( 'U' );
	}

	/**
	 * Get the timestamp with the WordPress timezone offset added or subtracted.
	 *
	 * @since  2.7.0
	 * @return int
	 */
	public function getOffsetTimestamp() {
		return $this->getTimestamp() + $this->getOffset();
	}
}
