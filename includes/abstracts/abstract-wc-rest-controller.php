<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Rest Controller Class
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/Abstracts
 * @extends  WP_REST_Controller
 * @version  2.6.0
 */
abstract class WC_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Add the schema from additional fields to an schema array.
	 *
	 * The type of object is inferred from the passed schema.
	 *
	 * @param array $schema Schema array.
	 *
	 * @return array
	 */
	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		/**
		 * Can't use $this->get_object_type otherwise we cause an inf loop.
		 */
		$object_type = $schema['title'];

		$additional_fields = $this->get_additional_fields( $object_type );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['schema'] ) {
				continue;
			}

			$schema['properties'][ $field_name ] = $field_options['schema'];
		}

		$schema['properties'] = apply_filters( 'woocommerce_rest_' . $object_type . '_schema', $schema['properties'] );

		return $schema;
	}

	/**
	 * Get normalized rest base.
	 *
	 * @return string
	 */
	protected function get_normalized_rest_base() {
		return preg_replace( '/\(.*\)\//i', '', $this->rest_base );
	}

	/**
	 * Check batch limit.
	 *
	 * @param array $items Request items.
	 * @return bool|WP_Error
	 */
	protected function check_batch_limit( $items ) {
		$limit = apply_filters( 'woocommerce_rest_batch_items_limit', 100, $this->get_normalized_rest_base() );
		$total = 0;

		if ( ! empty( $items['create'] ) ) {
			$total += count( $items['create'] );
		}

		if ( ! empty( $items['update'] ) ) {
			$total += count( $items['update'] );
		}

		if ( ! empty( $items['delete'] ) ) {
			$total += count( $items['delete'] );
		}

		if ( $total > $limit ) {
			/* translators: %s: items limit */
			return new WP_Error( 'woocommerce_rest_request_entity_too_large', sprintf( __( 'Unable to accept more than %s items for this request.', 'woocommerce' ), $limit ), array( 'status' => 413 ) );
		}

		return true;
	}

	/**
	 * Bulk create, update and delete items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Of WP_Error or WP_REST_Response.
	 */
	public function batch_items( $request ) {
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;

		// Get the request params.
		$items    = array_filter( $request->get_params() );
		$response = array();

		// Check batch limit.
		$limit = $this->check_batch_limit( $items );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}

		if ( ! empty( $items['create'] ) ) {
			foreach ( $items['create'] as $item ) {
				$_item = new WP_REST_Request( 'POST' );

				// Default parameters.
				$defaults = array();
				$schema   = $this->get_public_item_schema();
				foreach ( $schema['properties'] as $arg => $options ) {
					if ( isset( $options['default'] ) ) {
						$defaults[ $arg ] = $options['default'];
					}
				}
				$_item->set_default_params( $defaults );

				// Set request parameters.
				$_item->set_body_params( $item );
				$_response = $this->create_item( $_item );

				if ( is_wp_error( $_response ) ) {
					$response['create'][] = array(
						'id'    => 0,
						'error' => array( 'code' => $_response->get_error_code(), 'message' => $_response->get_error_message(), 'data' => $_response->get_error_data() ),
					);
				} else {
					$response['create'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		if ( ! empty( $items['update'] ) ) {
			foreach ( $items['update'] as $item ) {
				$_item = new WP_REST_Request( 'PUT' );
				$_item->set_body_params( $item );
				$_response = $this->update_item( $_item );

				if ( is_wp_error( $_response ) ) {
					$response['update'][] = array(
						'id'    => $item['id'],
						'error' => array( 'code' => $_response->get_error_code(), 'message' => $_response->get_error_message(), 'data' => $_response->get_error_data() ),
					);
				} else {
					$response['update'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		if ( ! empty( $items['delete'] ) ) {
			foreach ( $items['delete'] as $id ) {
				$id = (int) $id;

				if ( 0 === $id ) {
					continue;
				}

				$_item = new WP_REST_Request( 'DELETE' );
				$_item->set_query_params( array( 'id' => $id, 'force' => true ) );
				$_response = $this->delete_item( $_item );

				if ( is_wp_error( $_response ) ) {
					$response['delete'][] = array(
						'id'    => $id,
						'error' => array( 'code' => $_response->get_error_code(), 'message' => $_response->get_error_message(), 'data' => $_response->get_error_data() ),
					);
				} else {
					$response['delete'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		return $response;
	}

	/**
	 * Validate a text value for a text based setting.
	 *
	 * @since 3.0.0
	 * @param string $value
	 * @param array  $setting
	 * @return string
	 */
	public function validate_setting_text_field( $value, $setting ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses_post( trim( stripslashes( $value ) ) );
	}

	/**
	 * Validate select based settings.
	 *
	 * @since 3.0.0
	 * @param string $value
	 * @param array  $setting
	 * @return string|WP_Error
	 */
	public function validate_setting_select_field( $value, $setting ) {
		if ( array_key_exists( $value, $setting['options'] ) ) {
			return $value;
		} else {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Validate multiselect based settings.
	 *
	 * @since 3.0.0
	 * @param array $values
	 * @param array  $setting
	 * @return array|WP_Error
	 */
	public function validate_setting_multiselect_field( $values, $setting ) {
		if ( empty( $values ) ) {
			return array();
		}

		if ( ! is_array( $values ) ) {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$final_values = array();
		foreach ( $values as $value ) {
			if ( array_key_exists( $value, $setting['options'] ) ) {
				$final_values[] = $value;
			}
		}

		return $final_values;
	}

	/**
	 * Validate image_width based settings.
	 *
	 * @since 3.0.0
	 * @param array $values
	 * @param array $setting
	 * @return string|WP_Error
	 */
	public function validate_setting_image_width_field( $values, $setting ) {
		if ( ! is_array( $values ) ) {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$current = $setting['value'];
		if ( isset( $values['width'] ) ) {
			$current['width'] = intval( $values['width'] );
		}
		if ( isset( $values['height'] ) ) {
			$current['height'] = intval( $values['height'] );
		}
		if ( isset( $values['crop'] ) ) {
			$current['crop'] = (bool) $values['crop'];
		}
		return $current;
	}

	/**
	 * Validate radio based settings.
	 *
	 * @since 3.0.0
	 * @param string $value
	 * @param array  $setting
	 * @return string|WP_Error
	 */
	public function validate_setting_radio_field( $value, $setting ) {
		return $this->validate_setting_select_field( $value, $setting );
	}

	/**
	 * Validate checkbox based settings.
	 *
	 * @since 3.0.0
	 * @param string $value
	 * @param array  $setting
	 * @return string|WP_Error
	 */
	public function validate_setting_checkbox_field( $value, $setting ) {
		if ( in_array( $value, array( 'yes', 'no' ) ) ) {
			return $value;
		} elseif ( empty( $value ) ) {
			$value = isset( $setting['default'] ) ? $setting['default'] : 'no';
			return $value;
		} else {
			return new WP_Error( 'rest_setting_value_invalid', __( 'An invalid setting value was passed.', 'woocommerce' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Validate textarea based settings.
	 *
	 * @since 3.0.0
	 * @param string $value
	 * @param array  $setting
	 * @return string
	 */
	public function validate_setting_textarea_field( $value, $setting ) {
		$value = is_null( $value ) ? '' : $value;
		return wp_kses( trim( stripslashes( $value ) ),
			array_merge(
				array(
					'iframe' => array( 'src' => true, 'style' => true, 'id' => true, 'class' => true ),
				),
				wp_kses_allowed_html( 'post' )
			)
		);
	}

	/**
	 * Add meta query.
	 *
	 * @since 3.0.0
	 * @param array $args       Query args.
	 * @param array $meta_query Meta query.
	 * @return array
	 */
	protected function add_meta_query( $args, $meta_query ) {
		if ( ! empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		$args['meta_query'][] = $meta_query;

		return $args['meta_query'];
	}

	/**
	 * Get the batch schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_public_batch_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'batch',
			'type'       => 'object',
			'properties' => array(
				'create' => array(
					'description' => __( 'List of created resources.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
					),
				),
				'update' => array(
					'description' => __( 'List of updated resources.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'object',
					),
				),
				'delete' => array(
					'description' => __( 'List of delete resources.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'    => 'integer',
					),
				),
			),
		);

		return $schema;
	}
}
