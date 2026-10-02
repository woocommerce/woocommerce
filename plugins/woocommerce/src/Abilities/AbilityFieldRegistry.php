<?php
/**
 * Fields extensions add to WooCommerce's abilities, under `extensions.{namespace}`.
 *
 * One registration drives the input schema, the write and the read. `apply`
 * changes the object in memory; the ability owns the single save.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Abilities;

use WC_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Extension field registry.
 */
class AbilityFieldRegistry {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Whether registrations were collected.
	 *
	 * @var bool
	 */
	private $collected = false;

	/**
	 * Whether registrations are being collected.
	 *
	 * @var bool
	 */
	private $collecting = false;

	/**
	 * Registrations keyed by resource, then namespace.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private $registrations = array();

	/**
	 * Values added to Core enums, keyed by resource, field, then value.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private $enum_values = array();

	/**
	 * The shared registry.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		self::$instance->collect();
		return self::$instance;
	}

	/**
	 * Collect registrations on first use, once all plugins have loaded.
	 */
	private function collect(): void {
		if ( $this->collected || $this->collecting ) {
			return;
		}

		if ( ! did_action( 'plugins_loaded' ) || doing_action( 'plugins_loaded' ) ) {
			_doing_it_wrong(
				__CLASS__ . '::instance',
				esc_html__( 'Use this registry after all plugins have loaded.', 'woocommerce' ),
				'11.3.0'
			);
			return;
		}

		$this->collecting = true;

		/**
		 * Register extension fields on WooCommerce's abilities. Fires once, the first time the registry is used after all plugins have loaded.
		 *
		 * @since 11.3.0
		 *
		 * @param AbilityFieldRegistry $registry Registry.
		 */
		do_action( 'woocommerce_register_ability_fields', $this );
		$this->collecting = false;
		$this->collected  = true;
	}

	/**
	 * Register a namespace of fields. The first registration of a namespace wins.
	 *
	 * @param string               $resource_type       Resource, e.g. `product`.
	 * @param string               $extension_namespace Extension namespace, e.g. `subscriptions`.
	 * @param array<string, mixed> $definition          `product_types` (every type when absent) and `fields` (each with `schema`, `apply`, `read`, optional `validate`). A field's `schema` may carry a translatable, merchant-facing `title` next to its model-facing `description`; both reach the input and output schemas unchanged.
	 */
	public function register( string $resource_type, string $extension_namespace, array $definition ): void {
		if ( $this->refuse_late( __METHOD__ ) ) {
			return;
		}

		if ( ! $this->has( $resource_type, $extension_namespace ) ) {
			$this->registrations[ $resource_type ][ $extension_namespace ] = $definition;
		}
	}

	/**
	 * Add a value to a Core enum. The first registration of a value wins.
	 *
	 * @param string               $resource_type   Resource, e.g. `product`.
	 * @param string               $field      Core input field, e.g. `product_type_alias`.
	 * @param string               $value      Value to add.
	 * @param array<string, mixed> $definition `namespace` (owner), `description`, optional `validate`, called with the value and the applied object before the save, and, for `product_type_alias`, optional `behaves_like`: the Core alias (`physical`, `virtual`, `digital`, `affiliate` or `grouped`) whose fields the type accepts. Without it the type accepts Core's shared fields only.
	 */
	public function register_enum_value( string $resource_type, string $field, string $value, array $definition ): void {
		if ( $this->refuse_late( __METHOD__ ) ) {
			return;
		}

		if ( ! isset( $this->enum_values[ $resource_type ][ $field ][ $value ] ) ) {
			$this->enum_values[ $resource_type ][ $field ][ $value ] = $definition;
		}
	}

	/**
	 * Values registered for a Core enum, keyed by value.
	 *
	 * @param string $resource_type Resource.
	 * @param string $field    Core input field.
	 * @return array<string, array<string, mixed>>
	 */
	public function enum_values( string $resource_type, string $field ): array {
		return $this->enum_values[ $resource_type ][ $field ] ?? array();
	}

