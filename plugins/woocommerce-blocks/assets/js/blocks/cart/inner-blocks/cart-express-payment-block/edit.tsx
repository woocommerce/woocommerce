/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useExpressPaymentMethods } from '@woocommerce/base-context/hooks';
import clsx from 'clsx';
import { ExpressPaymentControls } from '@woocommerce/blocks/cart-checkout-shared';
import type { BlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import type { ExpressCartAttributes } from '../../../cart-checkout-shared/types';
import { ExpressPaymentContext } from '../../../cart-checkout-shared/payment-methods/express-payment/express-payment-context';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: ExpressCartAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ): JSX.Element | null => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();
	const hasExpressPaymentMethods = Object.keys( paymentMethods ).length > 0;
	const blockProps = useBlockProps( {
		className: clsx( {
			'wp-block-woocommerce-cart-express-payment-block--has-express-payment-methods':
				hasExpressPaymentMethods,
		} ),
	} );

	const { className, showButtonStyles, buttonHeight, buttonBorderRadius } =
		attributes;

	if ( ! isInitialized || ! hasExpressPaymentMethods ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<ExpressPaymentControls
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<ExpressPaymentContext.Provider
				value={ { showButtonStyles, buttonHeight, buttonBorderRadius } }
			>
				<Block className={ className } />
			</ExpressPaymentContext.Provider>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
