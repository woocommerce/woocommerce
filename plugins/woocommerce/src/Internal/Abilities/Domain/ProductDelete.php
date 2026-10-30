<?php
/**
 * Product delete ability definition file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\DomainAbilityHelpers;
use Automattic\WooCommerce\Internal\Abilities\Domain\Traits\ProductAbilityTrait;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product delete ability.
 *
 * The delete runs on the write pipeline of ActionableAbility, so the
 * validators of the product resource can refuse it, the syncs see the deleted
 * objects, and a failure rolls everything back. A deleted variable product
 * deletes its variations, and a deleted variation syncs its parent.
 */
class ProductDelete extends ActionableAbility {

	use DomainAbilityHelpers;
	use ProductAbilityTrait;

	/**
	 * Ability name.
	 */
	public const ABILITY_NAME = 'woocommerce/product-delete';

	/**
	 * ID of the product the call deletes, kept because a forced delete clears the object's ID.
	 *
	 * @var int
	 */
	private int $product_id = 0;

	/**
	 * Whether the call deletes for good instead of trashing.
	 *
	 * @var bool
	 */
	private bool $force = false;

	/**
	 * The parent of a deleted variation, synced after the delete.
	 *
	 * @var \WC_Product_Variable|null
	 */
	private ?\WC_Product_Variable $parent = null;

	/**
	 * The objects the call deleted for good.
	 *
	 * @var \WC_Product[]
	 */
	private array $deleted = array();

	/**
	 * The objects the call moved to the trash.
	 *
	 * @var \WC_Product[]
	 */
	private array $trashed = array();

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public static function get_registration_args(): array {
		return array(
			'label'               => __( 'Delete product', 'woocommerce' ),
			'description'         => __(
				'Delete, trash, or restore a product.',
				'woocommerce'
			),
			'category'            => 'woocommerce',
			'input_schema'        => self::build_input_schema(),
			'output_schema'       => self::get_delete_output_schema(),
			'permission_callback' => array( __CLASS__, 'can_delete_product' ),
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array(
					'public' => true,
					'type'   => 'tool',
				),
				'annotations'  => array(
					'readonly'    => false,
					'idempotent'  => true,
					'destructive' => true,
				),
			),
		);
	}

	/**
	 * The resource the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'product';
	}

	/**
	 * A delete writes no extension fields.
	 *
	 * @return bool
	 */
	protected function writes_extension_fields(): bool {
		return false;
	}

	/**
	 * Load the product and check that it can be trashed when the call does not force a delete.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Product|\WP_Error
	 */
	public function load( array $input ) {
		$this->product_id = 0;
		$this->force      = ! empty( $input['force'] );
		$this->parent     = null;
		$this->deleted    = array();
		$this->trashed    = array();

		$product = self::get_product_from_input( $input );

		if ( is_wp_error( $product ) ) {
			return $product;
		}

		/**
		 * Filter whether a product supports trashing in WooCommerce domain abilities.
		 *
		 * @param bool        $supports_trash Whether the product supports trashing.
		 * @param \WC_Product $product        The product being considered for trashing.
		 *
		 * @since 10.9.0
		 */
		$supports_trash = apply_filters( 'woocommerce_product_object_trashable', EMPTY_TRASH_DAYS > 0, $product );

		if ( ! $this->force && ! $supports_trash ) {
			return new \WP_Error(
				'woocommerce_trash_not_supported',
				__( 'Trash is disabled on this site. Pass force: true to permanently delete.', 'woocommerce' ),
				array( 'status' => 501 )
			);
		}

		$this->product_id = $product->get_id();

		return $product;
	}

	/**
	 * Nothing changes in memory. The parent of a variation is a secondary
	 * object, because the delete syncs and saves it.
	 *
	 * @param \WC_Product $subject Product to delete.
	 * @param array       $input   Ability input.
	 * @return null|array<int, array{0: \WC_Product_Variable, 1: array}>
	 */
	public function change( $subject, array $input ) {
		unset( $input );

		if ( $subject instanceof \WC_Product_Variation && $subject->get_parent_id() > 0 ) {
			$parent = wc_get_product( $subject->get_parent_id() );
			if ( $parent instanceof \WC_Product_Variable ) {
				$this->parent = $parent;
				return array( array( $parent, array() ) );
			}
		}

		return null;
	}

	/**
	 * Delete or trash the product, with the variations of a variable product,
	 * then sync the parent of a deleted variation.
	 *
	 * @param \WC_Product $subject Product to delete.
	 * @return null|\WP_Error
	 */
	public function save( $subject ) {
		$force      = $this->force;
		$product_id = $this->product_id;
		$variations = array();

		if ( $subject instanceof \WC_Product_Variable ) {
			$variation_ids = get_posts(
				array(
					'post_parent' => $product_id,
					'post_type'   => 'product_variation',
					'post_status' => array( 'any', 'trash', 'auto-draft' ),
					'fields'      => 'ids',
					'numberposts' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- Every variation of one product.
				)
			);
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation instanceof \WC_Product_Variation ) {
					$variations[] = $variation;
				}
			}
		}

		$removed = array_merge( array( clone $subject ), $variations );
		$deleted = $subject->delete( $force );

		if (
			! $deleted
			|| ( $force && null !== get_post( $product_id ) )
			|| ( ! $force && 'trash' !== get_post_status( $product_id ) )
		) {
			return new \WP_Error(
				'woocommerce_product_delete_failed',
				__( 'Failed to delete product.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		if ( $force ) {
			$this->deleted = $removed;
		} else {
			$this->trashed = $removed;
		}

		if ( null !== $this->parent ) {
			self::sync_variable_parent( $this->parent );
		}

		return null;
	}

	/**
	 * The product and, for a variable product, its variations, as they were
	 * before a forced delete, which clears the ID of the live object.
	 *
	 * @return \WC_Product[]
	 */
	protected function get_deleted_objects(): array {
		return $this->deleted;
	}

	/**
	 * The product and, for a variable product, its variations, when the call trashed them.
	 *
	 * @return \WC_Product[]
	 */
	protected function get_trashed_objects(): array {
		return $this->trashed;
	}

	/**
	 * The response of a delete.
	 *
	 * @param \WC_Product $subject Deleted product.
	 * @return array
	 */
	public function prepare_response( $subject ): array {
		unset( $subject );

		return array(
			'deleted' => true,
			'id'      => $this->product_id,
		);
	}

	/**
	 * Check whether the current user can delete the product.
	 *
	 * @param array $input Ability input.
	 * @return bool
	 */
	public static function can_delete_product( $input = array() ): bool {
		$product_id = self::get_product_permission_id( self::get_id_from_input( $input ) );

		return $product_id > 0 && wc_rest_check_post_permissions( 'product', 'delete', $product_id );
	}

	/**
	 * Get the input schema.
	 *
	 * @return array
	 */
	private static function build_input_schema(): array {
		return array(
			'type'                 => 'object',
			'properties'           => array(
				'id'    => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => __(
						'Permanently delete the product. Defaults to false, which moves the product to trash.',
						'woocommerce'
					),
					'default'     => false,
				),
			),
			'required'             => array( 'id' ),
			'additionalProperties' => false,
		);
	}
}
