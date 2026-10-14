/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import icon from './icon';
import registerCollections from './collections';
import { addProductCollectionToQueryPaginationParentOrAncestor } from './utils';

registerBlockType( metadata, {
	icon,
	edit,
	save,
} );
registerCollections();
addProductCollectionToQueryPaginationParentOrAncestor();
