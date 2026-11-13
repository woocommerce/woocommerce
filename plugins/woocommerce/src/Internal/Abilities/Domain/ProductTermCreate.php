<?php
/**
 * Product term create ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a product category, tag, brand or global attribute term.
 */
class ProductTermCreate extends AbstractDomainAbility implements AbilityDefinition {

	use ProductTermAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-term-create';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Create product term', 'woocommerce' ),
			'description'         => __( 'Create a product category, tag, brand or a term of a global attribute, such as pa_color. Product writes then assign it by ID.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'term', self::get_written_term_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_create_term' ),
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
	 * Check product term create access.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_create_term( $input = array() ): bool {
		return self::can_manage_product_terms( $input['taxonomy'], 'create' );
	}

	/**
	 * Create a product term.
	 *
	 * @since 11.4.0
	 * @param array $input Ability input.
	 * @return array|\WP_Error
	 */
	public static function execute( array $input ) {
		$taxonomy = $input['taxonomy'];
		if ( ! self::is_writable_product_taxonomy( $taxonomy ) ) {
			return self::get_unknown_taxonomy_error( $taxonomy );
		}

		$name   = sanitize_text_field( $input['name'] );
		$slug   = isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : null;
		$parent = $input['parent'] ?? 0;
		$error  = self::check_term_write( $taxonomy, $name, $slug, $parent );
		if ( $error ) {
			return $error;
		}

		$args = array(
			'parent'      => $parent,
			'description' => wp_kses_post( $input['description'] ?? '' ),
		);
		if ( null !== $slug ) {
			$args['slug'] = $slug;
		}
		$ids = wp_insert_term( wp_slash( $name ), $taxonomy, wp_slash( $args ) );
		if ( is_wp_error( $ids ) ) {
			return new \WP_Error( 'woocommerce_ability_product_term_create_failed', $ids->get_error_message(), array( 'status' => 500 ) );
		}

		$term = get_term( $ids['term_id'], $taxonomy );
		if ( ! $term instanceof \WP_Term ) {
			return new \WP_Error( 'woocommerce_ability_product_term_create_failed', __( 'The term was created but could not be read back.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$output = self::format_term( $term );
		if ( is_wp_error( $output ) ) {
			return ExtensibleAbilities::saved_write_error( $output, 'product_term', $term->term_id );
		}

		return array( 'term' => $output );
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function get_input_schema(): array {
		$properties                        = self::get_term_write_properties();
		$properties['slug']['description'] = __( 'Defaults to one made from the name.', 'woocommerce' );

		return array(
			'type'                 => 'object',
			'properties'           => array_merge(
				array(
					'taxonomy' => self::get_taxonomy_input_schema( __( 'Product taxonomy: product_cat, product_tag, product_brand, or the slug of a global attribute from product-attributes-query.', 'woocommerce' ) ),
				),
				$properties
			),
			'required'             => array( 'taxonomy', 'name' ),
			'additionalProperties' => false,
		);
	}
}
