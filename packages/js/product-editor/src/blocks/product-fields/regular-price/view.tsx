/**
 * External dependencies
 */
import { createElement, render } from '@wordpress/element';
import { ReactElement } from 'react';

export function View(): ReactElement {
	return <div>View of regular price. Can include components</div>;
}

// @ts-ignore
wp.hooks.addAction( 'woocommerce_product_form_init', 'woocommerce', () => {
	render(
		<View />,
		document.querySelector(
			'[data-block-name="woocommerce/product-regular-price-field"]'
		)
	);
} );
