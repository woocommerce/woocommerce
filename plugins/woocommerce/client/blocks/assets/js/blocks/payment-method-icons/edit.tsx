/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';

const CardPreview = ( { type }: { type: string } ) => {
	const pluginUrl = (
		window?.wcSettings?.wcBlocksConfig as { pluginUrl?: string }
	 )?.pluginUrl?.replace( 'woocommerce/', '' );
	const iconUrl = `${ pluginUrl }/woocommerce-payments/assets/images/payment-method-icons/${ type }.svg`;

	const CardIcon = (
		<div className="wp-block-woocommerce-payment-method-icons__item">
			<span
				className="wp-block-woocommerce-payment-method-icons__icon"
				style={ {
					backgroundImage: `url(${ iconUrl })`,
				} }
			>
				{ type }
			</span>
		</div>
	);

	return CardIcon;
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

	const availableTypes = [ 'visa', 'mastercard', 'amex', 'discover', 'jcb' ];
	const iconsToShow = Math.min( numberOfIcons, availableTypes.length );

	if ( wooPaymentsCards ) {
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Payment Method Icon Settings',
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
							max={ availableTypes.length }
							help={ __(
								'Choose how many icons to display.',
								'woocommerce'
							) }
						/>
					</PanelBody>
				</InspectorControls>
				<div className="wp-block-woocommerce-payment-method-icons">
					{ availableTypes.slice( 0, iconsToShow ).map( ( type ) => (
						<CardPreview key={ type } type={ type } />
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
