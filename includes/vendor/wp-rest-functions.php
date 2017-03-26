<?php
/**
 * @version 2.0-beta13.1
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
	 * Backwards compat shim
	 */
	function register_api_field( $object_type, $attributes, $args = array() ) {
		_deprecated_function( 'register_api_field', 'WPAPI-2.0', 'register_rest_field' );
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
				return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not one of %s', 'woocommerce' ), $param, implode( ', ', $args['enum'] ) ) );
			}
		}

		if ( 'integer' === $args['type'] && ! is_numeric( $value ) ) {
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not of type %s', 'woocommerce' ), $param, 'integer' ) );
		}

		if ( 'string' === $args['type'] && ! is_string( $value ) ) {
			return new WP_Error( 'rest_invalid_param', sprintf( __( '%s is not of type %s', 'woocommerce' ), $param, 'string' ) );
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
			}
		}

		if ( in_array( $args['type'], array( 'numeric', 'integer' ) ) && ( isset( $args['minimum'] ) || isset( $args['maximum'] ) ) ) {
			if ( isset( $args['minimum'] ) && ! isset( $args['maximum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && $value <= $args['minimum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be greater than %d (exclusive)', 'woocommerce' ), $param, $args['minimum'] ) );
				} else if ( empty( $args['exclusiveMinimum'] ) && $value < $args['minimum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be greater than %d (inclusive)', 'woocommerce' ), $param, $args['minimum'] ) );
				}
			} else if ( isset( $args['maximum'] ) && ! isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMaximum'] ) && $value >= $args['maximum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be less than %d (exclusive)', 'woocommerce' ), $param, $args['maximum'] ) );
				} else if ( empty( $args['exclusiveMaximum'] ) && $value > $args['maximum'] ) {
					return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be less than %d (inclusive)', 'woocommerce' ), $param, $args['maximum'] ) );
				}
			} else if ( isset( $args['maximum'] ) && isset( $args['minimum'] ) ) {
				if ( ! empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value <= $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (exclusive) and %d (exclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} else if ( empty( $args['exclusiveMinimum'] ) && ! empty( $args['exclusiveMaximum'] ) ) {
					if ( $value >= $args['maximum'] || $value < $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (inclusive) and %d (exclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} else if ( ! empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value <= $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (exclusive) and %d (inclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
					}
				} else if ( empty( $args['exclusiveMinimum'] ) && empty( $args['exclusiveMaximum'] ) ) {
					if ( $value > $args['maximum'] || $value < $args['minimum'] ) {
						return new WP_Error( 'rest_invalid_param', sprintf( __( '%s must be between %d (inclusive) and %d (inclusive)', 'woocommerce' ), $param, $args['minimum'], $args['maximum'] ) );
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
			}
		}

		return $value;
	}

}
