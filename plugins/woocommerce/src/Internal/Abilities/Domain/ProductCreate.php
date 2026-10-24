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
	 * The variations that change() planned, saved after their parent by save().
	 *
	 * @var array{save: \WC_Product_Variation[], delete: \WC_Product_Variation[], extensions: array<int, array>}
	 */
	private array $variations = array(
		'save'       => array(),
		'delete'     => array(),
		'extensions' => array(),
	);

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
		$this->variations = array(
			'save'       => array(),
			'delete'     => array(),
			'extensions' => array(),
		);

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
				if ( ! $subject->get_status( 'edit' ) ) {
					$subject->set_status( ProductStatus::PUBLISH );
				}
			}

			if ( $subject instanceof \WC_Product_Variable ) {
				$plan = self::plan_variations( $subject, is_array( $input['variations'] ?? null ) ? $input['variations'] : array() );
				if ( is_wp_error( $plan ) ) {
					return $plan;
				}
				$this->variations = $plan;
			}

			return null;
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
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
		return array_map( null, $this->variations['save'], $this->variations['extensions'] );
	}

	/**
	 * Save the product, then its variations.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		$error = self::save_product( $subject, 'woocommerce_product_create_failed' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		if ( $subject instanceof \WC_Product_Variable ) {
			return self::save_variations( $subject, $this->variations, 'woocommerce_product_create_failed' );
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
