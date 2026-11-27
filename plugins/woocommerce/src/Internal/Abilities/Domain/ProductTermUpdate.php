<?php
/**
 * Product term update ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermWriteTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Renames, re-slugs, moves or redescribes a product category, tag, brand or global attribute term.
 */
class ProductTermUpdate extends ActionableAbility {

	use ProductTermAbilityTrait;
	use ProductTermWriteTrait;
	use DomainAbilityHelpers;

	/** Ability name. */
	public const ABILITY_NAME = 'woocommerce/product-term-update';

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
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'term', self::get_written_term_output_schema() ),
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
	 * Load the term to change.
	 *
	 * @param array $input Ability input.
	 * @return \WP_Term|\WP_Error
	 */
	protected function load( array $input ) {
		$this->term_args = array();
		$taxonomy        = $input['taxonomy'];
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
		return $term;
	}

	/**
	 * Set the sent term fields in memory.
	 *
	 * @param \WP_Term $subject Term.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error
	 */
	protected function change( $subject, array $input ) {
		if ( ! array_intersect_key( $input, self::get_term_write_properties() + array( 'extensions' => true ) ) ) {
			return new \WP_Error(
				'woocommerce_ability_product_term_no_fields',
				__( 'No change was sent. Send at least one term field to change, in addition to the id.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$name   = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : wp_specialchars_decode( $subject->name, ENT_QUOTES );
		$slug   = isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : null;
		$parent = $input['parent'] ?? $subject->parent;
		// Only a new name or parent can collide with a sibling's name.
		$checked_name = isset( $input['name'] ) || isset( $input['parent'] ) ? $name : null;
		$error        = self::check_term_write( $subject->taxonomy, $checked_name, $slug, $parent, $subject->term_id );
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
		$this->stage_term_args( $subject, $args );
		return null;
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
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
