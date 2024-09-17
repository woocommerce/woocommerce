/**
 * External dependencies
 */
import { InspectorControls, HeightControl } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	RadioControl,
	Notice,
} from '@wordpress/components';
import ExternalLinkCard from '@woocommerce/editor-components/external-link-card';
import { __ } from '@wordpress/i18n';
import type { BlockAttributes } from '@wordpress/blocks';
import { select } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { ADMIN_URL } from '@woocommerce/settings';

const allStyleControls = [ 'height', 'borderRadius' ];

const atLeastOnePaymentMethodSupportsOneOf = ( styleControl: string[] ) => {
	const availableExpressMethods =
		select( PAYMENT_STORE_KEY ).getAvailableExpressPaymentMethods();

	return Object.values( availableExpressMethods ).reduce(
		( acc, currentValue ) => {
			return (
				acc ||
				currentValue?.supportsStyle.some( ( el ) =>
					styleControl.includes( el )
				)
			);
		},
		false
	);
};

const ExpressPaymentButtonStyleControls = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ) => {
	const { buttonHeight, buttonBorderRadius } = attributes;

	return (
		<>
			{ atLeastOnePaymentMethodSupportsOneOf( [ 'height' ] ) && (
				<RadioControl
					label={ __( 'Button height', 'woocommerce' ) }
					selected={ buttonHeight }
					options={ [
						{
							label: __( 'Small (40px)', 'woocommerce' ),
							value: '40',
						},
						{
							label: __( 'Medium (48px)', 'woocommerce' ),
							value: '48',
						},
						{
							label: __( 'Large (55px)', 'woocommerce' ),
							value: '55',
						},
					] }
					onChange={ ( newValue: string ) =>
						setAttributes( { buttonHeight: newValue } )
					}
				/>
			) }
			{ atLeastOnePaymentMethodSupportsOneOf( [ 'borderRadius' ] ) && (
				<div className="border-radius-control-container">
					<HeightControl
						label={ __( 'Button border radius', 'woocommerce' ) }
						value={ buttonBorderRadius }
						onChange={ ( newValue: string ) => {
							const valueOnly = newValue.replace( 'px', '' );
							setAttributes( {
								buttonBorderRadius: valueOnly,
							} );
						} }
					/>
				</div>
			) }
		</>
	);
};

const ExpressPaymentToggle = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ) => {
	if ( attributes.showButtonStyles ) {
		return (
			<ExpressPaymentButtonStyleControls
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
		);
	}
	return null;
};

const ExpressPaymentMethods = () => {
	const availableExpressMethods =
		select( PAYMENT_STORE_KEY ).getAvailableExpressPaymentMethods();

	if ( Object.entries( availableExpressMethods ).length < 1 ) {
		return (
			<p className="wc-block-checkout__controls-text">
				{ __(
					'You currently have no express payment integrations active.',
					'woocommerce'
				) }
			</p>
		);
	}

	return (
		<>
			<p className="wc-block-checkout__controls-text">
				{ __(
					'You currently have the following express payment integrations active.',
					'woocommerce'
				) }
			</p>
			{ Object.values( availableExpressMethods ).map( ( values ) => {
				return (
					<ExternalLinkCard
						key={ values.name }
						href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=checkout&section=${ encodeURIComponent(
							values.gatewayId
						) }` }
						title={ values.title }
						description={ values.description }
					/>
				);
			} ) }
		</>
	);
};

const toggleLabel = (
	<>
		{ __( 'Apply uniform styles', 'woocommerce' ) }{ ' ' }
		<span className="express-payment-styles-beta-badge">Beta</span>
	</>
);

export const ExpressPaymentControls = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ) => {
	return (
		<InspectorControls>
			{ atLeastOnePaymentMethodSupportsOneOf( allStyleControls ) && (
				<PanelBody
					title={ __( 'Button Settings', 'woocommerce' ) }
					className="express-payment-button-settings"
				>
					<ToggleControl
						label={ toggleLabel }
						checked={ attributes.showButtonStyles }
						onChange={ () =>
							setAttributes( {
								showButtonStyles: ! attributes.showButtonStyles,
							} )
						}
						help={ __(
							'Sets a consistent style for express payment buttons.',
							'woocommerce'
						) }
					/>
					<Notice
						status="warning"
						isDismissible={ false }
						className="wc-block-checkout__notice express-payment-styles-notice"
					>
						<strong>{ __( 'Note', 'woocommerce' ) }:</strong>{ ' ' }
						{ __(
							'Some payment methods might not yet support all style controls',
							'woocommerce'
						) }
					</Notice>
					<ExpressPaymentToggle
						attributes={ attributes }
						setAttributes={ setAttributes }
					/>
				</PanelBody>
			) }
			<PanelBody title={ __( 'Express Payment Methods', 'woocommerce' ) }>
				<ExpressPaymentMethods />
			</PanelBody>
		</InspectorControls>
	);
};
