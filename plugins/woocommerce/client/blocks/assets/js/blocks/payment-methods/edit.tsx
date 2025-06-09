/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { getIconsFromPaymentMethods } from '@woocommerce/base-utils';
import { usePaymentMethods } from '@woocommerce/base-context/hooks';
import PaymentMethodIcons from '@woocommerce/base-components/cart-checkout/payment-method-icons';
import { PaymentEventsProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import './style.scss';
import { getPaymentMethodIcons } from './getPaymentMethodIcons';

const PaymentMethodIconsElement = (): JSX.Element => {
	const { paymentMethods } = usePaymentMethods();
	return (
		<PaymentMethodIcons
			icons={ getIconsFromPaymentMethods( paymentMethods ) }
		/>
	);
};

const Edit = () => {
	const blockProps = useBlockProps();
	//const paymentMethods = getPaymentMethods();
	//const paymentMethodIcons = getPaymentMethodIcons( paymentMethods );
	const paymentMethods = [];
	const paymentMethodIcons = [];

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-payment-methods">
				<PaymentEventsProvider>
					<PaymentMethodIconsElement />
				</PaymentEventsProvider>
				{ Object.keys( paymentMethods ).length === 0 ? (
					<p>
						<small>
							{ __(
								'No payment methods are currently active.',
								'woocommerce'
							) }
						</small>
					</p>
				) : (
					<div className="wc-block-payment-methods__content">
						<ul className="wc-block-payment-methods__list">
							{ paymentMethodIcons.map( ( icon ) => {
								const src =
									typeof icon === 'string' ? icon : icon.src;
								const alt =
									typeof icon === 'string' ? '' : icon.alt;
								const id =
									typeof icon === 'string' ? icon : icon.id;
								return (
									<li key={ id }>
										<img
											src={ src || '' }
											alt={ alt || '' }
										/>
									</li>
								);
							} ) }
						</ul>
					</div>
				) }
			</div>
		</div>
	);
};

export default Edit;
