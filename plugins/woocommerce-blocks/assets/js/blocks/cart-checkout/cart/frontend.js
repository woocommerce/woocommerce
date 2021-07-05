/**
 * External dependencies
 */
import {
	withStoreCartApiHydration,
	withRestApiHydration,
} from '@woocommerce/block-hocs';
import { __ } from '@wordpress/i18n';
import {
	StoreNoticesProvider,
	StoreSnackbarNoticesProvider,
} from '@woocommerce/base-context/providers';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import {
	renderFrontend,
	getValidBlockAttributes,
} from '@woocommerce/base-utils';
/**
 * Internal dependencies
 */
import Block from './block.js';
import blockAttributes from './attributes';

const reloadPage = () => void window.location.reload( true );
/**
 * Wrapper component to supply API data and show empty cart view as needed.
 *
 * @param {*} props
 */
const CartFrontend = ( props ) => {
	return (
		<StoreSnackbarNoticesProvider context="wc/cart">
			<StoreNoticesProvider context="wc/cart">
				<Block { ...props } />
			</StoreNoticesProvider>
		</StoreSnackbarNoticesProvider>
	);
};

const getProps = ( el ) => {
	return {
		emptyCart: el.innerHTML,
		attributes: getValidBlockAttributes( blockAttributes, el.dataset ),
	};
};

const getErrorBoundaryProps = () => {
	return {
		header: __( 'Something went wrong…', 'woo-gutenberg-products-block' ),
		text: __(
			'The cart has encountered an unexpected error. If the error persists, please get in touch with us for help.',
			'woo-gutenberg-products-block'
		),
		showErrorMessage: CURRENT_USER_IS_ADMIN,
		button: (
			<button className="wc-block-button" onClick={ reloadPage }>
				{ __( 'Reload the page', 'woo-gutenberg-products-block' ) }
			</button>
		),
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-cart',
	Block: withStoreCartApiHydration( withRestApiHydration( CartFrontend ) ),
	getProps,
	getErrorBoundaryProps,
} );
