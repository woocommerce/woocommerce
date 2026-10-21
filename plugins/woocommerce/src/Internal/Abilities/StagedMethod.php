<?php
/**
 * StagedMethod class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * A Core method that sets and saves in one call, paired with the method that only stages the change in
 * memory. The first method calls the stage method, then save(). For example: update_status() and
 * stage_status().
 *
 * A class that overrides the first method with rules of its own must override the stage method too, at
 * the same level or below. Otherwise an ability that stages the change skips those rules.
 *
 * @internal Abilities use this class. It is not a public API.
 * @since 11.4.0
 */
final class StagedMethod {

	/**
	 * Name of the method that sets and saves, for example update_status.
	 *
	 * @var string
	 */
	private string $method;

	/**
	 * Name of the method that only stages the change, for example stage_status.
	 *
	 * @var string
	 */
	private string $stage_method;

	/**
	 * Constructor.
	 *
	 * @param string $method       Name of the method that sets and saves.
	 * @param string $stage_method Name of the method that only stages the change.
	 */
	public function __construct( string $method, string $stage_method ) {
		$this->method       = $method;
		$this->stage_method = $stage_method;
	}

	/**
	 * Whether the subject's class can stage the change. It cannot when it has no stage method, or when a
	 * class below the one that declares the stage method overrides the method that sets and saves.
	 *
	 * @param object $subject Object to change, for example an order.
	 * @return bool
	 */
	public function supports( object $subject ): bool {
		if ( ! method_exists( $subject, $this->method ) || ! method_exists( $subject, $this->stage_method ) ) {
			return false;
		}

		$method       = ( new \ReflectionMethod( $subject, $this->method ) )->getDeclaringClass()->getName();
		$stage_method = ( new \ReflectionMethod( $subject, $this->stage_method ) )->getDeclaringClass()->getName();

		return ! is_subclass_of( $method, $stage_method );
	}

	/**
	 * The error an ability returns when the subject's class cannot stage the change.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	public function unsupported_error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 400 ) );
	}
}
