/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CART_URL } from '@woocommerce/block-settings';
import { Icon, removeCart } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';

/**
 * When an order was not created for the checkout, for example, when an item
 * was out of stock, this component will be shown instead of the checkout form.
 *
 * The error message is derived by the hydrated API request passed to the
 * checkout block.
 */
const CheckoutError = () => {
	const checkoutData = getSetting( 'checkoutData', {} );
	const errorData = {
		code: checkoutData.code || 'unknown',
		message:
			checkoutData.message ||
			__(
				'There was a problem checking out. Please try again. If the problem persists, please get in touch with us so we can assist.',
				'woo-gutenberg-products-block'
			),
	};

	return (
		<div className="wc-block-checkout-error">
			<Icon
				className="wc-block-checkout-error__image"
				alt=""
				srcElement={ removeCart }
				size={ 100 }
			/>
			<ErrorTitle errorData={ errorData } />
			<ErrorMessage errorData={ errorData } />
			<ErrorButton errorData={ errorData } />
		</div>
	);
};

/**
 * Get the error message to display.
 *
 * @param {Object} errorData Object containing code and message.
 */
const ErrorTitle = ( { errorData } ) => {
	let heading = __( 'Checkout error', 'woo-gutenberg-products-block' );

	if ( errorData.code === 'woocommerce_product_out_of_stock' ) {
		heading = __(
			'There is a problem with your cart',
			'woo-gutenberg-products-block'
		);
	}

	return (
		<strong className="wc-block-checkout-error_title">{ heading }</strong>
	);
};

/**
 * Get the error message to display.
 *
 * @param {Object} errorData Object containing code and message.
 */
const ErrorMessage = ( { errorData } ) => {
	let message = errorData.message;

	if ( errorData.code === 'woocommerce_product_out_of_stock' ) {
		message =
			message +
			' ' +
			__(
				'Please edit your cart and try again.',
				'woo-gutenberg-products-block'
			);
	}

	return <p className="wc-block-checkout-error__description">{ message }</p>;
};

/**
 * Get the CTA button to display.
 *
 * @param {Object} errorData Object containing code and message.
 */
const ErrorButton = ( { errorData } ) => {
	let buttonText = __( 'Retry', 'woo-gutenberg-products-block' );
	let buttonUrl = 'javascript:window.location.reload(true)';

	if ( errorData.code === 'woocommerce_product_out_of_stock' ) {
		buttonText = __( 'Edit your cart', 'woo-gutenberg-products-block' );
		buttonUrl = CART_URL;
	}

	return (
		<span className="wp-block-button">
			<a href={ buttonUrl } className="wp-block-button__link">
				{ buttonText }
			</a>
		</span>
	);
};

export default CheckoutError;
