/**
 * Internal dependencies
 */
import { selectBlockByName } from '.';
import { insertBlockUsingQuickInserter } from './insert-block-using-quick-inserter';

/**
 * Inserts an inner block into the currently selected block. If a parent block
 * is provided, it will be selected before inserting the inner block.
 *
 * We make the parentBlockName optional to add more flexibility to the function,
 * enabling tests to have more control over the selection of the parent block.
 *
 * @param  blockTitle      Block title, such as "Add to Cart Button".
 * @param  parentBlockName Parent block name, such as core/group.
 */
export const insertInnerBlock = async (
	blockTitle: string,
	parentBlockName = ''
) => {
	if ( parentBlockName ) {
		await selectBlockByName( parentBlockName );
	}

	await insertBlockUsingQuickInserter( blockTitle );
};
