/**
 * External dependencies
 */
import {
	getBlockType,
	unregisterBlockType,
	registerBlockType,
} from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

// Registry of first-seen/original settings for blocks we modify via these helpers.
// Note: Store a shallow copy; callers should avoid mutating nested properties in-place.
const originalBlockSettings = new Map<
	string,
	BlockConfiguration< Record< string, unknown > >
>();

export function updateBlockSettings<
	TAttributes extends Record< string, unknown > = Record< string, unknown >
>(
	name: string,
	updater: (
		settings: BlockConfiguration< TAttributes >
	) =>
		| Partial< BlockConfiguration< TAttributes > >
		| BlockConfiguration< TAttributes >
): boolean {
	const type = getBlockType< TAttributes >( name );
	if ( ! type ) return false;

	const { name: blockName, ...currentSettings } = type as unknown as {
		name: string;
	} & BlockConfiguration< TAttributes >;

	try {
		// Backup original settings the first time this block is updated.
		if ( ! originalBlockSettings.has( blockName ) ) {
			originalBlockSettings.set( blockName, {
				...( currentSettings as BlockConfiguration<
					Record< string, unknown >
				> ),
			} );
		}

		const patch = updater(
			currentSettings as BlockConfiguration< TAttributes >
		);
		const nextSettings = {
			...( currentSettings as BlockConfiguration< TAttributes > ),
			...( patch as Partial< BlockConfiguration< TAttributes > > ),
		} as BlockConfiguration< TAttributes >;

		unregisterBlockType( blockName );
		registerBlockType< TAttributes >( blockName, nextSettings );
		return true;
	} catch ( e ) {
		// eslint-disable-next-line no-console
		console.error( 'Failed to update block settings for', name, e );
		return false;
	}
}

/**
 * Restore original settings for all blocks modified via these helpers.
 * Returns the list of block names successfully restored.
 */
export function restoreAllModifiedBlockSettings(): string[] {
	const restored: string[] = [];
	for ( const [ blockName, original ] of originalBlockSettings.entries() ) {
		try {
			unregisterBlockType( blockName );
			// original is a BlockConfiguration; re-register as-is
			registerBlockType( blockName, original );
			restored.push( blockName );
			originalBlockSettings.delete( blockName );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error(
				'Failed to restore block settings for',
				blockName,
				e
			);
		}
	}
	return restored;
}
