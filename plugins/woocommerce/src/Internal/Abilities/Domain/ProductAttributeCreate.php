<?php
/**
 * Product attribute create ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAttributeAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a global product attribute. Its terms come from the product writes that use it.
 *
 * Not an ActionableAbility: a global attribute is a table row, not a WC_Data object.
 */
class ProductAttributeCreate extends AbstractDomainAbility implements AbilityDefinition {

	use ProductAttributeAbilityTrait;

	/**
	 * Error codes of wc_create_attribute() and the ability codes they map to.
	 */
	private const CREATE_ERRORS = array(
		'invalid_product_attribute_slug_too_long'       => 'woocommerce_ability_product_attribute_slug_too_long',
		'invalid_product_attribute_slug_reserved_name'  => 'woocommerce_ability_product_attribute_slug_reserved',
		'invalid_product_attribute_slug_already_exists' => 'woocommerce_ability_product_attribute_slug_exists',
	);

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-attribute-create';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Create product attribute', 'woocommerce' ),
			'description'         => __( 'Create a global product attribute, such as Color or Size. Product writes then use it by ID or name, with global set to true, and create its terms from the options they send.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'attribute', self::get_product_attribute_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_create_attribute' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => false,
				),
			),
		);
	}

	/**
	 * Check product attribute create access.
	 *
	 * @since 11.4.0
	 * @return bool
	 */
	public static function can_create_attribute(): bool {
		return self::can_manage_product_attributes( 'create' );
	}

	/**
	 * Create a global product attribute.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$name = sanitize_text_field( $input['name'] );
		if ( '' === $name ) {
			return new \WP_Error( 'woocommerce_ability_product_attribute_name_empty', __( 'The attribute name is empty.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$name_slug = wc_attribute_taxonomy_slug( $name );
		// A slug of only punctuation sanitizes to nothing, and wc_create_attribute() then makes one from the name.
		$slug = wc_attribute_taxonomy_slug( $input['slug'] ?? '' );
		$slug = '' === $slug ? $name_slug : $slug;
		// wc_create_attribute() stores an empty slug as a row that no attribute read returns.
		if ( '' === $slug ) {
			return new \WP_Error( 'woocommerce_ability_product_attribute_slug_empty', __( 'The attribute needs a slug with letters or numbers. Send one, or a name that has some.', 'woocommerce' ), array( 'status' => 400 ) );
		}
		$existing_id = wc_attribute_taxonomy_id_by_name( $slug );
		// wc_create_attribute() only checks registered taxonomies, which misses an attribute created earlier in this request.
		if ( $existing_id > 0 ) {
			return new \WP_Error(
				self::CREATE_ERRORS['invalid_product_attribute_slug_already_exists'],
				/* translators: %s: attribute slug. */
				sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), $slug ),
				array(
					'status' => 400,
					'id'     => $existing_id,
				)
			);
		}

		// Product writes resolve a name by its sanitized slug first, then by label ignoring case.
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			if ( 0 === strcasecmp( (string) $attribute->attribute_label, $name ) ) {
				return new \WP_Error(
					'woocommerce_ability_product_attribute_name_exists',
					/* translators: %s: attribute name. */
					sprintf( __( 'A global attribute named "%s" already exists.', 'woocommerce' ), $attribute->attribute_label ),
					array(
						'status' => 400,
						'id'     => (int) $attribute->attribute_id,
					)
				);
			}
			if ( wc_attribute_taxonomy_slug( (string) $attribute->attribute_label ) === $slug || $attribute->attribute_name === $name_slug ) {
				return new \WP_Error(
					'woocommerce_ability_product_attribute_name_ambiguous',
					/* translators: 1: attribute name, 2: existing attribute name. */
					sprintf( __( 'Product writes could not tell "%1$s" apart from the global attribute "%2$s". Choose another name or slug.', 'woocommerce' ), $name, $attribute->attribute_label ),
					array(
						'status' => 400,
						'id'     => (int) $attribute->attribute_id,
					)
				);
			}
		}

		$id = wc_create_attribute(
			array(
				'name'         => $name,
				'slug'         => $slug,
				'type'         => $input['type'] ?? 'select',
				'order_by'     => $input['order_by'] ?? 'menu_order',
				'has_archives' => $input['has_archives'] ?? false,
			)
		);
		if ( is_wp_error( $id ) ) {
			$code = self::CREATE_ERRORS[ $id->get_error_code() ] ?? 'woocommerce_ability_product_attribute_create_failed';
			return new \WP_Error( $code, $id->get_error_message(), $id->get_error_data() );
		}

		$attributes = wc_get_attribute_taxonomies();
		if ( ! isset( $attributes[ 'id:' . $id ] ) ) {
			return new \WP_Error( 'woocommerce_ability_product_attribute_create_failed', __( 'The attribute was created but could not be read back.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		self::register_attribute_taxonomy( $attributes[ 'id:' . $id ] );

		$attribute = self::format_product_attribute( $attributes[ 'id:' . $id ], 0 );
		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		return array( 'attribute' => $attribute );
	}

	/**
	 * Register the attribute taxonomy for the rest of this request.
	 *
	 * WooCommerce registers attribute taxonomies on init only. Without this, a product
	 * write later in the same request, such as the next call of a stdio MCP session,
	 * saves the product without the attribute's terms and reports no error.
	 *
	 * @param \stdClass $attribute Row of wc_get_attribute_taxonomies().
	 * @return void
	 */
	private static function register_attribute_taxonomy( \stdClass $attribute ): void {
		global $wc_product_attributes;

		$taxonomy = wc_attribute_taxonomy_name( (string) $attribute->attribute_name );
		if ( taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$label  = (string) $attribute->attribute_label;
		$public = 1 === (int) $attribute->attribute_public;
		register_taxonomy(
			$taxonomy,
			/**
			 * Filters the object types of a global attribute taxonomy.
			 *
			 * @since 2.3.0
			 * @param array $object_types Object types.
			 */
			apply_filters( "woocommerce_taxonomy_objects_{$taxonomy}", array( 'product' ) ),
			/**
			 * Filters the registration arguments of a global attribute taxonomy.
			 *
			 * @since 2.3.0
			 * @param array $args Taxonomy arguments.
			 */
			apply_filters(
				"woocommerce_taxonomy_args_{$taxonomy}",
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_update_post_term_count',
					'labels'                => array(
						'name'          => $label,
						'singular_name' => $label,
					),
					'show_ui'               => true,
					'show_in_quick_edit'    => false,
					'show_in_menu'          => false,
					'meta_box_cb'           => false,
					'query_var'             => $public,
					'rewrite'               => false,
					'sort'                  => false,
					'public'                => $public,
					'show_in_nav_menus'     => false,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
				)
			)
		);

		$wc_product_attributes[ $taxonomy ] = $attribute;
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'name'         => array(
					'description' => __( 'Label shown to shoppers, such as Color.', 'woocommerce' ),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'slug'         => array(
					'description' => __( 'Slug without the pa_ prefix. Defaults to one made from the name.', 'woocommerce' ),
					'type'        => 'string',
					'minLength'   => 1,
				),
				'type'         => array(
					'type'    => 'string',
					'enum'    => array_keys( wc_get_attribute_types() ),
					'default' => 'select',
				),
				'order_by'     => array(
					'description' => __( 'Default sort order of the terms. name_num sorts names as numbers.', 'woocommerce' ),
					'type'        => 'string',
					'enum'        => array( 'menu_order', 'name', 'name_num', 'id' ),
					'default'     => 'menu_order',
				),
				'has_archives' => array(
					'description' => __( 'Give each term a public archive page.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
				),
			),
			'required'             => array( 'name' ),
			'additionalProperties' => false,
		);
	}
}
