/**
 * External dependencies
 */
import {
	registerBlockVariation,
	unregisterBlockVariation,
	type BlockVariation,
} from '@wordpress/blocks';

const newlyRegisteredVariations = new Set< string >();

function makeKey( blockName: string, variationName: string ): string {
	return `${ blockName }||${ variationName }`;
}

export function registerBlockVariationForEmail(
	blockName: string,
	variation: BlockVariation
): void {
	registerBlockVariation( blockName, variation );
	newlyRegisteredVariations.add( makeKey( blockName, variation.name ) );
}

export function resetBlockVariations(): void {
	// Remove variations added by email editor
	for ( const key of newlyRegisteredVariations ) {
		const [ blockName, variationName ] = key.split( '||' );
		unregisterBlockVariation( blockName, variationName );
	}
	newlyRegisteredVariations.clear();
}
