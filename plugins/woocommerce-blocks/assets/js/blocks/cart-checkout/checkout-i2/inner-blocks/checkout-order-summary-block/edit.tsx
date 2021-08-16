/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Block from './block';
import { useBlockPropsWithLocking } from '../../hacks';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		showRateAfterTaxName: boolean;
		lock: {
			move: boolean;
			remove: boolean;
		};
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockPropsWithLocking( { attributes } );
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
								checked={ attributes.showRateAfterTaxName }
								onChange={ () =>
									setAttributes( {
										showRateAfterTaxName: ! attributes.showRateAfterTaxName,
									} )
								}
							/>
						</PanelBody>
					) }
			</InspectorControls>
			<Block showRateAfterTaxName={ attributes.showRateAfterTaxName } />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
