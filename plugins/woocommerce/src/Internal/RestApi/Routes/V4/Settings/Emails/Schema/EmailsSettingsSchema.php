<?php
/**
 * EmailsSettingsSchema class.
 *
 * @package WooCommerce\RestApi
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\RestApi\Routes\V4\Settings\Emails\Schema;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\AbstractSchema;
use Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails\WCTransactionalEmailPostsManager;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use WC_Email;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * EmailsSettingsSchema class.
 *
 * Schema for individual email template settings in the REST API.
 */
class EmailsSettingsSchema extends AbstractSchema {
	/**
	 * The schema item identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'emails_settings';

	/**
	 * This fields support personalization tags and need to be unwrapped before returning to the client.
	 *
	 * @var array
	 */
	const FIELDS_SUPPORTING_PERSONALIZATION_TAGS = array( 'subject', 'preheader' );

	/**
	 * Personalization tags registry.
	 *
	 * @var Personalization_Tags_Registry|null
	 */
	private $personalization_tags_registry;

	/**
	 * Cached array of personalization tag prefixes.
	 *
	 * @var array|null
	 */
	private $cached_prefixes = null;

	/**
	 * Initialize the schema with dependencies.
	 *
	 * @internal This method is not intended to be used externally.
	 */
	final public function init() {
		$this->personalization_tags_registry = Email_Editor_Container::container()->get( Personalization_Tags_Registry::class );
	}

