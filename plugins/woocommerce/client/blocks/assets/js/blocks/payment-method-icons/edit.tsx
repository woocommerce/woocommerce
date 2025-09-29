/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';

type BlockAttributes = {
	numberOfIcons: number;
};

type PaymentMethod = {
	icon: string;
};

type PaymentMethods = Record< string, PaymentMethod >;

const CardPreview = ( {
	type,
	icon,
}: {
	type: string | undefined;
	icon: string | undefined;
} ) => {
	let CardIcon = null;

	if ( type && icon ) {
		CardIcon = (
			<div className="wc-block-payment-method-icons__item">
				<span
					className="wc-block-payment-method-icons__icon"
					style={ {
						backgroundImage: `url(${ icon })`,
					} }
					role="img"
					aria-label={ type }
				/>
			</div>
		);
	}

	return CardIcon;
};

const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attributes: Partial< BlockAttributes > ) => void;
} ) => {
	const blockProps = useBlockProps();
	const paymentMethods =
		( window.wcSettings?.availablePaymentMethods as PaymentMethods ) || {};
	const { numberOfIcons } = attributes;

	if ( paymentMethods && Object.keys( paymentMethods ).length > 0 ) {
		const icons = Object.keys( paymentMethods ).reduce( ( acc, type ) => {
			if ( ! paymentMethods[ type ] || ! paymentMethods[ type ].icon ) {
				return acc;
			}
			acc.push( {
				type,
				icon: paymentMethods[ type ].icon,
			} );
			return acc;
		}, [] as Array< { type: string; icon: string } > );

		const iconsToShow =
			numberOfIcons === 0
				? icons.length
				: Math.min( numberOfIcons, icons.length );

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
								setAttributes( { numberOfIcons: value || 0 } )
							}
							min={ 0 }
							max={ icons.length }
							help={ __(
								'Choose how many icons to display. To show all icons, use 0 (zero).',
								'woocommerce'
							) }
						/>
					</PanelBody>
				</InspectorControls>
				<div className="wc-block-payment-method-icons">
					{ icons.slice( 0, iconsToShow ).map( ( icon ) => (
						<CardPreview
							key={ icon?.type }
							type={ icon?.type }
							icon={ icon?.icon }
						/>
					) ) }
				</div>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ __( 'No active payment methods found.', 'woocommerce' ) }
		</div>
	);
};

export default Edit;