	/**
	 * Refuse a registration made after collection.
	 *
	 * @param string $method Registering method.
	 */
	private function refuse_late( string $method ): bool {
		if ( ! $this->collected ) {
			return false;
		}

		_doing_it_wrong(
			esc_html( $method ),
			/* translators: %s: action name. */
			sprintf( esc_html__( 'Register ability fields on the %s action.', 'woocommerce' ), 'woocommerce_register_ability_fields' ),
			'11.3.0'
		);
		return true;
	}

	/**
	 * Whether a namespace is registered for a resource.
	 *
	 * @param string $resource_type       Resource.
	 * @param string $extension_namespace Namespace.
	 */
	public function has( string $resource_type, string $extension_namespace ): bool {
		return isset( $this->registrations[ $resource_type ][ $extension_namespace ] );
	}

	/**
	 * Product types that registered namespaces apply to.
	 *
	 * @return string[]
	 */
	public function product_types(): array {
		$types = array();
		foreach ( $this->registrations['product'] ?? array() as $definition ) {
			$types = array_merge( $types, (array) ( $definition['product_types'] ?? array() ) );
		}
		return array_values( array_unique( $types ) );
	}

	/**
	 * Whether a registered namespace applies to this product type. A namespace
	 * registered without `product_types` applies to every type.
	 *
	 * @param string $product_type Product type.
	 */
	public function supports_product_type( string $product_type ): bool {
		foreach ( $this->registrations['product'] ?? array() as $definition ) {
			if ( ! isset( $definition['product_types'] ) || in_array( $product_type, (array) $definition['product_types'], true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * JSON schema for the `extensions` property, or null when no namespace applies.
	 *
	 * @param string      $resource_type     Resource.
	 * @param string|null $product_type Only namespaces that apply to this product type, or null for all.
	 * @return array<string, mixed>|null
	 */
	public function schema( string $resource_type, ?string $product_type = null ): ?array {
		$namespaces = array();
		foreach ( $this->registrations[ $resource_type ] ?? array() as $namespace => $definition ) {
			if ( null !== $product_type && isset( $definition['product_types'] ) && ! in_array( $product_type, (array) $definition['product_types'], true ) ) {
				continue;
			}
			$fields = array();
			foreach ( $definition['fields'] as $name => $field ) {
				$fields[ $name ] = $field['schema'];
			}
			$namespaces[ $namespace ] = array(
				'type'                 => 'object',
				'description'          => $definition['description'],
				'properties'           => $fields,
				'additionalProperties' => false,
			);
		}

		if ( empty( $namespaces ) ) {
			return null;
		}

		return array(
			'type'                 => 'object',
			'description'          => 'Fields added by extensions, grouped by extension.',
			'properties'           => $namespaces,
			'additionalProperties' => false,
		);
	}

	/**
	 * Validate every value, then apply them all in memory. Nothing is applied
	 * when any value is rejected.
	 *
	 * @param string  $resource_type   Resource.
	 * @param WC_Data $subject    Object to change.
	 * @param mixed   $extensions `extensions` input.
	 * @return string|null Error message, or null on success.
	 */
	public function apply( string $resource_type, WC_Data $subject, $extensions ): ?string {
		if ( ! is_array( $extensions ) ) {
			return __( 'extensions must be an object.', 'woocommerce' );
		}

		$writes = array();
		foreach ( $extensions as $namespace => $values ) {
			$definition = $this->registrations[ $resource_type ][ $namespace ] ?? null;
			if ( null === $definition || ! is_array( $values ) ) {
				/* translators: %s: extension namespace. */
				return sprintf( __( 'Unknown extension "%s".', 'woocommerce' ), $namespace );
			}
			if ( $subject instanceof \WC_Product && isset( $definition['product_types'] ) && ! $subject->is_type( $definition['product_types'] ) ) {
				return $this->applicable_namespaces_message( $subject );
			}
			foreach ( $values as $name => $value ) {
				$field = $definition['fields'][ $name ] ?? null;
				if ( null === $field ) {
					/* translators: 1: extension namespace, 2: field name. */
					return sprintf( __( 'Unknown field "%1$s.%2$s".', 'woocommerce' ), $namespace, $name );
				}
				$valid = rest_validate_value_from_schema( $value, $field['schema'], "extensions.$namespace.$name" );
				if ( is_wp_error( $valid ) ) {
					return $valid->get_error_message();
				}
				if ( isset( $field['validate'] ) ) {
					$valid = call_user_func( $field['validate'], $value, $subject );
					if ( is_wp_error( $valid ) ) {
						return $valid->get_error_message();
					}
				}
				$writes[] = array( $field['apply'], $value );
			}
		}

		foreach ( $writes as $write ) {
			call_user_func( $write[0], $write[1], $subject );
		}
		return null;
	}

	/**
	 * Names the extension namespaces that apply to a product's type.
	 *
	 * @param \WC_Product $product Product.
	 */
	private function applicable_namespaces_message( \WC_Product $product ): string {
		$namespaces = array();
		foreach ( $this->registrations['product'] ?? array() as $namespace => $definition ) {
			if ( ! isset( $definition['product_types'] ) || $product->is_type( $definition['product_types'] ) ) {
				$namespaces[] = "extensions.$namespace";
			}
		}

		if ( empty( $namespaces ) ) {
			/* translators: 1: product ID, 2: product type. */
			return sprintf( __( 'Product %1$d is of type %2$s. It accepts no extension fields.', 'woocommerce' ), $product->get_id(), $product->get_type() );
		}

		/* translators: 1: product ID, 2: product type, 3: comma-separated extension namespaces. */
		return sprintf( __( 'Product %1$d is of type %2$s. It accepts %3$s.', 'woocommerce' ), $product->get_id(), $product->get_type(), implode( ', ', $namespaces ) );
	}

	/**
	 * Write values without validating them. Undo uses this to put back what
	 * read() captured, which may be a value the schema would not accept.
	 *
	 * @param string                              $resource_type Resource.
	 * @param WC_Data                             $subject  Object to change.
	 * @param array<string, array<string, mixed>> $values   Values keyed by namespace, then field.
	 */
	public function restore( string $resource_type, WC_Data $subject, array $values ): void {
		foreach ( $values as $namespace => $fields ) {
			foreach ( (array) $fields as $name => $value ) {
				$field = $this->registrations[ $resource_type ][ $namespace ]['fields'][ $name ] ?? null;
				if ( null !== $field ) {
					call_user_func( $field['apply'], $value, $subject );
				}
			}
		}
	}

	/**
	 * Registered values that apply to this object, keyed by namespace.
	 *
	 * @param string              $resource_type Resource.
	 * @param WC_Data             $subject  Object to read.
	 * @param array<string,mixed> $only     Read only these fields, given in the `extensions` input shape.
	 * @return array<string, array<string, mixed>>
	 */
	public function read( string $resource_type, WC_Data $subject, ?array $only = null ): array {
		$values = array();
		foreach ( $this->registrations[ $resource_type ] ?? array() as $namespace => $definition ) {
			if ( $subject instanceof \WC_Product && isset( $definition['product_types'] ) && ! $subject->is_type( $definition['product_types'] ) ) {
				continue;
			}
			$names = null === $only ? array_keys( $definition['fields'] ) : array_keys( (array) ( $only[ $namespace ] ?? array() ) );
			foreach ( $names as $name ) {
				if ( isset( $definition['fields'][ $name ] ) ) {
					$values[ $namespace ][ $name ] = call_user_func( $definition['fields'][ $name ]['read'], $subject );
				}
			}
		}
		return $values;
	}
}
