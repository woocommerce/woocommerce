<?php
/**
 * Product create ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce product create ability. It runs the steps of ActionableAbility.
 */
class ProductCreate extends ActionableAbility {

	use DomainAbilityHelpers;
	use ProductAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/product-create';

	/**
	 * Variations built in memory by change(), saved after their parent by save().
	 *
	 * @var \WC_Product_Variation[]
	 */
	private array $pending_variations = array();

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Create product', 'woocommerce' ),
			'description'         => __(
				'Create a product using supported catalog fields.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'product', self::get_product_output_schema() ),
			'permission_callback' => array( __CLASS__, 'can_create_product' ),
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
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'product';
	}

	/**
	 * Create the product object of the input type, without saving it.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	public function load( array $input ) {
		$this->pending_variations = array();

		$product_type   = (string) ( $input['type'] ?? ProductType::SIMPLE );
		$product_config = self::get_product_type_config( $product_type, true );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		return self::get_product_object_for_type( $product_type );
	}

	/**
	 * Set the product properties in memory. A variable product also gets its
	 * variations built in memory, and a variation must belong to a saved
	 * variable product.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null|\WP_Error
	 */
	public function change( $subject, array $input ) {
		$product_config = self::get_product_config_for_product( $subject, true );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		try {
			self::apply_product_type_config( $subject, $product_config );

			$error = self::set_product_props_from_input( $subject, $input, $product_config );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			if ( $subject instanceof \WC_Product_Variation ) {
				if ( ! wc_get_product( $subject->get_parent_id() ) instanceof \WC_Product_Variable ) {
					return new \WP_Error(
						'woocommerce_product_parent_invalid',
						__( 'The parent must be a variable product.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				self::publish_variation_by_default( $subject );
			}

			if ( $subject instanceof \WC_Product_Variable ) {
				$variations = $this->build_variations( $subject, is_array( $input['variations'] ?? null ) ? $input['variations'] : array() );
				if ( is_wp_error( $variations ) ) {
					return $variations;
				}
				$this->pending_variations = $variations;
			}

			return null;
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * Build the variations of a variable product in memory. Each one must pick
	 * one option of every attribute of the parent, and no two may pick the same
	 * options or the same SKU. The database SKU check runs on each save and
	 * does not see the other new variations, so the call checks them here.
	 *
	 * @param \WC_Product_Variable $parent_product Parent, with its attributes set and not saved.
	 * @param array                $inputs Items of the `variations` input.
	 * @return \WC_Product_Variation[]|\WP_Error
	 * @throws \WC_Data_Exception When a variation setter rejects a value.
	 */
	private function build_variations( \WC_Product_Variable $parent_product, array $inputs ) {
		$variation_config = self::get_product_type_config( ProductType::VARIATION );
		$variations       = array();
		$combinations     = array();
		$skus             = array();

		foreach ( array_values( $inputs ) as $index => $input ) {
			$position  = $index + 1;
			$variation = self::get_product_object_for_type( ProductType::VARIATION );
			if ( is_wp_error( $variation ) ) {
				return $variation;
			}

			$error = self::set_product_props_from_input( $variation, is_array( $input ) ? $input : array(), $variation_config );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			self::publish_variation_by_default( $variation );

			$error = self::check_variation_attributes( $variation->get_attributes(), $parent_product, $position );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			$attributes = $variation->get_attributes();
			ksort( $attributes );
			$combination = wp_json_encode( $attributes );
			if ( isset( $combinations[ $combination ] ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_duplicate',
					/* translators: %d: variation position. */
					sprintf( __( 'Variation %d picks the same options as another variation.', 'woocommerce' ), $position ),
					array( 'status' => 400 )
				);
			}
			$combinations[ $combination ] = true;

			$sku = $variation->get_sku( 'edit' );
			if ( '' !== $sku && isset( $skus[ $sku ] ) ) {
				return new \WP_Error(
					'woocommerce_product_variation_sku_duplicate',
					/* translators: %d: variation position. */
					sprintf( __( 'Variation %d repeats the SKU of another variation.', 'woocommerce' ), $position ),
					array( 'status' => 400 )
				);
			}
			$skus[ $sku ] = true;

			$variations[] = $variation;
		}

		return $variations;
	}

	/**
	 * A new variation has no status until it is read back. WooCommerce saves it
	 * as published, so the object says so too.
	 *
	 * @param \WC_Product_Variation $variation Variation.
	 */
	private static function publish_variation_by_default( \WC_Product_Variation $variation ): void {
		if ( ! $variation->get_status( 'edit' ) ) {
			$variation->set_status( ProductStatus::PUBLISH );
		}
	}

	/**
	 * The variations of a variable product, each with the `extensions` input of
	 * its `variations` item.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @param array       $input   Ability input.
	 * @return array<int, array{0: \WC_Product_Variation, 1: array}>
	 */
	public function pending_objects( $subject, array $input ): array {
		$items   = array_values( is_array( $input['variations'] ?? null ) ? $input['variations'] : array() );
		$objects = array();
		foreach ( $this->pending_variations as $index => $variation ) {
			$objects[] = array( $variation, $items[ $index ]['extensions'] ?? array() );
		}
		return $objects;
	}

	/**
	 * Save the product, then each of its variations with the new parent ID,
	 * then sync the parent with its variations. A failed save returns an error,
	 * and the transaction of the pipeline rolls back the earlier saves.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		$error = self::save_product( $subject, 'woocommerce_product_create_failed' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		foreach ( $this->pending_variations as $variation ) {
			$variation->set_parent_id( $subject->get_id() );
			$error = self::save_product( $variation, 'woocommerce_product_create_failed' );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
		}

		if ( ! empty( $this->pending_variations ) ) {
			$children = $subject->get_data_store()->read_children( $subject, true );
			$subject->set_children( $children['all'] );
			$subject->set_visible_children( $children['visible'] );
			\WC_Product_Variable::sync( $subject );
			$subject->set_price( (string) get_post_meta( $subject->get_id(), '_price', true ) );
		}

		self::sync_variation_parent( $subject );

		return null;
	}

	/**
	 * The ability output for the saved product.
	 *
	 * @param \WC_Product $subject Saved product.
	 * @return array
	 */
	public function prepare_response( $subject ): array {
		return array(
			'product' => self::format_product_for_response( $subject ),
		);
	}

	/**
	 * Check product creation access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_create_product( $input = array() ): bool {
		// Cap is not object-scoped for create.
		unset( $input );

		return wc_rest_check_post_permissions( 'product', 'create' );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
		return self::get_product_create_input_schema();
	}
}
