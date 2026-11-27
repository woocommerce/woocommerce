<?php
/**
 * Product term create ability definition.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductTermWriteTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a product category, tag, brand or global attribute term.
 */
class ProductTermCreate extends ActionableAbility {

	use ProductTermAbilityTrait;
	use ProductTermWriteTrait;
	use DomainAbilityHelpers;

	/** Ability name. */
	public const ABILITY_NAME = 'woocommerce/product-term-create';

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
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'term', self::get_written_term_output_schema() ),
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
	 * Build the new term in memory.
	 *
	 * @param array $input Ability input.
	 * @return \WP_Term|\WP_Error
	 */
	protected function load( array $input ) {
		$this->term_args = array();
		if ( ! self::is_writable_product_taxonomy( $input['taxonomy'] ) ) {
			return self::get_unknown_taxonomy_error( $input['taxonomy'] );
		}

		return new \WP_Term(
			(object) array(
				'term_id'  => 0,
				'taxonomy' => $input['taxonomy'],
			)
		);
	}

	/**
	 * Set the term fields in memory.
	 *
	 * @param \WP_Term $subject New term.
	 * @param array    $input   Ability input.
	 * @return null|\WP_Error
	 */
	protected function change( $subject, array $input ) {
		$name   = sanitize_text_field( $input['name'] );
		$slug   = isset( $input['slug'] ) ? sanitize_title( $input['slug'] ) : null;
		$parent = $input['parent'] ?? 0;
		$error  = self::check_term_write( $subject->taxonomy, $name, $slug, $parent );
		if ( $error ) {
			return $error;
		}

		$args = array(
			'name'        => $name,
			'parent'      => $parent,
			'description' => wp_kses_post( $input['description'] ?? '' ),
		);
		if ( null !== $slug ) {
			$args['slug'] = $slug;
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
