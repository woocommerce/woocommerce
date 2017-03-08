<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Wrapper for PHP DateTime.
 *
 * @class    WC_DateTime
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_DateTime extends DateTime {

	/**
	 * Missing in PHP 5.2.
	 * @return int
	 */
	public function getTimestamp() {
		return method_exists( 'DateTime', 'getTimestamp' ) ? parent::getTimestamp() : $this->format( 'U' );
	}

	/**
	 * Get the timestamp with the WordPress timezone offset added or subtracted.
	 * @return int
	 */
	public function getOffsetTimestamp() {
		return $this->getTimestamp() + $this->getOffset();
	}
}
