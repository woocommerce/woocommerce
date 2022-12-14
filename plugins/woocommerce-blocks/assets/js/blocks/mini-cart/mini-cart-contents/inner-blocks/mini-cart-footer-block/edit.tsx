/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsItem } from '@woocommerce/blocks-checkout';
import Button from '@woocommerce/base-components/button';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	usePaymentMethods,
	useStoreCart,
} from '@woocommerce/base-context/hooks';
import PaymentMethodIcons from '@woocommerce/base-components/cart-checkout/payment-method-icons';
import { getIconsFromPaymentMethods } from '@woocommerce/base-utils';
import { getSetting } from '@woocommerce/settings';
import { PaymentEventsProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import {
	defaultCartButtonLabel,
	defaultCheckoutButtonLabel,
} from './constants';

const PaymentMethodIconsElement = (): JSX.Element => {
	const { paymentMethods } = usePaymentMethods();
	return (
		<PaymentMethodIcons
			icons={ getIconsFromPaymentMethods( paymentMethods ) }
		/>
	);
};

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		cartButtonLabel: string;
		checkoutButtonLabel: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const { cartButtonLabel, checkoutButtonLabel } = attributes;
	const { cartTotals } = useStoreCart();
	const subTotal = getSetting( 'displayCartPricesIncludingTax', false )
		? parseInt( cartTotals.total_items, 10 ) +
		  parseInt( cartTotals.total_items_tax, 10 )
		: parseInt( cartTotals.total_items, 10 );

	return (
		<div { ...blockProps }>
			<div className="wc-block-mini-cart__footer">
				<TotalsItem
					className="wc-block-mini-cart__footer-subtotal"
					currency={ getCurrencyFromPriceResponse( cartTotals ) }
					label={ __( 'Subtotal', 'woo-gutenberg-products-block' ) }
					value={ subTotal }
					description={ __(
						'Shipping, taxes, and discounts calculated at checkout.',
						'woo-gutenberg-products-block'
					) }
				/>
				<div className="wc-block-mini-cart__footer-actions">
					<Button
						className="wc-block-mini-cart__footer-cart"
						variant="outlined"
					>
						<RichText
							multiline={ false }
							allowedFormats={ [] }
							value={ cartButtonLabel }
							placeholder={ defaultCartButtonLabel }
							onChange={ ( content ) => {
								setAttributes( {
									cartButtonLabel: content,
								} );
							} }
						/>
					</Button>
					<Button className="wc-block-mini-cart__footer-checkout">
						<RichText
							multiline={ false }
							allowedFormats={ [] }
							value={ checkoutButtonLabel }
							placeholder={ defaultCheckoutButtonLabel }
							onChange={ ( content ) => {
								setAttributes( {
									checkoutButtonLabel: content,
								} );
							} }
						/>
					</Button>
				</div>
				<PaymentEventsProvider>
					<PaymentMethodIconsElement />
				</PaymentEventsProvider>
			</div>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() }></div>;
};
