/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import {
	useCheckoutContext,
	useValidationContext,
	ValidationContextProvider,
	CheckoutProvider,
	SnackbarNoticesContainer,
} from '@woocommerce/base-context';
import { StoreNoticesContainer } from '@woocommerce/base-context/providers';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import { CURRENT_USER_IS_ADMIN, getSetting } from '@woocommerce/settings';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import withScrollToTop from '@woocommerce/base-hocs/with-scroll-to-top';

/**
 * Internal dependencies
 */
import './styles/style.scss';
import EmptyCart from './empty-cart';
import CheckoutOrderError from './checkout-order-error';
import { LOGIN_TO_CHECKOUT_URL, isLoginRequired, reloadPage } from './utils';
import type { Attributes } from './types';
import { CheckoutBlockContext } from './context';
import { hasNoticesOfType } from '../../utils/notices';
import { StoreNoticesProvider } from '../../base/context/providers';

const LoginPrompt = () => {
	return (
		<>
			{ __(
				'You must be logged in to checkout. ',
				'woo-gutenberg-products-block'
			) }
			<a href={ LOGIN_TO_CHECKOUT_URL }>
				{ __(
					'Click here to log in.',
					'woo-gutenberg-products-block'
				) }
			</a>
		</>
	);
};

const Checkout = ( {
	attributes,
	children,
}: {
	attributes: Attributes;
	children: React.ReactChildren;
} ): JSX.Element => {
	const { hasOrder, customerId } = useCheckoutContext();
	const { cartItems, cartIsLoading } = useStoreCart();

	const {
		allowCreateAccount,
		showCompanyField,
		requireCompanyField,
		showApartmentField,
		showPhoneField,
		requirePhoneField,
	} = attributes;

	if ( ! cartIsLoading && cartItems.length === 0 ) {
		return <EmptyCart />;
	}

	if ( ! hasOrder ) {
		return <CheckoutOrderError />;
	}

	if (
		isLoginRequired( customerId ) &&
		allowCreateAccount &&
		getSetting( 'checkoutAllowsSignup', false )
	) {
		<LoginPrompt />;
	}

	return (
		<CheckoutBlockContext.Provider
			value={ {
				allowCreateAccount,
				showCompanyField,
				requireCompanyField,
				showApartmentField,
				showPhoneField,
				requirePhoneField,
			} }
		>
			{ children }
		</CheckoutBlockContext.Provider>
	);
};

const ScrollOnError = ( {
	scrollToTop,
}: {
	scrollToTop: ( props: Record< string, unknown > ) => void;
} ): null => {
	const { hasError: checkoutHasError, isIdle: checkoutIsIdle } =
		useCheckoutContext();
	const { hasValidationErrors, showAllValidationErrors } =
		useValidationContext();

	const hasErrorsToDisplay =
		checkoutIsIdle &&
		checkoutHasError &&
		( hasValidationErrors || hasNoticesOfType( 'wc/checkout', 'default' ) );

	useEffect( () => {
		let scrollToTopTimeout: number;
		if ( hasErrorsToDisplay ) {
			showAllValidationErrors();
			// Scroll after a short timeout to allow a re-render. This will allow focusableSelector to match updated components.
			scrollToTopTimeout = window.setTimeout( () => {
				scrollToTop( {
					focusableSelector: 'input:invalid, .has-error input',
				} );
			}, 50 );
		}
		return () => {
			clearTimeout( scrollToTopTimeout );
		};
	}, [ hasErrorsToDisplay, scrollToTop, showAllValidationErrors ] );

	return null;
};

const Block = ( {
	attributes,
	children,
	scrollToTop,
}: {
	attributes: Attributes;
	children: React.ReactChildren;
	scrollToTop: ( props: Record< string, unknown > ) => void;
} ): JSX.Element => {
	return (
		<BlockErrorBoundary
			header={ __(
				'Something went wrong. Please contact us for assistance.',
				'woo-gutenberg-products-block'
			) }
			text={ createInterpolateElement(
				__(
					'The checkout has encountered an unexpected error. <button>Try reloading the page</button>. If the error persists, please get in touch with us so we can assist.',
					'woo-gutenberg-products-block'
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
			<SnackbarNoticesContainer context="wc/checkout" />
			<StoreNoticesProvider>
				<StoreNoticesContainer context="wc/checkout" />
				<ValidationContextProvider>
					{ /* SlotFillProvider need to be defined before CheckoutProvider so fills have the SlotFill context ready when they mount. */ }
					<SlotFillProvider>
						<CheckoutProvider>
							<SidebarLayout
								className={ classnames( 'wc-block-checkout', {
									'has-dark-controls':
										attributes.hasDarkControls,
								} ) }
							>
								<Checkout attributes={ attributes }>
									{ children }
								</Checkout>
								<ScrollOnError scrollToTop={ scrollToTop } />
							</SidebarLayout>
						</CheckoutProvider>
					</SlotFillProvider>
				</ValidationContextProvider>
			</StoreNoticesProvider>
		</BlockErrorBoundary>
	);
};

export default withScrollToTop( Block );
