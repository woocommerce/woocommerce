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
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductFieldRefusalsTrait;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce product create ability. It runs the steps of ActionableAbility.
 */
class ProductCreate extends ActionableAbility {

	use DomainAbilityHelpers;
	use ProductAbilityTrait;
	use ProductFieldRefusalsTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/product-create';

	/**
	 * The variations that change() planned, saved after their parent by save().
	 *
	 * @var array{save: array<int, array{0: \WC_Product_Variation, 1: array}>, delete: \WC_Product_Variation[]}
	 */
	private array $variations = array(
		'save'   => array(),
		'delete' => array(),
	);

	/**
	 * The parent of a variation that the call creates on its own, loaded by load().
	 *
	 * @var \WC_Product_Variable|null
	 */
	private ?\WC_Product_Variable $variation_parent = null;

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
			'output_schema'       => self::get_product_mutation_output_schema(),
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
	protected function load( array $input ) {
		$this->variations = array(
			'save'   => array(),
			'delete' => array(),
		);

		$this->variation_parent = null;

		$product_type = $input['type'] ?? ProductType::SIMPLE;

		if ( ProductType::VARIATION !== $product_type && '' === trim( $input['name'] ?? '' ) ) {
			return new \WP_Error(
				'woocommerce_product_name_required',
				__( 'A product needs a name.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( ProductType::VARIATION === $product_type ) {
			$parent = wc_get_product( $input['parent_id'] ?? 0 );
			if ( ! $parent instanceof \WC_Product_Variable ) {
				return new \WP_Error(
					'woocommerce_product_parent_invalid',
					__( 'The parent must be a variable product.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
			$this->variation_parent = $parent;
			return self::get_new_variation( $parent );
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
	 * @return null|\WP_Error|array<int, array{0: \WC_Product, 1: array}> For a variable product, its variations, each with the `extensions` input of its `variations` item. For a variation, its parent.
	 */
	protected function change( $subject, array $input ) {
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

			if ( $subject instanceof \WC_Product_Variation && null !== $this->variation_parent ) {
				if ( ! $subject->get_status( 'edit' ) ) {
					$subject->set_status( ProductStatus::PUBLISH );
				}
				$error = self::check_variation_attributes( $subject, $this->variation_parent );
				if ( is_wp_error( $error ) ) {
					return $error;
				}
				$error = self::check_variation_siblings( $this->variation_parent, array( $subject ) );
				return is_wp_error( $error ) ? $error : array( array( $this->variation_parent, array() ) );
			}

			if ( $subject instanceof \WC_Product_Variable ) {
				$plan = self::plan_variations( $subject, $input['variations'] ?? array() );
				if ( is_wp_error( $plan ) ) {
					return $plan;
				}
				$this->variations = $plan;
				return $plan['save'];
			}

			return null;
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * Save the product, then its variations.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		$error = self::save_product( $subject, 'woocommerce_product_create_failed' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		if ( $subject instanceof \WC_Product_Variable ) {
			return self::save_variations( $subject, $this->variations, 'woocommerce_product_create_failed' );
		}

		if ( null !== $this->variation_parent ) {
			self::sync_variable_parent( $this->variation_parent );
		}

		return null;
	}

	/**
	 * The ability output for the saved product.
	 *
	 * @param \WC_Product $subject Saved product.
	 * @return array|\WP_Error
	 */
	protected function prepare_response( $subject ) {
		return self::format_product_mutation_response( $subject, array_column( $this->variations['save'], 0 ) );
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
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return false;
		}

		if ( ProductType::VARIATION !== ( $input['type'] ?? '' ) ) {
			return true;
		}

		$parent_id = $input['parent_id'] ?? 0;

		return $parent_id > 0 && wc_rest_check_post_permissions( 'product', 'edit', $parent_id );
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
