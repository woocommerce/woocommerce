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
import {
	renderFrontend,
	getValidBlockAttributes,
} from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import Block from './block.js';
import blockAttributes from './attributes';
import EmptyCart from './empty-cart/index.js';

const reloadPage = () => void window.location.reload( true );

/**
 * Wrapper component for the checkout block.
 *
 * @param {Object} props Props for the block.
 */
const CheckoutFrontend = ( props ) => {
	const { cartItems, cartIsLoading } = useStoreCart();

	return (
		<>
			{ ! cartIsLoading && cartItems.length === 0 ? (
				<EmptyCart />
			) : (
				<BlockErrorBoundary
					header={ __(
						'Something went wrong…',
						'woocommerce'
					) }
					text={ __experimentalCreateInterpolateElement(
						__(
							'The checkout has encountered an unexpected error. <button>Try reloading the page</button>. If the error persists, please get in touch with us so we can assist.',
							'woocommerce'
						),
						{
							button: (
								<button
									className="wc-block-link-button"
									onClick={ reloadPage }
								/>
							),
						}
					) }
					showErrorMessage={ CURRENT_USER_IS_ADMIN }
				>
					<StoreNoticesProvider context="wc/checkout">
						<ValidationContextProvider>
							<Block { ...props } />
						</ValidationContextProvider>
					</StoreNoticesProvider>
				</BlockErrorBoundary>
			) }
		</>
	);
};

const getProps = ( el ) => {
	return {
		attributes: getValidBlockAttributes( blockAttributes, el.dataset ),
	};
};

const getErrorBoundaryProps = () => {
	return {
		header: __( 'Something went wrong…', 'woocommerce' ),
		text: __experimentalCreateInterpolateElement(
			__(
				'The checkout has encountered an unexpected error. <button>Try reloading the page</button>. If the error persists, please get in touch with us so we can assist.',
				'woocommerce'
			),
			{
				button: (
					<button
						className="wc-block-link-button"
						onClick={ reloadPage }
					/>
				),
			}
		),
		showErrorMessage: CURRENT_USER_IS_ADMIN,
	};
};

renderFrontend( {
	selector: '.wp-block-woocommerce-checkout',
	Block: withStoreCartApiHydration(
		withRestApiHydration( CheckoutFrontend )
	),
	getProps,
	getErrorBoundaryProps,
} );
