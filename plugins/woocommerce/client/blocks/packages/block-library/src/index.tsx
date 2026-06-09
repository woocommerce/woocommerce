/**
 * Internal dependencies
 */
import * as categoryTitle from './category-title';

const getAllBlocks = () => [ categoryTitle ];

export const registerBlockLibraryBlocks = ( blocks = getAllBlocks() ) => {
	blocks.forEach( ( { init } ) => init() );
};

console.log( 'Registering WooCommerce block library blocks.' );

registerBlockLibraryBlocks();
