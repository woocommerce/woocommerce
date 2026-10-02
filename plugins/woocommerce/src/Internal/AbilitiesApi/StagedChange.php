<?php
/**
 * Staged change class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * An object changed in memory that passed every check and is not saved yet.
 *
 * @since 11.3.0
 */
final class StagedChange {

	/**
	 * Ability name.
	 *
	 * @var string
	 */
	private $ability_name;

	/**
	 * Object type.
	 *
	 * @var string
	 */
	private $object_type;

	/**
	 * Changed object.
	 *
	 * @var object
	 */
	private $target;

	/**
	 * Subject that saves the object.
	 *
	 * @var InMemorySubject
	 */
	private $subject;

	/**
	 * The object's data and extension field values before and after the change.
	 *
	 * @var array{before: array, after: array}
	 */
	private $values;

	/**
	 * Effects of the call.
	 *
	 * @var string[]
	 */
	private $side_effects;

	/**
	 * The ability call that undoes the change.
	 *
	 * @var array|null
	 */
	private $undo;

	/**
	 * Create a staged change.
	 *
	 * @param string          $ability_name Ability name.
	 * @param string          $object_type  Object type.
	 * @param object          $target       Changed object.
	 * @param InMemorySubject $subject      Subject that saves the object.
	 * @param array           $before       Data and extension field values before the change.
	 * @param array           $after        Data and extension field values after the change.
	 * @param string[]        $side_effects Effects of the call.
	 * @param array|null      $undo         The ability call that undoes the change.
	 */
	public function __construct( string $ability_name, string $object_type, $target, InMemorySubject $subject, array $before, array $after, array $side_effects, ?array $undo ) {
		$this->ability_name = $ability_name;
		$this->object_type  = $object_type;
		$this->target       = $target;
		$this->subject      = $subject;
		$this->values       = array(
			'before' => $before,
			'after'  => $after,
		);
		$this->side_effects = $side_effects;
		$this->undo         = $undo;
	}

	/**
	 * The changed object.
	 *
	 * @return object
	 */
	public function target() {
		return $this->target;
	}

	/**
	 * Save the object.
	 *
	 * @return true|\WP_Error
	 */
	public function save() {
		return $this->subject->save();
	}

	/**
	 * The object's current data.
	 */
	public function snapshot(): array {
		return $this->subject->snapshot();
	}

	/**
	 * What the change would do: each value that differs, the side effects and the undo call.
	 */
	public function summary(): array {
		$summary = array(
			'ability'     => $this->ability_name,
			'object_type' => $this->object_type,
		);
		if ( ! empty( $this->values['after']['id'] ) ) {
			$summary['object_id'] = $this->values['after']['id'];
		}

		$fields                  = AbilityFields::get( $this->object_type );
		$summary['changes']      = array_map(
			static function ( array $change ) use ( $fields ): array {
				return array(
					'field'  => $change['field'],
					'label'  => self::label( $change['field'], $fields ),
					'before' => $change['before'],
					'after'  => $change['after'],
				);
			},
			self::diff( $this->values['before'], $this->values['after'] )
		);
		$summary['side_effects'] = $this->side_effects;
		$summary['undo']         = $this->undo;
		return $summary;
	}

	/**
	 * Each leaf value that differs, with its dotted path.
	 *
	 * @param array  $before Values before.
	 * @param array  $after  Values after.
	 * @param string $prefix Path prefix.
	 * @return array<int, array{field: string, before: mixed, after: mixed}>
	 */
	private static function diff( array $before, array $after, string $prefix = '' ): array {
		$changes = array();
		foreach ( array_keys( $before + $after ) as $key ) {
			$old = $before[ $key ] ?? null;
			$new = $after[ $key ] ?? null;
			if ( ( self::is_map( $old ) || self::is_map( $new ) ) && ( null === $old || is_array( $old ) ) && ( null === $new || is_array( $new ) ) ) {
				$changes = array_merge( $changes, self::diff( (array) $old, (array) $new, $prefix . $key . '.' ) );
			} elseif ( $old !== $new ) {
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
	 */
	private static function is_map( $value ): bool {
		return is_array( $value ) && ! empty( $value ) && ! wp_is_numeric_array( $value );
	}

	/**
	 * The label of a changed value: the deepest schema `title` on an extension
	 * field's path, else the attribute, or the path for the object's own data.
	 *
	 * @param string                              $field  Dotted path.
	 * @param array<string, array<string, mixed>> $fields Extension fields keyed by attribute.
	 */
	private static function label( string $field, array $fields ): string {
		$path = explode( '.', $field );
		if ( 'extensions' !== $path[0] || ! isset( $path[1] ) ) {
			return $field;
		}

		$schema = $fields[ $path[1] ]['schema'] ?? array();
		$label  = (string) ( $schema['title'] ?? $path[1] );
		foreach ( array_slice( $path, 2 ) as $segment ) {
			$schema = $schema['properties'][ $segment ] ?? array();
			$label  = isset( $schema['title'] ) ? (string) $schema['title'] : $label;
		}
		return $label;
	}
}
