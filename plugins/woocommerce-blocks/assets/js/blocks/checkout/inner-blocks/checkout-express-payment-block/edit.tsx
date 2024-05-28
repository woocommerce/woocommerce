/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RadioControl } from '@wordpress/components';
import { useExpressPaymentMethods } from '@woocommerce/base-context/hooks';
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		className?: string;
		buttonHeight: string;
		lock: {
			move: boolean;
			remove: boolean;
		};
	};
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element | null => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();
	const hasExpressPaymentMethods = Object.keys( paymentMethods ).length > 0;
	const blockProps = useBlockProps( {
		className: classnames(
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

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Button Settings', 'woocommerce' ) }>
					<RadioControl
						label="Button size"
						selected={ attributes.buttonHeight }
						options={ [
							{ label: 'Small (40px)', value: '40px' },
							{ label: 'Medium (48px)', value: '48px' },
							{ label: 'Large (56px)', value: '56px' },
						] }
						onChange={ ( newValue ) =>
							setAttributes( { buttonHeight: newValue } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Block buttonHeight={ attributes.buttonHeight } />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
