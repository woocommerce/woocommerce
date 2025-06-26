/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

const CardPreview = ( { type }: { type: string } ) => {
	const { paymentMethodIcons } = window.wcSettings as {
		paymentMethodIcons: Record< string, { icon: string } >;
	};
	const iconUrl = paymentMethodIcons[ type ]?.icon;

	const CardIcon = (
		<div className="wp-block-woocommerce-payment-method-icons__item">
			<span
				className="wp-block-woocommerce-payment-method-icons__icon"
				style={ {
					backgroundImage: `url(${ iconUrl })`,
				} }
				role="img"
				aria-label={ type }
			/>
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
	const isWooPaymentsEnabled = window.wcSettings.wooPaymentsEnabled;

	if ( isWooPaymentsEnabled ) {
		const { paymentMethodIcons } = window.wcSettings as {
			paymentMethodIcons: Record< string, { icon: string } >;
		};
		const availableTypes = Object.keys( paymentMethodIcons );
		const iconsToShow =
			numberOfIcons === 0
				? availableTypes.length
				: Math.min( numberOfIcons, availableTypes.length );

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
							min={ 0 }
							max={ availableTypes.length }
							help={ __(
								'Choose how many icons to display. To show all icons, use 0 (zero).',
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
		<div { ...blockProps }>
			{ __(
				'No active WooPayments payment methods found.',
				'woocommerce'
			) }
		</div>
	);
};

export default Edit;
