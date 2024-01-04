/**
 * External dependencies
 */
import { getBlockTypes } from '@wordpress/blocks';
import { navigate as navigateFn } from '@woocommerce/interactivity';
import { getSetting } from '@woocommerce/settings';

/**
 * Returns an array of allowed block names excluding the disallowedBlocks array.
 *
 * @param disallowedBlocks Array of block names to disallow.
 * @return Array of allowed block names.
 */
export const getAllowedBlocks = ( disallowedBlocks: string[] ) => {
	const allBlocks = getBlockTypes();

	return allBlocks
		.map( ( block ) => block.name )
		.filter( ( name ) => ! disallowedBlocks.includes( name ) );
};

const isBlockTheme = getSetting< boolean >( 'isBlockTheme' );
const isProductArchive = getSetting< boolean >( 'isProductArchive' );

export function navigate( href: string, options = {} ) {
	if ( ! isBlockTheme && isProductArchive ) {
		return ( window.location.href = href );
	}
	return navigateFn( href, options );
}
