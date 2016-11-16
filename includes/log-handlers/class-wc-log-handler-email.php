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
	private $to = array();

	/**
	 * Constructor for the logger.
	 *
	 * @param $args additional args. {
	 *     Optional. @see WC_Log_Handler::__construct.
	 *
	 *     @type string|arr $to Optional. Email or emails to recieve log messages. Default site admin.
	 * }
	 */
	public function __construct( $args = array() ) {

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
	 * }
	 *
	 * @return bool log entry should bubble to further loggers.
	 */
	public function handle( $level, $timestamp, $message, $context ) {

		if ( $this->should_handle( $level ) ) {
			$subject = $this->get_subject( $level, $timestamp, $message, $context );
			$body = $this->get_body( $level, $timestamp, $message, $context );
			wp_mail( $this->to, $subject, $body );
		}

		return true;
	}

	/**
	 * Build subject for log email.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message
	 * @param array $context {
	 *     Optional. Array with additional information
	 * }
	 *
	 * @return string subject
	 */
	public function get_subject( $level, $timestamp, $message, $context ) {
		return sprintf( __( '[%s] WooCommerce log message', 'woocommerce' ), $level );
	}

	/**
	 * Build body for log email.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message
	 * @param array $context {
	 *     Optional. Array with additional information
	 * }
	 *
	 * @return string body
	 */
	public function get_body( $level, $timestamp, $message, $context ) {
		$entry = $this->format_entry( $level, $timestamp, $message, $context );
		return sprintf( __( "You have recieved the following WooCommerce log message: \n\n%s", 'woocommerce' ), $entry );
	}

	/**
	 * Adds an email to the list of recipients.
	 *
	 * @param string email Email address to add
	 */
	public function add_email( $email ) {
		array_push( $this->to, $email );
	}
}
