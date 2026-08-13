/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore - Ignoring because `__experimentalToggleGroupControl` is not yet in the type definitions.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

export enum QuantitySelectorStyle {
	Input = 'input',
	Stepper = 'stepper',
}

type AddToCartFormSettingsProps = {
	quantitySelectorStyle: QuantitySelectorStyle;
	setAttributes: ( attributes: {
		quantitySelectorStyle: QuantitySelectorStyle;
	} ) => void;
};

const getHelpText = ( quantitySelectorStyle: QuantitySelectorStyle ) => {
	if ( quantitySelectorStyle === QuantitySelectorStyle.Input ) {
		return __(
			'Shoppers can enter a number of items to add to cart.',
			'woocommerce'
		);
	}
	if ( quantitySelectorStyle === QuantitySelectorStyle.Stepper ) {
		return __(
			'Shoppers can use buttons to change the number of items to add to cart.',
			'woocommerce'
		);
	}
};

export const AddToCartFormSettings = ( {
	quantitySelectorStyle,
	setAttributes,
}: AddToCartFormSettingsProps ) => {
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Quantity Selector', 'woocommerce' ) }>
				<ToggleGroupControl
					__nextHasNoMarginBottom
					value={ quantitySelectorStyle }
					isBlock
					onChange={ ( value: QuantitySelectorStyle ) => {
						setAttributes( {
							quantitySelectorStyle:
								value as QuantitySelectorStyle,
						} );
					} }
					help={ getHelpText( quantitySelectorStyle ) }
				>
					<ToggleGroupControlOption
						label={ __( 'Input', 'woocommerce' ) }
						value={ QuantitySelectorStyle.Input }
					/>
					<ToggleGroupControlOption
						label={ __( 'Stepper', 'woocommerce' ) }
						value={ QuantitySelectorStyle.Stepper }
					/>
				</ToggleGroupControl>
			</PanelBody>
		</InspectorControls>
	);
};
