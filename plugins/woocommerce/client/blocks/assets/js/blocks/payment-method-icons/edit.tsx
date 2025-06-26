/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { getPaymentMethods } from '@woocommerce/blocks-registry';

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
			<div className="wp-block-woocommerce-payment-method-icons__item">
				<span
					className="wp-block-woocommerce-payment-method-icons__icon"
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
	attributes: {
		numberOfIcons: number;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const blockProps = useBlockProps();
	const paymentMethodData = getPaymentMethods();
	const wooPaymentMethods =
		paymentMethodData?.woocommerce_payments?.content?.props?.upeMethods;
	const { numberOfIcons } = attributes;

	if ( wooPaymentMethods ) {
		const wcSettings = window.wcSettings as {
			cardIcons?: Record< string, { icon: string } >;
		};
		const cardIcons = wcSettings?.cardIcons || {};
		const availableCardIcons = Object.keys( cardIcons )
			.map( ( type ) => {
				if ( ! cardIcons[ type ] || ! cardIcons[ type ].icon ) {
					return null;
				}
				return {
					type,
					icon: cardIcons[ type ].icon,
				};
			} )
			.filter( Boolean );
		const otherPaymentMethods = Object.keys( wooPaymentMethods )
			.filter( ( method ) => method !== 'card' )
			.sort();
		const otherPaymentMethodIcons = otherPaymentMethods.map( ( method ) => {
			return {
				type: method,
				icon: wooPaymentMethods[ method ].icon,
			};
		} );

		const availableIcons = [
			...availableCardIcons,
			...otherPaymentMethodIcons,
		];

		const iconsToShow =
			numberOfIcons === 0
				? availableIcons.length
				: Math.min( numberOfIcons, availableIcons.length );

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
							max={ availableIcons.length }
							help={ __(
								'Choose how many icons to display. To show all icons, use 0 (zero).',
								'woocommerce'
							) }
						/>
					</PanelBody>
				</InspectorControls>
				<div className="wp-block-woocommerce-payment-method-icons">
					{ availableIcons.slice( 0, iconsToShow ).map( ( icon ) => (
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
			{ __(
				'No active WooPayments payment methods found.',
				'woocommerce'
			) }
		</div>
	);
};

export default Edit;
