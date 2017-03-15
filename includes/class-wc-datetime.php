<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Wrapper for PHP DateTime.
 *
 * @class    WC_DateTime
 * @since    3.0.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_DateTime extends DateTime {

	/**
	 * Output an ISO 8601 date string in local timezone.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	public function __toString() {
		return $this->format( DATE_ATOM );
	}

	/**
	 * Missing in PHP 5.2.
	 *
	 * @since  3.0.0
	 * @return int
	 */
	public function getTimestamp() {
		return method_exists( 'DateTime', 'getTimestamp' ) ? parent::getTimestamp() : $this->format( 'U' );
	}

	/**
	 * Get the timestamp with the WordPress timezone offset added or subtracted.
	 *
	 * @since  3.0.0
	 * @return int
	 */
	public function getOffsetTimestamp() {
		return $this->getTimestamp() + $this->getOffset();
	}

	/**
	 * Format a date based on the offset timestamp.
	 *
	 * @since  3.0.0
	 * @param  string $format
	 * @return string
	 */
	public function date( $format ) {
		return gmdate( $format, $this->getOffsetTimestamp() );
	}

	/**
	 * Return a localised date based on offset timestamp. Wrapper for date_i18n function.
	 *
	 * @since  3.0.0
	 * @param  string $format
	 * @return string
	 */
	public function date_i18n( $format = 'Y-m-d' ) {
		return date_i18n( $format, $this->getOffsetTimestamp() );
	}
}
