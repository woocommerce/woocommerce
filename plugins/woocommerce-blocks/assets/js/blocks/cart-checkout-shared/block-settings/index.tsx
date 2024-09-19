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

export { ExpressPaymentControls } from './express-payment-settings';
