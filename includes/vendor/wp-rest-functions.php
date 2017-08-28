<?php
/**
 * @version 2.0-beta15
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * core-integration.php
 */

if ( ! function_exists( 'wp_parse_slug_list' ) ) {
	/**
	 * Clean up an array, comma- or space-separated list of slugs.
	 *
	 * @since
	 *
	 * @param  array|string $list List of slugs.
	 * @return array Sanitized array of slugs.
	 */
	function wp_parse_slug_list( $list ) {
		if ( ! is_array( $list ) ) {
			$list = preg_split( '/[\s,]+/', $list );
		}

		foreach ( $list as $key => $value ) {
			$list[ $key ] = sanitize_title( $value );
		}

		return array_unique( $list );
	}
}

if ( ! function_exists( 'rest_get_server' ) ) {
	/**
	 * Retrieves the current REST server instance.
	 *
	 * Instantiates a new instance if none exists already.
	 *
	 * @since 4.5.0
	 *
	 * @global WP_REST_Server $wp_rest_server REST server instance.
	 *
	 * @return WP_REST_Server REST server instance.
	 */
	function rest_get_server() {
		/* @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;

		if ( empty( $wp_rest_server ) ) {
			/**
			 * Filter the REST Server Class.
			 *
			 * This filter allows you to adjust the server class used by the API, using a
			 * different class to handle requests.
			 *
			 * @since 4.4.0
			 *
			 * @param string $class_name The name of the server class. Default 'WP_REST_Server'.
			 */
			$wp_rest_server_class = apply_filters( 'wp_rest_server_class', 'WP_REST_Server' );
			$wp_rest_server = new $wp_rest_server_class;

			/**
			 * Fires when preparing to serve an API request.
			 *
			 * Endpoint objects should be created and register their hooks on this action rather
			 * than another action to ensure they're only loaded when needed.
			 *
			 * @since 4.4.0
			 *
			 * @param WP_REST_Server $wp_rest_server Server object.
			 */
			do_action( 'rest_api_init', $wp_rest_server );
		}

		return $wp_rest_server;
	}
}

/**
 * plugin.php
 */

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	/**
	 * Returns a contextual HTTP error code for authorization failure.
	 *
	 * @return integer
	 */
	function rest_authorization_required_code() {
		return is_user_logged_in() ? 403 : 401;
	}
}

if ( ! function_exists( 'register_rest_field' ) ) {
	/**
	 * Registers a new field on an existing WordPress object type.
	 *
	 * @global array $wp_rest_additional_fields Holds registered fields, organized
	 *                                          by object type.
	 *
	 * @param string|array $object_type Object(s) the field is being registered
	 *                                  to, "post"|"term"|"comment" etc.
	 * @param string $attribute         The attribute name.
	 * @param array  $args {
	 *     Optional. An array of arguments used to handle the registered field.
	 *
	 *     @type string|array|null $get_callback    Optional. The callback function used to retrieve the field
	 *                                              value. Default is 'null', the field will not be returned in
	 *                                              the response.
	 *     @type string|array|null $update_callback Optional. The callback function used to set and update the
	 *                                              field value. Default is 'null', the value cannot be set or
	 *                                              updated.
	 *     @type string|array|null $schema          Optional. The callback function used to create the schema for
	 *                                              this field. Default is 'null', no schema entry will be returned.
	 * }
	 */
	function register_rest_field( $object_type, $attribute, $args = array() ) {
		$defaults = array(
			'get_callback'    => null,
			'update_callback' => null,
			'schema'          => null,
		);

		$args = wp_parse_args( $args, $defaults );

		global $wp_rest_additional_fields;

		$object_types = (array) $object_type;

		foreach ( $object_types as $object_type ) {
			$wp_rest_additional_fields[ $object_type ][ $attribute ] = $args;
		}
	}
}

if ( ! function_exists( 'register_api_field' ) ) {
	/**
	 * Backwards compatibility shim
	 *
	 * @param array|string $object_type
	 * @param string $attributes
	 * @param array $args
	 */
	function register_api_field( $object_type, $attributes, $args = array() ) {
		wc_deprecated_function( 'register_api_field', 'WPAPI-2.0', 'register_rest_field' );
		register_rest_field( $object_type, $attributes, $args );
	}
}

