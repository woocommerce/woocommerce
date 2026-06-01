/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Product } from '@woocommerce/data';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { ProductImageProps } from './types';

export function getProductImageStyle( product: Product ) {
	// @ts-expect-error @woocommerce/data's Product type is missing the `images` field (see products/types.ts).
	return product.images.length > 0
		? {
				// @ts-expect-error @woocommerce/data's Product type is missing the `images` field (see products/types.ts).
				backgroundImage: `url(${ product.images[ 0 ].src })`,
		  }
		: undefined;
}

export function ProductImage( {
	product,
	className,
	style,
	...props
}: ProductImageProps ) {
	return (
		<div
			aria-hidden="true"
			{ ...props }
			className={ clsx( 'woocommerce-product-image', className ) }
			style={ { ...style, ...getProductImageStyle( product ) } }
		/>
	);
}
