/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import type { BlockAttributes } from '@wordpress/blocks';

export const BlockSettings = ( {
	attributes,
	setAttributes,
	blockType,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attrs: BlockAttributes ) => void;
	blockType: 'cart' | 'checkout';
} ) => {
	const { hasDarkControls, showFormStepNumbers } = attributes;
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Style', 'woocommerce' ) }>
				{ blockType === 'checkout' && (
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show form step numbers', 'woocommerce' ) }
						checked={ showFormStepNumbers }
						onChange={ () =>
							setAttributes( {
								showFormStepNumbers: ! showFormStepNumbers,
							} )
						}
					/>
				) }
				<ToggleControl
					__nextHasNoMarginBottom
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

export { ExpressPaymentControls } from './express-payment-settings';
