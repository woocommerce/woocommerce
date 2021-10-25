/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Block from './block';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		showRateAfterTaxName: boolean;
		isShippingCalculatorEnabled: boolean;
		className: string;
		lock: {
			move: boolean;
			remove: boolean;
		};
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const {
		showRateAfterTaxName,
		isShippingCalculatorEnabled,
		className,
	} = attributes;
	const blockProps = useBlockProps();
	const taxesEnabled = getSetting( 'taxesEnabled' ) as boolean;
	const displayItemizedTaxes = getSetting(
		'displayItemizedTaxes',
		false
	) as boolean;
	const displayCartPricesIncludingTax = getSetting(
		'displayCartPricesIncludingTax',
		false
	) as boolean;
	return (
		<div { ...blockProps }>
			<InspectorControls>
				{ getSetting( 'shippingEnabled', true ) && (
					<PanelBody
						title={ __(
							'Shipping rates',
							'woo-gutenberg-products-block'
						) }
					>
						<ToggleControl
							label={ __(
								'Shipping calculator',
								'woo-gutenberg-products-block'
							) }
							help={ __(
								'Allow customers to estimate shipping by entering their address.',
								'woo-gutenberg-products-block'
							) }
							checked={ isShippingCalculatorEnabled }
							onChange={ () =>
								setAttributes( {
									isShippingCalculatorEnabled: ! isShippingCalculatorEnabled,
								} )
							}
						/>
					</PanelBody>
				) }
				{ taxesEnabled &&
					displayItemizedTaxes &&
					! displayCartPricesIncludingTax && (
						<PanelBody
							title={ __(
								'Taxes',
								'woo-gutenberg-products-block'
							) }
						>
							<ToggleControl
								label={ __(
									'Show rate after tax name',
									'woo-gutenberg-products-block'
								) }
								help={ __(
									'Show the percentage rate alongside each tax line in the summary.',
									'woo-gutenberg-products-block'
								) }
								checked={ showRateAfterTaxName }
								onChange={ () =>
									setAttributes( {
										showRateAfterTaxName: ! showRateAfterTaxName,
									} )
								}
							/>
						</PanelBody>
					) }
			</InspectorControls>
			<Disabled>
				<Block
					className={ className }
					showRateAfterTaxName={ showRateAfterTaxName }
					isShippingCalculatorEnabled={ isShippingCalculatorEnabled }
				/>
			</Disabled>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
