/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	withRestApiHydration,
	withStoreCartApiHydration,
} from '@woocommerce/block-hocs';
import { useStoreCart } from '@woocommerce/base-hooks';
import {
	StoreNoticesProvider,
	ValidationContextProvider,
} from '@woocommerce/base-context';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';
import { __experimentalCreateInterpolateElement } from 'wordpress-element';

/**
 * Internal dependencies
 */
import Block from './block.js';
import blockAttributes from './attributes';
import renderFrontend from '../../../utils/render-frontend.js';
import EmptyCart from './empty-cart';

/**
 * Wrapper component for the checkout block.
 *
 * @param {Object} props Props for the block.
 */
const CheckoutFrontend = ( props ) => {
	const {
		cartCoupons,
		cartItems,
		cartTotals,
		shippingRates,
		cartIsLoading,
	} = useStoreCart();

	return (
		<>
			{ ! cartIsLoading && cartItems.length === 0 ? (
				<EmptyCart />
			) : (
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
					<StoreNoticesProvider context="wc/checkout">
						<ValidationContextProvider>
							<Block
								{ ...props }
								cartCoupons={ cartCoupons }
								cartItems={ cartItems }
								cartTotals={ cartTotals }
								shippingRates={ shippingRates }
							/>
						</ValidationContextProvider>
					</StoreNoticesProvider>
				</BlockErrorBoundary>
			) }
		</>
	);
};

const getProps = ( el ) => {
	const attributes = {};

	Object.keys( blockAttributes ).forEach( ( key ) => {
		if ( typeof el.dataset[ key ] !== 'undefined' ) {
			switch ( blockAttributes[ key ].type ) {
				case 'boolean':
					attributes[ key ] = el.dataset[ key ] !== 'false';
					break;
				case 'number':
					attributes[ key ] = parseInt( el.dataset[ key ], 10 );
					break;
				default:
					attributes[ key ] = el.dataset[ key ];
					break;
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
