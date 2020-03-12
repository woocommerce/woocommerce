/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	withRestApiHydration,
	withStoreCartApiHydration,
} from '@woocommerce/block-hocs';
import { useStoreCart } from '@woocommerce/base-hooks';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';
import { __experimentalCreateInterpolateElement } from 'wordpress-element';

/**
 * Internal dependencies
 */
import Block from './block.js';
import blockAttributes from './attributes';
import renderFrontend from '../../../utils/render-frontend.js';

/**
 * Wrapper component for the checkout block.
 *
 * @param {Object} props Props for the block.
 */
const CheckoutFrontend = ( props ) => {
	const { cartErrors, shippingRates } = useStoreCart();

	if ( cartErrors && cartErrors.length > 0 ) {
		throw new Error( cartErrors[ 0 ].message );
	}

	return (
		<BlockErrorBoundary
			header={ __(
				'Something went wrong…',
				'woo-gutenberg-products-block'
			) }
			text={ __experimentalCreateInterpolateElement(
				__(
					'The checkout has encountered an unexpected error. <a>Try reloading the page</a>. If the error persists, please get in touch with us so we can assist.',
					'woo-gutenberg-products-block'
				),
				{
					a: (
						// eslint-disable-next-line jsx-a11y/anchor-has-content
						<a href="." />
					),
				}
			) }
			showErrorMessage={ CURRENT_USER_IS_ADMIN }
		>
			<Block { ...props } shippingRates={ shippingRates } />
		</BlockErrorBoundary>
	);
};

const getProps = ( el ) => {
	const attributes = {};

	Object.keys( blockAttributes ).forEach( ( key ) => {
		if ( typeof el.dataset[ key ] !== 'undefined' ) {
			if (
				el.dataset[ key ] === 'true' ||
				el.dataset[ key ] === 'false'
			) {
				attributes[ key ] = el.dataset[ key ] !== 'false';
			} else {
				attributes[ key ] = el.dataset[ key ];
			}
		} else {
			attributes[ key ] = blockAttributes[ key ].default;
		}
	} );

	return {
		attributes,
	};
};

const getErrorBoundaryProps = () => {
	return {
		header: __( 'Something went wrong…', 'woo-gutenberg-products-block' ),
		text: __experimentalCreateInterpolateElement(
			__(
				'The checkout has encountered an unexpected error. <a>Try reloading the page</a>. If the error persists, please get in touch with us so we can assist.',
				'woo-gutenberg-products-block'
			),
			{
				a: (
					// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/anchor-is-valid
					<a href="javascript:window.location.reload(true)" />
				),
			}
		),
		showErrorMessage: CURRENT_USER_IS_ADMIN,
	};
};

renderFrontend(
	'.wp-block-woocommerce-checkout',
	withStoreCartApiHydration( withRestApiHydration( CheckoutFrontend ) ),
	getProps,
	getErrorBoundaryProps
);
