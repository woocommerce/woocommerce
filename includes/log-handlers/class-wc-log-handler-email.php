<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Log_Handler' ) ) {
	include_once( dirname( dirname( __FILE__ ) ) . '/abstracts/abstract-wc-log-handler.php' );
}

/**
 * Handles log entries by sending an email.
 *
 * @class          WC_Log_Handler_Email
 * @version        1.0.0
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
class WC_Log_Handler_Email extends WC_Log_Handler {

	/**
	 * Stores email recipients.
	 *
	 * @var array
	 * @access private
	 */
	private $_to = array();

	/**
	 * Constructor for the logger.
	 *
	 * @param $args additional args. {
	 *     Optional. @see WC_Log_Handler::__construct.
	 *
	 *     @type string|arr $to Optional. Email or emails to recieve log messages. Default site admin.
	 * }
	 */
	public function __construct( $args ) {

		$args = wp_parse_args( $args, array(
			'threshold' => 'critical',
			'to' => get_option( 'admin_email' ),
		) );

		parent::__construct( $args );

		if ( is_array( $args['to'] ) ) {
			foreach ( $args['to'] as $to ) {
				$this->add_email( $to );
			}
		} else {
			$this->add_email( $args['to'] );
		}

	}

	/**
	 * Handle a log entry.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message
	 * @param array $context {
	 *     Optional. Array with additional information
	 *
	 *     @type string $tag Optional. The tag will be used to determine which file an entry will be written to.
	 * }
	 *
	 * @return bool log entry should bubble to further loggers.
	 */
	public function handle( $level, $timestamp, $message, $context ) {

		if ( $this->should_handle( $level ) ) {
			$subject = $this->get_subject( $level, $timestamp, $message, $context );
			$body = $this->get_body( $level, $timestamp, $message, $context );
			wp_mail( $this->_to, $subject, $body );
		}

		return true;
	}

	public function get_subject( $level, $timestamp, $message, $context ) {
		return 'subject';
	}

	public function get_body( $level, $timestamp, $message, $context ) {
		$entry = $this->format_entry( $level, $timestamp, $message, $context );
		return $entry;
	}

	/**
	 * Adds an email to the list of recipients.
	 */
	public function add_email( $email ) {
		array_push( $this->_to, $email );
	}
}
