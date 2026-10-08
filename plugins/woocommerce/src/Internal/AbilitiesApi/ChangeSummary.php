<?php
/**
 * Change summary class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\AbilityFields;

defined( 'ABSPATH' ) || exit;

/**
 * Compares the values of an object before and after a change, for the dry run
 * summary and the stale-change check of an ActionableAbility.
 *
 * @since 11.3.0
 */
final class ChangeSummary {

	/**
	 * Each leaf value that differs, with its dotted path and label. Lists of
	 * the same length are compared entry by entry, other lists as one value.
	 *
	 * @param string $object_type Object type, for the labels of extension fields.
	 * @param array  $before      Values before the change.
	 * @param array  $after       Values after the change.
	 * @return array<int, array{field: string, label: string, before: mixed, after: mixed}>
	 */
	public static function changes( string $object_type, array $before, array $after ): array {
		$fields = AbilityFields::get( $object_type );
		return array_map(
			static function ( array $change ) use ( $fields ): array {
				return array(
					'field'  => $change['field'],
					'label'  => self::label( $change['field'], $fields ),
					'before' => $change['before'],
					'after'  => $change['after'],
				);
			},
			self::diff( $before, $after, '' )
		);
	}

	/**
	 * The changes of the fields that an input sets: its top-level keys and the
	 * attributes under its `extensions`.
	 *
	 * @param array                 $changes Changes, as changes() returns them.
	 * @param array                 $input   Ability input.
	 * @param array<string, string> $names   Value names keyed by input field, for the fields whose names differ.
	 * @return array
	 */
	public static function requested( array $changes, array $input, array $names = array() ): array {
		$extensions = is_array( $input['extensions'] ?? null ) ? $input['extensions'] : array();
		unset( $input['id'], $input['expected'], $input['extensions'] );
		foreach ( array_intersect_key( $names, $input ) as $field => $name ) {
			$input[ $name ] = $input[ $field ];
		}

		return array_values(
			array_filter(
				$changes,
				static function ( array $change ) use ( $input, $extensions ): bool {
					$path = explode( '.', $change['field'] );
					return 'extensions' === $path[0]
						? array_key_exists( $path[1] ?? '', $extensions )
						: array_key_exists( $path[0], $input );
				}
			)
		);
	}

	/**
	 * The label of an object, such as "Order #71" or a product name, or null.
	 *
	 * @param object $subject Object.
	 * @return string|null
	 */
	public static function object_label( $subject ): ?string {
		if ( $subject instanceof \WC_Abstract_Order ) {
			$type = get_post_type_object( $subject->get_type() );
			return sprintf(
				/* translators: 1: Order type, such as Order or Subscription. 2: Order number. */
				__( '%1$s #%2$s', 'woocommerce' ),
				$type ? $type->labels->singular_name : __( 'Order', 'woocommerce' ),
				$subject->get_order_number()
			);
		}

		$label = is_callable( array( $subject, 'get_name' ) ) ? $subject->get_name() : null;
		return is_string( $label ) && '' !== $label ? $label : null;
	}

	/**
	 * The dotted paths whose values differ from the expected values.
	 *
	 * @param array $values   Current values.
	 * @param array $expected Expected values keyed by dotted path.
	 * @return string[]
	 */
	public static function stale( array $values, array $expected ): array {
		$stale = array();
		foreach ( $expected as $path => $value ) {
			if ( ! self::same( self::value_at( $values, (string) $path ), $value ) ) {
				$stale[] = (string) $path;
			}
		}
		return $stale;
	}

	/**
	 * Whether two values are the same after a JSON round trip, where 5.0 becomes 5.
	 *
	 * @param mixed $a Value.
	 * @param mixed $b Value.
	 * @return bool
	 */
	private static function same( $a, $b ): bool {
		if ( is_array( $a ) && is_array( $b ) ) {
			if ( count( $a ) !== count( $b ) ) {
				return false;
			}
			foreach ( $a as $key => $value ) {
				if ( ! array_key_exists( $key, $b ) || ! self::same( $value, $b[ $key ] ) ) {
					return false;
				}
			}
			return true;
		}
		if ( ( is_int( $a ) || is_float( $a ) ) && ( is_int( $b ) || is_float( $b ) ) ) {
			return (float) $a === (float) $b;
		}
		return $a === $b;
	}

	/**
	 * The value at a dotted path, or null when the path is missing.
	 *
	 * @param array  $values Values.
	 * @param string $path   Dotted path.
	 * @return mixed
	 */
	private static function value_at( array $values, string $path ) {
		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $values ) || ! array_key_exists( $segment, $values ) ) {
				return null;
			}
			$values = $values[ $segment ];
		}
		return $values;
	}

	/**
	 * Each leaf value that differs, with its dotted path.
	 *
	 * @param array  $before Values before.
	 * @param array  $after  Values after.
	 * @param string $prefix Path prefix.
	 * @return array<int, array{field: string, before: mixed, after: mixed}>
	 */
	private static function diff( array $before, array $after, string $prefix ): array {
		$changes = array();
		foreach ( array_keys( $before + $after ) as $key ) {
			$old   = $before[ $key ] ?? null;
			$new   = $after[ $key ] ?? null;
			$maps  = ( self::is_map( $old ) || self::is_map( $new ) ) && ( null === $old || is_array( $old ) ) && ( null === $new || is_array( $new ) );
			$lists = is_array( $old ) && is_array( $new ) && ! empty( $new ) && count( $old ) === count( $new ) && wp_is_numeric_array( $old ) && wp_is_numeric_array( $new );
			if ( $maps || $lists ) {
				$changes = array_merge( $changes, self::diff( (array) $old, (array) $new, $prefix . $key . '.' ) );
			} elseif ( ! self::same( $old, $new ) ) {
				$changes[] = array(
					'field'  => $prefix . $key,
					'before' => $old,
					'after'  => $new,
				);
			}
		}
		return $changes;
	}

	/**
	 * Whether a value is an array keyed by name.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function is_map( $value ): bool {
		return is_array( $value ) && ! empty( $value ) && ! wp_is_numeric_array( $value );
	}

	/**
	 * The label of a changed value: the schema `title` of an extension field,
	 * else its attribute, else the path.
	 *
	 * @param string                              $field  Dotted path.
	 * @param array<string, array<string, mixed>> $fields Extension fields keyed by attribute.
	 * @return string
	 */
	private static function label( string $field, array $fields ): string {
		$path = explode( '.', $field );
		if ( 'extensions' !== $path[0] || ! isset( $path[1] ) ) {
			return $field;
		}
		return (string) ( $fields[ $path[1] ]['schema']['title'] ?? $path[1] );
	}
}
