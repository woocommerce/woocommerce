<?php
/**
 * Product update ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce product update ability. It runs the steps of ActionableAbility.
 */
class ProductUpdate extends ActionableAbility {

	use DomainAbilityHelpers;
	use ProductAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/product-update';

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
			'label'               => __( 'Update product', 'woocommerce' ),
			'description'         => __(
				'Update an existing product using supported catalog fields.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_entity_output_schema( 'product', self::get_product_output_schema() ),
			'permission_callback' => array( __CLASS__, 'can_update_product' ),
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
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'product';
	}

	/**
	 * Load the product to change, with the input type.
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

		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		if ( $product instanceof \WC_Product_Variation ) {
			return self::get_unsupported_product_type_error();
		}

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_product_update_no_fields',
				__( 'At least one product field is required to update a product.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if (
			isset( $input['status'] )
			&& in_array(
				sanitize_key( $input['status'] ),
				array( ProductStatus::PUBLISH, ProductStatus::FUTURE, ProductStatus::PRIVATE ),
				true
			)
			&& sanitize_key( $input['status'] ) !== $product->get_status()
			&& ! self::current_user_can_publish_products()
		) {
			return new \WP_Error(
				'woocommerce_product_publish_forbidden',
				__( 'You are not allowed to publish products.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( isset( $input['type'] ) ) {
			$current_config = self::get_product_config_for_product( $product );
			if ( is_wp_error( $current_config ) ) {
				return $current_config;
			}
			return self::get_product_object_for_type( (string) $input['type'], $product->get_id() );
		}

		return $product;
	}

	/**
	 * Set the product properties in memory.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null|\WP_Error|array<int, array{0: \WC_Product_Variation, 1: array}> The variations that the input names, each with the `extensions` input of its `variations` item.
	 */
	public function change( $subject, array $input ) {
		$product_config = self::get_product_config_for_product( $subject );

		if ( is_wp_error( $product_config ) ) {
			return $product_config;
		}

		try {
			if ( isset( $input['type'] ) && \WC_Product_Factory::get_product_type( $subject->get_id() ) !== $input['type'] ) {
				self::apply_product_type_config( $subject, $product_config );
			}

			$error = self::set_product_props_from_input( $subject, $input, $product_config );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			if ( $subject instanceof \WC_Product_Variable && isset( $input['variations'] ) ) {
				$plan = self::plan_variations( $subject, is_array( $input['variations'] ) ? $input['variations'] : array() );
				if ( is_wp_error( $plan ) ) {
					return $plan;
				}
				$this->variations = $plan;
				return array_map( null, $plan['save'], $plan['extensions'] );
			}

			return null;
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * Save the product, then the variations that the input names.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		$error = self::save_product( $subject, 'woocommerce_product_update_failed' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		if ( $subject instanceof \WC_Product_Variable ) {
			return self::save_variations( $subject, $this->variations, 'woocommerce_product_update_failed' );
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
	 * Check product update access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_update_product( $input = array() ): bool {
		$product_id = self::get_product_permission_id( self::get_id_from_input( $input ) );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'edit', $product_id );
	}

	/**
	 * Check whether the current user can publish products.
	 *
	 * @return bool
	 */
	private static function current_user_can_publish_products(): bool {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers the publish_products capability.
		return current_user_can( 'publish_products' );
	}

	/**
	 * Get the ability input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
		return self::get_product_update_input_schema();
	}
}
