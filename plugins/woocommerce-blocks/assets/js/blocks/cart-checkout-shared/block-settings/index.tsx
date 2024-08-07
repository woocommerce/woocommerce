/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Notice,
	RadioControl,
	TextControl,
} from '@wordpress/components';
import ExternalLinkCard from '@woocommerce/editor-components/external-link-card';
import { __ } from '@wordpress/i18n';
import type { BlockAttributes } from '@wordpress/blocks';
import { ADMIN_URL } from '@woocommerce/settings';

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
				label={ __( 'Button Size', 'woocommerce' ) }
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
					label={ __( 'Button Border Radius', 'woocommerce' ) }
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
	return (
		<Notice
			status="info"
			isDismissible={ false }
			className="show-button-styles-notice"
		>
			<p className="wc-block-checkout__controls-text">
				{ __(
					'You can change the appearance of individual buttons in the respective payment extension settings page',
					'woocommerce'
				) }
			</p>
			<ExternalLinkCard
				href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=checkout` }
				title="Payment Settings"
			/>
		</Notice>
	);
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
				<p className="wc-block-checkout__controls-text">
					{ __(
						'These settings will override the plugin specific styles for these buttons',
						'woocommerce'
					) }
				</p>
				<ToggleControl
					label={ __( 'Express Button Styles', 'woocommerce' ) }
					checked={ attributes.showButtonStyles }
					onChange={ () =>
						setAttributes( {
							showButtonStyles: ! attributes.showButtonStyles,
						} )
					}
				/>
				<ExpressPaymentToggle
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</PanelBody>
		</InspectorControls>
	);
};
