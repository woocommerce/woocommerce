/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';

const CardPreview = ( { brand }: { brand: string } ) => {
	const CardIcon = (
		<div className="payment-method-item">
			<span
				className="payment-method-icon"
				style={ {
					backgroundImage: `url(${ window.wcSettings.wcAssetUrl }/images/payment-methods/${ brand }.svg)`,
				} }
			>
				{ brand }
			</span>
		</div>
	);

	return <div>{ CardIcon }</div>;
};

const Edit = () => {
	const blockProps = useBlockProps();
	const paymentMethods = getPaymentMethods();
	const wooPayments = paymentMethods?.woocommerce_payments;
	const wooPaymentsCards =
		wooPayments?.edit &&
		typeof wooPayments.edit === 'object' &&
		'props' in wooPayments.edit &&
		wooPayments.edit.props?.paymentMethodId === 'card';

	if ( wooPaymentsCards ) {
		return (
			<div { ...blockProps }>
				<div className="wp-block-woocommerce-payment-methods">
					<CardPreview brand="visa" />
					<CardPreview brand="mastercard" />
					<CardPreview brand="amex" />
					<CardPreview brand="discover" />
					<CardPreview brand="diners" />
					<CardPreview brand="jcb" />
					<CardPreview brand="cartes_bancaires" />
					<CardPreview brand="unionpay" />
				</div>
			</div>
		);
	}
	return (
		<div>
			{ __(
				'No active WooPayments payment methods found.',
				'woocommerce'
			) }
		</div>
	);
};

export default Edit;
