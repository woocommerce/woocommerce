/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	RadioControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockAttributes } from '@wordpress/blocks';

export const BlockSettings = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ) => {
	const { hasDarkControls } = attributes;
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Style', 'woocommerce' ) }>
				<ToggleControl
					label={ __( 'Dark mode inputs', 'woocommerce' ) }
					help={ __(
						'Inputs styled specifically for use on dark background colors.',
						'woocommerce'
					) }
					checked={ hasDarkControls }
					onChange={ () =>
						setAttributes( {
							hasDarkControls: ! hasDarkControls,
						} )
					}
				/>
			</PanelBody>
		</InspectorControls>
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
			<RadioControl
				label={ __( 'Button size', 'woocommerce' ) }
				selected={ buttonHeight }
				options={ [
					{ label: 'Small (40px)', value: '40' },
					{ label: 'Medium (48px)', value: '48' },
					{ label: 'Large (55px)', value: '55' },
				] }
				onChange={ ( newValue: string ) =>
					setAttributes( { buttonHeight: newValue } )
				}
			/>
			<div className="border-radius-control-container">
				<TextControl
					label={ __( 'Button border radius', 'woocommerce' ) }
					value={ buttonBorderRadius }
					onChange={ ( newValue: string ) =>
						setAttributes( {
							buttonBorderRadius: newValue,
						} )
					}
				/>
				<span className="border-radius-control-px">px</span>
			</div>
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

export const ExpressPaymentControls = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
} ) => {
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Button Settings', 'woocommerce' ) }>
				<ToggleControl
					label={ __( 'Apply uniform styles', 'woocommerce' ) }
					checked={ attributes.showButtonStyles }
					onChange={ () =>
						setAttributes( {
							showButtonStyles: ! attributes.showButtonStyles,
						} )
					}
					help={ __(
						'Overrides styles set by gateways to ensure all express payment buttons have a consistent appearance.',
						'woocommerce'
					) }
				/>
				<ExpressPaymentToggle
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
