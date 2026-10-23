<?php
/**
 * Ability resources class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of the objects that abilities return, such as `order`, and of the
 * fields on them, which decides what is withheld as personal data. Core registers
 * its resources while the extensible abilities feature is off too; extensions
 * reach it through ExtensibleAbilities.
 *
 * @since 11.4.0
 */
class AbilityResources {

	/**
	 * Registered resources, keyed by resource name.
	 *
	 * @var array<string, bool>
	 */
	private static array $resources = array();

	/**
	 * Fields at the root of a resource, keyed by resource, then field.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $fields = array();

	/**
	 * Groups of fields under one key of a resource, such as `billing`, keyed by resource, then group.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $groups = array();

	/**
	 * Fields that extensions return under `extensions.<namespace>`, keyed by resource, namespace, then field.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $extension_fields = array();

	/**
	 * Register a resource. Registering it again does nothing.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Slug of the resource, such as `order`.
	 */
	public static function register( string $resource_name ): void {
		self::$resources[ $resource_name ] = true;
	}

	/**
	 * Whether a resource is registered.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource.
	 * @return bool
	 */
	public static function is_registered( string $resource_name ): bool {
		return isset( self::$resources[ $resource_name ] );
	}

	/**
	 * Register a group of fields that a resource returns under one key, such as `billing`.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource.
	 * @param string $group         Key of the group in the formatted object.
	 * @param array  $args {
	 *     Group arguments.
	 *
	 *     @type bool  $personal_data Whether every field of the group is personal data. Default false.
	 *     @type array $fields        Field arguments keyed by field, where a field can set its own `personal_data`.
	 * }
	 */
	public static function register_field_group( string $resource_name, string $group, array $args ): void {
		if ( ! self::is_registered( $resource_name ) ) {
			self::doing_it_wrong( __METHOD__, $resource_name );
			return;
		}
		$fields = $args['fields'] ?? array();
		foreach ( array_merge( array( $args ), $fields ) as $flagged ) {
			if ( ! self::has_valid_personal_data_flag( __METHOD__, $flagged ) ) {
				return;
			}
		}

		self::$groups[ $resource_name ][ $group ] = array(
			'personal_data' => $args['personal_data'] ?? false,
			'fields'        => $fields,
		);
	}

