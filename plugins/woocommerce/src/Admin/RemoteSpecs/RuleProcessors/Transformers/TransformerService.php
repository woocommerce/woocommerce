<?php

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers;

use InvalidArgumentException;
use stdClass;

/**
 * A simple service class for the Transformer classes.
 *
 * Class TransformerService
 *
 * @package Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors\Transformers
 */
class TransformerService {
	/**
	 * Create a transformer object by name.
	 *
	 * @param string $name name of the transformer.
	 *
	 * @return TransformerInterface|null
	 */
	public static function create_transformer( $name ) {
		$camel_cased = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $name ) ) );

		$classname = __NAMESPACE__ . '\\' . $camel_cased;
		if ( ! class_exists( $classname ) ) {
			return null;
		}

		return new $classname();
	}

	/**
	 * Apply transformers to the given value.
	 *
	 * @param mixed  $target_value a value to transform.
	 * @param array  $transformer_configs transform configuration.
	 * @param bool   $is_default_set flag on is default value set.
	 * @param string $default_value default value.
	 *
	 * @throws InvalidArgumentException Throws when one of the required arguments is missing.
	 * @return mixed|null
	 */
	public static function apply( $target_value, array $transformer_configs, $is_default_set, $default_value ) {
		foreach ( $transformer_configs as $transformer_config ) {
			if ( ! isset( $transformer_config->use ) ) {
				throw new InvalidArgumentException( 'Missing required config value: use' );
			}

			if ( ! isset( $transformer_config->arguments ) ) {
				$transformer_config->arguments = null;
			}

			$transformer = self::create_transformer( $transformer_config->use );
			if ( null === $transformer ) {
				throw new InvalidArgumentException( "Unable to find a transformer by name: {$transformer_config->use}" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			$target_value = $transformer->transform( $target_value, $transformer_config->arguments, $is_default_set ? $default_value : null );

			// Break early when there's no more value to traverse.
			if ( null === $target_value ) {
				break;
			}
		}

		if ( $is_default_set ) {
			// Nulls always return the default value.
			if ( null === $target_value ) {
				return $default_value;
			}

			// When type of the default value is different from the target value, return the default value
			// to ensure type safety.
			if ( gettype( $default_value ) !== gettype( $target_value ) ) {
				return $default_value;
			}
		}

		return $target_value;
	}
}
