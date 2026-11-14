<?php
/**
 * Product term ability helpers.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain\Traits;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

defined( 'ABSPATH' ) || exit;

/**
 * Shares the taxonomy input, access check and term output of the product term abilities.
 */
trait ProductTermAbilityTrait {

	/**
	 * Get the taxonomy input schema, limited to product taxonomies.
	 *
	 * @param string $description Field description.
	 * @return array
	 */
	private static function get_taxonomy_input_schema( string $description ): array {
		return array(
			'description' => $description,
			'type'        => 'string',
			'pattern'     => '^(product_cat|product_tag|product_brand|pa_[^\\s]+)$',
		);
	}

	/**
	 * Check product term access, as the REST term endpoints do.
	 *
	 * @param string $taxonomy  Taxonomy name.
	 * @param string $context   `read`, `create` or `edit`.
	 * @param int    $object_id Term ID, for `edit`.
	 * @return bool
	 */
	private static function can_manage_product_terms( string $taxonomy, string $context, int $object_id = 0 ): bool {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			// Let execute() tell a store manager that the attribute does not exist, rather than refuse access.
			return (bool) wc_rest_check_manager_permissions( 'attributes', 'read' );
		}
		return (bool) wc_rest_check_product_term_permissions( $taxonomy, $context, $object_id );
	}

	/**
	 * Get the error for a taxonomy that is not registered, such as a deleted attribute.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return \WP_Error
	 */
	private static function get_unknown_taxonomy_error( string $taxonomy ): \WP_Error {
		return new \WP_Error(
			'woocommerce_ability_product_term_taxonomy_unknown',
			/* translators: %s: taxonomy name. */
			sprintf( __( 'The taxonomy "%s" does not exist. Use product-attributes-query to list the global attributes.', 'woocommerce' ), $taxonomy ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Check that a write targets a product taxonomy, where a `pa_` one must be a registered global attribute.
	 *
	 * Another plugin can register its own `pa_` taxonomy, which the input pattern alone would let through.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	private static function is_writable_product_taxonomy( string $taxonomy ): bool {
		return 0 === strpos( $taxonomy, 'pa_' ) ? taxonomy_is_product_attribute( $taxonomy ) : taxonomy_exists( $taxonomy );
	}

	/**
	 * Get the input schemas of the term fields that create and update both write.
	 *
	 * @return array
	 */
	private static function get_term_write_properties(): array {
		return array(
			'name'        => array(
				'type'      => 'string',
				'minLength' => 1,
			),
			'slug'        => array(
				'type'      => 'string',
				'minLength' => 1,
			),
			'parent'      => array(
				'description' => __( 'Parent term ID, for categories and brands only. Zero makes a top-level term.', 'woocommerce' ),
				'type'        => 'integer',
				'minimum'     => 0,
			),
			'description' => array( 'type' => 'string' ),
		);
	}

	/**
	 * Refuse a write that WordPress would store differently than asked.
	 *
	 * WordPress accepts a parent from any taxonomy, stores a parent on taxonomies without
	 * hierarchy, and renames a slug in use to a free one instead of failing.
	 *
	 * @param string      $taxonomy  Taxonomy name.
	 * @param string|null $name      Term name, sanitized, or null to skip the name checks.
	 * @param string|null $slug      Term slug, sanitized, or null when none was sent.
	 * @param int         $parent_id Parent term ID.
	 * @param int         $term_id   ID of the term being updated, or zero for a new term.
	 * @return \WP_Error|null
	 */
	private static function check_term_write( string $taxonomy, ?string $name, ?string $slug, int $parent_id, int $term_id = 0 ): ?\WP_Error {
		if ( '' === $name ) {
			return new \WP_Error( 'woocommerce_ability_product_term_name_empty', __( 'The term name is empty.', 'woocommerce' ), array( 'status' => 400 ) );
		}
		// WordPress treats a "0" slug as missing and generates one from the name instead.
		if ( '' === $slug || '0' === $slug ) {
			return new \WP_Error( 'woocommerce_ability_product_term_slug_empty', __( 'The term needs a slug with letters or numbers, other than "0". Send one, or leave slug out to make one from the name.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( $parent_id > 0 ) {
			if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
				return new \WP_Error(
					'woocommerce_ability_product_term_parent_unsupported',
					/* translators: %s: taxonomy name. */
					sprintf( __( 'Terms of "%s" have no parent. Leave parent out.', 'woocommerce' ), $taxonomy ),
					array( 'status' => 400 )
				);
			}
			if ( ! get_term( $parent_id, $taxonomy ) instanceof \WP_Term ) {
				return new \WP_Error(
					'woocommerce_ability_product_term_parent_not_found',
					/* translators: 1: term ID, 2: taxonomy name. */
					sprintf( __( 'There is no term %1$d in "%2$s" to use as the parent.', 'woocommerce' ), $parent_id, $taxonomy ),
					array( 'status' => 400 )
				);
			}
			if ( $term_id > 0 && ( $parent_id === $term_id || in_array( $term_id, get_ancestors( $parent_id, $taxonomy, 'taxonomy' ), true ) ) ) {
				return new \WP_Error( 'woocommerce_ability_product_term_parent_loop', __( 'A term cannot be its own parent or the parent of one of its ancestors.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		if ( null !== $slug ) {
			$existing = get_term_by( 'slug', $slug, $taxonomy );
			if ( $existing instanceof \WP_Term && $existing->term_id !== $term_id ) {
				return new \WP_Error(
					'woocommerce_ability_product_term_slug_exists',
					/* translators: %s: term slug. */
					sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), $slug ),
					array(
						'status' => 400,
						'id'     => $existing->term_id,
					)
				);
			}
		}

		if ( null === $name ) {
			return null;
		}

		$args = array(
			'taxonomy'   => $taxonomy,
			'name'       => $name,
			'hide_empty' => false,
		);
		// WordPress allows a repeated name in different branches of a hierarchy, and nowhere else.
		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$args['parent'] = $parent_id;
		}
		$matches = get_terms( $args );
		foreach ( is_array( $matches ) ? $matches : array() as $match ) {
			if ( $match->term_id !== $term_id && 0 === strcasecmp( wp_specialchars_decode( $match->name, ENT_QUOTES ), $name ) ) {
				return new \WP_Error(
					'woocommerce_ability_product_term_name_exists',
					/* translators: %s: term name. */
					sprintf( __( 'A term named "%s" already exists here.', 'woocommerce' ), $name ),
					array(
						'status' => 400,
						'id'     => $match->term_id,
					)
				);
			}
		}

		return null;
	}

	/**
	 * Get the term output schema, with the fields extensions register on product terms.
	 *
	 * @return array
	 */
	private static function get_product_term_output_schema(): array {
		return ExtensibleAbilities::add_fields_schema( self::get_term_schema(), 'product_term' );
	}

	/**
	 * Get the query's term fields without descriptions or the image breakdown.
	 *
	 * A write echoes the agent's own input, so those would cost tokens on every request and tell it nothing.
	 *
	 * @return array
	 */
	private static function get_written_term_output_schema(): array {
		$schema = self::get_term_schema();
		foreach ( $schema['properties'] as $field => $property ) {
			unset( $schema['properties'][ $field ]['description'] );
		}
		$schema['properties']['image'] = array( 'type' => 'object' );
		return ExtensibleAbilities::add_fields_schema( $schema, 'product_term' );
	}

	/**
	 * Format a term with the fields of the WooCommerce term reads.
	 *
	 * @param \WP_Term $term Term to format.
	 * @return array|\WP_Error A WP_Error when an extension field value does not match its schema.
	 */
	private static function format_term( \WP_Term $term ) {
		$output = array(
			'id'          => $term->term_id,
			'name'        => html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5 ),
			'slug'        => $term->slug,
			'description' => $term->description,
			'count'       => $term->count,
		);
		if ( taxonomy_is_product_attribute( $term->taxonomy ) ) {
			$output['menu_order'] = (int) get_term_meta( $term->term_id, 'order', true );
		} elseif ( 'product_tag' !== $term->taxonomy ) {
			$output['parent']     = $term->parent;
			$display              = get_term_meta( $term->term_id, 'display_type', true );
			$output['display']    = $display ? $display : 'default';
			$output['menu_order'] = (int) get_term_meta( $term->term_id, 'order', true );
			$image_id             = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			$image                = $image_id ? get_post( $image_id ) : null;
			$image_url            = $image ? wp_get_attachment_url( $image->ID ) : false;
			if ( $image && 'attachment' === $image->post_type && $image_url ) {
				$output['image'] = array(
					'id'   => $image->ID,
					'src'  => $image_url,
					'name' => $image->post_title,
					'alt'  => get_post_meta( $image->ID, '_wp_attachment_image_alt', true ),
				);
				foreach ( array(
					'date_created'  => 'date',
					'date_modified' => 'modified',
				) as $field => $source ) {
					foreach ( array(
						''     => 'local',
						'_gmt' => 'gmt',
					) as $suffix => $timezone ) {
						$date = get_post_datetime( $image, $source, $timezone );
						if ( $date ) {
							if ( 'gmt' === $timezone ) {
								$date = $date->setTimezone( new \DateTimeZone( 'UTC' ) );
							}
							$output['image'][ $field . $suffix ] = $date->format( DATE_RFC3339 );
						}
					}
				}
			}
		}
		return ExtensibleAbilities::add_fields_to_object( $output, 'product_term', $term );
	}

	/**
	 * Get the term output schema, including taxonomy-specific fields.
	 *
	 * @return array
	 */
	private static function get_term_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'          => array( 'type' => 'integer' ),
				'name'        => array( 'type' => 'string' ),
				'slug'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'count'       => array(
					'type'        => 'integer',
					'description' => __( 'Number of published, catalog-visible products. hide_empty uses stored counts and also retains parents with non-empty children, so a returned term can have count zero.', 'woocommerce' ),
				),
				'parent'      => array( 'type' => 'integer' ),
				'display'     => array( 'type' => 'string' ),
				'menu_order'  => array( 'type' => 'integer' ),
				'image'       => array(
					'type'                 => 'object',
					'properties'           => array(
						'id'                => array( 'type' => 'integer' ),
						'src'               => array( 'type' => 'string' ),
						'name'              => array( 'type' => 'string' ),
						'alt'               => array( 'type' => 'string' ),
						'date_created'      => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'date_created_gmt'  => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'date_modified'     => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
						'date_modified_gmt' => array(
							'type'   => 'string',
							'format' => 'date-time',
						),
					),
					'additionalProperties' => false,
				),
			),
			'additionalProperties' => false,
		);
	}
}
