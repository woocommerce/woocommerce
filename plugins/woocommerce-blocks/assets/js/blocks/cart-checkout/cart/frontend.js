/**
 * External dependencies
 */
import {
	withRestApiHydration,
	withStoreCartApiHydration,
} from '@woocommerce/block-hocs';
import { __ } from '@wordpress/i18n';
import { StoreNoticesProvider } from '@woocommerce/base-context';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/block-settings';
import { __experimentalCreateInterpolateElement } from 'wordpress-element';

/**
 * Internal dependencies
 */
import Block from './block.js';
import blockAttributes from './attributes';
import renderFrontend from '../../../utils/render-frontend.js';

const reloadPage = () => void window.location.reload( true );
/**
 * Wrapper component to supply API data and show empty cart view as needed.
 *
 * @param {*} props
 */
const CartFrontend = ( props ) => {
	return (
		<StoreNoticesProvider context="wc/cart">
			<Block { ...props } />
		</StoreNoticesProvider>
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
		emptyCart: el.innerHTML,
		attributes,
	};
};

const getErrorBoundaryProps = () => {
	return {
		header: __( 'Something went wrong…', 'woo-gutenberg-products-block' ),
		text: __experimentalCreateInterpolateElement(
			__(
				'The cart has encountered an unexpected error. <button>Try reloading the page</button>. If the error persists, please get in touch with us so we can assist.',
				'woo-gutenberg-products-block'
			),
			{
				button: (
					<button
						className="wp-block-link-button"
						onClick={ reloadPage }
					/>
				),
			}
		),
		showErrorMessage: CURRENT_USER_IS_ADMIN,
	};
};

renderFrontend(
	'.wp-block-woocommerce-cart',
	withStoreCartApiHydration( withRestApiHydration( CartFrontend ) ),
	getProps,
	getErrorBoundaryProps
);
