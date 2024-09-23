/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Products } from './imgs/products';
import { Product } from './imgs/product';
import { PlaceHolderProps } from './types';

export function PlaceHolder( { multiple = true }: PlaceHolderProps ) {
	return (
		<div className="woocommerce-image-placeholder__wrapper">
			{ multiple ? <Products /> : <Product /> }
			<p>
				{ multiple
					? __(
							'For best results, offer a variety of product images, like close-up details, lifestyle scenes, and color variations.',
							'woocommerce'
					  )
					: __(
							'Add an image which displays the unique characteristics of this variation.',
							'woocommerce'
					  ) }
			</p>
		</div>
	);
}
