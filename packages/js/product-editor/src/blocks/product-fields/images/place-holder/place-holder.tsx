/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Products } from './imgs/products';

export function PlaceHolder() {
	return (
		<div className="woocommerce-image-placeholder__wrapper">
			<Products />
			<p>
				{ __(
					'For best results, offer a variety of product images, like close-up details, lifestyle scenes, and color variations.',
					'woocommerce'
				) }
			</p>
		</div>
	);
}
