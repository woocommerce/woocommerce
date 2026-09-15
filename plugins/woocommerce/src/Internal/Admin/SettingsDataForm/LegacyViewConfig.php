<?php
/**
 * REST compatibility for classic settings definitions.
 *
 * @package WooCommerce
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\SettingsDataForm;

use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;
use WC_Admin_Settings;
use WC_Settings_Page;
use WP_Error;
use WP_HTML_Processor;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Exposes option-backed classic settings as a DataForm view configuration.
 *
 * @internal
 */
final class LegacyViewConfig {
	/**
	 * Supported classic option controls.
	 */
	private const SUPPORTED_TYPES = array( 'text', 'textarea', 'number', 'checkbox', 'select', 'multiselect', 'radio' );

	/**
	 * Handle only the explicitly requested legacy variant of a settings page.
	 *
	 * @since 11.2.0
	 * @param mixed           $result Previous pre-dispatch result.
	 * @param WP_REST_Server  $server REST server.
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return mixed
	 */
	public function handle_rest_pre_dispatch( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( null !== $result || '1' !== ( $request->get_query_params()['legacy-view-config'] ?? null ) ) {
			return $result;
		}

		$method = $request->get_method();
		if ( ! in_array( $method, array( 'GET', 'POST' ), true ) || 1 !== preg_match( '#^/wc/v4/settings/([^/]+)$#', $request->get_route(), $matches ) ) {
			return $result;
		}

		$capability = 'GET' === $method ? 'read' : 'edit';
		if ( ! wc_rest_check_manager_permissions( 'settings', $capability ) ) {
			return new WP_Error( 'rest_forbidden', __( 'Sorry, you are not allowed to access settings.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		try {
			$page = $this->get_page( rawurldecode( $matches[1] ) );
			if ( null === $page ) {
				return new WP_Error( 'rest_not_found', __( 'Settings page not found.', 'woocommerce' ), array( 'status' => 404 ) );
			}
			$definitions = $this->get_definitions( $page );
			if ( 'POST' === $method ) {
				$error = $this->save_values( $definitions, $request );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
				$definitions = $this->get_definitions( $page );
			}

			return new WP_REST_Response( $this->get_view_config( $definitions ) );
		} catch ( \Throwable $error ) {
			return new WP_Error( 'woocommerce_legacy_view_config_error', __( 'Unable to load settings.', 'woocommerce' ), array( 'status' => 500 ) );
		}
	}

	/**
	 * Find a registered classic settings page without constructing one from input.
	 *
	 * @param string $page_id Page identifier.
	 * @return WC_Settings_Page|null
	 */
	private function get_page( string $page_id ): ?WC_Settings_Page {
		foreach ( WC_Admin_Settings::get_settings_pages() as $page ) {
			if ( $page instanceof WC_Settings_Page && $page_id === $page->get_id() ) {
				return $page;
			}
		}

		return null;
	}

	/**
	 * Read filtered definitions through the overridable page method.
	 *
	 * @param WC_Settings_Page $page Settings page.
	 * @return array
	 */
	private function get_definitions( WC_Settings_Page $page ): array {
		$definitions = array();
		foreach ( $page->get_sections() as $section_id => $section_label ) {
			$settings = $page->get_settings( (string) $section_id );
			if ( is_array( $settings ) ) {
				$definitions[] = array(
					'id'       => (string) $section_id,
					'label'    => is_scalar( $section_label ) ? (string) $section_label : '',
					'settings' => $settings,
				);
			}
		}

		return $definitions;
	}

	/**
	 * Convert classic sections into one form and a flat values map.
	 *
	 * @param array $definitions Section definitions.
	 * @return array
	 */
	private function get_view_config( array $definitions ): array {
		$this->prime_option_caches( $definitions );
		$form_fields = array();
		$fields      = array();
		$values      = array();
		$unsupported = array();
		$seen_ids    = array();

		foreach ( $definitions as $section ) {
			$section_id = $section['id'];
			$group      = null;
			$group_num  = 0;
			foreach ( $section['settings'] as $setting_index => $setting ) {
				if ( ! is_array( $setting ) ) {
					continue;
				}
				$type = is_string( $setting['type'] ?? null ) ? $setting['type'] : 'text';
				if ( 'title' === $type ) {
					$this->append_group( $form_fields, $group );
					$group = array(
						'id'       => 'legacy-' . sanitize_key( $section_id ) . '-' . sanitize_key( (string) ( $setting['id'] ?? $group_num ) ) . '-' . $group_num,
						'label'    => $this->plain_text( (string) ( $setting['title'] ?? $section['label'] ) ),
						'layout'   => array(
							'type'          => 'card',
							'isCollapsible' => false,
						),
						'children' => array(),
					);
					++$group_num;
					continue;
				}
				if ( 'sectionend' === $type ) {
					$this->append_group( $form_fields, $group );
					$group = null;
					continue;
				}

				$id = is_string( $setting['id'] ?? null ) ? $setting['id'] : '';
				if ( '' === $id && ! is_scalar( $setting['title'] ?? null ) && ! is_scalar( $setting['desc'] ?? null ) ) {
					continue;
				}
				if ( '' === $id || isset( $seen_ids[ $id ] ) ) {
					$unsupported[] = array(
						'id'      => '' !== $id ? $id : 'legacy-control:' . $section_id . ':' . (string) $setting_index,
						'label'   => $this->get_setting_label( $setting, '' !== $id ? $id : $type ),
						'type'    => $type,
						'section' => $section_id,
					);
					continue;
				}
				$seen_ids[ $id ] = true;
				if ( ! $this->is_supported( $setting, $type ) ) {
					$unsupported[] = array(
						'id'      => $id,
						'label'   => $this->get_setting_label( $setting, $id ),
						'type'    => $type,
						'section' => $section_id,
					);
					continue;
				}
				if ( null === $group ) {
					$group = array(
						'id'       => 'legacy-' . sanitize_key( $section_id ) . '-default',
						'label'    => $this->plain_text( $section['label'] ),
						'layout'   => array(
							'type'          => 'card',
							'isCollapsible' => false,
						),
						'children' => array(),
					);
				}
				$field               = $this->get_field( $setting, $type, $id );
				$fields[]            = $field;
				$values[ $id ]       = $this->get_value( $setting, $type );
				$group['children'][] = $id;
			}
			$this->append_group( $form_fields, $group );
		}
		return array(
			'form'        => array(
				'layout' => array(
					'type'          => 'regular',
					'labelPosition' => 'top',
				),
				'fields' => $form_fields,
			),
			'fields'      => $fields,
			'values'      => $values,
			'unsupported' => $unsupported,
		);
	}

	/**
	 * Prime option keys before reading the form's values.
	 *
	 * @param array $definitions Section definitions.
	 * @return void
	 */
	private function prime_option_caches( array $definitions ): void {
		$keys = array();
		foreach ( $definitions as $section ) {
			foreach ( $section['settings'] as $setting ) {
				if ( ! is_array( $setting ) ) {
					continue;
				}
				$type = is_string( $setting['type'] ?? null ) ? $setting['type'] : 'text';
				if ( ! $this->is_supported( $setting, $type ) ) {
					continue;
				}

				$name         = $setting['field_name'] ?? $setting['id'];
				$key          = explode( '[', $name, 2 )[0];
				$keys[ $key ] = true;
			}
		}
		if ( ! empty( $keys ) ) {
			// Prime caches to reduce option queries while building the form.
			wp_prime_option_caches( array_keys( $keys ) );
		}
	}

	/**
	 * Append a non-empty classic group.
	 *
	 * @param array      $form_fields Form entries.
	 * @param array|null $group Current group.
	 * @return void
	 */
	private function append_group( array &$form_fields, ?array $group ): void {
		if ( null !== $group && ! empty( $group['children'] ) ) {
			$form_fields[] = $group;
		}
	}

	/**
	 * Accept only ordinary option-backed controls with safe field names.
	 *
	 * @param array  $setting Classic definition.
	 * @param string $type Classic type.
	 * @return bool
	 */
	private function is_supported( array $setting, string $type ): bool {
		if ( ! is_string( $setting['type'] ?? null ) || ! in_array( $type, self::SUPPORTED_TYPES, true ) || false === ( $setting['is_option'] ?? true ) || isset( $setting['save'] ) ) {
			return false;
		}
		if ( has_action( 'woocommerce_update_option_' . sanitize_title( $type ) ) ) {
			return false;
		}
		if ( in_array( $type, array( 'select', 'multiselect', 'radio' ), true ) && ! is_array( $setting['options'] ?? null ) ) {
			return false;
		}
		$name = $setting['field_name'] ?? $setting['id'] ?? null;
		return is_string( $name ) && 1 === preg_match( '/^[^\[\]]+(?:\[[^\[\]]+\])?$/', $name );
	}

	/**
	 * Get field labels and options without emitting HTML.
	 *
	 * @param array  $setting Classic definition.
	 * @param string $type Classic type.
	 * @param string $id Field id.
	 * @return array
	 */
	private function get_field( array $setting, string $type, string $id ): array {
		$checkbox_label = 'checkbox' === $type && is_string( $setting['desc'] ?? null ) && '' !== $setting['desc'];
		$label          = $checkbox_label ? $setting['desc'] : ( $setting['title'] ?? $id );
		$description    = $checkbox_label ? ( $setting['desc_tip'] ?? '' ) : ( $setting['desc'] ?? '' );
		if ( is_string( $setting['desc_tip'] ?? null ) && ! $checkbox_label ) {
			$description .= ' ' . $setting['desc_tip'];
		}
		$field = array(
			'id'          => $id,
			'label'       => $this->plain_text( is_scalar( $label ) ? (string) $label : $id ),
			'type'        => $this->get_dataviews_type( $setting, $type ),
			'description' => $this->plain_text( is_scalar( $description ) ? (string) $description : '' ),
		);
		if ( 'textarea' === $type ) {
			$field['multiline'] = true;
		}
		if ( is_string( $setting['placeholder'] ?? null ) ) {
			$field['placeholder'] = $this->plain_text( $setting['placeholder'] );
		}
		if ( in_array( $type, array( 'select', 'multiselect', 'radio' ), true ) && is_array( $setting['options'] ?? null ) ) {
			$field['options'] = array();
			foreach ( $setting['options'] as $value => $option_label ) {
				if ( is_scalar( $option_label ) ) {
					$field['options'][] = array(
						'value' => (string) $value,
						'label' => $this->plain_text( (string) $option_label ),
					);
				}
			}
		}
		if ( 'number' === $type && is_array( $setting['custom_attributes'] ?? null ) ) {
			foreach ( array( 'min', 'max', 'step' ) as $attribute ) {
				if ( isset( $setting['custom_attributes'][ $attribute ] ) && is_numeric( $setting['custom_attributes'][ $attribute ] ) ) {
					$field[ $attribute ] = (float) $setting['custom_attributes'][ $attribute ];
				}
			}
		}
		return $field;
	}

	/**
	 * Name a skipped control using the same plain-text conversion as supported fields.
	 *
	 * @param array  $setting Classic definition.
	 * @param string $fallback Name when the definition has no label.
	 * @return string
	 */
	private function get_setting_label( array $setting, string $fallback ): string {
		$label = $setting['title'] ?? $setting['desc'] ?? $fallback;
		$text  = $this->plain_text( is_scalar( $label ) ? (string) $label : $fallback );
		return '' !== $text ? $text : $fallback;
	}

	/**
	 * Map classic controls to DataViews field types.
	 *
	 * @param array  $setting Classic definition.
	 * @param string $type Classic type.
	 * @return string
	 */
	private function get_dataviews_type( array $setting, string $type ): string {
		if ( 'checkbox' === $type ) {
			return 'boolean';
		}
		if ( 'multiselect' === $type ) {
			return 'array';
		}
		if ( 'number' === $type ) {
			return 1.0 === (float) ( $setting['custom_attributes']['step'] ?? 0 ) ? 'integer' : 'number';
		}
		return 'text';
	}

	/**
	 * Read the current value using WooCommerce's option-name handling.
	 *
	 * @param array  $setting Classic definition.
	 * @param string $type Classic type.
	 * @return mixed
	 */
	private function get_value( array $setting, string $type ) {
		$name  = $setting['field_name'] ?? $setting['id'];
		$value = WC_Admin_Settings::get_option( $name, $setting['default'] ?? '' );
		if ( 'checkbox' === $type ) {
			return wc_string_to_bool( $value );
		}
		if ( 'number' === $type ) {
			return is_numeric( $value ) ? (float) $value : 0;
		}
		if ( 'multiselect' === $type ) {
			if ( ! is_array( $value ) ) {
				return array();
			}
			$selection = array();
			foreach ( $value as $item ) {
				if ( is_scalar( $item ) ) {
					$selection[] = (string) $item;
				}
			}
			return $selection;
		}
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Extract decoded text nodes from a sanitized HTML fragment.
	 *
	 * @param string $html HTML or plain text.
	 * @return string
	 */
	private function plain_text( string $html ): string {
		$processor = WP_HTML_Processor::create_fragment( wp_kses_post( $html ) );
		if ( null === $processor ) {
			return '';
		}
		$text = '';
		while ( $processor->next_token() ) {
			if ( '#text' === $processor->get_token_type() ) {
				$text .= ' ' . $processor->get_modifiable_text();
			}
		}
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Validate a partial payload, then delegate its option writes to WooCommerce.
	 *
	 * @param array           $definitions Section definitions.
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return true|WP_Error
	 */
	private function save_values( array $definitions, WP_REST_Request $request ) {
		$body   = $request->get_json_params();
		$values = is_array( $body ) ? ( $body['values'] ?? null ) : null;
		if ( ! is_array( $values ) || empty( $values ) ) {
			return $this->invalid_values();
		}

		$allowed  = array();
		$seen_ids = array();
		foreach ( $definitions as $section ) {
			foreach ( $section['settings'] as $setting ) {
				if ( ! is_array( $setting ) || ! is_string( $setting['id'] ?? null ) ) {
					continue;
				}
				$id = $setting['id'];
				if ( '' === $id || isset( $seen_ids[ $id ] ) ) {
					continue;
				}
				$seen_ids[ $id ] = true;
				$type            = is_string( $setting['type'] ?? null ) ? $setting['type'] : 'text';
				if ( $this->is_supported( $setting, $type ) ) {
					$allowed[ $id ] = $setting;
				}
			}
		}

		$changed = array();
		$data    = array();
		foreach ( $values as $id => $value ) {
			if ( ! is_string( $id ) || ! isset( $allowed[ $id ] ) || ! $this->is_valid_value( $allowed[ $id ], $value ) ) {
				return $this->invalid_values();
			}
			$setting = $allowed[ $id ];
			$name    = $setting['field_name'] ?? $id;
			$posted  = 'checkbox' === $setting['type'] ? ( $value ? 'yes' : 'no' ) : $value;
			if ( 'number' === $setting['type'] ) {
				$posted = (string) $posted;
			}
			if ( 1 === preg_match( '/^([^\[\]]+)\[([^\[\]]+)\]$/', $name, $name_parts ) ) {
				if ( ! isset( $data[ $name_parts[1] ] ) || ! is_array( $data[ $name_parts[1] ] ) ) {
					$data[ $name_parts[1] ] = array();
				}
				$data[ $name_parts[1] ][ $name_parts[2] ] = wp_slash( $posted );
			} else {
				$data[ $name ] = wp_slash( $posted );
			}
			$changed[] = $setting;
		}

		WC_Admin_Settings::save_fields( $changed, $data );
		return true;
	}

	/**
	 * Validate values against supported controls and declared bounds/options.
	 *
	 * @param array $setting Classic definition.
	 * @param mixed $value Candidate value.
	 * @return bool
	 */
	private function is_valid_value( array $setting, $value ): bool {
		$type = $setting['type'];
		if ( 'checkbox' === $type ) {
			return is_bool( $value );
		}
		if ( 'number' === $type ) {
			if ( ( ! is_int( $value ) && ! is_float( $value ) ) || ! is_finite( (float) $value ) ) {
				return false;
			}
			$attributes = is_array( $setting['custom_attributes'] ?? null ) ? $setting['custom_attributes'] : array();
			return ( ! isset( $attributes['min'] ) || (float) $value >= (float) $attributes['min'] )
				&& ( ! isset( $attributes['max'] ) || (float) $value <= (float) $attributes['max'] );
		}
		if ( 'multiselect' === $type ) {
			return is_array( $value ) && ArrayUtil::array_is_list( $value ) && count( $value ) === count( array_filter( $value, 'is_string' ) ) && $this->valid_options( $setting, $value );
		}
		if ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			return is_string( $value ) && $this->valid_options( $setting, array( $value ) );
		}
		return is_string( $value );
	}

	/**
	 * Check a selection against the page's current option keys.
	 *
	 * @param array $setting Classic definition.
	 * @param array $values Selected values.
	 * @return bool
	 */
	private function valid_options( array $setting, array $values ): bool {
		if ( ! is_array( $setting['options'] ?? null ) ) {
			return false;
		}
		$keys = array_map( 'strval', array_keys( $setting['options'] ) );
		return empty( array_diff( $values, $keys ) );
	}

	/**
	 * Reject an invalid payload without exposing filtered settings internals.
	 *
	 * @return WP_Error
	 */
	private function invalid_values(): WP_Error {
		return new WP_Error( 'rest_invalid_param', __( 'Invalid settings values.', 'woocommerce' ), array( 'status' => 400 ) );
	}
}
