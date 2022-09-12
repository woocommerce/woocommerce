/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BlockInstance,
	createBlock,
	switchToBlockType,
} from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { formatListNumbered, formatListBullets } from '@wordpress/icons';
import { ToolbarButton } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { convertGBListToRTJNodes } from '../gb2rtj/format-list';
import { useBlockSelection } from '../hooks/block-selection';
import { useCursor } from '../utils/replace-selected-blocks';

// these constants are defined in ../utils/register-blocks.ts
// but if we import them, we get SyntaxError: Cannot use import statement outside a module
// in jest tests, so we are defining these constants here instead
const PARAGRAPH_BLOCK_ID = 'core/paragraph';
const LIST_BLOCK_ID = 'core/list';

type ListType = 'ordered' | 'unordered';

type ListTransformProps = {
	listType: ListType;
};

type ReplaceBlocksType = (
	clientIds: string | string[],
	blocks: BlockInstance | BlockInstance[],
	indexToSelect?: number | undefined
) => IterableIterator< void >;

type SelectConvertedBlocksType = ( convertedBlocks: BlockInstance[] ) => void;

function getListItems( block: BlockInstance ) {
	const listNodes = convertGBListToRTJNodes( block );
	return listNodes.map( ( node ) => node.text.replace( /\n$/, '' ) );
}

function switchToParagraphBlocks(
	sourceBlocks: BlockInstance[] | BlockInstance,
	replaceBlocks: ReplaceBlocksType,
	replaceClientIds: string[],
	selectConvertedBlocks: SelectConvertedBlocksType
) {
	const newBlocks = switchToBlockType( sourceBlocks, PARAGRAPH_BLOCK_ID );
	if ( newBlocks ) {
		replaceBlocks( replaceClientIds, newBlocks );
		selectConvertedBlocks( newBlocks );
	}
}

function createListBlock(
	sourceBlock: BlockInstance | BlockInstance[],
	newListOrdered: boolean,
	replaceBlocks: ReplaceBlocksType,
	replaceClientIds: string[]
) {
	const listItems = getListItems( sourceBlock as BlockInstance );
	const values = listItems.map( ( item ) => `<li>${ item }</li>` ).join( '' );
	const newBlock = createBlock( LIST_BLOCK_ID, {
		values,
		ordered: newListOrdered,
	} );
	replaceBlocks( replaceClientIds, newBlock );
}

function switchToListBlock(
	sourceBlocks: BlockInstance[],
	newListOrdered: boolean,
	replaceBlocks: ReplaceBlocksType,
	replaceClientIds: string[]
) {
	const unorderedListBlocks = switchToBlockType(
		sourceBlocks,
		LIST_BLOCK_ID
	);
	if ( unorderedListBlocks ) {
		if ( newListOrdered ) {
			const { attributes } = unorderedListBlocks[ 0 ];

			const orderedListBlock = createBlock( LIST_BLOCK_ID, {
				...attributes,
				ordered: true,
			} );
			replaceBlocks( replaceClientIds, orderedListBlock );
		} else {
			replaceBlocks( replaceClientIds, unorderedListBlocks );
		}
	}
}

const convertBlocksToList = (
	blocks: BlockInstance[],
	blockClientIds: string[],
	listType: ListType,
	replaceBlocks: ReplaceBlocksType,
	selectConvertedBlocks: SelectConvertedBlocksType
) => {
	const newBlockType = LIST_BLOCK_ID;
	const newListOrdered = listType === 'ordered';
	const firstBlock = blocks[ 0 ];
	const blocksAreSameType = blocks.every(
		( block ) =>
			block.name === firstBlock.name &&
			block.attributes.ordered === firstBlock.attributes.ordered
	);
	const NOT_LIST = 'not-list-type';
	if ( blocksAreSameType ) {
		const sourceBlockKey = `${
			firstBlock.name === LIST_BLOCK_ID ? firstBlock.name : NOT_LIST
		}-${ firstBlock.attributes.ordered }`;
		const NON_LIST_TYPE = `${ NOT_LIST }-undefined`;
		const clickedBlockKey = `${ newBlockType }-${ newListOrdered }`;

		switch ( sourceBlockKey ) {
			case NON_LIST_TYPE:
				switch ( clickedBlockKey ) {
					case `${ LIST_BLOCK_ID }-true`:
					case `${ LIST_BLOCK_ID }-false`:
						switchToListBlock(
							blocks,
							newListOrdered,
							replaceBlocks,
							blockClientIds
						);
						break;
				}
				break;
			case `${ LIST_BLOCK_ID }-true`:
				switch ( clickedBlockKey ) {
					case `${ LIST_BLOCK_ID }-true`:
						switchToParagraphBlocks(
							firstBlock,
							replaceBlocks,
							blockClientIds,
							selectConvertedBlocks
						);
						break;
					case `${ LIST_BLOCK_ID }-false`:
						createListBlock(
							firstBlock,
							newListOrdered,
							replaceBlocks,
							blockClientIds
						);
						break;
				}
				break;
			case `${ LIST_BLOCK_ID }-false`:
				switch ( clickedBlockKey ) {
					case `${ LIST_BLOCK_ID }-true`:
						createListBlock(
							firstBlock,
							newListOrdered,
							replaceBlocks,
							blockClientIds
						);
						break;
					case `${ LIST_BLOCK_ID }-false`:
						switchToParagraphBlocks(
							firstBlock,
							replaceBlocks,
							blockClientIds,
							selectConvertedBlocks
						);
						break;
				}
				break;
		}
	}
};

export const ListTransform: React.VFC< ListTransformProps > = ( {
	listType,
} ) => {
	const { blocks, blockClientIds } = useBlockSelection();
	const { selectConvertedBlocks } = useCursor();
	const { replaceBlocks } = useDispatch( 'core/block-editor' );

	const icon = {
		unordered: formatListBullets,
		ordered: formatListNumbered,
	}[ listType ];

	const titles = {
		unordered: __( 'Unordered list', 'woocommerce' ),
		ordered: __( 'Ordered list', 'woocommerce' ),
	};

	return (
		<ToolbarButton
			icon={ icon }
			title={ titles[ listType ] }
			onClick={ () => {
				convertBlocksToList(
					blocks,
					blockClientIds,
					listType,
					replaceBlocks,
					selectConvertedBlocks
				);
			} }
			isActive={ false }
		/>
	);
};
