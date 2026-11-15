<?php
/**
 * Product term update ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Renames, re-slugs, moves or redescribes a product category, tag, brand or global attribute term.
 */
class ProductTermUpdate extends AbstractDomainAbility implements AbilityDefinition {

	use ProductTermAbilityTrait;

	/**
	 * Get the ability name.
	 *
	 * @since 11.4.0
	 * @return string
	 */
	public static function get_name(): string {
		return 'woocommerce/product-term-update';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Update product term', 'woocommerce' ),
			'description'         => __( 'Change the name, slug, parent or description of a product category, tag, brand or global attribute term. Fields left out keep their value.', 'woocommerce' ),
			'category'            => 'woocommerce',
			'input_schema'        => self::get_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'term', self::get_written_term_output_schema() ),
			'execute_callback'    => array( __CLASS__, 'execute' ),
			'permission_callback' => array( __CLASS__, 'can_update_term' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => false,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * Check product term edit access.
	 *
	 * @since 11.4.0
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public static function can_update_term( $input = array() ): bool {
		return self::can_manage_product_terms( $input['taxonomy'], 'edit', $input['id'] );
	}

	/**
	 * Update a product term.
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

		$term = get_term( $input['id'], $taxonomy );
		if ( ! $term instanceof \WP_Term ) {
			return new \WP_Error(
				'woocommerce_ability_product_term_not_found',
				/* translators: 1: term ID, 2: taxonomy name. */
				sprintf( __( 'There is no term %1$d in "%2$s".', 'woocommerce' ), $input['id'], $taxonomy ),
				array( 'status' => 404 )
			);
		}

		if ( ! array_intersect_key( $input, self::get_term_write_properties() ) ) {
			return new \WP_Error(
				'woocommerce_ability_product_term_no_fields',
				__( 'No change was sent. Send at least one term field to change, in addition to the id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$name   = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : wp_specialchars_decode( $term->name, ENT_QUOTES );
		$slug   = isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : null;
		$parent = $input['parent'] ?? $term->parent;
		// Only a new name or parent can collide with a sibling's name.
		$checked_name = isset( $input['name'] ) || isset( $input['parent'] ) ? $name : null;
		$error        = self::check_term_write( $taxonomy, $checked_name, $slug, $parent, $term->term_id );
		if ( $error ) {
			return $error;
		}

		$args = array();
		if ( isset( $input['name'] ) ) {
			$args['name'] = $name;
		}
		if ( isset( $input['parent'] ) ) {
			$args['parent'] = $parent;
		}
		if ( null !== $slug ) {
			$args['slug'] = $slug;
		}
		if ( isset( $input['description'] ) ) {
			$args['description'] = wp_kses_post( $input['description'] );
		}
		$result = wp_update_term( $term->term_id, $taxonomy, wp_slash( $args ) );
		if ( is_wp_error( $result ) ) {
			return new \WP_Error( 'woocommerce_ability_product_term_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}

		$term = get_term( $term->term_id, $taxonomy );
		if ( ! $term instanceof \WP_Term ) {
			return new \WP_Error( 'woocommerce_ability_product_term_update_failed', __( 'The term was updated but could not be read back.', 'woocommerce' ), array( 'status' => 500 ) );
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
		return array(
			'type'                 => 'object',
			'properties'           => array_merge(
				array(
					'taxonomy' => self::get_taxonomy_input_schema( __( 'Product taxonomy of the term: product_cat, product_tag, product_brand, or the slug of a global attribute.', 'woocommerce' ) ),
					'id'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
				),
				self::get_term_write_properties()
			),
			'required'             => array( 'taxonomy', 'id' ),
			'additionalProperties' => false,
		);
	}
}
