/**
 * External dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import type { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Block from './block';
import { Attributes } from './types';
import './editor.scss';

const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< Attributes > ) => {
	const { className, hideTabTitle } = attributes;
	const blockProps = useBlockProps( {
		className,
	} );

	return (
		<>
			<div { ...blockProps }>
				<InspectorControls key="inspector">
					<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
						<ToggleControl
							label={ __(
								'Show tab title in content',
								'woocommerce'
							) }
							checked={ ! hideTabTitle }
							onChange={ () =>
								setAttributes( {
									hideTabTitle: ! hideTabTitle,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<Disabled>
					<Block hideTabTitle={ hideTabTitle } />
				</Disabled>
			</div>
		</>
	);
};

export default Edit;
