<?php
/**
 * Product update ability class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductFieldRefusalsTrait;

defined( 'ABSPATH' ) || exit;

/**
 * The WooCommerce product update ability. It runs the steps of ActionableAbility.
 */
class ProductUpdate extends ActionableAbility {

	use DomainAbilityHelpers;
	use ProductAbilityTrait;
	use ProductFieldRefusalsTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/product-update';

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
			'output_schema'       => self::get_product_mutation_output_schema(),
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
	protected function load( array $input ) {
		$this->variations   = array(
			'save'   => array(),
			'delete' => array(),
		);
		$this->deleted      = array();
		$this->parent       = null;
		$this->changes_type = false;
		$this->type_deletes = false;

		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		if ( empty( array_diff( array_keys( $input ), array( 'id' ) ) ) ) {
			return new \WP_Error(
				'woocommerce_product_update_no_fields',
				__( 'At least one product field is required to update a product.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$error = self::check_publish_permission( $product, $input );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		if ( isset( $input['type'] ) && $input['type'] !== $product->get_type() ) {
			$target_class = \WC_Product_Factory::get_product_classname( $product->get_id(), $input['type'] );
			if ( $product instanceof \WC_Product_Variation || is_a( $target_class, \WC_Product_Variation::class, true ) ) {
				return new \WP_Error(
					'woocommerce_product_type_unsupported',
					__( 'A variation cannot change its type, and a product cannot become a variation.', 'woocommerce' ),
					array( 'status' => 400 )
				);
			}
			if ( $product instanceof \WC_Product_Variable ) {
				$this->type_deletes = self::type_change_deletes_variations( $product, $input['type'] );
				if ( ! $this->type_deletes && ! is_a( $target_class, \WC_Product_Variable::class, true ) ) {
					return new \WP_Error(
						'woocommerce_product_type_unsupported',
						__( 'WooCommerce would keep the variations of this product without a variable parent, so it cannot change into a type that is not variable.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				if ( $this->type_deletes && isset( $input['variations'] ) ) {
					return new \WP_Error(
						'woocommerce_product_type_unsupported',
						__( 'This type change deletes every variation of the product, so the call cannot also send variations.', 'woocommerce' ),
						array( 'status' => 400 )
					);
				}
				if ( $this->type_deletes ) {
					$children                   = self::read_variation_children( $product, true )['all'];
					$this->variations['delete'] = array_values( array_filter( array_map( 'wc_get_product', $children ), static fn( $child ) => $child instanceof \WC_Product_Variation ) );
				}
			}
			$this->changes_type = true;
			return self::get_product_object_for_type( $input['type'], $product->get_id() );
		}

		return $product;
	}

	/**
	 * Set the product properties in memory.
	 *
	 * @param \WC_Product $subject Product.
	 * @param array       $input   Ability input.
	 * @return null|\WP_Error|array<int, array{0: \WC_Product, 1: array}> The variations that the input names, each with the `extensions` input of its `variations` item. For a variation, its parent.
	 */
	protected function change( $subject, array $input ) {
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
				$plan = self::plan_variations( $subject, $input['variations'] );
				if ( is_wp_error( $plan ) ) {
					return $plan;
				}
				$this->variations = $plan;
				return $plan['save'];
			}

			$this->parent = self::get_variable_parent( $subject );

			return null === $this->parent ? null : array( array( $this->parent, array() ) );
		} catch ( \WC_Data_Exception $exception ) {
			return self::get_product_data_exception_error( $exception );
		}
	}

	/**
	 * The variations that the `variations` input marked for deletion, as they
	 * were before the delete.
	 *
	 * @var \WC_Product_Variation[]
	 */
	private array $deleted = array();

	/**
	 * The parent of an updated variation, synced after the save.
	 *
	 * @var \WC_Product_Variable|null
	 */
	private ?\WC_Product_Variable $parent = null;

	/**
	 * Whether the input changes the product type.
	 *
	 * @var bool
	 */
	private bool $changes_type = false;

	/**
	 * Whether the type change deletes the variations of the product.
	 *
	 * @var bool
	 */
	private bool $type_deletes = false;

	/**
	 * The variations that the `variations` input or the type change deleted.
	 *
	 * @return \WC_Product_Variation[]
	 */
	protected function get_deleted_objects(): array {
		return $this->deleted;
	}

	/**
	 * The variations that the `variations` input or the type change will delete, for the validators.
	 *
	 * @return \WC_Product_Variation[]
	 */
	protected function get_objects_to_delete(): array {
		return $this->variations['delete'];
	}

	/**
	 * Save the product, then the variations that the input names. A type change
	 * that deletes the variations leaves the delete to WooCommerce, which runs
	 * it in the save of the product, and checks that they are gone.
	 *
	 * @param \WC_Product $subject Changed product.
	 * @return null|\WP_Error
	 */
	protected function save( $subject ) {
		$this->deleted = array_map( static fn( \WC_Product_Variation $variation ) => clone $variation, $this->variations['delete'] );

		$error = self::save_product( $subject, 'woocommerce_product_update_failed' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		if ( $this->type_deletes ) {
			foreach ( $this->variations['delete'] as $variation ) {
				if ( null !== get_post( $variation->get_id() ) ) {
					return new \WP_Error( 'woocommerce_product_update_failed', __( 'Failed to delete a variation.', 'woocommerce' ), array( 'status' => 500 ) );
				}
			}
			$this->variations['delete'] = array();
		}

		if ( $subject instanceof \WC_Product_Variable ) {
			$error = self::save_variations( $subject, $this->variations, 'woocommerce_product_update_failed' );
			if ( null === $error && $this->changes_type && empty( $this->variations['save'] ) && empty( $this->variations['delete'] ) ) {
				self::sync_variable_parent( $subject );
			}
			return $error;
		}

		if ( null !== $this->parent ) {
			self::sync_variable_parent( $this->parent );
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
	 * Check product update access.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 *
	 * @since 10.9.0
	 */
	public static function can_update_product( $input = array() ): bool {
		$product_id = self::get_id_from_input( $input );
		$product    = isset( $input['type'] ) ? wc_get_product( $product_id ) : null;
		$deletes    = ! empty( array_filter( array_column( $input['variations'] ?? array(), 'delete' ) ) )
			|| ( $product instanceof \WC_Product_Variable && self::type_change_deletes_variations( $product, $input['type'] ) );

		return self::check_product_permission( $product_id, 'edit' )
			&& ( ! $deletes || self::check_product_permission( $product_id, 'delete' ) );
	}

	/**
	 * Whether WooCommerce deletes the variations of a variable product when
	 * its type changes, which it decides when the product is saved.
	 *
	 * @param \WC_Product_Variable $product Variable product.
	 * @param string               $type    New product type.
	 * @return bool
	 */
	private static function type_change_deletes_variations( \WC_Product_Variable $product, string $type ): bool {
		$from = $product->get_type();

		/**
		 * Filter whether WooCommerce deletes the variations of a variable product when its type changes.
		 *
		 * @since 5.0.0
		 *
		 * @param bool       $delete  Whether to delete the variations.
		 * @param WC_Product $product Product.
		 * @param string     $from    Old product type.
		 * @param string     $to      New product type.
		 */
		return $type !== $from && (bool) apply_filters( 'woocommerce_delete_variations_on_product_type_change', ProductType::VARIABLE === $from && ProductType::VARIABLE !== $type, $product, $from, $type );
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
