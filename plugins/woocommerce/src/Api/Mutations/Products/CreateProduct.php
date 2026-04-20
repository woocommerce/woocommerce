<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Mutations\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\ReturnType;
use Automattic\WooCommerce\Api\InputTypes\Products\CreateProductInput;
use Automattic\WooCommerce\Api\Interfaces\Product;
use Automattic\WooCommerce\Api\Traits\RequiresManageWoocommerce;
use Automattic\WooCommerce\Api\Utils\Products\ProductMapper;
use Automattic\WooCommerce\Api\Utils\Products\ProductRepository;

/**
 * Mutation to create a new product.
 *
 * Demonstrates: DI via init(), inherited capability (trait), ApiException with extensions.
 */
#[Description( 'Create a new product.' )]
#[RequiredCapability( 'edit_products' )]
class CreateProduct {
	use RequiresManageWoocommerce;

	/**
	 * The product repository.
	 *
	 * @var ProductRepository
	 */
	private ProductRepository $repository;

	/**
	 * Inject dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param ProductRepository $repository The product repository.
	 */
	final public function init( ProductRepository $repository ): void {
		$this->repository = $repository;
	}

	/**
	 * Execute the mutation.
	 *
	 * @param CreateProductInput $input The product creation data.
	 * @return object
	 * @throws ApiException When validation fails.
	 */
	#[ReturnType( Product::class )]
	public function execute(
		#[Description( 'Data for the new product.' )]
		CreateProductInput $input,
	): object {
		// Best-effort duplicate-name check. There is an inherent TOCTOU race
		// here: two nearly-simultaneous requests with the same name can both
		// pass this check and both succeed in creating the product, because
		// wp_posts.post_title is not a unique column in the schema and WP
		// offers no portable atomic "reserve name" primitive. Locking via
		// wp_cache_add() would help only on sites with a persistent object
		// cache (Redis/Memcached), so we do not rely on it here. If strict
		// uniqueness is ever required, callers should enforce it at a
		// higher layer (e.g. a mutex around the REST handler) rather than
		// assume the API guarantees it.
		$existing = new \WP_Query(
			array(
				'post_type'   => 'product',
				'title'       => $input->name,
				'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
				'fields'      => 'ids',
			)
		);

		if ( $existing->found_posts > 0 ) {
			throw new ApiException(
				'A product with this name already exists.',
				'VALIDATION_ERROR',
				array( 'field' => 'name' ),
				422,
			);
		}

		$wc_product = new \WC_Product();
		$wc_product->set_name( $input->name );

		foreach ( array( 'slug', 'sku', 'description', 'short_description', 'manage_stock', 'stock_quantity' ) as $field ) {
			if ( null !== $input->$field ) {
				$wc_product->{"set_{$field}"}( $input->$field );
			}
		}

		foreach ( array( 'regular_price', 'sale_price' ) as $field ) {
			if ( null !== $input->$field ) {
				$wc_product->{"set_{$field}"}( (string) $input->$field );
			}
		}

		if ( null !== $input->status ) {
			$wc_product->set_status( $input->status->value );
		}

		if ( null !== $input->dimensions ) {
			foreach ( array( 'length', 'width', 'height', 'weight' ) as $field ) {
				if ( null !== $input->dimensions->$field ) {
					$wc_product->{"set_{$field}"}( (string) $input->dimensions->$field );
				}
			}
		}

		$this->repository->save( $wc_product );

		return ProductMapper::from_wc_product( $wc_product );
	}
}
