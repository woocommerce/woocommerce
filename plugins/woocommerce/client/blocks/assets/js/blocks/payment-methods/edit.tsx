/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
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

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		numberOfIcons: number;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const blockProps = useBlockProps();
	const { numberOfIcons } = attributes;
	const paymentMethods = getPaymentMethods();
	const wooPayments = paymentMethods?.woocommerce_payments;
	const wooPaymentsCards =
		wooPayments?.edit &&
		typeof wooPayments.edit === 'object' &&
		'props' in wooPayments.edit &&
		wooPayments.edit.props?.paymentMethodId === 'card';

	const availableBrands = [
		'visa',
		'mastercard',
		'amex',
		'discover',
		'diners',
		'jcb',
		'cartes_bancaires',
		'unionpay',
	];

	const iconsToShow = Math.min( numberOfIcons, availableBrands.length );

	if ( wooPaymentsCards ) {
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Payment Methods Settings',
							'woocommerce'
						) }
					>
						<RangeControl
							label={ __( 'Number of icons', 'woocommerce' ) }
							value={ numberOfIcons }
							onChange={ ( value ) =>
								setAttributes( { numberOfIcons: value } )
							}
							min={ 1 }
							max={ availableBrands.length }
							help={ __(
								'Choose how many payment method icons to display.',
								'woocommerce'
							) }
						/>
					</PanelBody>
				</InspectorControls>
				<div className="wp-block-woocommerce-payment-methods">
					{ availableBrands
						.slice( 0, iconsToShow )
						.map( ( brand ) => (
							<CardPreview key={ brand } brand={ brand } />
						) ) }
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
