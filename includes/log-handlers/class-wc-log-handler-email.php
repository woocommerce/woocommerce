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
	 *     @type string|array $to Optional. Email or emails to recieve log messages. Default site admin.
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
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Optional. Additional information for log handlers.
	 *
	 * @return bool log entry should bubble to further loggers.
	 */
	public function handle( $timestamp, $level, $message, $context ) {

		if ( $this->should_handle( $level ) ) {
			$subject = $this->get_subject( $timestamp, $level, $message, $context );
			$body = $this->get_body( $timestamp, $level, $message, $context );
			wp_mail( $this->to, $subject, $body );
		}

		return true;
	}

	/**
	 * Build subject for log email.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Optional. Additional information for log handlers.
	 *
	 * @return string subject
	 */
	public function get_subject( $timestamp, $level, $message, $context ) {
		$site_name = get_bloginfo( 'name' );
		return sprintf( __( '[%1$s] WooCommerce log message from %2$s', 'woocommerce' ), strtoupper( $level ), $site_name );
	}

	/**
	 * Build body for log email.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Optional. Additional information for log handlers.
	 *
	 * @return string body
	 */
	public function get_body( $timestamp, $level, $message, $context ) {
		$entry = $this->format_entry( $timestamp, $level, $message, $context );
		return __( 'You have recieved the following WooCommerce log message:', 'woocommerce' )
			. PHP_EOL . PHP_EOL . $entry;
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
