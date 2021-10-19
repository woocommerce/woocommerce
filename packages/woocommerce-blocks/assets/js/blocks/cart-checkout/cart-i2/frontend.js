/**
 * External dependencies
 */
import {
	withStoreCartApiHydration,
	withRestApiHydration,
} from '@woocommerce/block-hocs';
import { getValidBlockAttributes } from '@woocommerce/base-utils';
import { Children, cloneElement, isValidElement } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-context';
import { useValidation } from '@woocommerce/base-context/hooks';
import { getRegisteredBlockComponents } from '@woocommerce/blocks-registry';

import { renderParentBlock } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import './inner-blocks/register-components';
import Block from './block';
import { blockName, blockAttributes } from './attributes';

const getProps = ( el ) => {
	return {
		attributes: getValidBlockAttributes(
			blockAttributes,
			!! el ? el.dataset : {}
		),
	};
};

const Wrapper = ( { children } ) => {
	// we need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const validation = useValidation();
	return Children.map( children, ( child ) => {
		if ( isValidElement( child ) ) {
			const componentProps = {
				extensions,
				cart,
				validation,
			};
			return cloneElement( child, componentProps );
		}
		return child;
	} );
};

renderParentBlock( {
	Block: withStoreCartApiHydration( withRestApiHydration( Block ) ),
	blockName,
	selector: '.wp-block-woocommerce-cart-i2',
	getProps,
	blockMap: getRegisteredBlockComponents( blockName ),
	blockWrapper: Wrapper,
} );
