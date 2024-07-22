/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RadioControl,
	ToggleControl,
	Notice,
	TextControl,
} from '@wordpress/components';
import ExternalLinkCard from '@woocommerce/editor-components/external-link-card';
import { ADMIN_URL } from '@woocommerce/settings';
import { useExpressPaymentMethods } from '@woocommerce/base-context/hooks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import { ExpressCheckoutAttributes } from './types';
import { ExpressCheckoutContext } from './context';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: ExpressCheckoutAttributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element | null => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();
	const hasExpressPaymentMethods = Object.keys( paymentMethods ).length > 0;
	const blockProps = useBlockProps( {
		className: clsx(
			{
				'wp-block-woocommerce-checkout-express-payment-block--has-express-payment-methods':
					hasExpressPaymentMethods,
			},
			attributes?.className
		),
		attributes,
	} );

	if ( ! isInitialized || ! hasExpressPaymentMethods ) {
		return null;
	}

	const { buttonHeight, buttonBorderRadius, showButtonStyles } = attributes;

	const buttonStyleControls = (
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

	const showControls = () => {
		if ( showButtonStyles ) {
			return buttonStyleControls;
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

	return (
		<div { ...blockProps }>
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
						checked={ showButtonStyles }
						onChange={ () =>
							setAttributes( {
								showButtonStyles: ! showButtonStyles,
							} )
						}
					/>
					{ showControls() }
				</PanelBody>
			</InspectorControls>
			<ExpressCheckoutContext.Provider
				value={ { showButtonStyles, buttonHeight, buttonBorderRadius } }
			>
				<Block />
			</ExpressCheckoutContext.Provider>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
