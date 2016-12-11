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
 * @package        WooCommerce/Classes/Log_Handlers
 * @category       Class
 * @author         WooThemes
 */
class WC_Log_Handler_Email extends WC_Log_Handler {

	/**
	 * Minimum log level this handler will process.
	 *
	 * @var int Integer representation of minimum log level to handle.
	 * @access private
	 */
	protected $threshold;

	/**
	 * Stores email recipients.
	 *
	 * @var array
	 * @access private
	 */
	private $recipients = array();

	/**
	 * Constructor for log handler.
	 *
	 * @param string|array $recipients Optional. Email(s) to receive log messages. Defaults to site admin email.
	 * @param string $threshold Optional. Minimum level that should receive log messages.
	 *     Default 'alert'. One of: emergency|alert|critical|error|warning|notice|info|debug
	 */
	public function __construct( $recipients = null, $threshold = 'alert' ) {
		if ( null === $recipients ) {
			$recipients = get_option( 'admin_email' );
		}

		if ( is_array( $recipients ) ) {
			foreach ( $recipients as $recipient ) {
				$this->add_email( $recipient );
			}
		} else {
			$this->add_email( $recipients );
		}

		$this->set_threshold( $threshold );
	}

	/**
	 * Set handler severity threshold.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 */
	public function set_threshold( $level ) {
		$this->threshold = WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Determine whether handler should handle log.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @return bool True if the log should be handled.
	 */
	public function should_handle( $level ) {
		return $this->threshold <= WC_Log_Levels::get_level_severity( $level );
	}

	/**
	 * Handle a log entry.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message Log message.
	 * @param array $context Optional. Additional information for log handlers.
	 *
	 * @return bool True on success.
	 */
	public function handle( $timestamp, $level, $message, $context ) {

		if ( $this->should_handle( $level ) ) {
			$subject = $this->get_subject( $timestamp, $level, $message, $context );
			$body = $this->get_body( $timestamp, $level, $message, $context );
			return wp_mail( $this->recipients, $subject, $body );
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
		return __( 'You have received the following WooCommerce log message:', 'woocommerce' )
			. PHP_EOL . PHP_EOL . $entry;
	}

	/**
	 * Adds an email to the list of recipients.
	 *
	 * @param string email Email address to add
	 */
	public function add_email( $email ) {
		array_push( $this->recipients, $email );
	}
}
