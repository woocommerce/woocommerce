/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CART_URL } from '@woocommerce/block-settings';
import { removeCart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { CheckoutResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	PRODUCT_OUT_OF_STOCK,
	PRODUCT_NOT_PURCHASABLE,
	PRODUCT_NOT_ENOUGH_STOCK,
	PRODUCT_SOLD_INDIVIDUALLY,
	GENERIC_CART_ITEM_ERROR,
} from './constants';

// Type definitions
interface ErrorData {
	code: string;
	message: string;
}
interface ErrorComponentProps {
	errorData: ErrorData;
}

const cartItemErrorCodes = [
	PRODUCT_OUT_OF_STOCK,
	PRODUCT_NOT_PURCHASABLE,
	PRODUCT_NOT_ENOUGH_STOCK,
	PRODUCT_SOLD_INDIVIDUALLY,
	GENERIC_CART_ITEM_ERROR,
];

const preloadedCheckoutData = getSetting<
	CheckoutResponse | Record< string, unknown >
>( 'checkoutData', {} );

/**
 * Get the error message to display.
 *
 * @param {Object} props           Incoming props for the component.
 * @param {Object} props.errorData Object containing code and message.
 */
const ErrorTitle = ( { errorData }: ErrorComponentProps ) => {
	let heading = __( 'Checkout error', 'woocommerce' );

	if ( cartItemErrorCodes.includes( errorData.code ) ) {
		heading = __( 'There is a problem with your cart', 'woocommerce' );
	}

	return (
		<strong className="wc-block-checkout-error_title">{ heading }</strong>
	);
};

/**
 * Get the error message to display.
 *
 * @param {Object} props           Incoming props for the component.
 * @param {Object} props.errorData Object containing code and message.
 */
const ErrorMessage = ( { errorData }: ErrorComponentProps ) => {
	let message = errorData.message;

	if (
		cartItemErrorCodes.includes(
			errorData.code as ( typeof cartItemErrorCodes )[ number ]
		)
	) {
		message =
			message +
			' ' +
			__( 'Please edit your cart and try again.', 'woocommerce' );
	}

	return <p className="wc-block-checkout-error__description">{ message }</p>;
};

/**
 * Get the CTA button to display.
 *
 * @param {Object} props           Incoming props for the component.
 * @param {Object} props.errorData Object containing code and message.
 */
const ErrorButton = ( { errorData }: ErrorComponentProps ) => {
	let buttonText = __( 'Retry', 'woocommerce' );

	if (
		cartItemErrorCodes.includes(
			errorData.code as ( typeof cartItemErrorCodes )[ number ]
		)
	) {
		buttonText = __( 'Edit your cart', 'woocommerce' );
	}

	const isLink =
		cartItemErrorCodes.includes(
			errorData.code as ( typeof cartItemErrorCodes )[ number ]
		) && CART_URL;

	return (
		<span className="wp-block-button">
			{ isLink ? (
				<a href={ CART_URL } className="wp-block-button__link">
					{ buttonText }
				</a>
			) : (
				<button
					className="wp-block-button__link"
					onClick={ () => window.location.reload() }
				>
					{ buttonText }
				</button>
			) }
		</span>
	);
};

/**
 * When an order was not created for the checkout, for example, when an item
 * was out of stock, this component will be shown instead of the checkout form.
 *
 * The error message is derived by the hydrated API request passed to the
 * checkout block.
 */
const CheckoutOrderError = () => {
	const checkoutData: CheckoutResponse = {
		code: '',
		message: '',
		...( preloadedCheckoutData || {} ),
	};

	const errorData: ErrorData = {
		code: checkoutData.code || 'unknown',
		message:
			decodeEntities( checkoutData.message ) ||
			__(
				'There was a problem checking out. Please try again. If the problem persists, please get in touch with us so we can assist.',
				'woocommerce'
			),
	};

	return (
		<div className="wc-block-checkout-error">
			<Icon
				className="wc-block-checkout-error__image"
				icon={ removeCart }
				size={ 100 }
			/>
			<ErrorTitle errorData={ errorData } />
			<ErrorMessage errorData={ errorData } />
			<ErrorButton errorData={ errorData } />
		</div>
	);
};

export default CheckoutOrderError;
