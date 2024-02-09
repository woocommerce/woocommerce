/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

/**
 * Retrieves a unique ID for a block instance.
 *
 * This function maintains uniqueness of IDs across block instances on a page. It will generate
 * a new ID if it does not have one and regenerate an ID if another block has the same ID.
 *
 * @param {BlockInstance}         blockInstance The instance of the block we want to generate an ID for.
 * @param {string}                idAttribute   The name of the attribute that contains the ID.
 * @param {Array.<BlockInstance>} allBlocks     All of the blocks currently in the editor.
 * @return {string} A unique ID for the block that can be persisted.
 */
export function getUniqueBlockId<
	// This needs to match the generic type of BlockInstance to work correctly.
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	T extends Record< string, any >
>(
	blockInstance: BlockInstance< T >,
	idAttribute: keyof T,
	allBlocks: BlockInstance[]
): string {
	// Note: Since it's a uuid, we can use the blocks' clientId instead of bundling a uuid library.
	// This ID is NOT stable across editor reloads but it's unique enough to use persistently too.
	if ( typeof idAttribute !== 'string' ) {
		throw new Error( 'ID attribute must be a string.' );
	}

	const existingId = blockInstance.attributes[ idAttribute ];
	if ( ! existingId ) {
		return blockInstance.clientId;
	}

	// Make sure that IDs are unique across all blocks on a page.
	for ( const block of allBlocks ) {
		if ( block.clientId === blockInstance.clientId ) {
			continue;
		}

		// We are only worried about blocks of the same type.
		if ( block.name !== blockInstance.name ) {
			continue;
		}

		// We need to change the ID if another block has the same ID so that they are
		// always unique. Since blocks are listed in order we can assume that the
		// earlier block is the one that had the ID first.
		const otherId = block.attributes[ idAttribute ];
		if ( otherId === existingId ) {
			return blockInstance.clientId;
		}
	}

	return existingId;
}
