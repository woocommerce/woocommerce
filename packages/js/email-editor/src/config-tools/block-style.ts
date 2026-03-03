/**
 * External dependencies
 */
import { registerBlockStyle, unregisterBlockStyle } from '@wordpress/blocks';
import { select } from '@wordpress/data';

export type BlockStyle = { name: string; label: string; isDefault?: boolean };

const newlyRegisteredStyles = new Set< string >();
const preservedUnregisteredStyles = new Map< string, BlockStyle[] >();

function makeKey( blockName: string, styleName: string ): string {
	return `${ blockName }||${ styleName }`;
}

export function registerBlockStyleForEmail(
	blockName: string,
	style: BlockStyle
): void {
	registerBlockStyle( blockName, style );
	newlyRegisteredStyles.add( makeKey( blockName, style.name ) );
}

export function unregisterBlockStyleForEmail(
	blockName: string,
	styleName: string
): void {
	// Try to preserve the style definition before unregistering
	const currentStyles =
		select( 'core/blocks' ).getBlockStyles( blockName ) || [];
	const found = currentStyles.find( ( s ) => s.name === styleName );
	if ( found ) {
		const list = preservedUnregisteredStyles.get( blockName ) || [];
		if ( ! list.find( ( s ) => s.name === styleName ) ) {
			list.push( found as BlockStyle );
			preservedUnregisteredStyles.set( blockName, list );
		}
	}
	unregisterBlockStyle( blockName, styleName );
}

export function resetBlockStyles(): void {
	// Remove styles registered by email editor
	for ( const key of newlyRegisteredStyles ) {
		const [ blockName, styleName ] = key.split( '||' );
		unregisterBlockStyle( blockName, styleName );
	}
	newlyRegisteredStyles.clear();

	// Restore preserved styles
	for ( const [
		blockName,
		styles,
	] of preservedUnregisteredStyles.entries() ) {
		styles.forEach( ( style ) => registerBlockStyle( blockName, style ) );
		preservedUnregisteredStyles.delete( blockName );
	}
}
