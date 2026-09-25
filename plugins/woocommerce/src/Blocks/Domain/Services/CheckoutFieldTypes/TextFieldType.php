<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldTypes;

/**
 * The "text" additional checkout field type.
 */
class TextFieldType extends AbstractFieldType {

	/**
	 * Formats a stored text value with its input mask.
	 *
	 * @param mixed $value The stored value.
	 * @param array $field The field.
	 * @return mixed The formatted value.
	 */
	public function format_value( $value, array $field ) {
		if ( ! empty( $field['mask'] ) && is_scalar( $value ) ) {
			return $this->apply_mask_to_value( (string) $value, $field['mask'] );
		}

		return $value;
	}

	/**
	 * Formats a raw value against a mask without consuming stored characters as literals.
	 *
	 * Returns the value formatted with the mask's literal characters when it fits the mask.
	 * Returns the value unchanged when it does not fit.
	 *
	 * @param string $value Raw value to format.
	 * @param string $mask  Mask pattern. Refer to docs.
	 * @return string
	 */
	private function apply_mask_to_value( string $value, string $mask ): string {
		$slot_patterns = array(
			'0' => '/^[0-9]$/u',
			'a' => '/^\p{L}$/u',
			'*' => '/^.$/su',
		);

		$mask_chars = preg_split( '//u', $mask, -1, PREG_SPLIT_NO_EMPTY );
		$mask_chars = false === $mask_chars ? array() : $mask_chars;
		$tokens     = array();

		for ( $i = 0, $count = count( $mask_chars ); $i < $count; $i++ ) {
			if ( '\\' === $mask_chars[ $i ] && $i + 1 < $count ) {
				$tokens[] = array(
					'type'  => 'literal',
					'value' => $mask_chars[ ++$i ],
				);
			} elseif ( isset( $slot_patterns[ $mask_chars[ $i ] ] ) ) {
				$tokens[] = array(
					'type'  => 'test',
					'value' => $slot_patterns[ $mask_chars[ $i ] ],
				);
			} else {
				$tokens[] = array(
					'type'  => 'literal',
					'value' => $mask_chars[ $i ],
				);
			}
		}

		$typed = preg_split( '//u', $value, -1, PREG_SPLIT_NO_EMPTY );
		$typed = false === $typed ? array() : $typed;

		$typed_length = count( $typed );
		$display      = '';
		$pending      = '';
		$t            = 0;

		foreach ( $tokens as $token ) {
			if ( 'literal' === $token['type'] ) {
				$pending .= $token['value'];
				continue;
			}

			if ( $t >= $typed_length ) {
				$pending = '';
				break;
			}

			if ( ! preg_match( $token['value'], $typed[ $t ] ) ) {
				return $value;
			}

			$display .= $pending . $typed[ $t ];
			$pending  = '';
			++$t;
		}

		if ( $t < $typed_length ) {
			return $value;
		}

		return $display . $pending;
	}
}
