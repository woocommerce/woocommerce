<?php

namespace Automattic\WooCommerce\RemoteSpecsValidation;

use Opis\JsonSchema\Validator;

/***
 * A thin wrapper of Opis JSON Schema Validator.
 *
 * Validates a remote spec against a JSON schema.
 */
class RemoteSpecValidator {
	/**
	 * Decoded JSON schema.
	 * @var string
	 */
	private $schema;

	private static $supported_bundles = [
		'remote-inbox-notification' => 'remote-inbox-notification.json',
		'wc-pay-promotions'   => 'wc-pay-promotions.json',
		'shipping-partner-suggestions' => 'shipping-partner-suggestions.json',
		'payment-gateway-suggestions' => 'payment-gateway-suggestions.json',
		'obw-free-extensions' => 'obw-free-extensions.json',
	];

	public function __construct( $json_schema_string ) {
		$this->schema = $json_schema_string;
	}

	public static function create_from_file( $json_schema_path ) {
		return new self( json_decode( file_get_contents( $json_schema_path ) ) );
	}

	/**
	 * Create a RemoteSpecValidator from a bundle file.
	 *
	 * @param string $bundle The name of the bundle.
	 * @return RemoteSpecValidator
	 * @throws \InvalidArgumentException If the bundle is not supported.
	 */
	public static function create_from_bundle( $bundle ) {
		return new self( static::get_bundle_json( $bundle ) );
	}

	public static function get_bundle_json( $bundle) {
		if ( ! array_key_exists( $bundle, static::$supported_bundles ) ) {
			throw new \InvalidArgumentException( "Unsupported bundle: $bundle. ".
			                                     "Supported bundles are: " . implode( ', ', array_keys( static::$supported_bundles ) ) );
		}

		return file_get_contents( __DIR__ . "/../bundles/" . static::$supported_bundles[ $bundle ] );
	}

	/**
	 * Validate a remote spec against the schema.
	 *
	 * @param string $spec The remote spec to validate.
	 * @return RemoteSpecValidationResult The validation result.
	 */
	public function validate( $spec ) {
		$validator = new Validator();
		return new RemoteSpecValidationResult( $validator->validate( $spec, $this->schema ) );
	}
}
