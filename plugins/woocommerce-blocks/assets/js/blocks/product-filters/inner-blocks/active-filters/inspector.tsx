/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { dispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { getInnerBlockByName } from '../../utils';

export const Inspector = ( {
	attributes,
	setAttributes,
	clientId,
}: EditProps ) => {
	const { clearButton } = attributes;

	useEffect( () => {
		const activeFilterBlock =
			select( 'core/block-editor' ).getBlock( clientId );

		const coreGroupBlock = getInnerBlockByName(
			activeFilterBlock,
			'core/group'
		);

		const clearButtonBlock = getInnerBlockByName(
			activeFilterBlock,
			'woocommerce/product-filter-clear-button'
		);

		if ( clearButtonBlock && ! clearButton ) {
			dispatch( 'core/block-editor' ).updateBlockAttributes(
				clearButtonBlock.clientId,
				{
					lock: {},
				}
			);

			dispatch( 'core/block-editor' ).removeBlock(
				clearButtonBlock.clientId
			);

			// After removing the block above, the block editor will select the next block in the list.
			// We need to reselect the current block for better UX.
			dispatch( 'core/block-editor' ).selectBlock( clientId );
		} else if ( ! clearButtonBlock && clearButton ) {
			if ( coreGroupBlock ) {
				dispatch( 'core/block-editor' ).insertBlock(
					createBlock( 'woocommerce/product-filter-clear-button', {
						lock: { move: false, remove: true },
					} ),
					1,
					coreGroupBlock.clientId,
					false
				);
			} else {
				dispatch( 'core/block-editor' ).insertBlock(
					createBlock( 'woocommerce/product-filter-clear-button', {
						lock: { move: false, remove: true },
					} ),
					0,
					clientId,
					false
				);
			}
		}
	}, [ clearButton, clientId ] );

	return (
		<>
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Display', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Clear button', 'woocommerce' ) }
						checked={ clearButton }
						onChange={ ( value ) => {
							setAttributes( { clearButton: value } );
						} }
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