	/**
	 * Return all properties for the item schema.
	 *
	 * @return array
	 */
	public function get_item_schema_properties(): array {
		return array(
			'id'                => array(
				'description' => __( 'Email template ID.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'title'             => array(
				'description' => __( 'Email title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'description'       => array(
				'description' => __( 'Email description.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'post_id'           => array(
				'description' => __( 'Template post ID.', 'woocommerce' ),
				'type'        => array( 'integer', 'null' ),
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'link'              => array(
				'description' => __( 'Link to template editor.', 'woocommerce' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'email_group'       => array(
				'description' => __( 'Email group identifier.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'email_group_title' => array(
				'description' => __( 'Email group title.', 'woocommerce' ),
				'type'        => 'string',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'is_customer_email' => array(
				'description' => __( 'Whether this is a customer email.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'is_manual'         => array(
				'description' => __( 'Whether this is sent only manually.', 'woocommerce' ),
				'type'        => 'boolean',
				'context'     => self::VIEW_EDIT_CONTEXT,
				'readonly'    => true,
			),
			'values'            => array(
				'description'          => __( 'Flat key-value mapping of all setting field values.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'description' => __( 'Setting field value.', 'woocommerce' ),
					'type'        => array( 'string', 'number', 'array', 'boolean' ),
				),
			),
			'groups'            => array(
				'description'          => __( 'Collection of setting groups.', 'woocommerce' ),
				'type'                 => 'object',
				'context'              => self::VIEW_EDIT_CONTEXT,
				'additionalProperties' => array(
					'type'        => 'object',
					'description' => __( 'Settings group.', 'woocommerce' ),
					'properties'  => array(
						'title'       => array(
							'description' => __( 'Group title.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'description' => array(
							'description' => __( 'Group description.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => self::VIEW_EDIT_CONTEXT,
						),
						'order'       => array(
							'description' => __( 'Display order for the group.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => self::VIEW_EDIT_CONTEXT,
							'readonly'    => true,
						),
						'fields'      => array(
							'description' => __( 'Settings fields.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => self::VIEW_EDIT_CONTEXT,
							'items'       => $this->get_field_schema(),
						),
					),
				),
			),
		);
	}

	/**
	 * Get the schema for individual setting fields.
	 *
	 * @return array
	 */
	private function get_field_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'id'      => array(
					'description' => __( 'Setting field ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'label'   => array(
					'description' => __( 'Setting field label.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'type'    => array(
					'description' => __( 'Setting field type.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'text', 'email', 'number', 'select', 'multiselect', 'checkbox', 'textarea', 'color', 'password' ),
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'desc'    => array(
					'description' => __( 'Description for the setting field.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
				'options' => array(
					'description' => __( 'Available options for select/multiselect fields.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => self::VIEW_EDIT_CONTEXT,
				),
			),
		);
	}

	/**
	 * Get the item response for a single email.
	 *
	 * @param WC_Email        $email   Email instance.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $include_fields Fields to include in the response.
	 * @return array The item response.
	 */
	public function get_item_response( $email, WP_REST_Request $request, array $include_fields = array() ): array {
		// Get template post ID.
		$email_post_manager = WCTransactionalEmailPostsManager::get_instance();
		$post_id            = $email_post_manager->get_email_template_post_id( $email->id ?? '' );
		// Convert false to null, ensure int otherwise.
		$post_id = $post_id ? (int) $post_id : null;

		$link = '';
		if ( $post_id ) {
			$permalink = get_permalink( $post_id );
			$link      = is_string( $permalink ) ? $permalink : '';
		}

		$email->init_form_fields();
		$response = array(
			'id'                => $email->id ?? '',
			'title'             => $email->title ?? '',
			'description'       => $email->description ?? '',
			'post_id'           => $post_id,
			'link'              => $link,
			'email_group'       => $email->email_group ?? '',
			'email_group_title' => method_exists( $email, 'get_email_group_title' ) ? $email->get_email_group_title() : '',
			'is_customer_email' => method_exists( $email, 'is_customer_email' ) ? $email->is_customer_email() : false,
			'is_manual'         => method_exists( $email, 'is_manual' ) ? $email->is_manual() : false,
			'values'            => $this->get_values( $email ),
			'groups'            => $this->get_groups( $email ),
		);

		if ( ! empty( $include_fields ) ) {
			$response = array_intersect_key( $response, array_flip( $include_fields ) );
		}

		return $response;
	}

	/**
	 * Get flat key-value mapping of all setting values.
	 *
	 * @param WC_Email $email Email instance.
	 * @return array
	 */
	private function get_values( WC_Email $email ): array {
		$values      = array();
		$form_fields = $email->get_form_fields();

		if ( ! is_array( $form_fields ) ) {
			return $values;
		}

		// Create a dummy order object, as some of the getter methods require one.
		$email->object = new \WC_Order();

		foreach ( $form_fields as $id => $field ) {
			$field_type = $field['type'] ?? 'text';

			// Skip non-data fields.
			if ( in_array( $field_type, array( 'title', 'sectionend' ), true ) ) {
				continue;
			}

			// Get saved value an fallback to default.
			$default = $this->get_field_default_value( $email, $id, $field );
			$value   = $email->get_option( $id, $default );

			// Unwrap personalization tags if the field supports them.
			if ( in_array( $id, self::FIELDS_SUPPORTING_PERSONALIZATION_TAGS, true ) ) {
				$value = $this->unwrap_woocommerce_tags( $value );
			}

			// Convert checkbox to boolean for API.
			if ( 'checkbox' === $field_type ) {
				$value = ( 'yes' === $value );
			}

			$values[ $id ] = $value;
		}

		return $values;
	}

	/**
	 * Prepare the default value for a field.
	 * We use special methods for well known core fields and use fallback to default value if no special method is available.
	 *
	 * @param WC_Email $email  Email instance.
	 * @param string   $id     Field ID.
	 * @param array    $field  Field definition.
	 * @return mixed The default value for the field.
	 */
	private function get_field_default_value( WC_Email $email, string $id, array $field ) {
		switch ( $id ) {
			case 'enabled':
				return method_exists( $email, 'is_enabled' ) ? $email->is_enabled() : false;
			case 'recipient':
				return method_exists( $email, 'get_recipient' ) ? $email->get_recipient() : '';
			case 'subject':
				return method_exists( $email, 'get_subject' ) ? $email->get_subject() : '';
			case 'heading':
				return method_exists( $email, 'get_heading' ) ? $email->get_heading() : '';
			case 'preheader':
				return method_exists( $email, 'get_preheader' ) ? $email->get_preheader() : '';
			case 'additional_content':
				return method_exists( $email, 'get_additional_content' ) ? $email->get_additional_content() : '';
			case 'cc':
				return $email->cc ?? '';
			case 'bcc':
				return $email->bcc ?? '';
			case 'email_type':
				return $email->email_type ?? '';
			default:
				return $field['default'] ?? ( $field['placeholder'] ?? '' );
		}
	}

	/**
	 * Remove HTML comment wrappers from personalization tags.
	 *
	 * Converts tags from <!--[prefix/tag-name]--> back to [prefix/tag-name] for all registered prefixes.
	 * For example: <!--[woocommerce/customer-name]--> becomes [woocommerce/customer-name].
	 *
	 * This is required because the email editor personalization tags are wrapped in HTML comment wrappers.
	 * We need to remove the tags to make editing easier for the end-users and also because the tags are not well formatted in the current DataForm implementation.
	 *
	 * @param string $value The value to unwrap.
	 * @return string The unwrapped value.
	 */
	private function unwrap_woocommerce_tags( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		// Get all registered prefixes dynamically.
		$prefixes = $this->get_personalization_tag_prefixes();

		// If no prefixes, return the value unchanged.
		if ( empty( $prefixes ) ) {
			return $value;
		}

		// Escape prefixes for use in regex and join with |.
		$escaped_prefixes = array_map( 'preg_quote', $prefixes );
		$prefixes_pattern = implode( '|', $escaped_prefixes );

		// Remove HTML comment wrappers from personalization tags.
		$unwrapped_value = preg_replace( '/<!--(\[(?:' . $prefixes_pattern . ')\/[^\]]+\])-->/i', '$1', $value );
		return $unwrapped_value;
	}

	/**
	 * Wrap personalization tags in HTML comments for the email editor.
	 * This is required for the email editor personalization.
	 * Use negative lookbehind and lookahead to avoid double-wrapping already wrapped tags.
	 *
	 * @param mixed $value The value to wrap.
	 * @return mixed The wrapped value.
	 */
	private function wrap_woocommerce_tags( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$prefixes = $this->get_personalization_tag_prefixes();

		if ( empty( $prefixes ) ) {
			return $value;
		}

		// Escape prefixes for use in regex and join with |.
		$escaped_prefixes = array_map( 'preg_quote', $prefixes );
		$prefixes_pattern = implode( '|', $escaped_prefixes );

		// Wrap tags that aren't already wrapped.
		return preg_replace( '/(?<!<!--)(\[(?:' . $prefixes_pattern . ')\/[^\]]+\])(?!-->)/i', '<!--$1-->', $value );
	}

	/**
	 * Get grouped settings structure with field metadata.
	 *
	 * @param WC_Email $email Email instance.
	 * @return array
	 */
	private function get_groups( WC_Email $email ): array {
		$group = array(
			'title'       => __( 'Email Settings', 'woocommerce' ),
			'description' => '',
			'order'       => 1,
			'fields'      => array(),
		);

		$form_fields = $email->get_form_fields();
		foreach ( $form_fields as $id => $field ) {
			$field_type = $field['type'] ?? 'text';

			// Skip non-data fields.
			if ( in_array( $field_type, array( 'title', 'sectionend' ), true ) ) {
				continue;
			}

			$field_schema = array(
				'id'    => $id,
				'label' => $field['title'] ?? $id,
				'type'  => $field_type,
				'desc'  => $field['description'] ?? '',
			);

			// Add options for select/multiselect fields.
			if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
				$field_schema['options'] = $field['options'];
			}

			$group['fields'][] = $field_schema;
		}

		if ( empty( $group['fields'] ) ) {
			return array();
		}

		return array( 'settings' => $group );
	}

	/**
	 * Validate and sanitize email settings.
	 *
	 * @param WC_Email $email  Email instance.
	 * @param array    $values Values to validate and sanitize.
	 * @return array|WP_Error Validated settings or error.
	 */
	public function validate_and_sanitize_settings( WC_Email $email, array $values ) {
		$email->init_form_fields();
		$validated = array();

		foreach ( $values as $field_id => $value ) {
			// Only allow valid form fields.
			if ( ! isset( $email->form_fields[ $field_id ] ) ) {
				continue;
			}

			$field      = $email->form_fields[ $field_id ];
			$field_type = $field['type'] ?? 'text';

			// Unwrap personalization tags if the field supports them to make sure we don't strip them in the sanitization process.
			if ( in_array( $field_id, self::FIELDS_SUPPORTING_PERSONALIZATION_TAGS, true ) ) {
				$value = $this->unwrap_woocommerce_tags( $value );
			}

			// Sanitize by type.
			$sanitized = $this->sanitize_field_value( $field_type, $value );

			// Sanitize Personalization tags. Wrap them in HTML comments for the email editor.
			if ( in_array( $field_id, self::FIELDS_SUPPORTING_PERSONALIZATION_TAGS, true ) ) {
				$sanitized = $this->wrap_woocommerce_tags( $sanitized );
			}

			// Validate.
			$validation = $this->validate_field_value( $field_id, $sanitized, $field );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			$validated[ $field_id ] = $sanitized;
		}

		return $validated;
	}

	/**
	 * Sanitize field value based on type.
	 *
	 * @param string $type  Field type.
	 * @param mixed  $value Field value.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_field_value( string $type, $value ) {
		switch ( $type ) {
			case 'checkbox':
				// Ensure we have a scalar value for checkbox settings.
				if ( is_array( $value ) ) {
					$value = ! empty( $value ); // Convert array to boolean based on emptiness.
				}
				return wc_bool_to_string( $value );

			case 'email':
				return sanitize_email( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'number':
				if ( ! is_numeric( $value ) ) {
					return 0;
				}
				$int_value = filter_var( $value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE );
				return null !== $int_value ? $int_value : floatval( $value );

			case 'multiselect':
				if ( is_array( $value ) ) {
					return array_map( 'sanitize_text_field', $value );
				}
				return is_string( $value ) ? array( sanitize_text_field( $value ) ) : array();

			case 'color':
			case 'password':
			case 'text':
			case 'select':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Validate field value.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Sanitized value.
	 * @param array  $field Field definition.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_field_value( string $key, $value, array $field ) {
		$field_type = $field['type'] ?? 'text';

		// Validate email format.
		if ( 'email' === $field_type && ! empty( $value ) && ! is_email( $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				sprintf(
					/* translators: %s: field key */
					__( 'Invalid email format for %s.', 'woocommerce' ),
					$key
				),
				array( 'status' => 400 )
			);
		}

		// Validate select options.
		if ( 'select' === $field_type && ! empty( $field['options'] ) ) {
			if ( ! array_key_exists( $value, $field['options'] ) && '' !== $value ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
						/* translators: 1: field key, 2: valid options */
						__( 'Invalid value for %1$s. Valid options: %2$s', 'woocommerce' ),
						$key,
						implode( ', ', array_keys( $field['options'] ) )
					),
					array( 'status' => 400 )
				);
			}
		}

		// Validate multiselect options.
		if ( 'multiselect' === $field_type && ! empty( $field['options'] ) ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					if ( ! array_key_exists( $v, $field['options'] ) ) {
						return new WP_Error(
							'rest_invalid_param',
							sprintf(
								/* translators: 1: field key, 2: invalid value */
								__( 'Invalid option "%2$s" for %1$s.', 'woocommerce' ),
								$key,
								$v
							),
							array( 'status' => 400 )
						);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Get all unique prefixes from registered personalization tags.
	 *
	 * Extracts the prefix part (before the /) from all registered personalization tags.
	 * For example, from [woocommerce/customer-name] it extracts 'woocommerce'.
	 * Results are cached to avoid repeated processing.
	 *
	 * @return array Array of unique prefixes, escaped for use in regex patterns.
	 */
	private function get_personalization_tag_prefixes(): array {
		if ( null === $this->personalization_tags_registry ) {
			return array();
		}

		// Return cached prefixes if available.
		if ( null !== $this->cached_prefixes ) {
			return $this->cached_prefixes;
		}

		$prefixes = array();
		$tags     = $this->personalization_tags_registry->get_all();

		foreach ( $tags as $tag ) {
			$token = $tag->get_token(); // E.g., [woocommerce/customer-name].

			// Extract the prefix from the token (the part before the /).
			// Remove brackets and get the part before /.
			if ( preg_match( '/^\[([^\/\]]+)\//', $token, $matches ) ) {
				$prefixes[ $matches[1] ] = true; // Use array key to ensure uniqueness.
			}
		}

		// Convert to array of values and cache.
		$this->cached_prefixes = array_keys( $prefixes );
		return $this->cached_prefixes;
	}
}