if ( ! function_exists( 'rest_validate_request_arg' ) ) {
	/**
	 * Validate a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value
	 * @param  WP_REST_Request  $request
	 * @param  string           $param
	 * @return WP_Error|boolean
	 */
	function rest_validate_request_arg( $value, $request, $param ) {

		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return true;
		}
		$args = $attributes['args'][ $param ];

		if ( ! empty( $args['enum'] ) ) {
			if ( ! in_array( $value, $args['enum'] ) ) {
				/* translators: 1: parameter 2: arguments */
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not one of %2$s', 'woocommerce' ), $param, implode( ', ', $args['enum'] ) ) );
			}
		}

		if ( 'integer' === $args['type'] && ! is_numeric( $value ) ) {
			/* translators: 1: parameter 2: integer type */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s', 'woocommerce' ), $param, 'integer' ) );
		}

		if ( 'boolean' === $args['type'] && ! rest_is_boolean( $value ) ) {
			/* translators: 1: parameter 2: boolean type */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s', 'woocommerce' ), $value, 'boolean' ) );
		}

		if ( 'string' === $args['type'] && ! is_string( $value ) ) {
			/* translators: 1: parameter 2: string type */
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not of type %2$s', 'woocommerce' ), $param, 'string' ) );
		}

		if ( isset( $args['format'] ) ) {
			switch ( $args['format'] ) {
				case 'date-time' :
					if ( ! rest_parse_date( $value ) ) {
						return new WP_Error( 'rest_invalid_date', __( 'The date you provided is invalid.', 'woocommerce' ) );
					}
					break;

				case 'email' :
					if ( ! is_email( $value ) ) {
						return new WP_Error( 'rest_invalid_email', __( 'The email address you provided is invalid.', 'woocommerce' ) );
					}
					break;
				case 'ipv4' :
					if ( ! rest_is_ip_address( $value ) ) {
						/* translators: %s: IP address */
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not a valid IP address.', 'woocommerce' ), $value ) );
					}
					break;
			}
		}

		if ( in_array( $args['type'], array( 'numeric', 'integer' ) ) && ( isset( $args['minimum'] ) || isset( $args['maximum'] ) ) ) {
			if ( isset( $args['minimum'] ) && ! isset( $args['maximum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && $value <= $args['minimum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be greater than %2$d (exclusive)', 'woocommerce' ), $param, $args['minimum'] ) );
				} elseif ( empty( $args['exclusiveMinimum'] ) && $value < $args['minimum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be greater than %2$d (inclusive)', 'woocommerce' ), $param, $args['minimum'] ) );
				}
			} elseif ( isset( $args['maximum'] ) && ! isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMaximum'] ) && $value >= $args['maximum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be less than %2$d (exclusive)', 'woocommerce' ), $param, $args['maximum'] ) );
				} elseif ( empty( $args['exclusiveMaximum'] ) && $value > $args['maximum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be less than %2$d (inclusive)', 'woocommerce' ), $param, $args['maximum'] ) );
				}
			} elseif ( isset( $args['maximum'] ) && isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value <= $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (exclusive) and %3$d (exclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} elseif ( empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value < $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (inclusive) and %3$d (exclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} elseif ( ! empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value <= $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (exclusive) and %3$d (inclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} elseif ( empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value < $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s must be between %2$d (inclusive) and %3$d (inclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				}
			}
		}

		return true;
	}
}

if ( ! function_exists( 'rest_sanitize_request_arg' ) ) {
	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed            $value
	 * @param  WP_REST_Request  $request
	 * @param  string           $param
	 * @return mixed
	 */
	function rest_sanitize_request_arg( $value, $request, $param ) {

		$attributes = $request->get_attributes();
		if ( ! isset( $attributes['args'][ $param ] ) || ! is_array( $attributes['args'][ $param ] ) ) {
			return $value;
		}
		$args = $attributes['args'][ $param ];

		if ( 'integer' === $args['type'] ) {
			return (int) $value;
		}

		if ( 'boolean' === $args['type'] ) {
			return rest_sanitize_boolean( $value );
		}

		if ( isset( $args['format'] ) ) {
			switch ( $args['format'] ) {
				case 'date-time' :
					return sanitize_text_field( $value );

				case 'email' :
					/*
					 * sanitize_email() validates, which would be unexpected
					 */
					return sanitize_text_field( $value );

				case 'uri' :
					return esc_url_raw( $value );

				case 'ipv4' :
					return sanitize_text_field( $value );
			}
		}

		return $value;
	}
}


if ( ! function_exists( 'rest_parse_request_arg' ) ) {
	/**
	 * Parse a request argument based on details registered to the route.
	 *
	 * Runs a validation check and sanitizes the value, primarily to be used via
	 * the `sanitize_callback` arguments in the endpoint args registration.
	 *
	 * @param  mixed            $value
	 * @param  WP_REST_Request  $request
	 * @param  string           $param
	 * @return mixed
	 */
	function rest_parse_request_arg( $value, $request, $param ) {

		$is_valid = rest_validate_request_arg( $value, $request, $param );

		if ( is_wp_error( $is_valid ) ) {
			return $is_valid;
		}

		$value = rest_sanitize_request_arg( $value, $request, $param );

		return $value;
	}
}

if ( ! function_exists( 'rest_is_ip_address' ) ) {
	/**
	 * Determines if a IPv4 address is valid.
	 *
	 * Does not handle IPv6 addresses.
	 *
	 * @param  string $ipv4 IP 32-bit address.
	 * @return string|false The valid IPv4 address, otherwise false.
	 */
	function rest_is_ip_address( $ipv4 ) {
		$pattern = '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/';

		if ( ! preg_match( $pattern, $ipv4 ) ) {
			return false;
		}

		return $ipv4;
	}
}

/**
 * Changes a boolean-like value into the proper boolean value.
 *
 * @param bool|string|int $value The value being evaluated.
 * @return boolean Returns the proper associated boolean value.
 */
if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
	function rest_sanitize_boolean( $value ) {
		// String values are translated to `true`; make sure 'false' is false.
		if ( is_string( $value )  ) {
			$value = strtolower( $value );
			if ( in_array( $value, array( 'false', '0' ), true ) ) {
				$value = false;
			}
		}

		// Everything else will map nicely to boolean.
		return (boolean) $value;
	}
}

/**
 * Determines if a given value is boolean-like.
 *
 * @param bool|string $maybe_bool The value being evaluated.
 * @return boolean True if a boolean, otherwise false.
 */
if ( ! function_exists( 'rest_is_boolean' ) ) {
	function rest_is_boolean( $maybe_bool ) {
		if ( is_bool( $maybe_bool ) ) {
			return true;
		}

		if ( is_string( $maybe_bool ) ) {
			$maybe_bool = strtolower( $maybe_bool );

			$valid_boolean_values = array(
				'false',
				'true',
				'0',
				'1',
			);

			return in_array( $maybe_bool, $valid_boolean_values, true );
		}

		if ( is_int( $maybe_bool ) ) {
			return in_array( $maybe_bool, array( 0, 1 ), true );
		}

		return false;
	}
}