	/**
	 * Register a field at the root of a resource, with `group` inside a registered group, or with `namespace` as an extension field.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource.
	 * @param string $field         Key of the field in the formatted object or its group.
	 * @param array  $args {
	 *     Field arguments.
	 *
	 *     @type bool   $personal_data Whether the value is personal data. Defaults to the group's setting, else false.
	 *     @type string $group         Optional. Group that holds the field.
	 *     @type string $namespace     Optional. Extension that returns the field under `extensions.<namespace>`.
	 * }
	 * @return bool Whether the field was registered.
	 */
	public static function register_field( string $resource_name, string $field, array $args = array() ): bool {
		if ( ! self::is_registered( $resource_name ) ) {
			self::doing_it_wrong( __METHOD__, $resource_name );
			return false;
		}
		if ( ! self::has_valid_personal_data_flag( __METHOD__, $args ) ) {
			return false;
		}

		if ( isset( $args['namespace'] ) ) {
			$namespace = $args['namespace'];
			unset( $args['namespace'] );
			self::$extension_fields[ $resource_name ][ $namespace ][ $field ] = $args;
			return true;
		}
		if ( ! isset( $args['group'] ) ) {
			self::$fields[ $resource_name ][ $field ] = $args;
			return true;
		}
		if ( ! isset( self::$groups[ $resource_name ][ $args['group'] ] ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: 1: group name, 2: resource name. */
					__( 'The "%1$s" group of "%2$s" is not registered.', 'woocommerce' ),
					$args['group'],
					$resource_name
				),
				'11.4.0'
			);
			return false;
		}
		$group = $args['group'];
		unset( $args['group'] );
		self::$groups[ $resource_name ][ $group ]['fields'][ $field ] = $args;
		return true;
	}

	/**
	 * Withhold the personal data of a formatted object while the store does not share it. Core
	 * fields become null, so the object keeps its schema, and extension fields are left out.
	 *
	 * @since 11.4.0
	 *
	 * @param array<string, mixed> $output        Formatted object.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function withhold_personal_data( array $output, string $resource_name ): array {
		$paths = self::get_personal_data_paths( $resource_name );
		if ( empty( $paths ) || PersonalData::is_shared( $resource_name ) ) {
			return $output;
		}

		foreach ( $paths as $path ) {
			$output = self::withhold_path( $output, $path );
		}
		if ( isset( $output['extensions'] ) && is_array( $output['extensions'] ) ) {
			$output['extensions'] = array_filter( $output['extensions'] );
			if ( empty( $output['extensions'] ) ) {
				unset( $output['extensions'] );
			}
		}
		return $output;
	}

	/**
	 * Paths of the fields that hold personal data, such as `array( 'billing', 'phone' )`.
	 *
	 * @param string $resource_name Resource.
	 * @return array<int, string[]>
	 */
	private static function get_personal_data_paths( string $resource_name ): array {
		$paths = array();
		foreach ( self::$fields[ $resource_name ] ?? array() as $field => $args ) {
			if ( ! empty( $args['personal_data'] ) ) {
				$paths[] = array( $field );
			}
		}
		foreach ( self::$groups[ $resource_name ] ?? array() as $group => $group_args ) {
			foreach ( $group_args['fields'] as $field => $args ) {
				if ( $args['personal_data'] ?? $group_args['personal_data'] ) {
					$paths[] = array( $group, $field );
				}
			}
		}
		foreach ( self::$extension_fields[ $resource_name ] ?? array() as $namespace => $fields ) {
			foreach ( $fields as $field => $args ) {
				if ( ! empty( $args['personal_data'] ) ) {
					$paths[] = array( 'extensions', $namespace, $field );
				}
			}
		}
		return $paths;
	}

	/**
	 * Null a Core field, or leave out an extension field, at a path of a formatted object.
	 *
	 * @param array<string, mixed> $output Formatted object.
	 * @param string[]             $path   Keys of the field, such as `array( 'billing', 'phone' )`.
	 * @return array<string, mixed>
	 */
	private static function withhold_path( array $output, array $path ): array {
		if ( 'extensions' === $path[0] ) {
			if ( is_array( $output['extensions'][ $path[1] ] ?? null ) ) {
				unset( $output['extensions'][ $path[1] ][ $path[2] ] );
			}
		} elseif ( ! isset( $path[1] ) ) {
			if ( array_key_exists( $path[0], $output ) ) {
				$output[ $path[0] ] = null;
			}
		} elseif ( is_array( $output[ $path[0] ] ?? null ) && array_key_exists( $path[1], $output[ $path[0] ] ) ) {
			$output[ $path[0] ][ $path[1] ] = null;
		}
		return $output;
	}

	/**
	 * Whether the `personal_data` argument is missing or a boolean. Anything else is refused, so a
	 * field never falls back to being shared.
	 *
	 * @param string               $method Method that was called.
	 * @param array<string, mixed> $args   Field or group arguments.
	 * @return bool
	 */
	private static function has_valid_personal_data_flag( string $method, array $args ): bool {
		if ( ! isset( $args['personal_data'] ) || is_bool( $args['personal_data'] ) ) {
			return true;
		}

		wc_doing_it_wrong( $method, __( 'The "personal_data" argument must be a boolean.', 'woocommerce' ), '11.4.0' );
		return false;
	}

	/**
	 * Report a field registered on a resource that does not exist.
	 *
	 * @param string $method        Method that was called.
	 * @param string $resource_name Resource.
	 */
	private static function doing_it_wrong( string $method, string $resource_name ): void {
		wc_doing_it_wrong(
			$method,
			sprintf(
				/* translators: %s: resource name. */
				__( 'The "%s" resource is not registered.', 'woocommerce' ),
				$resource_name
			),
			'11.4.0'
		);
	}
}
