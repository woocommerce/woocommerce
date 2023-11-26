/**
 * External dependencies
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockTemplateArray, BlockTemplate, EvaluationContext } from '../types';
import { TabPanel } from '../tab-panel';
import { BlockTemplateDetailsPanel } from './block-template-details-panel';
import { BlockTemplateTree } from './block-template-tree';

export function TemplateTabPanel( {
	isSelected,
	evaluationContext,
	setSelectedBlockTemplateId,
	selectedBlockTemplateId,
	selectedBlock,
}: {
	isSelected: boolean;
	evaluationContext: EvaluationContext;
	setSelectedBlockTemplateId: ( templateBlockId: string ) => void;
	selectedBlockTemplateId?: string | null;
	selectedBlock: any | null;
} ) {
	const template: BlockTemplateArray =
		// @ts-ignore
		globalThis.productBlockEditorSettings.templates[
			evaluationContext.postType
		];

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
				const matchingInnerBlock = findBlockTemplateById(
					innerBlocks,
					blockTemplateId
				);
				if ( matchingInnerBlock ) {
					return matchingInnerBlock;
				}
			}
		}

		return null;
	}

	function onBlockTemplateSelect( blockTemplateId: string ) {
		setSelectedBlockTemplateId( blockTemplateId );
	}

	const selectedBlockTemplate = findBlockTemplateById(
		template,
		selectedBlockTemplateId ?? ''
	);

	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-template">
				<BlockTemplateTree
					template={ template }
					evaluationContext={ evaluationContext }
					selectedBlockTemplateId={
						selectedBlockTemplate?.[ 1 ]._templateBlockId ?? null
					}
					onSelect={ onBlockTemplateSelect }
				/>
				<BlockTemplateDetailsPanel
					blockTemplate={ selectedBlockTemplate }
					block={ selectedBlock }
					evaluationContext={ evaluationContext }
				/>
			</div>
		</TabPanel>
	);
}
