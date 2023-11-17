/**
 * External dependencies
 */
import { useState } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TabPanel } from '../tab-panel';

interface BlockTemplateAttributes {
	[ key: string ]: unknown;
	_templateBlockId: string;
	_templateBlockOrder: number;
}

interface BlockTemplate {
	0: string;
	1: BlockTemplateAttributes;
	2?: BlockTemplate[];
}

type BlockTemplateArray = BlockTemplate[];

function BlockTemplateTreeItem( {
	blockTemplate,
	onSelect,
}: {
	blockTemplate: BlockTemplate;
	onSelect: ( blockTemplateId: string ) => void;
} ) {
	const name = blockTemplate[ 0 ];
	const attributes = blockTemplate[ 1 ];
	const innerBlocks = blockTemplate[ 2 ];

	const templateBlockId = attributes?._templateBlockId;
	const templateBlockOrder = attributes?._templateBlockOrder;

	function onClick( event: React.MouseEvent ) {
		event.stopPropagation();

		onSelect( templateBlockId );
	}

	return (
		<div
			className="woocommerce-product-editor-dev-tools-template-block"
			onClick={ onClick }
		>
			<div className="woocommerce-product-editor-dev-tools-template-block__header">
				{ templateBlockId }
			</div>
			<div className="woocommerce-product-editor-dev-tools-template-block__sub-header">
				<span className="woocommerce-product-editor-dev-tools-template-block__name">
					{ name }
				</span>
				<span className="woocommerce-product-editor-dev-tools-template-block__order">
					{ templateBlockOrder }
				</span>
			</div>
			{ innerBlocks && (
				<div className="woocommerce-product-editor-dev-tools-template__inner-blocks">
					{ innerBlocks.map( ( innerBlockTemplate, index ) => (
						<BlockTemplateTreeItem
							blockTemplate={ innerBlockTemplate }
							key={ index }
							onSelect={ onSelect }
						/>
					) ) }
				</div>
			) }
		</div>
	);
}

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
				<div className="woocommerce-product-editor-dev-tools-template-tree">
					{ template.map( ( blockTemplate, index ) => (
						<BlockTemplateTreeItem
							blockTemplate={ blockTemplate }
							key={ index }
							onSelect={ onBlockTemplateSelect }
						/>
					) ) }
				</div>
				<div className="woocommerce-product-editor-dev-tools-block-template-details">
					{ JSON.stringify( selectedBlockTemplate, null, 4 ) }
				</div>
			</div>
		</TabPanel>
	);
}
