<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\CashSessions;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exceptions become REST error data, never HTML output.

use Exception;
use WP_Error;

/**
 * A cash session request that cannot be completed, with the REST error code, HTTP status and recovery data.
 *
 * @since 11.3.0
 */
class CashSessionException extends Exception {

	/**
	 * REST error code, such as woocommerce_rest_cash_session_closed.
	 *
	 * @var string
	 */
	private string $error_code;

	/**
	 * HTTP status.
	 *
	 * @var int
	 */
	private int $status;

	/**
	 * Extra error data for recovery, such as session_id or current_revision.
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $error_code REST error code.
	 * @param string               $message    Translated message.
	 * @param int                  $status     HTTP status.
	 * @param array<string, mixed> $data       Extra error data.
	 */
	public function __construct( string $error_code, string $message, int $status, array $data = array() ) {
		parent::__construct( $message );
		$this->error_code = $error_code;
		$this->status     = $status;
		$this->data       = $data;
	}

	/**
	 * Convert to a REST error.
	 *
	 * @since 11.3.0
	 *
	 * @return WP_Error
	 */
	public function to_wp_error(): WP_Error {
		return new WP_Error( $this->error_code, $this->getMessage(), array_merge( array( 'status' => $this->status ), $this->data ) );
	}

	/**
	 * Session not found.
	 *
	 * @since 11.3.0
	 *
	 * @return self
	 */
	public static function session_not_found(): self {
		return new self( 'woocommerce_rest_cash_session_not_found', __( 'Cash session not found.', 'woocommerce' ), 404 );
	}

	/**
	 * Session already closed.
	 *
	 * @since 11.3.0
	 *
	 * @param int $session_id Session ID.
	 * @return self
	 */
	public static function session_closed( int $session_id ): self {
		return new self( 'woocommerce_rest_cash_session_closed', __( 'The cash session is closed.', 'woocommerce' ), 409, array( 'session_id' => $session_id ) );
	}

	/**
	 * Request ID reused with a different payload.
	 *
	 * @since 11.3.0
	 *
	 * @return self
	 */
	public static function request_conflict(): self {
		return new self( 'woocommerce_rest_cash_request_conflict', __( 'This request ID was already used with different data.', 'woocommerce' ), 409 );
	}

	/**
	 * A request with the same ID is still being processed.
	 *
	 * @since 11.3.0
	 *
	 * @return self
	 */
	public static function request_in_progress(): self {
		return new self(
			'woocommerce_rest_cash_request_in_progress',
			__( 'A request with this ID is still being processed. Retry shortly.', 'woocommerce' ),
			409,
			array( 'retry_after_seconds' => 1 )
		);
	}

	/**
	 * Invalid input for a business rule the route schema cannot express.
	 *
	 * @since 11.3.0
	 *
	 * @param string $error_code REST error code.
	 * @param string $message    Translated message.
	 * @return self
	 */
	public static function invalid( string $error_code, string $message ): self {
		return new self( $error_code, $message, 400 );
	}
}
