<?php
/**
 * Abilities API adapter class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityContracts;
use Automattic\WooCommerce\Abilities\AbilityFieldRegistry;
use Automattic\WooCommerce\Abilities\ObjectValidatorRegistry;
use Automattic\WooCommerce\Internal\AbilitiesApi\RegistrationArgs;

defined( 'ABSPATH' ) || exit;

/**
 * Connects WooCommerce to the generic ability layer: the ability hooks behind
 * the feature, WC_Data subjects, product loading, the extension field
 * registry, the object validator registry and WooCommerce's data exceptions.
 *
 * @since 11.3.0
 */
class AbilitiesApiAdapter {

	/**
	 * Hook the adapters, unless WordPress handles ability fields itself.
	 *
	 * @internal
	 */
	final public static function init(): void {
		if ( function_exists( 'wp_register_ability_field' ) ) {
			return;
		}

		add_filter( 'wp_register_ability_args', array( __CLASS__, 'registration_args' ) );
		add_filter( 'wp_ability_execute_result', array( __CLASS__, 'execute_result' ), 10, 4 );
		add_filter( 'woocommerce_ability_object', array( __CLASS__, 'load_object' ), 10, 3 );
		add_filter( 'woocommerce_ability_in_memory_subject', array( __CLASS__, 'subject' ), 10, 2 );
		add_filter( 'woocommerce_ability_fields', array( __CLASS__, 'fields' ), 10, 2 );
		add_filter( 'woocommerce_ability_object_validators', array( __CLASS__, 'validators' ), 10, 2 );
		add_filter( 'woocommerce_in_memory_write_exception_error', array( __CLASS__, 'exception_error' ), 10, 3 );
		add_action( 'woocommerce_ability_side_effect_blocked', array( __CLASS__, 'log_side_effect' ), 10, 2 );
	}

	/**
	 * Build the ability from its `meta.woocommerce` when ability contracts are on.
	 *
	 * @internal
	 *
	 * @param mixed $args Registration arguments.
	 * @return mixed
	 */
	public static function registration_args( $args ) {
		return is_array( $args ) && AbilityContracts::is_enabled() ? RegistrationArgs::apply( $args ) : $args;
	}

	/**
	 * Add extension field values to the output when ability contracts are on.
	 *
	 * @internal
	 *
	 * @param mixed  $result       Execute result.
	 * @param string $ability_name Ability name.
	 * @param mixed  $input        Input.
	 * @param mixed  $ability      Ability.
	 * @return mixed
	 */
	public static function execute_result( $result, $ability_name, $input, $ability ) {
		return $ability instanceof \WP_Ability && AbilityContracts::is_enabled() ? RegistrationArgs::fill_result( $result, $ability ) : $result;
	}

	/**
	 * Load a product by ID.
	 *
	 * @internal
	 *
	 * @param mixed  $subject     Object.
	 * @param string $object_type Object type.
	 * @param mixed  $id          Object ID.
	 * @return mixed
	 */
	public static function load_object( $subject, $object_type, $id ) {
		if ( null !== $subject || 'product' !== $object_type ) {
			return $subject;
		}
		$product = wc_get_product( $id );
		return $product ? $product : null;
	}

	/**
	 * Save a WC_Data object.
	 *
	 * @internal
	 *
	 * @param mixed  $subject Subject.
	 * @param object $target  Object to save.
	 * @return mixed
	 */
	public static function subject( $subject, $target ) {
		return null === $subject && $target instanceof \WC_Data ? new WCDataSubject( $target ) : $subject;
	}

	/**
	 * Add each namespace of the extension field registry as a field.
	 *
	 * @internal
	 *
	 * @param array  $fields      Fields keyed by attribute.
	 * @param string $object_type Object type.
	 * @return array
	 */
	public static function fields( $fields, $object_type ): array {
		return array_merge( (array) $fields, AbilityFieldRegistry::instance()->ability_fields( (string) $object_type ) );
	}

	/**
	 * Add the object validator registry and, for products, the validator of the
	 * extension that registered the product type.
	 *
	 * @internal
	 *
	 * @param array  $validators  Validators.
	 * @param string $object_type Object type.
	 * @return array
	 */
	public static function validators( $validators, $object_type ): array {
		$validators   = (array) $validators;
		$validators[] = static function ( $subject ) use ( $object_type ) {
			$rejection = ObjectValidatorRegistry::instance()->validate( $subject, (string) $object_type );
			return null === $rejection ? true : new \WP_Error( 'woocommerce_object_rejected', $rejection );
		};

		if ( 'product' === $object_type ) {
			$validators[] = static function ( $product ) {
				$validate = $product instanceof \WC_Product ? ( AbilityFieldRegistry::instance()->enum_values( 'product', 'product_type_alias' )[ $product->get_type() ]['validate'] ?? null ) : null;
				return null === $validate ? true : call_user_func( $validate, $product->get_type(), $product );
			};
		}
		return $validators;
	}

	/**
	 * Log the side effects a step tried.
	 *
	 * @internal
	 *
	 * @param array  $attempts     Blocked attempts keyed by kind.
	 * @param string $ability_name Ability name.
	 */
	public static function log_side_effect( $attempts, $ability_name ): void {
		wc_get_logger()->error(
			'A step that must not have side effects tried one. Nothing was saved.',
			array(
				'source'   => 'woocommerce-abilities',
				'ability'  => $ability_name,
				'attempts' => $attempts,
			)
		);
	}

	/**
	 * Return a WooCommerce data exception as a 400 error, and log any other exception.
	 *
	 * @internal
	 *
	 * @param mixed      $error        Error.
	 * @param \Exception $exception    Exception.
	 * @param string     $ability_name Ability name.
	 * @return mixed
	 */
	public static function exception_error( $error, $exception, $ability_name ) {
		if ( $exception instanceof \WC_Data_Exception ) {
			return new \WP_Error( 'woocommerce_in_memory_write_save_failed', $exception->getMessage(), array( 'status' => 400 ) );
		}

		wc_get_logger()->error(
			'In-memory write failed.',
			array(
				'source'    => 'woocommerce-abilities',
				'ability'   => $ability_name,
				'exception' => get_class( $exception ),
				'message'   => $exception->getMessage(),
			)
		);
		return $error;
	}
}
