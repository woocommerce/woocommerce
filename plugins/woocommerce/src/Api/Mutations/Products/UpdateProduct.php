<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Api\Mutations\Products;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Attributes\Description;
use Automattic\WooCommerce\Api\Attributes\RequiredCapability;
use Automattic\WooCommerce\Api\Attributes\ReturnType;
use Automattic\WooCommerce\Api\InputTypes\Products\UpdateProductInput;
use Automattic\WooCommerce\Api\Interfaces\Product;
use Automattic\WooCommerce\Api\Utils\Products\ProductMapper;

/**
 * Mutation to update an existing product.
 */
#[Description( 'Update an existing product.' )]
#[RequiredCapability( 'manage_woocommerce' )]
class UpdateProduct {
	/**
	 * Execute the mutation.
	 *
	 * @param UpdateProductInput $input The fields to update.
	 * @return object
	 * @throws ApiException When the product is not found.
	 */
	#[ReturnType( Product::class )]
	public function execute(
		#[Description( 'The fields to update.' )]
		UpdateProductInput $input,
	): object {
		$wc_product = wc_get_product( $input->id );

		if ( ! $wc_product instanceof \WC_Product ) {
			throw new ApiException( 'Product not found.', 'NOT_FOUND', status_code: 404 );
		}

		foreach ( array( 'name', 'slug', 'sku', 'description', 'short_description', 'manage_stock', 'stock_quantity' ) as $field ) {
			if ( $input->was_provided( $field ) ) {
				$wc_product->{"set_{$field}"}( $input->$field );
			}
		}

		foreach ( array( 'regular_price', 'sale_price' ) as $field ) {
			if ( $input->was_provided( $field ) ) {
				$wc_product->{"set_{$field}"}( null !== $input->$field ? (string) $input->$field : '' );
			}
		}

		// Nullable enum: only invoke the setter when the client supplied a
		// non-null value. An explicit null means "ignore this field" here —
		// WC_Product's set_status doesn't accept null and would fall back
		// to a default, silently overwriting whatever is already on the
		// product.
		if ( $input->was_provided( 'status' ) && null !== $input->status ) {
			$wc_product->set_status( $input->status->value );
		}

		if ( $input->was_provided( 'dimensions' ) ) {
			foreach ( array( 'length', 'width', 'height', 'weight' ) as $field ) {
				if ( $input->dimensions->was_provided( $field ) ) {
					$wc_product->{"set_{$field}"}( null !== $input->dimensions->$field ? (string) $input->dimensions->$field : '' );
				}
			}
		}

		$wc_product->save();

		return ProductMapper::from_wc_product( $wc_product );
	}
}
