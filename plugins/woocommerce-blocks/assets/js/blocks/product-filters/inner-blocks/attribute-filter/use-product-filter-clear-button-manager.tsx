/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { BlockAttributes, createBlock } from '@wordpress/blocks';
import { useEffect, useState } from '@wordpress/element';

function findClientIdByName(
	block: BlockAttributes,
	targetName: string
): string | undefined {
	if ( block.name === targetName ) {
		return block.clientId;
	}

	if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
		for ( const innerBlock of block.innerBlocks ) {
			const blockId: string | undefined = findClientIdByName(
				innerBlock,
				targetName
			);
			if ( blockId ) {
				return blockId;
			}
		}
	}

	return undefined;
}

export const useProductFilterClearButtonManager = ( {
	clientId,
	showClearButton,
}: {
	clientId: string;
	showClearButton: boolean;
} ) => {
	const [ previousShowClearButtonState, setPreviousShowClearButtonState ] =
		useState< boolean >( showClearButton );
	const [
		clearButtonParentBlockBeforeRemove,
		setClearButtonParentBlockBeforeRemove,
	] = useState( undefined );
	// @ts-expect-error @wordpress/data types are outdated.
	const { insertBlock, removeBlock, updateBlockAttributes } =
		useDispatch( blockEditorStore );

	const { clearButtonBlock, clearButtonParentBlock } = useSelect(
		( select ) => {
			const { getBlock, getBlockParents } = select( blockEditorStore );
			const filterBlockInstance = getBlock( clientId );
			const clearButtonId = findClientIdByName(
				filterBlockInstance,
				'woocommerce/product-filter-clear-button'
			);
			const clearButtonBlockInstance = clearButtonId
				? getBlock( clearButtonId )
				: undefined;
			const clearButtonParentBlocks = getBlockParents(
				clearButtonId,
				true
			);
			const clearButtonParentBlockInstance =
				clearButtonParentBlocks.length
					? getBlock( clearButtonParentBlocks[ 0 ] )
					: null;

			return {
				filterBlock: filterBlockInstance,
				clearButtonBlock: clearButtonBlockInstance,
				clearButtonParentBlock: clearButtonParentBlockInstance,
			};
		}
	);

	function findPositionToAddTheClearButtonBlock() {
		if ( clearButtonParentBlock ) {
			return {
				clearButtonBlockPosition: 1,
				clearButtonParentBlockId: clearButtonParentBlock?.clientId,
			};
		}
		const filterblock = getBlock( clientId );
		const clearButtonParentBlocks = getBlockParents( clientId, true );
	}

	useEffect( () => {
		if ( showClearButton !== previousShowClearButtonState ) {
			if ( showClearButton === true && ! clearButtonBlock ) {
				setClearButtonParentBlockBeforeRemove( undefined );
				insertBlock(
					createBlock( 'woocommerce/product-filter-clear-button' ),
					1,
					clearButtonParentBlockBeforeRemove?.clientId,
					false
				);
			} else if (
				showClearButton === false &&
				Boolean( clearButtonBlock?.clientId )
			) {
				setClearButtonParentBlockBeforeRemove( clearButtonParentBlock );
				updateBlockAttributes( clearButtonBlock?.clientId, {
					lock: { remove: false, move: false },
				} );
				removeBlock( clearButtonBlock?.clientId, false );
			}
		}
		setPreviousShowClearButtonState( showClearButton );
	}, [ showClearButton, previousShowClearButtonState, clearButtonBlock ] );
};
