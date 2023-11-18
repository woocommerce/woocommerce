/**
 * External dependencies
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockTemplateArray, BlockTemplate } from './types';
import { TabPanel } from '../tab-panel';
import { BlockTemplateTree } from './block-template-tree';

export function TemplateTabPanel( {
	isSelected,
	postType,
}: {
	isSelected: boolean;
	postType: string;
} ) {
	const template: BlockTemplateArray =
		// @ts-ignore
		globalThis.productBlockEditorSettings.templates[ postType ];

	const [ selectedBlockTemplate, setSelectedBlockTemplate ] =
		useState< BlockTemplate | null >( null );

	function findBlockTemplateById(
		blockTemplates: BlockTemplateArray,
		blockTemplateId: string
	): BlockTemplate | null {
		for ( const blockTemplate of blockTemplates ) {
			const attributes = blockTemplate[ 1 ];
			const innerBlocks = blockTemplate[ 2 ];

			if ( attributes._templateBlockId === blockTemplateId ) {
				return blockTemplate;
			}

			if ( innerBlocks ) {
				return findBlockTemplateById( innerBlocks, blockTemplateId );
			}
		}

		return null;
	}

	function onBlockTemplateSelect( blockTemplateId: string ) {
		const blockTemplate = findBlockTemplateById(
			template,
			blockTemplateId
		);

		setSelectedBlockTemplate( blockTemplate );
	}

	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-template">
				<BlockTemplateTree
					template={ template }
					onSelect={ onBlockTemplateSelect }
				/>
				<div className="woocommerce-product-editor-dev-tools-block-template-details">
					{ JSON.stringify( selectedBlockTemplate, null, 4 ) }
				</div>
			</div>
		</TabPanel>
	);
}
