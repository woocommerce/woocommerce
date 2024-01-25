<?php
/**
 * Compare two operands using the specified operation.
 */

namespace Automattic\WooCommerce\Admin\RemoteInboxNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Compare two operands using the specified operation.
 */
class ComparisonOperation {
	/**
	 * Compare two operands using the specified operation.
	 *
	 * @param object $left_operand  The left hand operand.
	 * @param object $right_operand The right hand operand.
	 * @param string $operation     The operation used to compare the operands.
	 */
	public static function compare( $left_operand, $right_operand, $operation ) {
		switch ( $operation ) {
			case '=':
				return $left_operand === $right_operand;
			case '<':
				return $left_operand < $right_operand;
			case '<=':
				return $left_operand <= $right_operand;
			case '>':
				return $left_operand > $right_operand;
			case '>=':
				return $left_operand >= $right_operand;
			case '!=':
				return $left_operand !== $right_operand;
			case 'contains':
				if ( is_array( $left_operand ) && is_string( $right_operand ) ) {
					return in_array( $right_operand, $left_operand, true );
				}
				if ( is_string( $right_operand ) && is_string( $left_operand ) ) {
					return strpos( $right_operand, $left_operand ) !== false;
				}
				break;
			case '!contains':
				if ( is_array( $left_operand ) && is_string( $right_operand ) ) {
					return ! in_array( $right_operand, $left_operand, true );
				}
				if ( is_string( $right_operand ) && is_string( $left_operand ) ) {
					return strpos( $right_operand, $left_operand ) === false;
				}
				break;
			case 'in':
				if ( is_array( $right_operand ) && is_string( $left_operand ) ) {
					return in_array( $left_operand, $right_operand, true );
				}
				if ( is_string( $left_operand ) && is_string( $right_operand ) ) {
					return strpos( $left_operand, $right_operand ) !== false;
				}
				break;
			case '!in':
				if ( is_array( $right_operand ) && is_string( $left_operand ) ) {
					return ! in_array( $left_operand, $right_operand, true );
				}
				if ( is_string( $left_operand ) && is_string( $right_operand ) ) {
					return strpos( $left_operand, $right_operand ) === false;
				}
				break;
		}

		return false;
	}
}